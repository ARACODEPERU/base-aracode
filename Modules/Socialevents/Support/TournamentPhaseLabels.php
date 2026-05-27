<?php

namespace Modules\Socialevents\Support;

final class TournamentPhaseLabels
{
    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            'league' => 'Fase de grupos / Liga',
            'groups' => 'Fase de grupos',
            'group_stage' => 'Fase de grupos',
            'round_16' => 'Octavos de final',
            'round_8' => 'Octavos de final',
            'quarter' => 'Cuartos de final',
            'quarterfinals' => 'Cuartos de final',
            'semi' => 'Semifinales',
            'semifinals' => 'Semifinales',
            'third_place' => 'Tercer puesto',
            'final' => 'Gran final',
            'playoff' => 'Playoffs',
            'relampago' => 'Relámpago',
        ];
    }

    public static function label(?string $phase): string
    {
        if ($phase === null || $phase === '') {
            return 'Fase';
        }

        $labels = self::labels();

        if (isset($labels[$phase])) {
            return $labels[$phase];
        }

        return ucfirst(str_replace('_', ' ', $phase));
    }
}
