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
 * Operación diaria de Tesorería:
 *
 * - Arqueo de caja: cuenta física por denominación, diferencia contra el saldo
 *   calculado y cierre de la sesión de caja chica.
 * - Cartera de cheques y letras: documentos por cobrar y por pagar con su
 *   vencimiento, estado y trazabilidad (no se borran).
 * - Presupuesto por centro de costo: monto mensual por centro y categoría para
 *   comparar contra el gasto real.
 * - Lote de pago masivo: cada orden exportada conserva el lote con el que se
 *   envió al banco.
 *
 * Es idempotente: se puede ejecutar sobre una base que ya tenga los cambios.
 */
return new class extends Migration
{
    private const MODULE_IDENTIFIER = 'M023';

    private const MODULE_DESCRIPTION = 'Tesorería';

    /** @var array<int, string> */
    private const ADMIN_ROLES = ['admin', 'Administrador'];

    /** @var array<string, string> permiso nuevo => permiso existente que lo habilita */
    private const PERMISSIONS = [
        'treasury_efectivo_arqueo' => 'treasury_efectivo',
        'treasury_documentos' => 'treasury_movimientos',
        'treasury_documentos_gestionar' => 'treasury_movimientos_nuevo',
        'treasury_pagos_masivos' => 'treasury_pagos_ejecutar',
        'treasury_presupuesto' => 'treasury_reportes',
    ];

    public function up(): void
    {
        $this->createCashCounts();
        $this->createDocuments();
        $this->createBudgets();
        $this->addBulkColumns();
        $this->seedPermissions();
    }

    public function down(): void
    {
        // Migración aditiva: no se borra el historial de arqueos, documentos ni
        // presupuestos. Solo se retiran las columnas del lote de pago.
        if (Schema::hasTable('treasury_payment_orders')) {
            Schema::table('treasury_payment_orders', function (Blueprint $table) {
                if (Schema::hasColumn('treasury_payment_orders', 'bulk_batch')) {
                    $table->dropColumn('bulk_batch');
                }

                if (Schema::hasColumn('treasury_payment_orders', 'bulk_exported_at')) {
                    $table->dropColumn('bulk_exported_at');
                }
            });
        }
    }

    /**
     * Arqueo de una sesión de caja chica.
     */
    private function createCashCounts(): void
    {
        if (Schema::hasTable('treasury_cash_counts')) {
            return;
        }

        Schema::create('treasury_cash_counts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('petty_cash_id')->constrained('petty_cashes')->restrictOnDelete();
            $table->date('cutoff_date');
            $table->decimal('expected_amount', 14, 2)->comment('Saldo calculado de la sesión');
            $table->decimal('counted_amount', 14, 2)->comment('Dinero contado físicamente');
            $table->decimal('difference', 14, 2)->comment('Contado - esperado (faltante o sobrante)');
            $table->json('denominations')->nullable()->comment('Billetes y monedas contados');
            $table->text('notes')->nullable()->comment('Explicación obligatoria cuando hay diferencia');
            $table->boolean('closed_session')->default(false)->comment('El arqueo cerró la sesión de caja');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['petty_cash_id', 'cutoff_date'], 'treasury_cash_counts_session_index');
        });
    }

    /**
     * Cartera de cheques y letras (por cobrar y por pagar).
     */
    private function createDocuments(): void
    {
        if (Schema::hasTable('treasury_documents')) {
            return;
        }

        Schema::create('treasury_documents', function (Blueprint $table) {
            $table->id();
            $table->string('type', 12)->comment('cheque | letra');
            $table->string('direction', 12)->comment('receivable (cobrar) | payable (pagar)');
            $table->string('number', 40)->comment('Número del documento');
            $table->string('bank_name', 80)->nullable();
            $table->string('issuer', 160)->nullable()->comment('Cliente o proveedor que emite el documento');
            $table->foreignId('supplier_id')->nullable()->constrained('treasury_suppliers')->nullOnDelete();
            $table->decimal('amount', 14, 2);
            $table->string('currency', 3)->default('PEN');
            $table->date('issue_date');
            $table->date('due_date');
            $table->string('status', 20)->default('portfolio')
                ->comment('portfolio | deposited | collected | paid | protested | cancelled');
            $table->foreignId('treasury_account_id')->nullable()->constrained('treasury_accounts')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['direction', 'status', 'due_date'], 'treasury_documents_due_index');
        });
    }

    /**
     * Presupuesto mensual por centro de costo y, opcionalmente, por categoría.
     */
    private function createBudgets(): void
    {
        if (Schema::hasTable('treasury_budgets')) {
            return;
        }

        Schema::create('treasury_budgets', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('period_year');
            $table->unsignedTinyInteger('period_month');
            $table->foreignId('cost_center_id')->constrained('treasury_cost_centers')->restrictOnDelete();
            $table->foreignId('treasury_category_id')->nullable()->constrained('treasury_categories')->nullOnDelete();
            $table->string('currency', 3)->default('PEN');
            $table->decimal('amount', 14, 2);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['period_year', 'period_month'], 'treasury_budgets_period_index');
            $table->unique(
                ['period_year', 'period_month', 'cost_center_id', 'treasury_category_id', 'currency'],
                'treasury_budgets_unique'
            );
        });
    }

    /**
     * Lote con el que la orden se envió al banco (trazabilidad del pago masivo).
     */
    private function addBulkColumns(): void
    {
        if (! Schema::hasTable('treasury_payment_orders')) {
            return;
        }

        Schema::table('treasury_payment_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('treasury_payment_orders', 'bulk_batch')) {
                $table->string('bulk_batch', 40)->nullable()->after('status')
                    ->comment('Lote de pago masivo con el que se exportó al banco');
            }

            if (! Schema::hasColumn('treasury_payment_orders', 'bulk_exported_at')) {
                $table->timestamp('bulk_exported_at')->nullable()->after('bulk_batch');
            }
        });
    }

    /**
     * Permisos de las funciones nuevas, heredados del permiso base de cada una.
     */
    private function seedPermissions(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles')) {
            return;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $module = Schema::hasTable('modulos')
            ? Modulo::firstOrCreate(
                ['identifier' => self::MODULE_IDENTIFIER],
                ['description' => self::MODULE_DESCRIPTION]
            )
            : null;

        foreach (self::PERMISSIONS as $name => $parent) {
            $permission = Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);

            $roles = Role::query()
                ->where('guard_name', 'web')
                ->where(function ($query) use ($parent) {
                    $query->whereIn('name', self::ADMIN_ROLES)
                        ->orWhereHas('permissions', fn ($q) => $q->where('name', $parent));
                })
                ->get();

            foreach ($roles as $role) {
                if (! $role->hasPermissionTo($permission)) {
                    $role->givePermissionTo($permission);
                }
            }

            if ($module) {
                DB::table('model_has_permissions')->insertOrIgnore([
                    'permission_id' => $permission->id,
                    'model_type' => Modulo::class,
                    'model_id' => $module->identifier,
                ]);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
