<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Permisos del canal SMSGate.
     *
     * - aca_smsgate_configuracion: configurar y usar el canal (admin y
     *   Administrador). Es el permiso que se usa para el panel de configuracion.
     * - aca_smsgate_guia: ver la guia paso a paso de conexion. Por defecto solo
     *   el rol admin lo tiene.
     *
     * Al inicio solo los roles administradores; no se registra en
     * model_has_permissions del modulo M007 para que ningun otro rol lo herede
     * (el seeder del modulo los enlaza a su modulo).
     */
    public function up(): void
    {
        Permission::firstOrCreate(['name' => 'aca_smsgate_configuracion']);
        Permission::firstOrCreate(['name' => 'aca_smsgate_guia']);

        $this->grant('aca_smsgate_configuracion', ['admin', 'Administrador']);
        $this->grant('aca_smsgate_guia', ['admin']);
    }

    public function down(): void
    {
        $this->revoke('aca_smsgate_configuracion', ['admin', 'Administrador']);
        $this->revoke('aca_smsgate_guia', ['admin']);

        Permission::whereIn('name', ['aca_smsgate_configuracion', 'aca_smsgate_guia'])->delete();

        // El seeder del modulo vuelve a crear los permisos al re-sembrar; el
        // cache de spatie debe refrescarse para que la revocacion surta efecto.
        Artisan::call('permission:cache-reset');
    }

    /**
     * @param  array<int, string> $roleNames
     */
    private function grant(string $permissionName, array $roleNames): void
    {
        foreach ($roleNames as $roleName) {
            $role = Role::where('name', $roleName)->first();

            if ($role && ! $role->hasPermissionTo($permissionName)) {
                $role->givePermissionTo($permissionName);
            }
        }
    }

    /**
     * @param  array<int, string> $roleNames
     */
    private function revoke(string $permissionName, array $roleNames): void
    {
        foreach ($roleNames as $roleName) {
            $role = Role::where('name', $roleName)->first();

            if ($role && $role->hasPermissionTo($permissionName)) {
                $role->revokePermissionTo($permissionName);
            }
        }
    }
};
