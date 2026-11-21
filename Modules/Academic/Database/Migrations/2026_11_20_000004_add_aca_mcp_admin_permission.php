<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Permiso de la auditoría del servidor MCP: solo los administradores ven qué
 * llamó cada agente. No se asigna a ningún otro rol.
 */
return new class extends Migration
{
    public function up(): void
    {
        Permission::firstOrCreate(['name' => 'aca_mcp_admin', 'guard_name' => 'web']);

        foreach (['admin', 'Administrador'] as $nombreRol) {
            $rol = Role::where('name', $nombreRol)->first();

            if ($rol && ! $rol->hasPermissionTo('aca_mcp_admin')) {
                $rol->givePermissionTo('aca_mcp_admin');
            }
        }
    }

    public function down(): void
    {
        foreach (['admin', 'Administrador'] as $nombreRol) {
            $rol = Role::where('name', $nombreRol)->first();

            if ($rol && $rol->hasPermissionTo('aca_mcp_admin')) {
                $rol->revokePermissionTo('aca_mcp_admin');
            }
        }

        Permission::where('name', 'aca_mcp_admin')->delete();

        Artisan::call('permission:cache-reset');
    }
};
