<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::firstOrCreate(['name' => 'aca_estudiante_enviar_correo_acceso']);

        $ventasRole = Role::where('name', 'Ventas')->first();
        if ($ventasRole && !$ventasRole->hasPermissionTo('aca_estudiante_enviar_correo_acceso')) {
            $ventasRole->givePermissionTo('aca_estudiante_enviar_correo_acceso');
        }

        $adminRole = Role::find(1);
        if ($adminRole && !$adminRole->hasPermissionTo('aca_estudiante_enviar_correo_acceso')) {
            $adminRole->givePermissionTo('aca_estudiante_enviar_correo_acceso');
        }
    }

    public function down(): void
    {
        $ventasRole = Role::where('name', 'Ventas')->first();
        if ($ventasRole) {
            $ventasRole->revokePermissionTo('aca_estudiante_enviar_correo_acceso');
        }

        $adminRole = Role::find(1);
        if ($adminRole) {
            $adminRole->revokePermissionTo('aca_estudiante_enviar_correo_acceso');
        }

        Permission::where('name', 'aca_estudiante_enviar_correo_acceso')->delete();
    }
};
