<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tesorería v2 — cierre de periodos, conciliación con acta, auditoría y
 * moneda explícita por movimiento.
 *
 * Es idempotente: cada tabla y columna se crea solo si falta, y el backfill
 * se puede repetir sin cambiar datos ya migrados. Regla del proyecto: todo
 * registro histórico sin moneda se interpreta en soles (PEN), salvo que la
 * cuenta del movimiento declare otra moneda.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->createPeriods();
        $this->createPeriodBalances();
        $this->createCostCenters();
        $this->createReconciliations();
        $this->createReconciliationItems();
        $this->createAuditLogs();
        $this->addColumnsToTransactions();
        $this->addColumnsToTransfers();
        $this->backfillCurrency();
    }

    private function createPeriods(): void
    {
        if (Schema::hasTable('treasury_periods')) {
            return;
        }

        Schema::create('treasury_periods', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('period_year');
            $table->unsignedTinyInteger('period_month');
            $table->string('status', 10)->default('open')->comment('open | closed');
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reopened_at')->nullable();
            $table->foreignId('reopened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['period_year', 'period_month'], 'treasury_periods_year_month_unique');
        });
    }

    private function createPeriodBalances(): void
    {
        if (Schema::hasTable('treasury_period_balances')) {
            return;
        }

        Schema::create('treasury_period_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('period_id')->constrained('treasury_periods')->cascadeOnDelete();
            $table->foreignId('treasury_account_id')->constrained('treasury_accounts')->restrictOnDelete();
            $table->string('currency', 3)->default('PEN');
            $table->decimal('opening_balance', 14, 2)->default(0);
            $table->decimal('incomes', 14, 2)->default(0);
            $table->decimal('expenses', 14, 2)->default(0);
            $table->decimal('closing_balance', 14, 2)->default(0);
            $table->unsignedInteger('tx_count')->default(0);
            $table->timestamps();

            $table->unique(['period_id', 'treasury_account_id'], 'treasury_period_balances_unique');
        });
    }

    private function createCostCenters(): void
    {
        if (Schema::hasTable('treasury_cost_centers')) {
            return;
        }

        Schema::create('treasury_cost_centers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 120);
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    private function createReconciliations(): void
    {
        if (Schema::hasTable('treasury_reconciliations')) {
            return;
        }

        Schema::create('treasury_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('treasury_account_id')->constrained('treasury_accounts')->restrictOnDelete();
            $table->date('cutoff_date')->comment('Día hasta el que se concilia');
            $table->decimal('statement_balance', 14, 2)->comment('Saldo según el extracto del banco');
            $table->decimal('system_balance', 14, 2)->comment('Saldo del libro a la fecha de corte');
            $table->decimal('difference', 14, 2)->comment('Sistema − extracto');
            $table->string('currency', 3)->default('PEN');
            $table->string('status', 10)->default('closed')->comment('draft | closed');
            $table->string('snapshot_hash', 64)->nullable()->comment('Huella de las líneas conciliadas');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->unique(['treasury_account_id', 'cutoff_date'], 'treasury_reconciliations_account_date_unique');
        });
    }

    private function createReconciliationItems(): void
    {
        if (Schema::hasTable('treasury_reconciliation_items')) {
            return;
        }

        Schema::create('treasury_reconciliation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('treasury_account_id')->constrained('treasury_accounts')->restrictOnDelete();
            $table->foreignId('treasury_reconciliation_id')->nullable()
                ->constrained('treasury_reconciliations')->nullOnDelete();
            $table->date('cutoff_date');
            $table->string('description', 200);
            $table->decimal('amount', 14, 2);
            $table->string('type', 10)->default('expense')->comment('income | expense');
            $table->string('status', 10)->default('pending')->comment('pending | resolved');
            $table->foreignId('resolved_transaction_id')->nullable()
                ->constrained('treasury_transactions')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['treasury_account_id', 'cutoff_date'], 'treasury_reconciliation_items_account_date_index');
        });
    }

    private function createAuditLogs(): void
    {
        if (Schema::hasTable('treasury_audit_logs')) {
            return;
        }

        Schema::create('treasury_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('entity', 40);
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('action', 40);
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('meta')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['entity', 'entity_id'], 'treasury_audit_logs_entity_index');
            $table->index('created_at', 'treasury_audit_logs_created_index');
        });
    }

    private function addColumnsToTransactions(): void
    {
        if (! Schema::hasTable('treasury_transactions')) {
            return;
        }

        Schema::table('treasury_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('treasury_transactions', 'currency')) {
                $table->string('currency', 3)->default('PEN')->after('amount')
                    ->comment('Moneda del movimiento; sin dato = PEN');
            }

            if (! Schema::hasColumn('treasury_transactions', 'exchange_rate')) {
                $table->decimal('exchange_rate', 12, 4)->nullable()->after('currency');
            }

            if (! Schema::hasColumn('treasury_transactions', 'amount_base')) {
                $table->decimal('amount_base', 14, 2)->nullable()->after('exchange_rate')
                    ->comment('Equivalente en soles congelado al registrar');
            }

            if (! Schema::hasColumn('treasury_transactions', 'treasury_reconciliation_id')) {
                $table->foreignId('treasury_reconciliation_id')->nullable()->after('transfer_id')
                    ->constrained('treasury_reconciliations')->nullOnDelete();
            }

            if (! Schema::hasColumn('treasury_transactions', 'cost_center_id')) {
                $table->foreignId('cost_center_id')->nullable()->after('treasury_category_id')
                    ->constrained('treasury_cost_centers')->nullOnDelete();
            }
        });
    }

    private function addColumnsToTransfers(): void
    {
        if (! Schema::hasTable('treasury_transfers')) {
            return;
        }

        Schema::table('treasury_transfers', function (Blueprint $table) {
            if (! Schema::hasColumn('treasury_transfers', 'from_currency')) {
                $table->string('from_currency', 3)->default('PEN')->after('amount');
            }

            if (! Schema::hasColumn('treasury_transfers', 'to_currency')) {
                $table->string('to_currency', 3)->default('PEN')->after('from_currency');
            }

            if (! Schema::hasColumn('treasury_transfers', 'exchange_rate')) {
                $table->decimal('exchange_rate', 12, 4)->nullable()->after('to_currency');
            }

            if (! Schema::hasColumn('treasury_transfers', 'amount_destination')) {
                $table->decimal('amount_destination', 14, 2)->nullable()->after('exchange_rate')
                    ->comment('Monto acreditado en la cuenta destino, en su moneda');
            }

            if (! Schema::hasColumn('treasury_transfers', 'voided_at')) {
                $table->timestamp('voided_at')->nullable()->after('user_id');
            }

            if (! Schema::hasColumn('treasury_transfers', 'voided_by')) {
                $table->foreignId('voided_by')->nullable()->after('voided_at')
                    ->constrained('users')->nullOnDelete();
            }
        });
    }

    /**
     * Completa moneda y equivalente en soles de los movimientos históricos.
     * Se apoya en la moneda de la cuenta; si la cuenta no la declara, es PEN.
     */
    private function backfillCurrency(): void
    {
        if (! Schema::hasTable('treasury_transactions')
            || ! Schema::hasColumn('treasury_transactions', 'currency')) {
            return;
        }

        $accountCurrency = DB::table('treasury_accounts')->pluck('currency_type_id', 'id');

        DB::table('treasury_transactions')
            ->select('id', 'treasury_account_id', 'amount', 'amount_base')
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($accountCurrency) {
                foreach ($rows as $row) {
                    $currency = strtoupper((string) ($accountCurrency[$row->treasury_account_id] ?? 'PEN')) ?: 'PEN';

                    DB::table('treasury_transactions')->where('id', $row->id)->update([
                        'currency' => $currency,
                        'amount_base' => $currency === 'PEN' ? $row->amount : $row->amount_base,
                    ]);
                }
            });

        DB::table('treasury_transfers')
            ->select('id', 'from_account_id', 'to_account_id', 'net_received', 'amount_destination')
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($accountCurrency) {
                foreach ($rows as $row) {
                    DB::table('treasury_transfers')->where('id', $row->id)->update([
                        'from_currency' => strtoupper((string) ($accountCurrency[$row->from_account_id] ?? 'PEN')) ?: 'PEN',
                        'to_currency' => strtoupper((string) ($accountCurrency[$row->to_account_id] ?? 'PEN')) ?: 'PEN',
                        'amount_destination' => $row->amount_destination ?? $row->net_received,
                    ]);
                }
            });
    }

    public function down(): void
    {
        // Migración aditiva: no se eliminan tablas ni columnas para no perder
        // actas de conciliación ni el rastro de auditoría.
    }
};
