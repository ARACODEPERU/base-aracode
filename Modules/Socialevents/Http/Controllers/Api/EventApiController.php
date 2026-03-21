<?php

namespace Modules\Socialevents\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Modules\Socialevents\Entities\EvenEvent;
use Modules\Socialevents\Entities\EventEdition;

class EventApiController extends Controller
{
    /**
     * Obtiene la lista de eventos activos con sus ediciones
     * 
     * @return JsonResponse
     */
    public function getActiveEvents(): JsonResponse
    {
        $events = EvenEvent::whereIn('status', ['PR', 'PE'])
            ->orderBy('created_at', 'desc')
            ->get();

        if ($events->isEmpty()) {
            $events = EvenEvent::orderBy('created_at', 'desc')->get();
        }

        $eventsData = [];
        
        foreach ($events as $event) {
            $edition = EventEdition::where('event_id', $event->id)
                ->whereNotIn('status', ['CA'])
                ->orderBy('created_at', 'desc')
                ->first();

            $editionData = null;
            if ($edition) {
                $editionData = [
                    'id' => $edition->id,
                    'name' => $edition->name,
                    'year' => $edition->year,
                    'start_date' => $edition->start_date,
                    'end_date' => $edition->end_date,
                    'status' => $edition->status,
                    'teams_count' => $edition->equipos()->count(),
                ];
            }

            $eventsData[] = [
                'id' => $event->id,
                'title' => $event->title,
                'description' => $event->description,
                'image' => $this->formatImageUrl($event->image1),
                'image1' => $this->formatImageUrl($event->image1),
                'image2' => $this->formatImageUrl($event->image2),
                'image3' => $this->formatImageUrl($event->image3),
                'image4' => $this->formatImageUrl($event->image4),
                'date_start' => $event->date_start,
                'date_end' => $event->date_end,
                'status' => $event->status,
                'broadcast' => (bool) $event->broadcast,
                'has_edition' => $editionData !== null,
                'edition' => $editionData,
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Eventos obtenidos correctamente',
            'data' => $eventsData
        ]);
    }

    /**
     * Obtiene la información pública del evento y edición actual
     * 
     * @return JsonResponse
     */
    public function getCurrentEvent(): JsonResponse
    {
        $event = EvenEvent::whereIn('status', ['PR', 'PE'])
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$event) {
            $event = EvenEvent::orderBy('created_at', 'desc')->first();
        }

