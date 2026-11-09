<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        // Permiso de acceso a la configuración del asistente Aracodito.
        $permission = Permission::firstOrCreate([
            'name' => 'conf_asistente',
            'guard_name' => 'web',
        ]);

        // Acceso inicial para los roles de administración; el resto de roles
        // puede recibirlo después desde Configuraciones → Roles.
        foreach (['Administrador', 'admin'] as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();

            if ($role && ! $role->hasPermissionTo($permission)) {
                $role->givePermissionTo($permission);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::where('name', 'conf_asistente')
            ->where('guard_name', 'web')
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
