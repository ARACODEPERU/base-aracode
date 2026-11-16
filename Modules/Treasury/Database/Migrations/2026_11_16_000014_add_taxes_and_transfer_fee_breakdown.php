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
 * Desglose de gastos de transferencia (comisión e IGV) y soporte del IGV
 * trasladado de boletas y facturas.
 *
 * - treasury_transfers guarda el importe calculado de cada comisión y su IGV
 *   para poder mostrar el gasto sin recalcular con las tasas del momento.
 * - Se agregan las categorías de sistema «ITF» e «IGV por pagar» y el permiso
 *   que habilita la pantalla de impuestos.
 *
 * Idempotente: se puede correr sobre una base que ya tenga las columnas.
 */
return new class extends Migration
{
    private const MODULE_IDENTIFIER = 'M023';

    private const MODULE_DESCRIPTION = 'Tesorería';

    /** @var array<int, string> */
    private const ADMIN_ROLES = ['admin', 'Administrador'];

    /** @var array<int, array{name: string, color: string}> */
    private const CATEGORIES = [
        ['name' => 'ITF', 'color' => '#0284c7'],
        ['name' => 'IGV por pagar', 'color' => '#dc2626'],
    ];

    public function up(): void
    {
        if (Schema::hasTable('treasury_transfers')) {
            Schema::table('treasury_transfers', function (Blueprint $table) {
                if (! Schema::hasColumn('treasury_transfers', 'from_fee_amount')) {
                    $table->decimal('from_fee_amount', 12, 2)->default(0)->comment('Comisión del origen sin IGV');
                }
                if (! Schema::hasColumn('treasury_transfers', 'from_fee_igv')) {
                    $table->decimal('from_fee_igv', 12, 2)->default(0)->comment('IGV de la comisión del origen');
                }
                if (! Schema::hasColumn('treasury_transfers', 'to_fee_amount')) {
                    $table->decimal('to_fee_amount', 12, 2)->default(0)->comment('Comisión del destino sin IGV');
                }
                if (! Schema::hasColumn('treasury_transfers', 'to_fee_igv')) {
                    $table->decimal('to_fee_igv', 12, 2)->default(0)->comment('IGV de la comisión del destino');
                }
            });
        }

        $this->seedCategories();
        $this->seedPermission();
    }

    public function down(): void
    {
        // Las categorías del sistema y los permisos no se eliminan.
        if (! Schema::hasTable('treasury_transfers')) {
            return;
        }

        Schema::table('treasury_transfers', function (Blueprint $table) {
            $table->dropColumn(['from_fee_amount', 'from_fee_igv', 'to_fee_amount', 'to_fee_igv']);
        });
    }

    private function seedCategories(): void
    {
        if (! Schema::hasTable('treasury_categories')) {
            return;
        }

        $now = now();

        foreach (self::CATEGORIES as $category) {
            if (DB::table('treasury_categories')->where('name', $category['name'])->exists()) {
                continue;
            }

            DB::table('treasury_categories')->insert([
                'name' => $category['name'],
                'applies_to' => 'expense',
                'is_system' => true,
                'color' => $category['color'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function seedPermission(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('modulos')) {
            return;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $modulo = Modulo::firstOrCreate(
            ['identifier' => self::MODULE_IDENTIFIER],
            ['description' => self::MODULE_DESCRIPTION]
        );

        $permission = Permission::firstOrCreate([
            'name' => 'treasury_impuestos',
            'guard_name' => 'web',
        ]);

        Role::query()
            ->whereIn('name', self::ADMIN_ROLES)
            ->where('guard_name', 'web')
            ->get()
            ->each(function (Role $role) use ($permission) {
                if (! $role->hasPermissionTo($permission)) {
                    $role->givePermissionTo($permission);
                }
            });

        DB::table('model_has_permissions')->insertOrIgnore([
            'permission_id' => $permission->id,
            'model_type' => Modulo::class,
            'model_id' => $modulo->identifier,
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
