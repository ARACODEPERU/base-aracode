<?php

use App\Models\Modulo;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permisos nuevos de Tesorería: panel de efectivo y vinculación manual de
 * cobros de venta a una cuenta.
 *
 * Mismo patrón que 2026_11_09_000001: la lista es una copia congelada (una
 * migración no debe depender de una constante que puede cambiar), es
 * idempotente y aditiva, y usa los mismos roles de administración.
 */
return new class extends Migration
{
    /** Identificador del módulo en la tabla `modulos`. */
    private const MODULE_IDENTIFIER = 'M023';

    /** Descripción con la que se crea el módulo si falta. */
    private const MODULE_DESCRIPTION = 'Tesorería';

    /**
     * Roles de administración que reciben los permisos.
     *
     * @var array<int, string>
     */
    private const ADMIN_ROLES = ['admin', 'Administrador'];

    /**
     * Permisos que exigen Modules/Treasury/Routes/web.php y el menú lateral.
     *
     * @var array<int, string>
     */
    private const PERMISSIONS = [
        'treasury_efectivo',
        'treasury_cobros_vincular',
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
        // Aditiva a propósito: quitarlos podría dejar sin acceso a un rol que
        // los recibió a mano.
    }
};
