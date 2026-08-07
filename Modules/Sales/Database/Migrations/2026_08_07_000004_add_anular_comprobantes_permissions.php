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
        // Permisos de anulación/eliminación de comprobantes de pago.
        $permissions = [
            'sale_documento_fisico_eliminar', // Documento Físico: anular boleta/factura física
            'invo_documento_anular',          // Facturación Electrónica: anular documento electrónico
        ];

        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);

            // Solo Administrador y admin conservan la posibilidad de anular comprobantes.
            foreach (['Administrador', 'admin'] as $roleName) {
                $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
                if ($role && ! $role->hasPermissionTo($permission)) {
                    $role->givePermissionTo($permission);
                }
            }

            // Enlazar el permiso al módulo de Ventas para agruparlo en el editor de roles.
            $modulo = Modulo::where('identifier', 'M002')->first();
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
            'sale_documento_fisico_eliminar',
            'invo_documento_anular',
        ])->delete();
    }
};
