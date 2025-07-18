<?php

namespace Modules\Dental\Database\Seeders;

use App\Models\Modulo;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionsDentalTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $role = Role::find(1);

        $modulo = Modulo::create(['identifier' => 'M010', 'description' => 'Odontología']);


        $permissions = [];

        array_push($permissions, Permission::create(['name' => 'dental_dashboard']));
        array_push($permissions, Permission::create(['name' => 'dental_citas_listado']));
        array_push($permissions, Permission::create(['name' => 'dental_citas_nuevo']));
        array_push($permissions, Permission::create(['name' => 'dental_citas_editar']));
        array_push($permissions, Permission::create(['name' => 'dental_citas_eliminar']));
        array_push($permissions, Permission::create(['name' => 'dental_citas_acceso_atencion']));
        array_push($permissions, Permission::create(['name' => 'dental_atencion_listado']));
        array_push($permissions, Permission::create(['name' => 'dental_atencion_nuevo']));
        array_push($permissions, Permission::create(['name' => 'dental_atencion_editar']));
        array_push($permissions, Permission::create(['name' => 'dental_atencion_eliminar']));

        foreach ($permissions as $permission) {
            $role->givePermissionTo($permission->name);
            DB::table('model_has_permissions')->insert([
                'permission_id' => $permission->id,
                'model_type' => Modulo::class,
                'model_id' => $modulo->identifier
            ]);
        }

        // $user = User::find(1);

        // $user->assignRole('webAdmin');
    }
}
