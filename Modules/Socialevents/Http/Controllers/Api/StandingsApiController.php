<?php

namespace Modules\Socialevents\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Socialevents\Entities\EventEditionTeam;

class StandingsApiController extends Controller
{
    public function getStandings(int $editionId): JsonResponse
    {
        $standings = EventEditionTeam::with(['equipo'])
            ->where('edition_id', $editionId)
            ->orderBy('rank', 'asc')
            ->get();

        $data = $standings->map(function ($team) {
            $equipo = $team->equipo;

            return [
                'position' => $team->rank,
                'team_id' => $team->team_id,
                'team_name' => $equipo ? $equipo->name : 'Equipo',
                'team_short_name' => $equipo ? ($equipo->short_name ?? substr($equipo->name, 0, 3)) : 'EQ',
                'team_logo' => $equipo && $equipo->logo_path
                    ? $this->formatImageUrl($equipo->logo_path)
                    : null,
                'points' => $team->points,
                'matches_played' => $team->matches_played,
                'matches_won' => $team->matches_won,
                'matches_drawn' => $team->matches_drawn,
                'matches_lost' => $team->matches_lost,
                'goals_for' => $team->goals_for,
                'goals_against' => $team->goals_against,
                'goal_difference' => $team->goal_difference,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Tabla de posiciones obtenida correctamente',
            'data' => $data
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
}
