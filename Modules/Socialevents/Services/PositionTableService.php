<?php

namespace Modules\Socialevents\Services;

use Modules\Socialevents\Entities\EventEditionMatch;
use Modules\Socialevents\Entities\EventEditionTeam;
use Modules\Socialevents\Support\TournamentLandingCache;

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

            // Verificamos si cada equipo sigue en la edicion. Si uno fue eliminado
            // (p. ej. por no presentarse y retirarse), igualmente se contabiliza el
            // partido para el equipo que sigue participando (walkover).
            $homePresent = isset($table[$hId]);
            $awayPresent = isset($table[$aId]);

            // Si ninguno esta inscrito, el partido no aplica.
            if (! $homePresent && ! $awayPresent) {
                $count++;
                continue;
            }

            // Sumar Partidos Jugados y Goles a cada equipo presente.
            $addStats = function (string $sideKey, int $gf, int $ga) use (&$table) {
                $table[$sideKey]['played'] += 1;
                $table[$sideKey]['gf'] += $gf;
                $table[$sideKey]['ga'] += $ga;
            };

            if ($homePresent) {
                $addStats($hId, (int) $m->score_h, (int) $m->score_a);
            }
            if ($awayPresent) {
                $addStats($aId, (int) $m->score_a, (int) $m->score_h);
            }

            // Lógica de Puntos y Resultados
            $hasPenalties = !empty($m->penalty_rounds) && !empty($m->penalties);

            if ($hasPenalties) {
                $penaltyGoalsH = collect($m->penalties)->where('team', 'local')->where('result', 'goal')->count();
                $penaltyGoalsA = collect($m->penalties)->where('team', 'visitor')->where('result', 'goal')->count();

                if ($penaltyGoalsH > $penaltyGoalsA) {
                    // Local gana por penales
                    if ($homePresent) { $table[$hId]['won'] += 1; $table[$hId]['points'] += 3; }
                    if ($awayPresent) { $table[$aId]['lost'] += 1; }
                } elseif ($penaltyGoalsA > $penaltyGoalsH) {
                    // Visitante gana por penales
                    if ($awayPresent) { $table[$aId]['won'] += 1; $table[$aId]['points'] += 3; }
                    if ($homePresent) { $table[$hId]['lost'] += 1; }
                } else {
                    // Empate
                    if ($homePresent) { $table[$hId]['drawn'] += 1; $table[$hId]['points'] += 1; }
                    if ($awayPresent) { $table[$aId]['drawn'] += 1; $table[$aId]['points'] += 1; }
                }
            } elseif ($m->score_h > $m->score_a) {
                // Gana local
                if ($homePresent) { $table[$hId]['won'] += 1; $table[$hId]['points'] += 3; }
                if ($awayPresent) { $table[$aId]['lost'] += 1; }
            } elseif ($m->score_h < $m->score_a) {
                // Gana visitante
                if ($awayPresent) { $table[$aId]['won'] += 1; $table[$aId]['points'] += 3; }
                if ($homePresent) { $table[$hId]['lost'] += 1; }
            } else {
                // Empate
                if ($homePresent) { $table[$hId]['drawn'] += 1; $table[$hId]['points'] += 1; }
                if ($awayPresent) { $table[$aId]['drawn'] += 1; $table[$aId]['points'] += 1; }
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

        TournamentLandingCache::forget((int) $editionId);
    }

    public function getStandings($editionId)
    {
        $rankedTeams = EventEditionTeam::with('equipo')->where('edition_id', $editionId)
            ->orderByRaw('CASE WHEN matches_played = 0 THEN 1 ELSE 0 END') // Equipos sin partidos al final
            ->orderByRaw('(points + bonus_points) DESC')
            ->orderBy('goal_difference', 'desc')
            ->orderBy('goals_for', 'desc')
            ->get();

        $standings = [];
        foreach ($rankedTeams as $index => $team) {
            $standings[] = [
                'team_id' => $team->team_id,
                'team_name' => $team->equipo->name,
                'position' => $index + 1,
                'points' => $team->totalPoints(),
                'bonus_points' => (int) $team->bonus_points,
                'base_points' => (int) $team->points,
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
            ->orderByRaw('CASE WHEN matches_played = 0 THEN 1 ELSE 0 END') // Equipos sin partidos al final
            ->orderByRaw('(points + bonus_points) DESC')
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
