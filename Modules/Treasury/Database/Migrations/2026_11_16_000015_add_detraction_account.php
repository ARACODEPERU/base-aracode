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
 * Detracciones del IGV.
 *
 * - Nuevo tipo de cuenta «detraccion»: guarda el dinero retenido por los
 *   clientes. Solo se usa para pagar IGV o para liberar el saldo a una cuenta
 *   de la empresa (ambos movimientos críticos).
 * - Facturas: porcentaje e importe detraído y la marca de auto-detracción.
 * - Categorías y permiso de la pantalla de detracciones.
 *
 * Es idempotente: se puede correr sobre una base que ya tenga los cambios.
 */
return new class extends Migration
{
    private const MODULE_IDENTIFIER = 'M023';

    private const MODULE_DESCRIPTION = 'Tesorería';

    /** @var array<int, string> */
    private const ADMIN_ROLES = ['admin', 'Administrador'];

    /** @var array<int, array{name: string, color: string, applies_to: string}> */
    private const CATEGORIES = [
        ['name' => 'Detracción', 'color' => '#0ea5e9', 'applies_to' => 'income'],
        ['name' => 'Ajuste de detracción', 'color' => '#64748b', 'applies_to' => 'expense'],
    ];

    public function up(): void
    {
        $this->extendAccountType();
        $this->addDetractionColumnsToDocuments();
        $this->seedCategories();
        $this->seedPermission();
        $this->ensureDetractionAccount();
    }

    public function down(): void
    {
        // Migración aditiva: no se eliminan cuentas, columnas ni permisos.
    }

    /**
     * Agrega «detraccion» al enum de tipos de cuenta (MySQL y SQLite).
     */
    private function extendAccountType(): void
    {
        if (! Schema::hasTable('treasury_accounts')) {
            return;
        }

        Schema::table('treasury_accounts', function (Blueprint $table) {
            $table->enum('type', ['bank', 'wallet', 'cash', 'detraccion'])
                ->comment('bank = banco, wallet = billetera, cash = efectivo, detraccion = cuenta de detracciones')
                ->change();
        });
    }

    private function addDetractionColumnsToDocuments(): void
    {
        if (! Schema::hasTable('sale_documents')) {
            return;
        }

        Schema::table('sale_documents', function (Blueprint $table) {
            if (! Schema::hasColumn('sale_documents', 'detraccion')) {
                $table->boolean('detraccion')->default(false)->comment('La factura está sujeta a detracción del IGV');
            }
            if (! Schema::hasColumn('sale_documents', 'detraccion_auto')) {
                $table->boolean('detraccion_auto')->default(false)->comment('La empresa deposita la detracción (auto-detracción)');
            }
            if (! Schema::hasColumn('sale_documents', 'detraccion_percent')) {
                $table->decimal('detraccion_percent', 5, 2)->nullable()->comment('Porcentaje detraído (12, 10 o 4 según el servicio)');
            }
            if (! Schema::hasColumn('sale_documents', 'detraccion_amount')) {
                $table->decimal('detraccion_amount', 12, 2)->default(0)->comment('Importe detraído de la factura');
            }
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
                'applies_to' => $category['applies_to'],
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
            'name' => 'treasury_detraccion',
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

    /**
     * La cuenta de detracciones es única y en soles.
     */
    private function ensureDetractionAccount(): void
    {
        if (! Schema::hasTable('treasury_accounts')) {
            return;
        }

        $exists = DB::table('treasury_accounts')->where('type', 'detraccion')->exists();

        if ($exists) {
            return;
        }

        DB::table('treasury_accounts')->insert([
            'type' => 'detraccion',
            'label' => 'Detracción',
            'currency_type_id' => 'PEN',
            'opening_balance' => 0,
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
