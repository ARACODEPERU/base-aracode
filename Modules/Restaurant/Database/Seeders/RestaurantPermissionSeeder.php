<?php

namespace Modules\Restaurant\Database\Seeders;

use App\Models\Modulo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RestaurantPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $role = Role::find(1);

        $modulo = Modulo::create(['identifier' => 'M012', 'description' => 'Restaurante']);

        $permissions = [];

        array_push($permissions, Permission::create(['name' => 'res_dashboard']));
        // res_categorias y res_reporte_venta reservados para fase posterior
        array_push($permissions, Permission::create(['name' => 'res_categorias']));
        array_push($permissions, Permission::create(['name' => 'res_comandas']));
        array_push($permissions, Permission::create(['name' => 'res_comandas_agregar_categoria']));
        array_push($permissions, Permission::create(['name' => 'res_comandas_nuevo']));
        array_push($permissions, Permission::create(['name' => 'res_comandas_editar']));
        array_push($permissions, Permission::create(['name' => 'res_comandas_eliminar']));
        array_push($permissions, Permission::create(['name' => 'res_menu']));
        array_push($permissions, Permission::create(['name' => 'res_menu_verimprimir']));
        array_push($permissions, Permission::create(['name' => 'res_menu_agregar_comandas']));
        array_push($permissions, Permission::create(['name' => 'res_menu_nuevo']));
        array_push($permissions, Permission::create(['name' => 'res_menu_editar']));
        array_push($permissions, Permission::create(['name' => 'res_menu_eliminar']));
        array_push($permissions, Permission::create(['name' => 'res_venta']));
        array_push($permissions, Permission::create(['name' => 'res_venta_nuevo']));
        array_push($permissions, Permission::create(['name' => 'res_venta_editar']));
        array_push($permissions, Permission::create(['name' => 'res_venta_eliminar']));
        array_push($permissions, Permission::create(['name' => 'res_reporte_venta']));
        // Insumos, recetas y lista de compras
        array_push($permissions, Permission::create(['name' => 'res_insumos']));
        array_push($permissions, Permission::create(['name' => 'res_insumos_nuevo']));
        array_push($permissions, Permission::create(['name' => 'res_insumos_editar']));
        array_push($permissions, Permission::create(['name' => 'res_insumos_compra']));
        array_push($permissions, Permission::create(['name' => 'res_recetas']));
        array_push($permissions, Permission::create(['name' => 'res_recetas_editar']));
        array_push($permissions, Permission::create(['name' => 'res_lista_compras']));

        foreach ($permissions as $permission) {
            $role->givePermissionTo($permission->name);
            DB::table('model_has_permissions')->insert([
                'permission_id' => $permission->id,
                'model_type' => Modulo::class,
                'model_id' => $modulo->identifier
            ]);
        }
    }
}
