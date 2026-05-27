<?php

namespace Modules\Socialevents\Support;

final class TournamentMedia
{
    public static function url(?string $path): ?string
    {
        if (! filled($path)) {
            return null;
        }

        return asset('storage/'.$path);
    }
}
