<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            'cms_mensajes_contacto',
            'cms_mensajes_contacto_ver',
            'cms_mensajes_contacto_editar',
            'cms_blog_suscriptores',
            'cms_blog_suscriptores_exportar',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name]);
        }

        foreach (['admin', 'Administrador'] as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                foreach ($permissions as $name) {
                    if (!$role->hasPermissionTo($name)) {
                        $role->givePermissionTo($name);
                    }
                }
            }
        }
    }

    public function down(): void
    {
        $permissions = [
            'cms_mensajes_contacto',
            'cms_mensajes_contacto_ver',
            'cms_mensajes_contacto_editar',
            'cms_blog_suscriptores',
            'cms_blog_suscriptores_exportar',
        ];

        foreach (['admin', 'Administrador'] as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                foreach ($permissions as $name) {
                    if ($role->hasPermissionTo($name)) {
                        $role->revokePermissionTo($name);
                    }
                }
            }
        }

        Permission::whereIn('name', $permissions)->delete();
    }
};
