<?php

namespace Modules\Socialevents\Support;

use Illuminate\Support\Facades\Cache;

final class TournamentLandingCache
{
    public static function key(int $editionId): string
    {
        return "tournament_landing_view_{$editionId}";
    }

    public static function ttl(): int
    {
        return (int) config('socialevents.landing_cache_ttl', 120);
    }

    /**
     * @param  callable(): array<string, mixed>  $resolver
     * @return array<string, mixed>
     */
    public static function remember(int $editionId, callable $resolver): array
    {
        if (! config('socialevents.landing_cache_enabled', true)) {
            return $resolver();
        }

        return Cache::remember(self::key($editionId), self::ttl(), $resolver);
    }

    public static function forget(int $editionId): void
    {
        Cache::forget(self::key($editionId));

        // El detalle partido a partido de cada jugador (modal de la landing) se
        // cachea por jugador y categoría. Los almacenes de caché habituales no
        // permiten borrar por prefijo, así que se sube una versión por edición:
        // las claves anteriores quedan abandonadas y el siguiente request
        // recalcula con las estadísticas corregidas.
        Cache::forever(self::versionKey($editionId), self::version($editionId) + 1);
    }

    /**
     * Versión de caché de los detalles de jugador de la edición.
     */
    public static function version(int $editionId): int
    {
        return (int) Cache::get(self::versionKey($editionId), 0);
    }

    private static function versionKey(int $editionId): string
    {
        return "tournament_landing_players_version_{$editionId}";
    }

    /**
     * Clave del detalle de un jugador (modal de la landing).
     */
    public static function playerKey(int $editionId, int $playerId, string $category): string
    {
        return "tournament_landing_player_{$editionId}_v".self::version($editionId)."_{$playerId}_{$category}";
    }

    /**
     * Devuelve null cuando el jugador no tiene estadísticas en la edición.
     *
     * @param  callable(): (array<string, mixed>|null)  $resolver
     * @return array<string, mixed>|null
     */
    public static function rememberPlayerDetail(
        int $editionId,
        int $playerId,
        string $category,
        callable $resolver
    ): ?array {
        if (! config('socialevents.landing_cache_enabled', true)) {
            return $resolver();
        }

        // Solo se cachea una respuesta válida: así un jugador sin datos no
        // queda "en cacheado" como inexistente.
        $cached = Cache::get(self::playerKey($editionId, $playerId, $category));

        if (is_array($cached)) {
            return $cached;
        }

        $payload = $resolver();

        if (is_array($payload)) {
            Cache::put(self::playerKey($editionId, $playerId, $category), $payload, self::ttl());
        }

        return $payload;
    }
}
