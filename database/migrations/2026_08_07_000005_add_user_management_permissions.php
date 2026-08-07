<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        // Permisos de gestión de usuarios del núcleo (Configuraciones → Usuarios).
        $permissions = [
            'usuarios',            // listar usuarios / acceso al módulo
            'usuarios_nuevo',      // crear usuario
            'usuarios_editar',     // editar usuario
            'usuarios_eliminar',   // eliminar usuario
            'usuarios_ver',        // ver detalle de usuario
        ];

        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);

            // Solo Administrador y admin gestionan usuarios del sistema.
            foreach (['Administrador', 'admin'] as $roleName) {
                $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
                if ($role && ! $role->hasPermissionTo($permission)) {
                    $role->givePermissionTo($permission);
                }
            }
        }
    }

    public function down(): void
    {
        Permission::whereIn('name', [
            'usuarios',
            'usuarios_nuevo',
            'usuarios_editar',
            'usuarios_eliminar',
            'usuarios_ver',
        ])->delete();
    }
};
