<?php

namespace Modules\Socialevents\Services;

use Modules\SocialEvents\Entities\EventEditionMatch;
use Modules\SocialEvents\Entities\EventEditionTeam;

class PositionTableService
{
    public function updateTablePositions($editionId)
    {
        // 1. Obtener todos los partidos finalizados de esta edición
        $matches = EventEditionMatch::where('edition_id', $editionId)
            ->where('status', 'finished')
            ->get();

        // 2. Obtener los equipos de esta edición
        $teams = EventEditionTeam::where('edition_id', $editionId)->get();

        foreach ($teams as $team) {
            $stats = [
                'played' => 0, 'won' => 0, 'drawn' => 0, 'lost' => 0,
                'gf' => 0, 'ga' => 0, 'points' => 0
            ];

            foreach ($matches as $m) {
                $isLocal = $m->team_h_id == $team->team_id;
                $isVisit = $m->team_a_id == $team->team_id;

                if ($isLocal || $isVisit) {
                    $stats['played']++;
                    $golesFavor = $isLocal ? $m->score_h : $m->score_a;
                    $golesContra = $isLocal ? $m->score_a : $m->score_h;

                    $stats['gf'] += $golesFavor;
                    $stats['ga'] += $golesContra;

                    if ($golesFavor > $golesContra) {
                        $stats['won']++;
                        $stats['points'] += 3;
                    } elseif ($golesFavor == $golesContra) {
                        $stats['drawn']++;
                        $stats['points'] += 1;
                    } else {
                        $stats['lost']++;
                    }
                }
            }

            // 3. Actualizar la tabla event_edition_teams
            EventEditionTeam::where('edition_id', $editionId)
                ->where('team_id', $team->team_id)
                ->update([
                    'matches_played'  => $stats['played'],
                    'matches_won'     => $stats['won'],
                    'matches_drawn'   => $stats['drawn'],
                    'matches_lost'    => $stats['lost'],
                    'goals_for'       => $stats['gf'],
                    'goals_against'   => $stats['ga'],
                    'goal_difference' => $stats['gf'] - $stats['ga'],
                    'points'          => $stats['points']
                ]);
        }

        // 4. Actualizar el RANK (Posición 1, 2, 3...) basado en los nuevos puntos
        $this->updateRank($editionId);
    }

    private function updateRank($editionId)
    {
        $rankedTeams = EventEditionTeam::where('edition_id', $editionId)
            ->orderBy('points', 'desc')
            ->orderBy('goal_difference', 'desc') // Primer criterio de desempate
            ->orderBy('goals_for', 'desc')       // Segundo criterio
            ->get();

        foreach ($rankedTeams as $index => $team) {
            EventEditionTeam::where('team_id', $team->team_id)
                ->update(['rank' => $index + 1]);
        }
    }
}
