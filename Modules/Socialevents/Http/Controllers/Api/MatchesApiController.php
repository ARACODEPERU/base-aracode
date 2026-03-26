<?php

namespace Modules\Socialevents\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Socialevents\Entities\EventEditionMatch;

class MatchesApiController extends Controller
{
    public function getUpcomingMatches(int $editionId): JsonResponse
    {
        $matches = EventEditionMatch::with(['equipolocal', 'equipovisitante'])
            ->where('edition_id', $editionId)
            ->where('status', 'pending')
            ->where(function ($query) {
                $query->whereNotNull('match_date')
                    ->where('match_date', '>=', now());
            })
            ->orWhere(function ($query) {
                $query->whereNull('match_date')
                    ->whereNotNull('placeholder_h')
                    ->orWhereNotNull('placeholder_a');
            })
            ->orderByRaw("CASE WHEN match_date IS NULL THEN 1 ELSE 0 END")
            ->orderBy('match_date', 'asc')
            ->limit(20)
            ->get();

        $matchesData = $matches->map(function ($match) {
            $hasLocalTeam = $match->equipolocal != null;
            $hasVisitorTeam = $match->equipovisitante != null;

            return [
                'id' => $match->id,
                'team_h_id' => $match->team_h_id,
                'team_h_name' => $hasLocalTeam
                    ? $match->equipolocal->name
                    : ($match->placeholder_h ?? 'Equipo Local'),
                'team_h_short_name' => $hasLocalTeam
                    ? $match->equipolocal->short_name
                    : ($match->placeholder_h ?? 'LOC'),
                'team_h_logo' => $hasLocalTeam
                    ? $this->formatImageUrl($match->equipolocal->logo_path)
                    : null,
                'team_h_has_placeholder' => !$hasLocalTeam && $match->placeholder_h != null,
                'team_a_id' => $match->team_a_id,
                'team_a_name' => $hasVisitorTeam
                    ? $match->equipovisitante->name
                    : ($match->placeholder_a ?? 'Equipo Visitante'),
                'team_a_short_name' => $hasVisitorTeam
                    ? $match->equipovisitante->short_name
                    : ($match->placeholder_a ?? 'VIS'),
                'team_a_logo' => $hasVisitorTeam
                    ? $this->formatImageUrl($match->equipovisitante->logo_path)
                    : null,
                'team_a_has_placeholder' => !$hasVisitorTeam && $match->placeholder_a != null,
                'match_date' => $match->match_date ? $match->match_date->format('Y-m-d H:i:s') : null,
                'match_date_formatted' => $match->match_date
                    ? $match->match_date->format('d MMM - HH:mm')
                    : 'Horario por definir',
                'location' => $match->location,
                'phase' => $match->phase,
                'round' => $match->round_number,
                'status' => $match->status,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Partidos obtenidos correctamente',
            'data' => $matchesData
        ]);
    }

    private function formatImageUrl(?string $path): ?string
    {
        $url = null;
        if ($path) {
            $url = asset('storage/'.$path);
        }

        return $url;
    }

    public function getRecentResults(int $editionId): JsonResponse
    {
        // Encontrar el round más alto con partidos cerrados en la última fase
        $lastRoundWithClosed = EventEditionMatch::where('edition_id', $editionId)
            ->where('status', 'closed')
            ->max('round_number');

        if (!$lastRoundWithClosed) {
            return response()->json([
                'success' => true,
                'message' => 'No hay partidos jugados',
                'data' => []
            ]);
        }

        $matches = EventEditionMatch::with(['equipolocal', 'equipovisitante'])
            ->where('edition_id', $editionId)
            ->where('round_number', $lastRoundWithClosed)
            ->where('status', 'closed')
            ->orderBy('match_date', 'desc')
            ->get();

        $matchesData = $matches->map(function ($match) {
            return [
                'id' => $match->id,
                'phase' => $match->phase,
                'round' => $match->round_number,
                'team_h_id' => $match->team_h_id,
                'team_h_name' => $match->equipolocal ? $match->equipolocal->name : 'Equipo Local',
                'team_h_short_name' => $match->equipolocal ? $match->equipolocal->short_name : 'LOC',
                'team_h_logo' => $match->equipolocal && $match->equipolocal->logo_path
                    ? $this->formatImageUrl($match->equipolocal->logo_path)
                    : null,
                'score_h' => $match->score_h,
                'team_a_id' => $match->team_a_id,
                'team_a_name' => $match->equipovisitante ? $match->equipovisitante->name : 'Equipo Visitante',
                'team_a_short_name' => $match->equipovisitante ? $match->equipovisitante->short_name : 'VIS',
                'team_a_logo' => $match->equipovisitante && $match->equipovisitante->logo_path
                    ? $this->formatImageUrl($match->equipovisitante->logo_path)
                    : null,
                'score_a' => $match->score_a,
                'match_date' => $match->match_date ? $match->match_date->format('Y-m-d H:i:s') : null,
                'match_date_formatted' => $match->match_date
                    ? $match->match_date->format('d/m/Y')
                    : null,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Resultados obtenidos correctamente',
            'data' => $matchesData
        ]);
    }

    public function getAllMatches(int $editionId, string $filter = 'all', int $page = 1, int $perPage = 10): JsonResponse
    {
        $filterStr = (string)$filter;

        $query = EventEditionMatch::with(['equipolocal', 'equipovisitante'])
            ->where('edition_id', $editionId);

        if ($filterStr == 'played') {
            $query->where('status', 'closed');
        } else if ($filterStr == 'pending') {
            $query->where('status', 'pending');
        }

        $query->orderByRaw("CASE WHEN match_date IS NULL THEN 1 ELSE 0 END")
            ->orderBy('match_date', 'asc');

        $total = $query->count();

        $matches = $query->skip(($page - 1) * $perPage)->take($perPage)->get();
        $lastPage = ceil($total / $perPage);

        $matchesData = $matches->map(function ($match) {
            $hasLocalTeam = $match->equipolocal != null;
            $hasVisitorTeam = $match->equipovisitante != null;

            return [
                'id' => $match->id,
                'phase' => $match->phase,
                'round' => $match->round_number,
                'status' => $match->status,
                'team_h_id' => $match->team_h_id,
                'team_h_name' => $hasLocalTeam ? $match->equipolocal->name : ($match->placeholder_h ?? 'Equipo Local'),
                'team_h_short_name' => $hasLocalTeam ? $match->equipolocal->short_name : ($match->placeholder_h ?? 'LOC'),
                'team_h_logo' => $hasLocalTeam ? $this->formatImageUrl($match->equipolocal->logo_path) : null,
                'team_h_has_placeholder' => !$hasLocalTeam && $match->placeholder_h != null,
                'score_h' => $match->score_h,
                'team_a_id' => $match->team_a_id,
                'team_a_name' => $hasVisitorTeam ? $match->equipovisitante->name : ($match->placeholder_a ?? 'Equipo Visitante'),
                'team_a_short_name' => $hasVisitorTeam ? $match->equipovisitante->short_name : ($match->placeholder_a ?? 'VIS'),
                'team_a_logo' => $hasVisitorTeam ? $this->formatImageUrl($match->equipovisitante->logo_path) : null,
                'team_a_has_placeholder' => !$hasVisitorTeam && $match->placeholder_a != null,
                'score_a' => $match->score_a,
                'match_date' => $match->match_date ? $match->match_date->format('Y-m-d H:i:s') : null,
                'match_date_formatted' => $match->match_date
                    ? $match->match_date->format('d/m/Y H:i')
                    : 'Sin fecha',
                'location' => $match->location,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Partidos obtenidos correctamente',
            'data' => $matchesData,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => $lastPage
            ]
        ]);
    }
}
