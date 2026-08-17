<?php

namespace Modules\Socialevents\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Socialevents\Entities\EventEdition;
use Modules\Socialevents\Entities\EventEditionMatch;
use Modules\Socialevents\Entities\EventEditionMedia;
use Modules\Socialevents\Support\TournamentDateLabels;
use Modules\Socialevents\Support\TournamentLandingCache;
use Modules\Socialevents\Support\TournamentMedia;

class EventEditionGalleryController extends Controller
{
    private const MAX_FILE_SIZE = 102400; // 100 MB en KB

    public function index(int $editionId): Response
    {
        $edition = EventEdition::with(['evento'])->findOrFail($editionId);

        $matches = EventEditionMatch::with(['equipolocal', 'equipovisitante'])
            ->where('edition_id', $editionId)
            ->whereNotNull('match_date')
            ->orderBy('match_date', 'asc')
            ->get();

        $media = EventEditionMedia::with('match')
            ->where('edition_id', $editionId)
            ->orderByDesc('media_date')
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('Socialevents::Editions/Gallery', [
            'edition' => [
                'id' => $edition->id,
                'name' => $edition->name,
                'year' => $edition->year,
                'evento' => $edition->evento?->title,
                'landing_published' => (bool) $edition->landing_published,
                'landing_url' => $edition->landingUrl(),
            ],
            'matches' => $this->matchesPayload($matches),
            'media' => $this->mediaPayload($media),
        ]);
    }

    public function store(Request $request, int $editionId): JsonResponse
    {
        $edition = EventEdition::findOrFail($editionId);

        $request->validate([
            'media_date' => ['required', 'date'],
            'match_id' => ['nullable', 'integer', 'exists:event_edition_matches,id'],
            'files' => ['required', 'array', 'min:1', 'max:20'],
            'files.*' => ['required', 'file', 'max:'.self::MAX_FILE_SIZE],
        ]);

        $mediaDate = $request->input('media_date');
        $matchId = $request->input('match_id');

        if ($matchId) {
            $match = EventEditionMatch::where('id', $matchId)
                ->where('edition_id', $editionId)
                ->first();

            if (! $match) {
                return response()->json([
                    'success' => false,
                    'message' => 'El partido seleccionado no pertenece a esta edición.',
                ], 422);
            }
        }

        $uploaded = [];
        $errors = [];

        foreach ($request->file('files') as $file) {
            $mime = $file->getMimeType();

            if (str_starts_with((string) $mime, 'image/')) {
                $type = 'image';
            } elseif (str_starts_with((string) $mime, 'video/')) {
                $type = 'video';
            } else {
                $errors[] = $file->getClientOriginalName().' no es un archivo de foto o video valido.';
                continue;
            }

            $extension = $file->getClientOriginalExtension() ?: ($type === 'image' ? 'jpg' : 'mp4');
            $storedName = time().'_'.bin2hex(random_bytes(6)).'.'.$extension;
            $destination = 'socialevents/galleries/'.$editionId.'/'.date('Y-m-d', strtotime($mediaDate));

            $path = Storage::disk('public')->putFileAs($destination, $file, $storedName);

            if (! $path) {
                $errors[] = 'No se pudo guardar '.$file->getClientOriginalName().'.';
                continue;
            }

            $record = EventEditionMedia::create([
                'edition_id' => $editionId,
                'match_id' => $matchId,
                'media_date' => $mediaDate,
                'type' => $type,
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'mime_type' => $mime,
            ]);

            $uploaded[] = $record;
        }

        if ($uploaded) {
            TournamentLandingCache::forget($editionId);
        }

        $message = $uploaded
            ? count($uploaded).' archivo(s) subido(s) correctamente.'
            : 'No se subio ningun archivo.';

        if ($errors) {
            $message .= ' '.implode(' ', $errors);
        }

        return response()->json([
            'success' => (bool) $uploaded,
            'message' => $message,
            'uploaded' => count($uploaded),
            'errors' => $errors,
        ]);
    }

    public function destroy(int $editionId, int $mediaId): JsonResponse
    {
        $media = EventEditionMedia::where('id', $mediaId)
            ->where('edition_id', $editionId)
            ->firstOrFail();

        if ($media->file_path) {
            Storage::disk('public')->delete($media->file_path);
        }

        $media->delete();

        TournamentLandingCache::forget($editionId);

        return response()->json([
            'success' => true,
            'message' => 'Archivo eliminado correctamente.',
        ]);
    }

    /**
     * Partidos disponibles para asignar medios, agrupados por fecha.
     *
     * @return array<int, array<string, mixed>>
     */
    private function matchesPayload($matches): array
    {
        return $matches
            ->map(fn (EventEditionMatch $match) => [
                'id' => $match->id,
                'round_number' => $match->round_number,
                'match_date' => $match->match_date?->format('Y-m-d'),
                'match_date_label' => $match->match_date?->format('d/m/Y'),
                'label' => $this->matchLabel($match),
                'phase' => $match->phase,
            ])
            ->groupBy('match_date')
            ->map(fn ($group, $date) => [
                'date' => $date,
                'label' => TournamentDateLabels::full($date),
                'matches' => $group->values()->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Collection<int, EventEditionMedia>  $media
     * @return array<int, array<string, mixed>>
     */
    private function mediaPayload($media): array
    {
        return $media
            ->map(fn (EventEditionMedia $item) => [
                'id' => $item->id,
                'type' => $item->type,
                'url' => TournamentMedia::url($item->file_path),
                'file_name' => $item->file_name,
                'mime_type' => $item->mime_type,
                'media_date' => $item->media_date->format('Y-m-d'),
                'media_date_label' => TournamentDateLabels::full($item->media_date),
                'match' => $item->match
                    ? [
                        'id' => $item->match->id,
                        'label' => $this->matchLabel($item->match),
                    ]
                    : null,
            ])
            ->values()
            ->all();
    }

    private function matchLabel(EventEditionMatch $match): string
    {
        $home = $match->equipolocal?->name ?? $match->placeholder_h ?? 'Por definir';
        $away = $match->equipovisitante?->name ?? $match->placeholder_a ?? 'Por definir';

        return $home.' vs '.$away;
    }
}
