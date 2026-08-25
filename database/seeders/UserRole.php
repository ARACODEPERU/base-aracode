<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserRole extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $role = Role::firstOrCreate(['name' => 'admin']);

        $permissions = [];

        array_push($permissions, Permission::firstOrCreate(['name' => 'dashboard']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'configuracion']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'empresa']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'modulos']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'usuarios']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'usuarios_nuevo']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'usuarios_editar']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'usuarios_eliminar']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'usuarios_ver']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'roles']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'permisos']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'parametros']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'parametros_nuevo']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'parametros_editar']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'parametros_eliminar']));




        foreach ($permissions as $permission) {
            $role->givePermissionTo($permission->name);
        }

        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('12345678'),
            'email_verified_at' => Carbon::now(),
            'local_id' => 1,
            'company_id' => 1
        ]);

        $user->assignRole('admin');
    }
}
