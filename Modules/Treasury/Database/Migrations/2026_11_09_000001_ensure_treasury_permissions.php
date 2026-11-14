<?php

use App\Models\Modulo;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Asegura los permisos del módulo Tesorería en cualquier instalación.
 *
 * El módulo se instaló con un seeder y no con una migración, así que las bases
 * que solo corren `php artisan migrate` (producción) nunca recibieron los
 * permisos: el menú aparecía, pero crear o editar cuentas devolvía 403.
 *
 * La lista es una copia congelada de PermissionsModuleSeeder::PERMISSIONS: una
 * migración no debe depender de una constante que puede cambiar después.
 *
 * Es idempotente y no revoca nada: si el permiso ya existe, ya está enlazado al
 * módulo o ya lo tiene el rol, no hace nada.
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
     * Son los mismos que usa PermissionsReconcileSeeder: la instalación puede
     * tener `admin` (id 1) o `Administrador` como rol del dueño.
     *
     * @var array<int, string>
     */
    private const ADMIN_ROLES = ['admin', 'Administrador'];

    /**
     * Permisos que exige Modules/Treasury/Routes/web.php y el menú lateral.
     *
     * @var array<int, string>
     */
    private const PERMISSIONS = [
        'treasury_dashboard',
        'treasury_cuentas',
        'treasury_cuentas_nuevo',
        'treasury_cuentas_editar',
        'treasury_cuentas_eliminar',
        'treasury_movimientos',
        'treasury_movimientos_nuevo',
        'treasury_movimientos_editar',
        'treasury_movimientos_eliminar',
        'treasury_conciliacion',
        'treasury_categorias',
    ];

    public function up(): void
    {
        // En una instalación desde cero las tablas de Spatie ya existen (esta
        // migración de módulo corre después de las del framework), pero no vale
        // la pena fallar si el orden cambiara.
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

            // Enlace permiso -> módulo para que el editor de roles lo agrupe.
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
