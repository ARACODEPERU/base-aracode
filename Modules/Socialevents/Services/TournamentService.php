<?php

namespace Modules\Socialevents\Services;

use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Modules\Socialevents\Entities\EventEditionMatch;

class TournamentService
{
    public function generate($editionId, $format, array $teamIds)
    {
        if (empty($teamIds)) {
            throw ValidationException::withMessages([
                'teams' => 'No se han seleccionado equipos para generar el torneo.'
            ]);
        }

        return DB::transaction(function () use ($editionId, $format, $teamIds) {
            // Borra partidos pendientes antes de crear nuevos
            EventEditionMatch::where('edition_id', $editionId)
                ->where('status', 'pending')
                ->delete();

            shuffle($teamIds);

            return match($format) {
                'round_robin'              => $this->generateRoundRobin($teamIds, $editionId),
                'round_robin_playoff'      => $this->generateHybridFormat($teamIds, $editionId),
                'group_stage_and_playoffs' => $this->generateGroupStage($teamIds, $editionId),
                'relampago'                => $this->generateKnockout($teamIds, $editionId),
                'single_elimination'       => $this->generateSingleElimination($teamIds, $editionId),
                default                    => throw new \Exception("Formato no reconocido"),
            };
        });
    }

    // --- FORMATO 1: TODOS CONTRA TODOS ---
    private function generateRoundRobin($teams, $editionId, $groupName = null)
    {
        // Si la cantidad de equipos es impar, añadimos un equipo "fantasma" (descanso)
        if (count($teams) % 2 != 0) $teams[] = null;

        $totalTeams = count($teams);
        $rounds = $totalTeams - 1; // Número total de jornadas
        $matchesPerRound = $totalTeams / 2;

        for ($i = 0; $i < $rounds; $i++) {
            // $i + 1 representa la Jornada actual (Jornada 1, Jornada 2...)
            $roundNumber = $i + 1;

            for ($j = 0; $j < $matchesPerRound; $j++) {
                $home = $teams[$j];
                $away = $teams[$totalTeams - 1 - $j];

                // Solo creamos el partido si ninguno de los dos es el equipo de descanso (null)
                if ($home && $away) {
                    EventEditionMatch::create([
                        'edition_id'   => $editionId,
                        'team_h_id'    => $home,
                        'team_a_id'    => $away,
                        'round_number' => $roundNumber, // <-- AQUÍ SE ASIGNA LA JORNADA CORRECTA
                        'group_name'   => $groupName,
                        'phase'        => $groupName ? 'group' : 'league',
                        'status'       => 'pending'
                    ]);
                }
            }

            // Algoritmo de Rotación de Berger (mantiene el primer equipo fijo y rota los demás)
            $pivot = array_shift($teams);
            array_unshift($teams, array_pop($teams));
            array_unshift($teams, $pivot);
        }
    }

    // --- FORMATO 2: POR GRUPOS ---
    private function generateGroupStage($teams, $editionId)
    {
        $chunks = array_chunk($teams, 4);
        $alphabet = range('A', 'Z');

        foreach ($chunks as $index => $groupTeams) {
            $this->generateRoundRobin($groupTeams, $editionId, $alphabet[$index]);
        }
    }

    private function generateKnockout($teams, $editionId)
    {
        $count = count($teams);
        if ($count < 2) throw new \Exception("Mínimo 2 equipos para generar eliminación directa");

        // 1. Barajar equipos para el "sorteo" inicial
        shuffle($teams);

        // 2. Determinar la potencia de 2 superior (2, 4, 8, 16, 32...) para el cuadro
        $power = 1;
        while ($power < $count) {
            $power *= 2;
        }

        $roundNumber = 1;
        $currentTeams = $teams;

        // 3. Crear la Primera Ronda (con manejo de BYES si no son potencia de 2)
        // Los equipos que no tienen pareja pasan automáticamente (BYE)
        $matchesInRound = $power / 2;
        $nextRoundPlaceholders = [];

        for ($i = 0; $i < $matchesInRound; $i++) {
            $teamH = array_shift($currentTeams);
            $teamA = array_shift($currentTeams);

            // Si hay ambos equipos, se crea partido normal
            if ($teamH && $teamA) {
                EventEditionMatch::create([
                    'edition_id'   => $editionId,
                    'team_h_id'    => $teamH,
                    'team_a_id'    => $teamA,
                    'phase'        => 'knockout',
                    'round_number' => $roundNumber,
                    'match_order'  => $i + 1, // Para saber qué ganador va contra quién
                    'status'       => 'pending'
                ]);
            }
            // Si solo hay uno (es un BYE/Pasa directo por sorteo)
            elseif ($teamH) {
                // Este equipo pasa directo a la siguiente ronda sin jugar
                $nextRoundPlaceholders[] = $teamH;
            }
        }

        // 4. Generar los "huecos" (Placeholders) para las rondas siguientes hasta la Final
        // Esto permite que el frontend visualice el camino al título aunque no haya equipos definidos
        $roundNumber++;
        $remainingMatches = $matchesInRound / 2;

        while ($remainingMatches >= 1) {
            for ($j = 0; $j < $remainingMatches; $j++) {
                EventEditionMatch::create([
                    'edition_id'   => $editionId,
                    'team_h_id'    => array_shift($nextRoundPlaceholders) ?? null, // NULL significa "Esperando Ganador"
                    'team_a_id'    => array_shift($nextRoundPlaceholders) ?? null,
                    'phase'        => 'knockout',
                    'round_number' => $roundNumber,
                    'match_order'  => $j + 1,
                    'status'       => 'pending'
                ]);
            }
            $remainingMatches /= 2;
            $roundNumber++;
        }
    }

    private function generateSingleElimination($teams, $editionId)
    {
        $this->generateKnockout($teams, $editionId);
    }

    // --- FORMATO HÍBRIDO (LIGA + PLAYOFFS) ---
    protected function generateHybridFormat($teamIds, $editionId)
    {
        // 1. Genera la Liga con jornadas automáticas (ej. del 1 al 9 si son 10 equipos)
        $this->generateRoundRobin($teamIds, $editionId);

        // 2. FASE DE PLAYOFFS
        $playoffPhases = [
            ['phase' => 'quarterfinals', 'count' => 4],
            ['phase' => 'semifinals',    'count' => 2],
            ['phase' => 'third_place',   'count' => 1],
            ['phase' => 'final',         'count' => 1],
        ];

        foreach ($playoffPhases as $config) {
            for ($i = 1; $i <= $config['count']; $i++) {
                EventEditionMatch::create([
                    'edition_id'    => $editionId,
                    'team_h_id'     => null,
                    'team_a_id'     => null,
                    'phase'         => $config['phase'],
                    'round_number'  => 1, // En eliminatorias suele ser 1 por fase
                    'status'        => 'pending',
                    'placeholder_h' => $this->getPlaceholderName($config['phase'], $i, 'h'),
                    'placeholder_a' => $this->getPlaceholderName($config['phase'], $i, 'a'),
                ]);
            }
        }

        return true;
    }

    // Auxiliar para que la base de datos no se vea vacía, sino con texto de ayuda
    private function getPlaceholderName($phase, $index, $side)
    {
        if ($phase === 'quarterfinals') return "Por Clasificar";
        if ($phase === 'semifinals') return "Ganador QF";
        if ($phase === 'final') return "Ganador SF";
        return "TBD";
    }
}
