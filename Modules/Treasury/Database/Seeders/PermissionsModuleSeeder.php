<?php

namespace Modules\Treasury\Database\Seeders;

use App\Models\Modulo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionsModuleSeeder extends Seeder
{
    /** Identificador del módulo en la tabla `modulos`. */
    public const MODULE_IDENTIFIER = 'M023';

    /** Descripción con la que se crea el módulo. */
    public const MODULE_DESCRIPTION = 'Tesorería';

    /** Rol que recibe todos los permisos del módulo. */
    public const ADMIN_ROLE = 'admin';

    /**
     * Todos los permisos del módulo Treasury.
     *
     * Es la lista única del módulo: debe coincidir con los permisos que exige
     * Modules/Treasury/Routes/web.php y con los que declara el menú lateral.
     *
     * @var array<int, string>
     */
    public const PERMISSIONS = [
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
        'treasury_efectivo',
        'treasury_cobros_vincular',
    ];

    /**
     * Run the database seeds.
     *
     * Idempotente: se puede reejecutar sobre una base ya sembrada sin duplicar
     * permisos, sin duplicar el enlace al módulo y sin volver a conceder lo que
     * el rol admin ya tiene.
     */
    public function run(): void
    {
        $modulo = Modulo::firstOrCreate(
            ['identifier' => self::MODULE_IDENTIFIER],
            ['description' => self::MODULE_DESCRIPTION]
        );

        $admin = Role::query()
            ->where('name', self::ADMIN_ROLE)
            ->where('guard_name', 'web')
            ->first();

        foreach (self::PERMISSIONS as $name) {
            $permission = Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);

            // Solo el rol admin, y solo si todavía no lo tiene.
            if ($admin && ! $admin->hasPermissionTo($permission)) {
                $admin->givePermissionTo($permission);
            }

            // Enlace permiso -> módulo: el editor de roles agrupa por módulo.
            // insertOrIgnore respeta la clave primaria y evita duplicados.
            DB::table('model_has_permissions')->insertOrIgnore([
                'permission_id' => $permission->id,
                'model_type' => Modulo::class,
                'model_id' => $modulo->identifier,
            ]);
        }
    }
}
