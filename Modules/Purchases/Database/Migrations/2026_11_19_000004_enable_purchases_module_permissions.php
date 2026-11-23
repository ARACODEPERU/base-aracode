<?php

use App\Models\Modulo;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Habilita el módulo Compras en el menú.
 *
 * El menú filtra por `purc_dashboard`, `purc_documentos_listado` y los permisos
 * de alta/edición, pero en esta instalación solo existía `proveedores`: el
 * bloque de Compras quedaba oculto aunque el módulo y sus rutas funcionaban.
 *
 * Aditiva e idempotente: producción solo ejecuta `php artisan migrate`.
 */
return new class extends Migration
{
    private const MODULE_IDENTIFIER = 'M001';

    private const MODULE_DESCRIPTION = 'Compras';

    /** @var array<int, string> */
    private const ADMIN_ROLES = ['admin', 'Administrador'];

    /** Permisos que usa el menú y las pantallas del módulo. */
    private const PERMISSIONS = [
        'purc_dashboard',
        'purc_documentos_listado',
        'purc_documentos_nuevo',
        'purc_documentos_editar',
        'purc_documentos_eliminar',
        'purc_reporte',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $modulo = Schema::hasTable('modulos')
            ? Modulo::firstOrCreate(
                ['identifier' => self::MODULE_IDENTIFIER],
                ['description' => self::MODULE_DESCRIPTION]
            )
            : null;

        $roles = Schema::hasTable('roles')
            ? Role::query()
                ->whereIn('name', self::ADMIN_ROLES)
                ->where('guard_name', 'web')
                ->get()
            : collect();

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

            if ($modulo !== null) {
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
        // Aditiva a propósito: quitar los permisos ocultaría el módulo y
        // dejaría sin acceso a los usuarios que ya lo usan.
    }
};
