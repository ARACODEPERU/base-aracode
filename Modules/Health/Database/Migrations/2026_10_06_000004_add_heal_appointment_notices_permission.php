<?php

use App\Models\Modulo;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /** Identificador del modulo Salud en `modulos`. */
    private const MODULE_IDENTIFIER = 'M009';

    /**
     * Permiso de la pantalla Salud > Avisos (notificaciones a pacientes).
     *
     * Se concede a los roles administradores y se enlaza al modulo M009 para
     * que el editor de roles lo agrupe. El seeder del modulo tambien lo crea
     * (instalacion desde cero), de modo que la prueba de catalogo de permisos
     * no lo considera huerfano.
     */
    public function up(): void
    {
        $permission = Permission::firstOrCreate([
            'name' => 'heal_avisos',
            'guard_name' => 'web',
        ]);

        foreach (['admin', 'Administrador', 'webAdmin'] as $roleName) {
            $role = Role::where('name', $roleName)->first();

            if ($role && ! $role->hasPermissionTo('heal_avisos')) {
                $role->givePermissionTo($permission);
            }
        }

        // Rol base (id 1): mismo patron que PermissionsHealthTableSeeder.
        $baseRole = Role::find(1);

        if ($baseRole && ! $baseRole->hasPermissionTo('heal_avisos')) {
            $baseRole->givePermissionTo($permission);
        }

        if (Modulo::where('identifier', self::MODULE_IDENTIFIER)->exists()) {
            DB::table('model_has_permissions')->updateOrInsert(
                [
                    'permission_id' => $permission->id,
                    'model_type' => Modulo::class,
                    'model_id' => self::MODULE_IDENTIFIER,
                ],
                []
            );
        }
    }

    public function down(): void
    {
        $permission = Permission::where('name', 'heal_avisos')->where('guard_name', 'web')->first();

        if (! $permission) {
            return;
        }

        foreach (['admin', 'Administrador', 'webAdmin'] as $roleName) {
            $role = Role::where('name', $roleName)->first();

            if ($role && $role->hasPermissionTo('heal_avisos')) {
                $role->revokePermissionTo('heal_avisos');
            }
        }

        DB::table('model_has_permissions')->where('permission_id', $permission->id)->delete();
        $permission->delete();

        Artisan::call('permission:cache-reset');
    }
};
