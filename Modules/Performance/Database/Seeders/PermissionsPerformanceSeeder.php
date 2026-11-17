<?php

namespace Modules\Performance\Database\Seeders;

use App\Models\Modulo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionsPerformanceSeeder extends Seeder
{
    /** Identificador del módulo en la tabla `modulos`. */
    public const MODULE_IDENTIFIER = 'M024';

    /** Descripción con la que se crea el módulo. */
    public const MODULE_DESCRIPTION = 'Rendimiento';

    /**
     * Roles de administración que reciben los permisos.
     *
     * @var array<int, string>
     */
    public const ADMIN_ROLES = ['admin', 'Administrador'];

    /**
     * Todos los permisos del módulo Performance.
     *
     * @var array<int, string>
     */
    public const PERMISSIONS = [
        'performance_images',
        'performance_weight',
        'performance_queue',
    ];

    public function run(): void
    {
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
}
