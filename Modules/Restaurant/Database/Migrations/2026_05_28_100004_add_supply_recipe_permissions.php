<?php

use App\Models\Modulo;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $names = [
            'res_insumos',
            'res_insumos_nuevo',
            'res_insumos_editar',
            'res_insumos_compra',
            'res_recetas',
            'res_recetas_editar',
            'res_lista_compras',
        ];

        $modulo = Modulo::where('identifier', 'M012')->first();
        $role = Role::find(1);

        foreach ($names as $name) {
            $permission = Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);

            if ($role && ! $role->hasPermissionTo($name)) {
                $role->givePermissionTo($permission);
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

    public function down(): void
    {
        Permission::whereIn('name', [
            'res_insumos',
            'res_insumos_nuevo',
            'res_insumos_editar',
            'res_insumos_compra',
            'res_recetas',
            'res_recetas_editar',
            'res_lista_compras',
        ])->delete();
    }
};
