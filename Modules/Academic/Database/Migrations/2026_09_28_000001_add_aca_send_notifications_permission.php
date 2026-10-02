<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Permiso para enviar notificaciones masivas a los alumnos (SMS via Vonage
     * o WhatsApp por Integrationhub).
     *
     * Al inicio solo los roles administradores (admin y Administrador) lo
     * tienen. No se registra en model_has_permissions del modulo M007 (patron
     * del PermissionTableSeeder) para que ningun otro rol lo herede.
     */
    public function up(): void
    {
        Permission::firstOrCreate(['name' => 'aca_send_notifications']);

        foreach (['admin', 'Administrador'] as $roleName) {
            $role = Role::where('name', $roleName)->first();

            if ($role && ! $role->hasPermissionTo('aca_send_notifications')) {
                $role->givePermissionTo('aca_send_notifications');
            }
        }
    }

    public function down(): void
    {
        foreach (['admin', 'Administrador'] as $roleName) {
            $role = Role::where('name', $roleName)->first();

            if ($role && $role->hasPermissionTo('aca_send_notifications')) {
                $role->revokePermissionTo('aca_send_notifications');
            }
        }

        Permission::where('name', 'aca_send_notifications')->delete();

        // El seeder del modulo vuelve a crear el permiso al re-sembrar; el cache
        // de permisos de spatie debe refrescarse para que la revocacion surta
        // efecto de inmediato.
        Artisan::call('permission:cache-reset');
    }
};
