<?php

namespace Modules\Onlineshop\Database\Seeders;

use App\Models\Modulo;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class OnliPermissionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $admin = Role::find(1);

        $modulo = Modulo::create(['identifier' => 'M004', 'description' => 'Ventas en línea']);

        $permissions = [];

        array_push($permissions, Permission::create(['name' => 'onli_dashboard']));
        array_push($permissions, Permission::create(['name' => 'onli_items']));
        array_push($permissions, Permission::create(['name' => 'onli_items_nuevo']));
        array_push($permissions, Permission::create(['name' => 'onli_items_editar']));
        array_push($permissions, Permission::create(['name' => 'onli_items_eliminar']));
        array_push($permissions, Permission::create(['name' => 'onli_pedidos']));
        array_push($permissions, Permission::create(['name' => 'onli_pedidos_aprobar']));
        array_push($permissions, Permission::create(['name' => 'onli_pedidos_responder']));
        array_push($permissions, Permission::create(['name' => 'onli_pedidos_enviar_boletas']));

        foreach ($permissions as $permission) {
            $admin->givePermissionTo($permission->name);
            DB::table('model_has_permissions')->insert([
                'permission_id' => $permission->id,
                'model_type' => Modulo::class,
                'model_id' => $modulo->identifier
            ]);
        }
    }
}
