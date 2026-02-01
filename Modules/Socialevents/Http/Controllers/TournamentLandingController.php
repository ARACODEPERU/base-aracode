<?php

namespace Modules\Socialevents\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Socialevents\Entities\EventEdition;
use Modules\Socialevents\Entities\EventEditionMatch;
use Modules\Socialevents\Entities\EventEditionTeam;
use Modules\Socialevents\Entities\EventEditionMatchPlayerStat;
use Modules\Socialevents\Entities\EventEditionMatchSanction;

class TournamentLandingController extends Controller
{
    public function show($eId)
    {
        // Obtener los datos de la edición con el evento relacionado
        $edition = EventEdition::with([
            'evento',
            'equipos.equipo'
        ])->where('id', $eId)
            ->first();

        if (!$edition) {
            abort(404, 'Edición o evento no encontrado.');
        }

        // Obtener todos los partidos
        $matches = EventEditionMatch::with(['equipolocal', 'equipovisitante'])
            ->where('edition_id', $eId)
            ->orderByRaw("CASE
                WHEN match_date >= NOW() THEN 1
                WHEN match_date < NOW() THEN 2
                ELSE 3 END ASC")
            ->orderBy('match_date', 'asc')
            ->get();

        // 2. Agrupamos por fase y fecha
        $matches = $matches->groupBy(['phase', 'round_number'])->map(function($rounds) {
            return $rounds->sortKeys();
        });

        // Consulta para la tabla de posiciones
        $currentEquipment = EventEditionTeam::with('equipo')
            ->where('edition_id', $eId)
            ->orderBy('points', 'desc')
            ->get();

        // Consulta para estadísticas de jugadores
        $playerStats = EventEditionMatchPlayerStat::with(['player', 'match'])
            ->whereHas('match', function($q) use ($eId) {
                $q->where('edition_id', $eId);
            })
            ->get();

        // Consulta para sanciones
        $sanctions = EventEditionMatchSanction::with(['player'])
            ->whereHas('match', function($q) use ($eId) {
                $q->where('edition_id', $eId);
            })
            ->get();

        // Calcular rankings
        $playersRanking = $this->calculatePlayersRanking($playerStats, $sanctions);
        $goalkeepersRanking = $this->calculateGoalkeepersRanking($playerStats, $sanctions);

        // Pasar los datos a la vista
        return view('socialevents::torneos.landing', compact('edition', 'matches', 'currentEquipment', 'playersRanking', 'goalkeepersRanking'));
    }

    private function calculatePlayersRanking($playerStats, $sanctions)
    {
        $players = [];

        // Agrupar sanciones por player_id
        $sanctionsByPlayer = $sanctions->groupBy('player_id');

        foreach ($playerStats as $stat) {
            if ($stat->saves > 0) continue; // Skip goalkeepers

            $playerId = $stat->player_id;
            $points = ($stat->goals * 3) + ($stat->assists * 2) + ($stat->is_mvp ? 5 : 0) + ($stat->clean_sheet ? 1 : 0);

            // Restar sanciones
            $playerSanctions = $sanctionsByPlayer->get($playerId, collect());
            $points -= $playerSanctions->count();

            if (!isset($players[$playerId])) {
                $players[$playerId] = [
                    'player' => $stat->player,
                    'points' => 0,
                    'stats' => ['goals' => 0, 'assists' => 0, 'mvp' => 0, 'clean_sheet' => 0]
                ];
            }

            $players[$playerId]['points'] += $points;
            $players[$playerId]['stats']['goals'] += $stat->goals;
            $players[$playerId]['stats']['assists'] += $stat->assists;
            $players[$playerId]['stats']['mvp'] += $stat->is_mvp ? 1 : 0;
            $players[$playerId]['stats']['clean_sheet'] += $stat->clean_sheet ? 1 : 0;
        }

        // Ordenar y tomar top 5
        return collect($players)->sortByDesc('points')->take(5)->values();
    }

    private function calculateGoalkeepersRanking($playerStats, $sanctions)
    {
        $goalkeepers = [];

        // Agrupar sanciones por player_id
        $sanctionsByPlayer = $sanctions->groupBy('player_id');

        foreach ($playerStats as $stat) {
            if ($stat->saves == 0) continue; // Skip non-goalkeepers

            $playerId = $stat->player_id;
            $points = ($stat->saves * 0.5) + ($stat->is_mvp ? 5 : 0) + ($stat->clean_sheet ? 5 : 0);

            // Restar sanciones
            $playerSanctions = $sanctionsByPlayer->get($playerId, collect());
            $points -= $playerSanctions->count();

            if (!isset($goalkeepers[$playerId])) {
                $goalkeepers[$playerId] = [
                    'player' => $stat->player,
                    'points' => 0,
                    'stats' => ['saves' => 0, 'mvp' => 0, 'clean_sheet' => 0]
                ];
            }

            $goalkeepers[$playerId]['points'] += $points;
            $goalkeepers[$playerId]['stats']['saves'] += $stat->saves;
            $goalkeepers[$playerId]['stats']['mvp'] += $stat->is_mvp ? 1 : 0;
            $goalkeepers[$playerId]['stats']['clean_sheet'] += $stat->clean_sheet ? 1 : 0;
        }

        // Ordenar y tomar top 5
        return collect($goalkeepers)->sortByDesc('points')->take(5)->values();
    }
}
