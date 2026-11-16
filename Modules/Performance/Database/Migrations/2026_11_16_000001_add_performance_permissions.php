<?php

use App\Models\Modulo;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Asegura el permiso del módulo Rendimiento.
 *
 * Es idempotente y no revoca nada: si el permiso ya existe, ya está enlazado al
 * módulo o ya lo tiene el rol, no hace nada. En una instalación desde cero los
 * roles se crean después de las migraciones, así que el seeder del módulo
 * completa la concesión.
 */
return new class extends Migration
{
    private const MODULE_IDENTIFIER = 'M024';

    private const MODULE_DESCRIPTION = 'Rendimiento';

    /**
     * Roles de administración que reciben el permiso.
     *
     * @var array<int, string>
     */
    private const ADMIN_ROLES = ['admin', 'Administrador'];

    /**
     * Permisos que exige Routes/web.php y declara el menú lateral.
     *
     * @var array<int, string>
     */
    private const PERMISSIONS = [
        'performance_images',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('modulos')) {
            return;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $modulo = Modulo::firstOrCreate(
            ['identifier' => self::MODULE_IDENTIFIER],
            ['description' => self::MODULE_DESCRIPTION]
        );

        $roles = Role::query()
            ->whereIn('name', self::ADMIN_ROLES)
            ->where('guard_name', 'web')
            ->get();

        foreach (self::PERMISSIONS as $name) {
            $permission = Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);

            foreach ($roles as $role) {
                if (! $role->hasPermissionTo($permission)) {
                    $role->givePermissionTo($permission);
                }
            }

            DB::table('model_has_permissions')->insertOrIgnore([
                'permission_id' => $permission->id,
                'model_type' => Modulo::class,
                'model_id' => $modulo->identifier,
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // No se revocan permisos: quitarlos podría dejar sin acceso a un rol
        // que los recibió a mano. La migración es aditiva a propósito.
    }
};
