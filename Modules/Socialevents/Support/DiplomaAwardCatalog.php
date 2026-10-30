<?php

namespace Modules\Socialevents\Support;

/**
 * Catálogo de premios que puede recibir un diploma.
 *
 * Cada clave es estable porque el diseño del diploma se asocia al premio por
 * `award_key`, no por su etiqueta visible (que el organizador puede cambiar).
 */
final class DiplomaAwardCatalog
{
    /**
     * Premios con ganador sugerido automáticamente desde rankings o tabla.
     *
     * @return array<string, array{label: string, recipient_type: string, place: ?int, source: string}>
     */
    public static function defaults(): array
    {
        return [
            'champion' => ['label' => 'Campeón', 'recipient_type' => 'team', 'place' => 1, 'source' => 'auto'],
            'runner_up' => ['label' => 'Subcampeón', 'recipient_type' => 'team', 'place' => 2, 'source' => 'auto'],
            'third_place' => ['label' => 'Tercer lugar', 'recipient_type' => 'team', 'place' => 3, 'source' => 'auto'],
            'fourth_place' => ['label' => 'Cuarto lugar', 'recipient_type' => 'team', 'place' => 4, 'source' => 'auto'],
            'mvp' => ['label' => 'Mejor jugador', 'recipient_type' => 'player', 'place' => null, 'source' => 'auto'],
            'top_scorer' => ['label' => 'Goleador', 'recipient_type' => 'player', 'place' => null, 'source' => 'auto'],
            'best_goalkeeper' => ['label' => 'Mejor arquero', 'recipient_type' => 'player', 'place' => null, 'source' => 'auto'],
            'top_assists' => ['label' => 'Máximo asistidor', 'recipient_type' => 'player', 'place' => null, 'source' => 'auto'],
            'best_defender' => ['label' => 'Mejor defensa', 'recipient_type' => 'player', 'place' => null, 'source' => 'manual'],
            'fair_play' => ['label' => 'Juego limpio', 'recipient_type' => 'team', 'place' => null, 'source' => 'manual'],
            'revelation_team' => ['label' => 'Equipo revelación', 'recipient_type' => 'team', 'place' => null, 'source' => 'manual'],
        ];
    }

    /** Claves que siempre se pueden usar en un diseño. */
    public static function keys(): array
    {
        return array_keys(self::defaults());
    }

    public static function exists(string $key): bool
    {
        return array_key_exists($key, self::defaults()) || str_starts_with($key, 'custom_');
    }

    public static function isTeamAward(string $key): bool
    {
        return (self::defaults()[$key]['recipient_type'] ?? null) === 'team';
    }

    public static function label(string $key): string
    {
        return self::defaults()[$key]['label'] ?? 'Premio';
    }

    /** Etiqueta por defecto para premios libres: custom_mejor_defensa -> "Mejor defensa". */
    public static function labelFromKey(string $key): string
    {
        if (isset(self::defaults()[$key])) {
            return self::defaults()[$key]['label'];
        }

        $plain = str_replace(['custom_', '_'], ['', ' '], $key);

        return ucfirst(trim($plain)) ?: 'Premio';
    }
}
