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
        if (count($teams) % 2 != 0) $teams[] = null;

        $totalTeams = count($teams);
        $rounds = $totalTeams - 1;
        $matchesPerRound = $totalTeams / 2;

        for ($i = 0; $i < $rounds; $i++) {
            for ($j = 0; $j < $matchesPerRound; $j++) {
                $home = $teams[$j];
                $away = $teams[$totalTeams - 1 - $j];

                if ($home && $away) {
                    EventEditionMatch::create([
                        'edition_id'   => $editionId,
                        'team_h_id'    => $home, // Corregido
                        'team_a_id'    => $away, // Corregido
                        'round_number' => $i + 1,
                        'group_name'   => $groupName,
                        'phase'        => $groupName ? 'group' : 'league',
                        'status'       => 'pending'
                    ]);
                }
            }
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

    // --- FORMATO 3: RELÁMPAGO / SINGLE ELIMINATION ---
    private function generateKnockout($teams, $editionId)
    {
        $count = count($teams);
        if ($count < 2) throw new \Exception("Mínimo 2 equipos");

        $roundNumber = 1;
        while (count($teams) >= 2) {
            EventEditionMatch::create([
                'edition_id'   => $editionId,
                'team_h_id'    => array_shift($teams), // Corregido
                'team_a_id'    => array_shift($teams), // Corregido
                'phase'        => 'knockout',
                'round_number' => $roundNumber,
                'status'       => 'pending'
            ]);
        }
    }

    private function generateSingleElimination($teams, $editionId)
    {
        $this->generateKnockout($teams, $editionId);
    }

    // --- FORMATO HÍBRIDO (LIGA + PLAYOFFS) ---
    protected function generateHybridFormat($teamIds, $editionId)
    {
        // 1. FASE DE LIGA (Round Robin completo)
        $this->generateRoundRobin($teamIds, $editionId);

        // 2. FASE DE PLAYOFFS (Estructura vacía)
        // Corregidos todos los nombres de campos a team_h_id y team_a_id

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
                    'team_h_id'     => null, // Correcto
                    'team_a_id'     => null, // Correcto
                    'phase'         => $config['phase'],
                    'round_number'  => 1,
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
