<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /** Permiso de la pantalla Academico > Modos de Prueba. */
    public const TEST_MODE_PERMISSION = 'aca_school_modo_prueba';

    /** Unicos roles con acceso: solo el administrador del sistema. */
    public const ALLOWED_ROLES = ['admin'];

    /**
     * Permiso de la pantalla "Modos de Prueba" (llenar, borrar y dejar en modo
     * produccion los datos de prueba del colegio).
     *
     * Se concede unicamente al rol admin: la pantalla crea y elimina datos
     * escolares masivos, asi que no se reparte con los demas roles. Si mas
     * adelante se necesita otro rol, se concede desde Seguridad > Roles y
     * permisos (el permiso ya queda listado dentro del modulo Academico).
     *
     * Idempotente: firstOrCreate + enlaces/rechazos condicionados.
     */
    public function up(): void
    {
        $permission = Permission::firstOrCreate(['name' => self::TEST_MODE_PERMISSION]);
        $this->linkToModule($permission);

        foreach (self::ALLOWED_ROLES as $roleName) {
            $role = Role::where('name', $roleName)->first();

            if ($role && ! $role->hasPermissionTo(self::TEST_MODE_PERMISSION)) {
                $role->givePermissionTo(self::TEST_MODE_PERMISSION);
            }
        }

        Artisan::call('permission:cache-reset');
    }

    public function down(): void
    {
        foreach (self::ALLOWED_ROLES as $roleName) {
            $role = Role::where('name', $roleName)->first();

            if ($role && $role->hasPermissionTo(self::TEST_MODE_PERMISSION)) {
                $role->revokePermissionTo(self::TEST_MODE_PERMISSION);
            }
        }

        DB::table('model_has_permissions')->where('permission_id', Permission::where('name', self::TEST_MODE_PERMISSION)->value('id'))->delete();

        Permission::where('name', self::TEST_MODE_PERMISSION)->delete();

        Artisan::call('permission:cache-reset');
    }

    /**
     * Enlaza el permiso al modulo Academico (M007) para que el editor de roles
     * lo agrupe en su modulo en lugar de dejarlo en "Otros permisos".
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
