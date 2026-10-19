<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /** Permiso exclusivo para ver y configurar el canal Telegram del colegio. */
    public const CONFIG_PERMISSION = 'aca_school_telegram_configuracion';

    /** Único rol con acceso: solo el rol admin. */
    public const ALLOWED_ROLES = ['admin'];

    /**
     * Permiso de la pantalla Académico > Colegio > Parámetros Telegram.
     *
     * Se concede únicamente al rol admin, tal como se solicitó para la
     * administración del bot de Telegram de entrada y salida escolar.
     */
    public function up(): void
    {
        $permission = Permission::firstOrCreate(['name' => self::CONFIG_PERMISSION]);
        $this->linkToModule($permission);

        foreach (self::ALLOWED_ROLES as $roleName) {
            $role = Role::where('name', $roleName)->first();

            if ($role && ! $role->hasPermissionTo(self::CONFIG_PERMISSION)) {
                $role->givePermissionTo(self::CONFIG_PERMISSION);
            }
        }

        Artisan::call('permission:cache-reset');
    }

    public function down(): void
    {
        foreach (self::ALLOWED_ROLES as $roleName) {
            $role = Role::where('name', $roleName)->first();

            if ($role && $role->hasPermissionTo(self::CONFIG_PERMISSION)) {
                $role->revokePermissionTo(self::CONFIG_PERMISSION);
            }
        }

        DB::table('model_has_permissions')
            ->where('permission_id', Permission::where('name', self::CONFIG_PERMISSION)->value('id'))
            ->delete();

        Permission::where('name', self::CONFIG_PERMISSION)->delete();

        Artisan::call('permission:cache-reset');
    }

    /**
     * Enlaza el permiso al módulo Académico (M007).
     */
    private function linkToModule(Permission $permission): void
    {
        if (! DB::table('modulos')->where('identifier', 'M007')->exists()) {
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
};
