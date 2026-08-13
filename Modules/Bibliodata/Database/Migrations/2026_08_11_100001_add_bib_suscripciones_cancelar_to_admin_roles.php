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
        // Permiso de cancelación de suscripciones de la biblioteca.
        $permission = Permission::firstOrCreate([
            'name' => 'bib_suscripciones_cancelar',
            'guard_name' => 'web',
        ]);

        // Solo Administrador y admin gestionan suscripciones de la biblioteca.
        foreach (['Administrador', 'admin'] as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            if ($role && ! $role->hasPermissionTo($permission)) {
                $role->givePermissionTo($permission);
            }
        }

        // Enlazar el permiso al módulo de Biblio Data para agruparlo en el editor de roles.
        $modulo = Modulo::where('identifier', 'M017')->first();
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

    public function down(): void
    {
        Permission::where('name', 'bib_suscripciones_cancelar')->delete();
    }
};
