<?php

namespace Modules\Socialevents\Services;

use Modules\SocialEvents\Entities\EventEditionMatch;
use Modules\SocialEvents\Entities\EventEditionTeam;

class PositionTableService
{
    public function updateTablePositions($editionId)
    {
        //dd($editionId);
        // 1. Obtener equipos ÚNICOS de esta edición para evitar duplicados en el array de memoria
        $editionTeams = EventEditionTeam::where('edition_id', $editionId)->get();

        // 2. Inicializar el array de estadísticas (RESET TOTAL A CERO)
        $table = [];
        foreach ($editionTeams as $team) {
            // Usamos el team_id como llave única
            $table[$team->team_id] = [
                'played' => 0, 'won' => 0, 'drawn' => 0, 'lost' => 0,
                'gf' => 0, 'ga' => 0, 'points' => 0
            ];
        }

        // 3. Obtener partidos SOLAMENTE de esta edición y con marcador válido
        $matches = EventEditionMatch::where('edition_id', $editionId)
            ->whereNotNull('team_h_id')
            ->whereNotNull('team_a_id')
            ->where(function ($query) {
                $query->where('status','finished')
                    ->orWhere('status','closed');
            })
            ->get();
        //dd($matches);
        $count = 0;
        // 4. Procesar cada partido UNA SOLA VEZ
        foreach ($matches as $m) {
            $hId = $m->team_h_id;
            $aId = $m->team_a_id;

            // Validamos que AMBOS equipos existan en el array de la edición
            if (isset($table[$hId]) && isset($table[$aId])) {

                // Sumar Partidos Jugados
                $table[$hId]['played'] += 1;
                $table[$aId]['played'] += 1;

                // Sumar Goles
                $table[$hId]['gf'] += (int)$m->score_h;
                $table[$hId]['ga'] += (int)$m->score_a;
                $table[$aId]['gf'] += (int)$m->score_a;
                $table[$aId]['ga'] += (int)$m->score_h;

                // Lógica de Puntos y Resultados
                if ($m->score_h > $m->score_a) {
                    $table[$hId]['won'] += 1;
                    $table[$hId]['points'] += 3;
                    $table[$aId]['lost'] += 1;
                } elseif ($m->score_h < $m->score_a) {
                    $table[$aId]['won'] += 1;
                    $table[$aId]['points'] += 3;
                    $table[$hId]['lost'] += 1;
                } else {
                    $table[$hId]['drawn'] += 1;
                    $table[$hId]['points'] += 1;
                    $table[$aId]['drawn'] += 1;
                    $table[$aId]['points'] += 1;
                }
            }
            $count++;
        }
        //dd($count);
        // 5. Guardar en BD (Usando el ID de la tabla pivot para mayor precisión)
        foreach ($editionTeams as $teamRecord) {
            $stats = $table[$teamRecord->team_id] ?? null;

            if ($stats) {
                EventEditionTeam::where('edition_id', $editionId)
                ->where('team_id', $teamRecord->team_id)
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
        }

        $this->updateRank($editionId);
    }

    public function getStandings($editionId)
    {
        $rankedTeams = EventEditionTeam::with('equipo')->where('edition_id', $editionId)
            ->orderBy('points', 'desc')
            ->orderBy('goal_difference', 'desc')
            ->orderBy('goals_for', 'desc')
            ->get();

        $standings = [];
        foreach ($rankedTeams as $index => $team) {
            $standings[] = [
                'team_id' => $team->team_id,
                'team_name' => $team->equipo->name,
                'position' => $index + 1,
                'points' => $team->points,
                'matches_played' => $team->matches_played,
                'matches_won' => $team->matches_won,
                'matches_drawn' => $team->matches_drawn,
                'matches_lost' => $team->matches_lost,
                'goals_for' => $team->goals_for,
                'goals_against' => $team->goals_against,
                'goal_difference' => $team->goal_difference,
            ];
        }

        return $standings;
    }

    private function updateRank($editionId)
    {
        $rankedTeams = EventEditionTeam::where('edition_id', $editionId)
            ->orderBy('points', 'desc')
            ->orderBy('goal_difference', 'desc')
            ->orderBy('goals_for', 'desc')
            ->get();

        foreach ($rankedTeams as $index => $team) {
            // Usamos el ID de la fila para ser precisos
            EventEditionTeam::where('edition_id', $editionId)
                ->where('team_id', $team->team_id)
                ->update(['rank' => $index + 1]);
        }
    }
}
