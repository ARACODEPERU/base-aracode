<?php

use App\Models\Modulo;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permiso de la pantalla «Peso por página».
 *
 * Aditiva e idempotente, igual que el resto de permisos del módulo: crea el
 * permiso, lo concede a los roles de administración y lo enlaza al módulo.
 */
return new class extends Migration
{
    private const MODULE_IDENTIFIER = 'M024';

    private const MODULE_DESCRIPTION = 'Rendimiento';

    /**
     * @var array<int, string>
     */
    private const ADMIN_ROLES = ['admin', 'Administrador'];

    private const PERMISSION = 'performance_weight';

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

        $permission = Permission::firstOrCreate([
            'name' => self::PERMISSION,
            'guard_name' => 'web',
        ]);

        foreach (Role::query()->whereIn('name', self::ADMIN_ROLES)->where('guard_name', 'web')->get() as $role) {
            if (! $role->hasPermissionTo($permission)) {
                $role->givePermissionTo($permission);
            }
        }

        DB::table('model_has_permissions')->insertOrIgnore([
            'permission_id' => $permission->id,
            'model_type' => Modulo::class,
            'model_id' => $modulo->identifier,
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Aditiva: no se revoca el acceso concedido.
    }
};
