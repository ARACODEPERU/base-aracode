<?php

namespace Modules\Treasury\Http\Controllers;

use Illuminate\Support\Facades\Auth;

/**
 * Validación de permisos spatie en controladores del módulo.
 * Lanza 403 si el usuario autenticado no tiene el permiso dado.
 */
trait ValidatesTreasuryRequests
{
    protected function authorizePermission(string $permission): void
    {
        $user = Auth::user();

        if (! $user || ! method_exists($user, 'hasPermissionTo') || ! $user->hasPermissionTo($permission)) {
            abort(403, 'No tienes permiso para realizar esta acción.');
        }
    }
}
