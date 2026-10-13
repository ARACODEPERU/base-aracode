<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /** Permiso para ver y configurar el canal SMS de los avisos de asistencia. */
    public const CONFIG_PERMISSION = 'aca_school_sms_configuracion';

    /**
     * Permiso de la pantalla Parametros / SMS del modulo Academico.
     *
     * Es distinto de aca_smsgate_configuracion (el canal de las campanas de
     * especializacion): aqui se administran las credenciales propias de los
     * avisos de asistencia escolar. Se crea con firstOrCreate (idempotente), se
     * enlaza al modulo M007 y se concede a los roles administradores.
     */
    public function up(): void
    {
        $permission = Permission::firstOrCreate(['name' => self::CONFIG_PERMISSION]);
        $this->linkToModule($permission);
        $this->grant(self::CONFIG_PERMISSION, ['admin', 'Administrador']);

        Artisan::call('permission:cache-reset');
    }

    public function down(): void
    {
        $this->revoke(self::CONFIG_PERMISSION, ['admin', 'Administrador']);

        Permission::where('name', self::CONFIG_PERMISSION)->delete();

        Artisan::call('permission:cache-reset');
    }

    /**
     * Enlaza el permiso al modulo Academico (M007) para que el editor de roles
     * lo agrupe en su modulo en lugar de dejarlo en "Otros permisos".
     */
    private function linkToModule(Permission $permission): void
    {
        $moduleExists = DB::table('modulos')->where('identifier', 'M007')->exists();

        if (! $moduleExists) {
            return;
        }

        $alreadyLinked = DB::table('model_has_permissions')
            ->where('permission_id', $permission->id)
            ->where('model_type', 'App\\Models\\Modulo')
            ->where('model_id', 'M007')
            ->exists();

        if ($alreadyLinked) {
            return;
        }

        DB::table('model_has_permissions')->insertOrIgnore([
            'permission_id' => $permission->id,
            'model_type' => 'App\\Models\\Modulo',
            'model_id' => 'M007',
        ]);
    }

    /**
     * @param  array<int, string> $roleNames
     */
    private function grant(string $permissionName, array $roleNames): void
    {
        foreach ($roleNames as $roleName) {
            $role = Role::where('name', $roleName)->first();

            if ($role && ! $role->hasPermissionTo($permissionName)) {
                $role->givePermissionTo($permissionName);
            }
        }
    }

    /**
     * @param  array<int, string> $roleNames
     */
    private function revoke(string $permissionName, array $roleNames): void
    {
        foreach ($roleNames as $roleName) {
            $role = Role::where('name', $roleName)->first();

            if ($role && $role->hasPermissionTo($permissionName)) {
                $role->revokePermissionTo($permissionName);
            }
        }
    }
};
