<?php

namespace Modules\Security\Database\Seeders;

use App\Models\Modulo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SecurityDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Todos los permisos se crean con firstOrCreate para poder re-ejecutar el
     * seed en cualquier instalacion (produccion incluida) sin errores de
     * duplicados, y se asignan siempre al rol admin (id 1).
     */
    public function run(): void
    {
        $role = Role::find(1);

        $modulo = Modulo::firstOrCreate(
            ['identifier' => 'M019'],
            ['description' => 'Configuración y seguridad']
        );

        // Permisos que usa el modulo: el menu de Configuraciones (Menu.js) y el
        // dashboard/historial. Si falta alguno, el item no se muestra al usuario.
        $permissions = [];

        $permissionNames = [
            'conf_dashboard',
            'conf_historial_actividades',
            'configuracion',
            'empresa',
            'modulos',
            'roles',
            'permisos',
            'usuarios',
            'parametros',
        ];

        foreach ($permissionNames as $name) {
            $permissions[] = Permission::firstOrCreate(['name' => $name]);
        }

        foreach ($permissions as $permission) {
            if ($role) {
                $role->givePermissionTo($permission->name);
            }

            // Vinculo permiso <-> modulo M019 (evita insert duplicado al re-ejecutar).
            $exists = DB::table('model_has_permissions')
                ->where('permission_id', $permission->id)
                ->where('model_type', Modulo::class)
                ->where('model_id', $modulo->identifier)
                ->exists();

            if (! $exists) {
                DB::table('model_has_permissions')->insert([
                    'permission_id' => $permission->id,
                    'model_type' => Modulo::class,
                    'model_id' => $modulo->identifier,
                ]);
            }
        }
    }
}
