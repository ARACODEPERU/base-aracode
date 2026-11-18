<?php

use App\Models\Modulo;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Fase 2 del SIRE: datos que el RCE necesita en los comprobantes de compra
 * (tipo de cambio y comprobante modificado) y el permiso del registro de
 * compras. El estado de anulación ya existe en `status` ('A'), que es el que
 * graba la pantalla de compras.
 *
 * Aditiva e idempotente: producción solo ejecuta `php artisan migrate`.
 */
return new class extends Migration
{
    private const MODULE_IDENTIFIER = 'M003';

    private const PERMISSION = 'sire_rce_compras';

    /**
     * @var array<int, string>
     */
    private const ADMIN_ROLES = ['admin', 'Administrador'];

    public function up(): void
    {
        if (Schema::hasTable('purc_documents')) {
            Schema::table('purc_documents', function (Blueprint $table) {
                if (! Schema::hasColumn('purc_documents', 'exchange_rate')) {
                    $table->decimal('exchange_rate', 12, 4)->nullable()->after('currency_type_id');
                }

                if (! Schema::hasColumn('purc_documents', 'document_id')) {
                    $table->unsignedBigInteger('document_id')->nullable()->after('exchange_rate');
                }
            });
        }

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
        // Aditiva a propósito.
    }
};
