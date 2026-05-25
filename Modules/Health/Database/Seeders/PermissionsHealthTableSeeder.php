<?php

namespace Modules\Health\Database\Seeders;

use App\Models\Modulo;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionsHealthTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $role = Role::find(1);

        $modulo = Modulo::firstOrCreate(
            ['identifier' => 'M009'],
            ['description' => 'Salud']
        );


        $permissions = [];

        array_push($permissions, Permission::firstOrCreate(['name' => 'heal_dashboard']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'heal_pacientes_listado']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'heal_pacientes_nuevo']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'heal_pacientes_editar']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'heal_pacientes_eliminar']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'heal_codigo_cie10_listado']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'heal_codigo_cie10_nuevo']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'heal_codigo_cie10_editar']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'heal_codigo_cie10_eliminar']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'heal_doctores_listado']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'heal_doctores_nuevo']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'heal_doctores_editar']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'heal_doctores_eliminar']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'heal_atenciones_listado']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'heal_atenciones_nuevo']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'heal_atenciones_editar']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'heal_atenciones_eliminar']));
        ///////odontologico///////////
        array_push($permissions, Permission::firstOrCreate(['name' => 'heal_odontology']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'heal_citas_listado']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'heal_citas_nuevo']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'heal_citas_editar']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'heal_citas_eliminar']));

        foreach ($permissions as $permission) {
            if ($role && !$role->hasPermissionTo($permission->name)) {
                $role->givePermissionTo($permission->name);
            }

            DB::table('model_has_permissions')->updateOrInsert(
                [
                    'permission_id' => $permission->id,
                    'model_type' => Modulo::class,
                    'model_id' => $modulo->identifier
                ],
                []
            );
        }

        $doctorRole = Role::firstOrCreate(['name' => 'Doctor']);
        $doctorPermissionNames = [
            'heal_dashboard',
            'heal_pacientes_listado',
            'heal_atenciones_listado',
            'heal_atenciones_nuevo',
            'heal_atenciones_editar',
            'heal_citas_listado',
            'heal_citas_nuevo',
            'heal_citas_editar',
            'heal_odontology',
        ];

        foreach ($doctorPermissionNames as $permissionName) {
            $permission = Permission::firstOrCreate(['name' => $permissionName]);

            if (!$doctorRole->hasPermissionTo($permissionName)) {
                $doctorRole->givePermissionTo($permission);
            }
        }

        // $user = User::find(1);

        // $user->assignRole('webAdmin');
    }
}
