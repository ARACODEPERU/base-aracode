<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /** Permiso para escanear el QR del carné en la puerta. */
    public const SCAN_PERMISSION = 'aca_school_porteria_escaner';

    /** Permiso para consultar el reporte de portería del día. */
    public const REPORT_PERMISSION = 'aca_school_porteria_reporte';

    /** Rol dedicado del equipo de la puerta: solo escanea. */
    public const GATE_ROLE = 'Portero';

    /**
     * Permisos de la portería del colegio (asistencia institucional).
     *
     * - aca_school_porteria_escaner: registrar entrada y salida escaneando el
     *   carné. Lo tienen administración y el rol Portero, que es el usuario con
     *   el que entra la tablet fija de la puerta.
     * - aca_school_porteria_reporte: consultar y exportar el reporte del día.
     *
     * Se crean con firstOrCreate (idempotente), se enlazan al módulo M007 y se
     * conceden a los roles que corresponden. En una instalación desde cero los
     * seeders del módulo terminan el trabajo.
     */
    public function up(): void
    {
        $scan = Permission::firstOrCreate(['name' => self::SCAN_PERMISSION]);
        $this->linkToModule($scan);
        $this->grant(self::SCAN_PERMISSION, ['admin', 'Administrador']);

        $report = Permission::firstOrCreate(['name' => self::REPORT_PERMISSION]);
        $this->linkToModule($report);
        $this->grant(self::REPORT_PERMISSION, ['admin', 'Administrador']);

        // El rol de la puerta existe y solo puede escanear.
        $gateRole = Role::firstOrCreate(['name' => self::GATE_ROLE]);

        if (! $gateRole->hasPermissionTo(self::SCAN_PERMISSION)) {
            $gateRole->givePermissionTo(self::SCAN_PERMISSION);
        }

        Artisan::call('permission:cache-reset');
    }

    public function down(): void
    {
        $this->revoke(self::SCAN_PERMISSION, ['admin', 'Administrador', self::GATE_ROLE]);
        $this->revoke(self::REPORT_PERMISSION, ['admin', 'Administrador']);

        Permission::whereIn('name', [self::SCAN_PERMISSION, self::REPORT_PERMISSION])->delete();

        $gateRole = Role::where('name', self::GATE_ROLE)->first();
        if ($gateRole && $gateRole->permissions()->count() === 0) {
            $gateRole->delete();
        }

        Artisan::call('permission:cache-reset');
    }

    /**
     * Enlaza el permiso al modulo Academico (M007) para que el editor de roles
     * lo agrupe en su modulo en lugar de dejarlo en "Otros permisos".
     */
    private function linkToModule(Permission $permission): void
    {
        $moduleExists = DB::table('modulos')->where('identifier', 'M007')->exists();

        if (! $moduleExists) {
            return;
        }

        $alreadyLinked = DB::table('model_has_permissions')
            ->where('permission_id', $permission->id)
            ->where('model_type', 'App\\Models\\Modulo')
            ->where('model_id', 'M007')
            ->exists();

        if ($alreadyLinked) {
            return;
        }

        DB::table('model_has_permissions')->insertOrIgnore([
            'permission_id' => $permission->id,
            'model_type' => 'App\\Models\\Modulo',
            'model_id' => 'M007',
        ]);
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
