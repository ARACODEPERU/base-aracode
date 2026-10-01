<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Permiso de la pestaña Alertas del módulo Security.
 *
 * Idempotente: si ya existe no se duplica, y solo se concede a los roles de
 * administración (igual que el resto de permisos de configuración).
 */
return new class extends Migration
{
    private const PERMISSION = 'conf_alertas';

    public function up(): void
    {
        if (! class_exists(Permission::class)) {
            return;
        }

        $permission = Permission::firstOrCreate([
            'name' => self::PERMISSION,
            'guard_name' => 'web',
        ]);

        foreach (['Administrador', 'admin'] as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();

            if ($role && ! $role->hasPermissionTo($permission)) {
                $role->givePermissionTo($permission);
            }
        }
    }

    public function down(): void
    {
        if (! class_exists(Permission::class)) {
            return;
        }

        Permission::where('name', self::PERMISSION)
            ->where('guard_name', 'web')
            ->delete();
    }
};
