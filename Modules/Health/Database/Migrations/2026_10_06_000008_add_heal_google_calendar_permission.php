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

    /** Nombre del permiso de la pantalla Salud > Google Calendar. */
    private const PERMISSION = 'heal_google_calendar';

    /**
     * Permiso de la pantalla Salud > Google Calendar (sincronizacion con la
     * Agenda).
     *
     * Se concede a los roles administradores y se enlaza al modulo M009 para
     * que el editor de roles lo agrupe. El seeder del modulo tambien lo crea
     * (instalacion desde cero), de modo que la prueba de catalogo de permisos
     * no lo considera huerfano.
     */
    public function up(): void
    {
        $permission = Permission::firstOrCreate([
            'name' => self::PERMISSION,
            'guard_name' => 'web',
        ]);

        foreach (['admin', 'Administrador', 'webAdmin'] as $roleName) {
            $role = Role::where('name', $roleName)->first();

            if ($role && ! $role->hasPermissionTo(self::PERMISSION)) {
                $role->givePermissionTo($permission);
            }
        }

        // Rol base (id 1): mismo patron que PermissionsHealthTableSeeder.
        $baseRole = Role::find(1);

        if ($baseRole && ! $baseRole->hasPermissionTo(self::PERMISSION)) {
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
        $permission = Permission::where('name', self::PERMISSION)->where('guard_name', 'web')->first();

        if (! $permission) {
            return;
        }

        foreach (['admin', 'Administrador', 'webAdmin'] as $roleName) {
            $role = Role::where('name', $roleName)->first();

            if ($role && $role->hasPermissionTo(self::PERMISSION)) {
                $role->revokePermissionTo(self::PERMISSION);
            }
        }

        DB::table('model_has_permissions')->where('permission_id', $permission->id)->delete();
        $permission->delete();

        Artisan::call('permission:cache-reset');
    }
};
