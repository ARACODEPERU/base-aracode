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
        if (empty($path)) {
            return null;
        }

        // Usar HTTP para evitar problemas con certificados SSL autofirmados en desarrollo local
        $url = asset('storage/'.$path);
        return str_replace('https://', 'http://', $url);
    }
}
