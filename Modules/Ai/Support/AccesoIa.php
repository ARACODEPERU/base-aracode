<?php

namespace Modules\Ai\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Throwable;

/**
 * Regla única de acceso a Aracodito con IA.
 *
 * - El permiso `ia_aracodito` se asigna por rol (lo administra el rol admin).
 * - El rol `admin` conserva su interruptor personal en el perfil.
 * - Los demás roles habilitados usan la IA sin interruptor propio: al
 *   quitarles el permiso dejan de tener acceso de inmediato.
 */
class AccesoIa
{
    public const PERMISO = 'ia_aracodito';

    public const ROL_ADMIN = 'admin';

    public static function disponible(?Authenticatable $usuario): bool
    {
        if ($usuario === null || ! (bool) ($usuario->aracodito_enabled ?? false)) {
            return false;
        }

        try {
            if (! method_exists($usuario, 'hasPermissionTo') || ! $usuario->hasPermissionTo(self::PERMISO)) {
                return false;
            }
        } catch (Throwable) {
            // El permiso todavía no existe (módulo sin migrar): sin acceso.
            return false;
        }

        if (self::esAdmin($usuario)) {
            return (bool) ($usuario->aracodito_ia_enabled ?? false);
        }

        return true;
    }

    public static function esAdmin(?Authenticatable $usuario): bool
    {
        return $usuario !== null
            && method_exists($usuario, 'hasRole')
            && $usuario->hasRole(self::ROL_ADMIN);
    }
}
