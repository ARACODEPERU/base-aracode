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
        // Permisos de negociaciones del módulo Comercial.
        $permissions = [
            'comm_negociaciones_listado',
            'comm_negociaciones_nuevo',
            'comm_negociaciones_editar',
            'comm_negociaciones_eliminar',
            'comm_negociaciones_verificar',
        ];

        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);

            // Otorgar al rol administrador.
            foreach (['Administrador', 'admin'] as $roleName) {
                $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
                if ($role && ! $role->hasPermissionTo($permission)) {
                    $role->givePermissionTo($permission);
                }
            }

            // Enlazar el permiso al módulo Comercial para agruparlo en el editor de roles.
            $modulo = Modulo::where('identifier', 'M021')->first();
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
            'comm_negociaciones_listado',
            'comm_negociaciones_nuevo',
            'comm_negociaciones_editar',
            'comm_negociaciones_eliminar',
            'comm_negociaciones_verificar',
        ])->delete();
    }
};
