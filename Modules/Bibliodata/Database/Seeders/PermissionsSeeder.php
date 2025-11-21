<?php

namespace Modules\Bibliodata\Database\Seeders;

use App\Models\Modulo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       $role = Role::find(1);

        $modulo = Modulo::create(['identifier' => 'M017', 'description' => 'Biblio Data']);

        $permissions = [];

        array_push($permissions, Permission::create(['name' => 'bib_dashboard']));
        array_push($permissions, Permission::create(['name' => 'bib_categorias_nuevo']));
        array_push($permissions, Permission::create(['name' => 'bib_categorias_editar']));
        array_push($permissions, Permission::create(['name' => 'bib_categorias_listar']));
        array_push($permissions, Permission::create(['name' => 'bib_categorias_eliminar']));
        array_push($permissions, Permission::create(['name' => 'bib_libros_listado']));
        array_push($permissions, Permission::create(['name' => 'bib_libros_nuevo']));
        array_push($permissions, Permission::create(['name' => 'bib_libros_editar']));
        array_push($permissions, Permission::create(['name' => 'bib_libros_eliminar']));
        array_push($permissions, Permission::create(['name' => 'bib_libros_secciones']));
        array_push($permissions, Permission::create(['name' => 'bib_libros_paginas']));

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
