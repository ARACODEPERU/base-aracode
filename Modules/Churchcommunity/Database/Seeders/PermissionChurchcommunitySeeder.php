<?php

namespace Modules\Churchcommunity\Database\Seeders;

use App\Models\Modulo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionChurchcommunitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       $role = Role::find(1);

        $modulo = Modulo::create(['identifier' => 'M016', 'description' => 'Comunidad de la iglesia']);

        $permissions = [];

        array_push($permissions, Permission::create(['name' => 'cigle_dashboard']));
        array_push($permissions, Permission::create(['name' => 'cigle_miembros']));
        array_push($permissions, Permission::create(['name' => 'cigle_tipo_miembro']));
        array_push($permissions, Permission::create(['name' => 'cigle_tipo_miembro_nuevo']));
        array_push($permissions, Permission::create(['name' => 'cigle_tipo_miembro_eliminar']));
        array_push($permissions, Permission::create(['name' => 'cigle_creyente_listado']));
        array_push($permissions, Permission::create(['name' => 'cigle_creyente_nuevo']));
        array_push($permissions, Permission::create(['name' => 'cigle_creyente_editar']));
        array_push($permissions, Permission::create(['name' => 'cigle_creyente_eliminar']));

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
