<?php

use App\Models\Modulo;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permisos de Tesorería v2: previsión, pagos, periodos, auditoría, reportes
 * y cierre de conciliación.
 *
 * Idempotente y sin revocaciones, igual que la migración de permisos
 * original del módulo.
 */
return new class extends Migration
{
    private const MODULE_IDENTIFIER = 'M023';

    private const MODULE_DESCRIPTION = 'Tesorería';

    /** @var array<int, string> */
    private const ADMIN_ROLES = ['admin', 'Administrador'];

    /** @var array<int, string> */
    private const PERMISSIONS = [
        'treasury_prevision',
        'treasury_pagos',
        'treasury_pagos_nuevo',
        'treasury_pagos_aprobar',
        'treasury_pagos_ejecutar',
        'treasury_pagos_anular',
        'treasury_proveedores',
        'treasury_recurrentes',
        'treasury_periodos',
        'treasury_periodos_cerrar',
        'treasury_periodos_reabrir',
        'treasury_conciliacion_cerrar',
        'treasury_auditoria',
        'treasury_reportes',
        'treasury_reintentos',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('modulos')) {
            return;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $modulo = Modulo::firstOrCreate(
            ['identifier' => self::MODULE_IDENTIFIER],
            ['description' => self::MODULE_DESCRIPTION]
        );

        $roles = Role::query()
            ->whereIn('name', self::ADMIN_ROLES)
            ->where('guard_name', 'web')
            ->get();

        foreach (self::PERMISSIONS as $name) {
            $permission = Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);

            foreach ($roles as $role) {
                if (! $role->hasPermissionTo($permission)) {
                    $role->givePermissionTo($permission);
                }
            }

            DB::table('model_has_permissions')->insertOrIgnore([
                'permission_id' => $permission->id,
                'model_type' => Modulo::class,
                'model_id' => $modulo->identifier,
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // No se revocan permisos.
    }
};
