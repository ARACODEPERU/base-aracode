<?php

use App\Models\Modulo;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Da acceso al módulo Compras y al Registro de Compras (SIRE) al rol que
 * registra las compras.
 *
 * La migración 2026_11_19_000004 habilitó el menú para admin/Administrador,
 * pero el área contable quedaba fuera y el bloque de Compras seguía oculto
 * para quien digita los comprobantes de compra.
 *
 * Aditiva e idempotente: producción solo ejecuta `php artisan migrate`.
 */
return new class extends Migration
{
    /** Rol de panel que registra las compras. */
    private const ROLE = 'Contabilidad';

    private const MODULE_IDENTIFIER = 'M001';

    /**
     * Permisos que necesita para ver el módulo, sus pantallas y el SIRE.
     *
     * @var array<int, string>
     */
    private const PERMISSIONS = [
        'purc_dashboard',
        'purc_documentos_listado',
        'purc_documentos_nuevo',
        'purc_documentos_editar',
        'purc_documentos_eliminar',
        'purc_reporte',
        'sire_rce_compras',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles')) {
            return;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::query()
            ->where('name', self::ROLE)
            ->where('guard_name', 'web')
            ->first();

        // Sin el rol no hay nada que conceder; no se crea para no inventar
        // usuarios ni roles que la instalación no tenga.
        if ($role === null) {
            return;
        }

        $modulo = Schema::hasTable('modulos')
            ? Modulo::query()->where('identifier', self::MODULE_IDENTIFIER)->first()
            : null;

        foreach (self::PERMISSIONS as $name) {
            $permission = Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);

            if (! $role->hasPermissionTo($permission)) {
                $role->givePermissionTo($permission);
            }

            // `sire_rce_compras` ya está agrupado en el módulo del SIRE por su
            // propia migración; solo los permisos de Compras pertenecen a M001.
            if ($modulo !== null && str_starts_with($name, 'purc_')) {
                DB::table('model_has_permissions')->insertOrIgnore([
                    'permission_id' => $permission->id,
                    'model_type' => Modulo::class,
                    'model_id' => $modulo->identifier,
                ]);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Aditiva a propósito: quitar los permisos dejaría sin acceso al área
        // contable que ya registra compras.
    }
};
