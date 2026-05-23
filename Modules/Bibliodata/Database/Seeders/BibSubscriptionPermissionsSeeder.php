<?php

namespace Modules\Bibliodata\Database\Seeders;

use App\Models\Modulo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class BibSubscriptionPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $names = [
            'bib_planes_listar',
            'bib_planes_nuevo',
            'bib_planes_editar',
            'bib_planes_eliminar',
            'bib_organizaciones_listar',
            'bib_organizaciones_nuevo',
            'bib_organizaciones_editar',
            'bib_organizaciones_eliminar',
            'bib_suscripciones_listar',
            'bib_suscripciones_nuevo',
            'bib_suscripciones_editar',
            'bib_suscripciones_cancelar',
        ];

        $role = Role::find(1);
        $modulo = Modulo::where('identifier', 'M017')->first();

        foreach ($names as $name) {
            $permission = Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);

            if ($role && ! $role->hasPermissionTo($name)) {
                $role->givePermissionTo($name);
            }

            if ($modulo) {
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
}
