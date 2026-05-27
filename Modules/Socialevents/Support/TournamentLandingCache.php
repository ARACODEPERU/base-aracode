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
    }
}
