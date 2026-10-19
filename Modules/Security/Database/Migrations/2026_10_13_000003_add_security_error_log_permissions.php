<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Permisos de la pantalla Errores (módulo Security).
 *
 * conf_errores            ver el listado y el contexto de los errores guardados.
 * conf_errores_eliminar   borrarlos (uno, todos o los vencidos). Solo se concede
 *                         a los roles de administración: borrar la evidencia de
 *                         un fallo es una acción de administrador.
 *
 * Idempotente: si ya existen no se duplican, y solo se concede a los roles de
 * administración (igual que el resto de permisos de configuración).
 */
return new class extends Migration
{
    private const PERMISSIONS = [
        'conf_errores',
        'conf_errores_eliminar',
    ];

    public function up(): void
    {
        if (! class_exists(Permission::class)) {
            return;
        }

        foreach (self::PERMISSIONS as $name) {
            $permission = Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);

            foreach (['Administrador', 'admin'] as $roleName) {
                $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();

                if ($role && ! $role->hasPermissionTo($permission)) {
                    $role->givePermissionTo($permission);
                }
            }

            $this->linkToModule((int) $permission->id);
        }
    }

    public function down(): void
    {
        if (! class_exists(Permission::class)) {
            return;
        }

        Permission::whereIn('name', self::PERMISSIONS)
            ->where('guard_name', 'web')
            ->delete();
    }

    /**
     * Enlaza el permiso al módulo de Configuración y seguridad para que el
     * editor de roles lo agrupe ahí en lugar de dejarlo en "Otros permisos".
     */
    private function linkToModule(int $permissionId): void
    {
        $moduleExists = DB::table('modulos')->where('identifier', 'M019')->exists();

        if (! $moduleExists) {
            return;
        }

        $alreadyLinked = DB::table('model_has_permissions')
            ->where('permission_id', $permissionId)
            ->where('model_type', 'App\\Models\\Modulo')
            ->where('model_id', 'M019')
            ->exists();

        if ($alreadyLinked) {
            return;
        }

        DB::table('model_has_permissions')->insertOrIgnore([
            'permission_id' => $permissionId,
            'model_type' => 'App\\Models\\Modulo',
            'model_id' => 'M019',
        ]);
    }
};