        if (!$event) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró ningún evento',
                'data' => null
            ], 404);
        }

        $edition = EventEdition::where('event_id', $event->id)
            ->whereNotIn('status', ['CA'])
            ->orderBy('created_at', 'desc')
            ->first();

        $eventData = $this->formatEventData($event);
        $editionData = $edition ? $this->formatEditionData($edition) : null;
        $rankings = $edition ? $this->getRankings($edition) : [];

        return response()->json([
            'success' => true,
            'message' => 'Evento obtenido correctamente',
            'data' => [
                'event' => $eventData,
                'edition' => $editionData,
                'rankings' => $rankings
            ]
        ]);
    }

    /**
     * Obtiene la información del evento por ID
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function getEventById(int $id): JsonResponse
    {
        $event = EvenEvent::find($id);

        if (!$event) {
            return response()->json([
                'success' => false,
                'message' => 'Evento no encontrado',
                'data' => null
            ], 404);
        }

        $edition = EventEdition::where('event_id', $event->id)
            ->whereNotIn('status', ['CA'])
            ->orderBy('created_at', 'desc')
            ->first();

        $eventData = $this->formatEventData($event);
        $editionData = $edition ? $this->formatEditionData($edition) : null;
        $rankings = $edition ? $this->getRankings($edition) : [];

        return response()->json([
            'success' => true,
            'message' => 'Evento obtenido correctamente',
            'data' => [
                'event' => $eventData,
                'edition' => $editionData,
                'rankings' => $rankings
            ]
        ]);
    }

    /**
     * Obtiene solo la edición actual (sin datos del evento)
     * 
     * @return JsonResponse
     */
    public function getCurrentEdition(): JsonResponse
    {
        $edition = EventEdition::with('evento')
            ->whereNotIn('status', ['CA'])
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$edition) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró ninguna edición',
                'data' => null
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Edición obtenida correctamente',
            'data' => [
                'id' => $edition->id,
                'name' => $edition->name,
                'year' => $edition->year,
                'start_date' => $edition->start_date,
                'end_date' => $edition->end_date,
                'competition_format' => $edition->competition_format,
                'status' => $edition->status,
                'prize_details' => $edition->prize_details,
                'has_bases' => !empty($edition->path_database_file),
                'bases_file' => $this->formatBasesFile($edition),
                'teams_count' => $edition->equipos()->count(),
                'matches_count' => $edition->matches()->count(),
                'event' => [
                    'id' => $edition->evento?->id,
                    'title' => $edition->evento?->title,
                    'image' => $this->formatImageUrl($edition->evento?->image1),
                ]
            ]
        ]);
    }

    /**
     * Formatea los datos del evento
     */
    private function formatEventData(EvenEvent $event): array
    {
        return [
            'id' => $event->id,
            'title' => $event->title,
            'description' => $event->description,
            'image' => $this->formatImageUrl($event->image1),
            'image1' => $this->formatImageUrl($event->image1),
            'image2' => $this->formatImageUrl($event->image2),
            'image3' => $this->formatImageUrl($event->image3),
            'image4' => $this->formatImageUrl($event->image4),
            'date_start' => $event->date_start,
            'date_end' => $event->date_end,
            'status' => $event->status,
            'broadcast' => (bool) $event->broadcast,
        ];
    }

    /**
     * Formatea los datos de la edición
     */
    private function formatEditionData(EventEdition $edition): array
    {
        return [
            'id' => $edition->id,
            'name' => $edition->name,
            'year' => $edition->year,
            'start_date' => $edition->start_date,
            'end_date' => $edition->end_date,
            'competition_format' => $edition->competition_format,
            'status' => $edition->status,
            'prize_details' => $edition->prize_details,
            'has_bases' => !empty($edition->path_database_file),
            'bases_file' => $this->formatBasesFile($edition),
            'teams_count' => $edition->equipos()->count(),
            'matches_count' => $edition->matches()->count(),
        ];
    }

    /**
     * Obtiene los rankings de la edición
     */
    private function getRankings(EventEdition $edition): array
    {
        $rankings = [];
        
        $equipos = $edition->equipos()
            ->with('equipo')
            ->orderBy('rank', 'asc')
            ->get();
        
        foreach ($equipos as $et) {
            $rankings[] = [
                'rank' => $et->rank,
                'team_id' => $et->team_id,
                'team_name' => $et->equipo?->name ?? 'Equipo',
                'team_short_name' => $et->equipo?->short_name ?? '',
                'team_logo' => $this->formatImageUrl($et->equipo?->logo_path),
                'matches_played' => $et->matches_played,
                'matches_won' => $et->matches_won,
                'matches_drawn' => $et->matches_drawn,
                'matches_lost' => $et->matches_lost,
                'goals_for' => $et->goals_for,
                'goals_against' => $et->goals_against,
                'goal_difference' => $et->goal_difference,
                'points' => $et->points,
                'is_champion' => (bool) $et->is_champion,
            ];
        }
        
        return $rankings;
    }

    /**
     * Formatea la URL de una imagen
     */
    private function formatImageUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (str_starts_with($path, 'storage/') || str_starts_with($path, 'public/')) {
            return asset($path);
        }

        if (file_exists(public_path($path))) {
            return asset($path);
        }

        if (Storage::disk('public')->exists($path)) {
            return Storage::url($path);
        }

        return null;
    }

    /**
     * Formatea la información del archivo de bases
     */
    private function formatBasesFile(EventEdition $edition): ?array
    {
        if (empty($edition->path_database_file)) {
            return null;
        }

        $url = null;

        if (str_starts_with($edition->path_database_file, 'http')) {
            $url = $edition->path_database_file;
        } elseif (file_exists(public_path($edition->path_database_file))) {
            $url = asset($edition->path_database_file);
        } elseif (Storage::disk('public')->exists($edition->path_database_file)) {
            $url = Storage::url($edition->path_database_file);
        }

        if (!$url) {
            return null;
        }

        return [
            'name' => $edition->name_database_file ?? 'bases_torneo.pdf',
            'url' => $url
        ];
    }
}
