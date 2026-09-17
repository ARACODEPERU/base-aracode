<?php

namespace Modules\CMS\Database\Seeders;

use App\Models\Modulo;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SeedPermissionsUsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Idempotente: puede ejecutarse varias veces sin duplicar roles,
     * permisos, modulos, pivotes ni usuarios.
     *
     * @return void
     */
    public function run()
    {
        $roles = collect(['webAdmin', 'admin', 'Administrador'])
            ->map(fn ($name) => Role::firstOrCreate(['name' => $name]));

        $modulo = Modulo::firstOrCreate(
            ['identifier' => 'M005'],
            ['description' => 'CMS']
        );

        $permissions = [];

        array_push($permissions, Permission::firstOrCreate(['name' => 'cms_dashboard']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'cms_pagina']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'cms_pagina_nuevo']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'cms_pagina_editar']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'cms_pagina_eliminar']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'cms_pagina_seccion']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'cms_pagina_seccion_items']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'cms_pagina_seccion_items_delete']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'cms_seccion']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'cms_seccion_editar']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'cms_seccion_items']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'cms_seccion_grupos']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'cms_seccion_nuevo']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'cms_seccion_eliminar']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'cms_editor']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'cms_items']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'cms_testimonios']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'cms_testimonios_nuevo']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'cms_testimonios_editar']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'cms_testimonios_eliminar']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'cms_publicidad']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'cms_landings']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'cms_landing_curso_gratis']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'cms_suscriptores_exportar_excel']));

        // Permisos agregados despues por migraciones del modulo (testimonios, contacto y suscriptores).
        array_push($permissions, Permission::firstOrCreate(['name' => 'cms_testimonios_aprobar']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'cms_mensajes_contacto']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'cms_mensajes_contacto_ver']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'cms_mensajes_contacto_editar']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'cms_blog_suscriptores']));
        array_push($permissions, Permission::firstOrCreate(['name' => 'cms_blog_suscriptores_exportar']));

        foreach ($permissions as $permission) {
            foreach ($roles as $role) {
                $role->givePermissionTo($permission->name);
            }

            DB::table('model_has_permissions')->insertOrIgnore([
                'permission_id' => $permission->id,
                'model_type' => Modulo::class,
                'model_id' => $modulo->identifier
            ]);
        }

        $user = User::firstOrCreate(
            ['email' => 'webAdmin@gmail.com'],
            [
                'name' => 'webAdmin',
                'password' => Hash::make('12345678'),
                'email_verified_at' => Carbon::now(),
                'local_id' => 1,
                'company_id' => 1
            ]
        );

        $user->assignRole('webAdmin');
    }
}
