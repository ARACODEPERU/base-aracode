<?php

namespace Modules\Blog\Database\Seeders;

use App\Models\Modulo;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SeedPermissionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $role = Role::find(1);

        $modulo = Modulo::create(['identifier' => 'M006', 'description' => 'Blog']);

        $permissions = [];

        array_push($permissions, Permission::create(['name' => 'blog_dashboard']));
        array_push($permissions, Permission::create(['name' => 'blog_categorias']));
        array_push($permissions, Permission::create(['name' => 'blog_categorias_nuevo']));
        array_push($permissions, Permission::create(['name' => 'blog_categorias_editar']));
        array_push($permissions, Permission::create(['name' => 'blog_categorias_eliminar']));
        array_push($permissions, Permission::create(['name' => 'blog_articulos']));
        array_push($permissions, Permission::create(['name' => 'blog_articulos_nuevo']));
        array_push($permissions, Permission::create(['name' => 'blog_articulos_editar']));
        array_push($permissions, Permission::create(['name' => 'blog_articulos_eliminar']));

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
