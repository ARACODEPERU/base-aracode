<?php

namespace Modules\Integrationhub\Database\Seeders;

use App\Models\Modulo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;


class IntegrationhubDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Role::find(1);

        $modulo = Modulo::updateOrCreate(
            ['identifier' => 'M020'],
            ['description' => 'Centro de integraciónes', 'icon' => 'faPuzzlePiece']
        );

        $permissions = [];

        array_push($permissions, Permission::create(['name' => 'integrationhub_dashboard']));
        array_push($permissions, Permission::create(['name' => 'integrationhub_listado']));
        array_push($permissions, Permission::create(['name' => 'integrationhub_crear']));
        array_push($permissions, Permission::create(['name' => 'integrationhub_editar']));
        array_push($permissions, Permission::create(['name' => 'integrationhub_eliminar']));
        array_push($permissions, Permission::create(['name' => 'integrationhub_ejecutar']));

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
