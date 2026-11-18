<?php

use App\Models\Modulo;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permiso del registro de ventas del SIRE (RVIE): previsualizar y descargar el
 * archivo plano de un período desde Facturación Electrónica.
 *
 * Mismo patrón que las migraciones de permisos de Tesorería: lista congelada,
 * idempotente, aditiva y con los roles de administración.
 */
return new class extends Migration
{
    /** Identificador del módulo en la tabla `modulos`. */
    private const MODULE_IDENTIFIER = 'M003';

    private const PERMISSION = 'sire_rvie_ventas';

    /**
     * Roles de administración que reciben el permiso.
     *
     * @var array<int, string>
     */
    private const ADMIN_ROLES = ['admin', 'Administrador'];

    public function up(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permission::firstOrCreate([
            'name' => self::PERMISSION,
            'guard_name' => 'web',
        ]);

        $roles = Role::query()
            ->whereIn('name', self::ADMIN_ROLES)
            ->where('guard_name', 'web')
            ->get();

        foreach ($roles as $role) {
            if (! $role->hasPermissionTo($permission)) {
                $role->givePermissionTo($permission);
            }
        }

        // Se enlaza al módulo de Facturación Electrónica para que aparezca en
        // la administración de permisos; si el módulo no existe, el permiso
        // igual queda creado y asignado.
        if (Schema::hasTable('modulos')) {
            $modulo = Modulo::query()->where('identifier', self::MODULE_IDENTIFIER)->first();

            if ($modulo) {
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
        // Aditiva a propósito: quitarlo podría dejar sin acceso a un rol que lo
        // recibió a mano.
    }
};
