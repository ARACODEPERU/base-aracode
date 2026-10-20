<?php

namespace Modules\Socialevents\Support;

/**
 * Catálogo único de fases del torneo.
 *
 * Las claves canónicas son las que se guardan en `event_edition_matches.phase`.
 * Los alias (round_8, quarter, semi, group_stage, etc.) existen solo para leer
 * datos históricos y nunca deben usarse como opciones de un selector.
 */
final class TournamentPhaseLabels
{
    /**
     * Claves canónicas en orden de competencia.
     *
     * El tercer puesto se juega antes de la final, por eso va antes en el orden.
     *
     * @var array<string, string>
     */
    public const CANONICAL = [
        'league' => 'Fase de grupos / Liga',
        'groups' => 'Fase de grupos',
        'round_16' => 'Octavos de final',
        'quarterfinals' => 'Cuartos de final',
        'semifinals' => 'Semifinales',
        'third_place' => 'Tercer y cuarto puesto',
        'final' => 'Gran final',
    ];

    /**
     * Alias de lectura: clave heredada => clave canónica.
     *
     * @var array<string, string>
     */
    public const ALIASES = [
        'round_8' => 'round_16',
        'round_of_16' => 'round_16',
        'eighth_finals' => 'round_16',
        'quarter' => 'quarterfinals',
        'quarters' => 'quarterfinals',
        'semi' => 'semifinals',
        'semis' => 'semifinals',
        'third' => 'third_place',
        'third_and_fourth' => 'third_place',
        'group_stage' => 'groups',
        'knockout' => 'knockout',
        'playoff' => 'playoff',
        'relampago' => 'relampago',
    ];

    /**
     * Fases de eliminación: aquí sí se permite ida y vuelta y muerte súbita.
     *
     * @var array<int, string>
     */
    public const KNOCKOUT_PHASES = [
        'round_16',
        'quarterfinals',
        'semifinals',
        'third_place',
        'final',
        'knockout',
        'playoff',
    ];

    /**
     * Diccionario completo (canónicas + alias + fases heredadas) para etiquetar
     * cualquier partido ya guardado.
     *
     * @return array<string, string>
     */
    public static function labels(): array
    {
        $labels = self::CANONICAL;

        foreach (self::ALIASES as $alias => $canonical) {
            $labels[$alias] = self::CANONICAL[$canonical] ?? ucfirst(str_replace('_', ' ', $alias));
        }

        // Fases heredadas sin equivalente canónico: se etiquetan para que no salgan crudas.
        $labels['knockout'] = 'Eliminatorias';
        $labels['playoff'] = 'Playoffs';
        $labels['relampago'] = 'Relámpago';

        return $labels;
    }

    /**
     * Opciones para los selectores: solo claves canónicas, sin duplicados.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return collect(self::CANONICAL)
            ->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])
            ->values()
            ->all();
    }

    /**
     * Devuelve la clave canónica de una fase (los alias se resuelven).
     * Las claves fuera del catálogo se devuelven tal cual.
     */
    public static function canonical(?string $phase): ?string
    {
        if ($phase === null || $phase === '') {
            return null;
        }

        if (isset(self::CANONICAL[$phase])) {
            return $phase;
        }

        return self::ALIASES[$phase] ?? $phase;
    }

    /**
     * ¿La fase es de eliminación (permite cruce de ida y vuelta)?
     */
    public static function isKnockout(?string $phase): bool
    {
        $canonical = self::canonical($phase);

        return $canonical !== null && in_array($canonical, self::KNOCKOUT_PHASES, true);
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

    /**
     * Orden de visualización de las fases (menor = primero).
     */
    public static function order(?string $phase): int
    {
        $canonical = self::canonical($phase);

        if ($canonical === null) {
            return 999;
        }

        $position = array_search($canonical, array_keys(self::CANONICAL), true);

        return $position === false ? 999 : $position + 1;
    }
}
