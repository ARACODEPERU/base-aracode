<?php

namespace Modules\Socialevents\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\Socialevents\Entities\EventEdition;
use Modules\Socialevents\Entities\EventEditionMatch;
use Modules\Socialevents\Entities\EventEditionMedia;
use Modules\Socialevents\Support\SavesBase64Image;
use Modules\Socialevents\Support\TournamentDateLabels;
use Modules\Socialevents\Support\TournamentLandingCache;
use Modules\Socialevents\Support\TournamentMedia;

/**
 * Galería de la landing del torneo para el administrador móvil.
 *
 * Reutiliza las mismas tablas, rutas de almacenamiento y caché que el módulo web
 * (EventEditionGalleryController), de modo que lo que se sube desde el celular
 * aparece en la landing sin pasos adicionales.
 */
class EditionGalleryAdminController extends Controller
{
    use SavesBase64Image;

    public function index(Request $request, int $editionId): JsonResponse
    {
        EventEdition::findOrFail($editionId);

        $query = EventEditionMedia::with(['match.equipolocal', 'match.equipovisitante'])
            ->where('edition_id', $editionId);

        if ($request->filled('match_id')) {
            $query->where('match_id', $request->integer('match_id'));
        }

        $media = $query->orderByDesc('media_date')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Galería obtenida correctamente',
            'data' => [
                'total' => $media->count(),
                'media' => $media->map(fn (EventEditionMedia $item) => $this->formatMedia($item))->values()->all(),
            ],
        ]);
    }

    public function store(Request $request, int $editionId): JsonResponse
    {
        EventEdition::findOrFail($editionId);

        $validated = $request->validate([
            'media_date' => ['required', 'date'],
            'match_id' => ['nullable', 'integer', 'exists:event_edition_matches,id'],
            'images' => ['required', 'array', 'min:1', 'max:20'],
            'images.*' => ['required', 'string'],
        ]);

        $matchId = $validated['match_id'] ?? null;

        if ($matchId) {
            $belongs = EventEditionMatch::where('id', $matchId)
                ->where('edition_id', $editionId)
                ->exists();

            if (! $belongs) {
                return response()->json([
                    'success' => false,
                    'message' => 'El partido seleccionado no pertenece a esta edición.',
                ], 422);
            }
        }

        $mediaDate = $validated['media_date'];
        $destination = 'socialevents/galleries/'.$editionId.'/'.date('Y-m-d', strtotime($mediaDate));

        $saved = [];
        $errors = [];

        foreach ($validated['images'] as $index => $base64Image) {
            $path = $this->saveBase64Image(
                $base64Image,
                $destination,
                time().'_'.bin2hex(random_bytes(6))
            );

            if (! $path) {
                $errors[] = 'No se pudo guardar la imagen '.($index + 1).'.';

                continue;
            }

            $saved[] = EventEditionMedia::create([
                'edition_id' => $editionId,
                'match_id' => $matchId,
                'media_date' => $mediaDate,
                'type' => 'image',
                'file_path' => $path,
                'file_name' => basename($path),
                'mime_type' => Storage::disk('public')->mimeType($path) ?: 'image/jpeg',
            ]);
        }

        if ($saved) {
            TournamentLandingCache::forget($editionId);
        }

        $message = $saved
            ? count($saved).' foto(s) subida(s) correctamente.'
            : 'No se subió ninguna foto.';

        if ($errors) {
            $message .= ' '.implode(' ', $errors);
        }

        return response()->json([
            'success' => (bool) $saved,
            'message' => $message,
            'uploaded' => count($saved),
            'errors' => $errors,
            'data' => collect($saved)
                ->map(fn (EventEditionMedia $item) => $this->formatMedia($item))
                ->values()
                ->all(),
        ], $saved ? 201 : 422);
    }

    public function destroy(int $editionId, int $mediaId): JsonResponse
    {
        $media = EventEditionMedia::where('id', $mediaId)
            ->where('edition_id', $editionId)
            ->first();

        if (! $media) {
            return response()->json([
                'success' => false,
                'message' => 'La foto no existe o no pertenece a esta edición.',
            ], 404);
        }

        if (TournamentMedia::exists($media->file_path)) {
            Storage::disk('public')->delete($media->file_path);
        }

        $media->delete();

        TournamentLandingCache::forget($editionId);

        return response()->json([
            'success' => true,
            'message' => 'Foto eliminada correctamente.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatMedia(EventEditionMedia $item): array
    {
        return [
            'id' => $item->id,
            'type' => $item->type,
            'url' => TournamentMedia::url($item->file_path),
            'file_missing' => ! TournamentMedia::exists($item->file_path),
            'media_date' => $item->media_date?->format('Y-m-d'),
            'media_date_label' => $item->media_date
                ? TournamentDateLabels::full($item->media_date)
                : null,
            'match' => $item->match
                ? [
                    'id' => $item->match->id,
                    'label' => $this->matchLabel($item->match),
                ]
                : null,
        ];
    }

    private function matchLabel(EventEditionMatch $match): string
    {
        $home = $match->equipolocal?->name ?? $match->placeholder_h ?? 'Por definir';
        $away = $match->equipovisitante?->name ?? $match->placeholder_a ?? 'Por definir';

        return $home.' vs '.$away;
    }
}
