<?php

namespace Modules\Socialevents\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Modules\Socialevents\Entities\EventEditionMatchPlayerStat;

class PlayerStatsApiController extends Controller
{
    public function getAllStats(int $editionId, string $filter = 'goals'): JsonResponse
    {
        // Get all player stats with team and player info
        $stats = EventEditionMatchPlayerStat::selectRaw('
            player_id,
            team_id,
            SUM(goals) as total_goals,
            SUM(assists) as total_assists,
            SUM(saves) as total_saves,
            SUM(is_mvp) as total_mvp,
            SUM(clean_sheet) as total_clean_sheet
        ')
        ->whereHas('match', function ($query) use ($editionId) {
            $query->where('edition_id', $editionId);
        })
        ->groupBy('player_id', 'team_id');

        switch ($filter) {
            case 'assists':
                $stats = $stats->orderByDesc('total_assists');
                break;
            case 'mvp':
                $stats = $stats->orderByDesc('total_mvp');
                break;
            case 'saves':
                $stats = $stats->orderByDesc('total_saves');
                break;
            case 'cleansheet':
                $stats = $stats->orderByDesc('total_clean_sheet');
                break;
            default:
                $stats = $stats->orderByDesc('total_goals');
        }

        $results = $stats->limit(50)->get();

        $data = $results->map(function ($stat) use ($editionId) {
            // Get player name from event_edition_team_players + people
            $playerInfo = $this->getPlayerInfo($stat->player_id, $editionId);

            // Get team info
            $teamInfo = $this->getTeamInfo($stat->team_id);

            return [
                'player_id' => $stat->player_id,
                'player_name' => $playerInfo['name'],
                'player_photo' => $playerInfo['photo'],
                'team_id' => $stat->team_id,
                'team_name' => $teamInfo['name'],
                'team_logo' => $teamInfo['logo'],
                'goals' => (int) $stat->total_goals,
                'assists' => (int) $stat->total_assists,
                'saves' => (int) $stat->total_saves,
                'mvp' => (int) $stat->total_mvp,
                'clean_sheet' => (int) $stat->total_clean_sheet,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Estadísticas obtenidas correctamente',
            'data' => $data
        ]);
    }

    private function getPlayerInfo(int $playerId, int $editionId): array
    {
        $player = DB::table('event_edition_team_players')
            ->where('edition_id', $editionId)
            ->where('person_id', $playerId)
            ->first();

        if (!$player) {
            return ['name' => 'Jugador #' . $playerId, 'photo' => null];
        }

        $person = DB::table('people')
            ->where('id', $player->person_id)
            ->first();

        return [
            'name' => $person ? $person->full_name : 'Jugador #' . $playerId,
            'photo' => $person && $person->image ? $this->formatImageUrl($person->image) : null
        ];
    }

    private function getTeamInfo(?int $teamId): array
    {
        if (!$teamId) {
            return ['name' => 'Sin equipo', 'logo' => null];
        }

        $team = DB::table('event_teams')
            ->where('id', $teamId)
            ->first();

        return [
            'name' => $team ? $team->name : 'Equipo #' . $teamId,
            'logo' => $team && $team->logo_path ? $this->formatImageUrl($team->logo_path) : null
        ];
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
