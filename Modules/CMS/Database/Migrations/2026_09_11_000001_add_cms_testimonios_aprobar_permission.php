<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Permiso para aprobar o rechazar los testimonios enviados por los alumnos.
     */
    public function up(): void
    {
        Permission::firstOrCreate(['name' => 'cms_testimonios_aprobar']);

        foreach (['admin', 'Administrador'] as $roleName) {
            $role = Role::where('name', $roleName)->first();

            if ($role && !$role->hasPermissionTo('cms_testimonios_aprobar')) {
                $role->givePermissionTo('cms_testimonios_aprobar');
            }
        }
    }

    public function down(): void
    {
        foreach (['admin', 'Administrador'] as $roleName) {
            $role = Role::where('name', $roleName)->first();

            if ($role && $role->hasPermissionTo('cms_testimonios_aprobar')) {
                $role->revokePermissionTo('cms_testimonios_aprobar');
            }
        }

        Permission::where('name', 'cms_testimonios_aprobar')->delete();
    }
};
