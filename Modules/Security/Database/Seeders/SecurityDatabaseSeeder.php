<?php

namespace Modules\Security\Database\Seeders;

use App\Models\Modulo;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SecurityDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
       // Model::unguard();
        // historial_actividades
        $role = Role::find(1);

        $modulo = Modulo::create(['identifier' => 'M019', 'description' => 'Configuración y seguridad']);

        $permissions = [];

        array_push($permissions, Permission::create(['name' => 'conf_dashboard']));
        array_push($permissions, Permission::create(['name' => 'conf_historial_actividades']));


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
