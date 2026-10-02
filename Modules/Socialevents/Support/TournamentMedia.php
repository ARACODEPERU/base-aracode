<?php

namespace Modules\Socialevents\Support;

use Illuminate\Support\Facades\Storage;

final class TournamentMedia
{
    public static function url(?string $path): ?string
    {
        if (! filled($path)) {
            return null;
        }

        return asset('storage/'.$path);
    }

    /**
     * Verifica que el archivo exista fisicamente en el disco 'public'.
     * Protege la landing de registros cuya imagen/video fue eliminado
     * fuera de la aplicacion (limpieza manual, antivirus, backup, etc.).
     */
    public static function exists(?string $path): bool
    {
        if (! filled($path)) {
            return false;
        }

        return Storage::disk('public')->exists($path);
    }
}
