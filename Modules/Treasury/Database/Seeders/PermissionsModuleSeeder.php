<?php

namespace Modules\Treasury\Database\Seeders;

use App\Models\Modulo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionsModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = Role::find(1);

        $modulo = Modulo::firstOrCreate(['identifier' => 'M023'], ['description' => 'Tesorería']);

        $permissions = [];

        array_push($permissions, Permission::firstOrCreate(['name' => 'treasury_dashboard']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'treasury_cuentas']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'treasury_cuentas_nuevo']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'treasury_cuentas_editar']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'treasury_cuentas_eliminar']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'treasury_movimientos']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'treasury_movimientos_nuevo']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'treasury_movimientos_editar']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'treasury_movimientos_eliminar']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'treasury_categorias']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'treasury_conciliacion']));

        foreach ($permissions as $permission) {
            if ($admin) {
                $admin->givePermissionTo($permission->name);
            }

            $exists = DB::table('model_has_permissions')
                ->where('permission_id', $permission->id)
                ->where('model_type', Modulo::class)
                ->where('model_id', $modulo->identifier)
                ->exists();

            if (! $exists) {
                DB::table('model_has_permissions')->insert([
                    'permission_id' => $permission->id,
                    'model_type' => Modulo::class,
                    'model_id' => $modulo->identifier
                ]);
            }
        }
    }
}
