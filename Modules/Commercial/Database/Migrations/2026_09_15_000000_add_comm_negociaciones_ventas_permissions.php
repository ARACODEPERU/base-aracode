<?php

use App\Models\Modulo;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Permisos de negociaciones del modulo Comercial para el rol Ventas.
     *
     * Ventas puede ver el listado completo, crear, editar y eliminar, pero el detalle,
     * la edicion y el borrado solo aplican a las negociaciones que el mismo creo: esa
     * regla de propiedad se valida en CommercialNegotiationController.
     *
     * El permiso "verificar" (aprobar, rechazar, reactivar y ejecutar el proceso de
     * aprobacion) queda reservado para los roles administradores.
     */
    public function up(): void
    {
        $permissions = [
            'comm_negociaciones_listado',
            'comm_negociaciones_nuevo',
            'comm_negociaciones_editar',
            'comm_negociaciones_eliminar',
        ];

        $ventas = Role::where('name', 'Ventas')->where('guard_name', 'web')->first();

        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);

            if ($ventas && ! $ventas->hasPermissionTo($permission)) {
                $ventas->givePermissionTo($permission);
            }

            // Enlazar el permiso al modulo Comercial para agruparlo en el editor de roles.
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

        // Aprobar/rechazar/reactivar y el proceso completo: solo administradores.
        $verificar = Permission::where('name', 'comm_negociaciones_verificar')
            ->where('guard_name', 'web')
            ->first();

        if ($ventas && $verificar && $ventas->hasPermissionTo($verificar)) {
            $ventas->revokePermissionTo($verificar);
        }
    }

    public function down(): void
    {
        $ventas = Role::where('name', 'Ventas')->where('guard_name', 'web')->first();

        if (! $ventas) {
            return;
        }

        foreach ([
            'comm_negociaciones_listado',
            'comm_negociaciones_nuevo',
            'comm_negociaciones_editar',
            'comm_negociaciones_eliminar',
        ] as $permissionName) {
            $permission = Permission::where('name', $permissionName)->where('guard_name', 'web')->first();

            if ($permission && $ventas->hasPermissionTo($permission)) {
                $ventas->revokePermissionTo($permission);
            }
        }
    }
};
