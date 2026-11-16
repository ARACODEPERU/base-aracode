<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tesorería v2 — pagos, compras, proveedores y gastos recurrentes.
 *
 * Idempotente: solo crea lo que falta. `treasury_payment_orders` concentra
 * pagos, compras, pagos a proveedores, gastos imprevistos y gastos
 * recurrentes; el campo `type` distingue el caso y `recurring_id` enlaza la
 * orden generada por una programación.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->createSuppliers();
        $this->createPaymentOrders();
        $this->createRecurringExpenses();
        $this->createRecurringRuns();
        $this->createForecastAdjustments();
    }

    private function createSuppliers(): void
    {
        if (Schema::hasTable('treasury_suppliers')) {
            return;
        }

        Schema::create('treasury_suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('doc_type', 2)->default('6')->comment('6 RUC | 1 DNI | 0 sin documento');
            $table->string('doc_number', 20)->nullable();
            $table->string('name', 200);
            $table->string('trade_name', 200)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('address', 250)->nullable();
            $table->unsignedSmallInteger('payment_terms_days')->default(0);
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->unique(['doc_type', 'doc_number'], 'treasury_suppliers_document_unique');
        });
    }

    private function createPaymentOrders(): void
    {
        if (Schema::hasTable('treasury_payment_orders')) {
            return;
        }

        Schema::create('treasury_payment_orders', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->nullable()->unique()->comment('OP-AAAA-000001');
            $table->string('idempotency_key', 80)->nullable()->unique()
                ->comment('Evita duplicar el mismo formulario enviado dos veces');
            $table->string('type', 30)->default('purchase')
                ->comment('purchase | supplier_payment | unexpected_expense | recurring_expense | payroll | service | other');
            $table->foreignId('supplier_id')->nullable()->constrained('treasury_suppliers')->nullOnDelete();
            $table->foreignId('cost_center_id')->nullable()->constrained('treasury_cost_centers')->nullOnDelete();
            $table->string('description', 500);
            $table->foreignId('treasury_category_id')->nullable()->constrained('treasury_categories')->nullOnDelete();
            $table->string('currency', 3)->default('PEN');
            $table->decimal('amount', 14, 2);
            $table->date('issue_date');
            $table->date('due_date')->nullable();
            $table->date('scheduled_date')->nullable()->comment('Fecha en que se piensa pagar');
            $table->foreignId('treasury_account_id')->nullable()->constrained('treasury_accounts')->nullOnDelete();
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->nullOnDelete();
            $table->string('status', 20)->default('draft')
                ->comment('draft | pending_approval | approved | scheduled | paid | rejected | voided');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejected_reason')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('treasury_transaction_id')->nullable()
                ->constrained('treasury_transactions')->nullOnDelete();
            $table->unsignedBigInteger('recurring_id')->nullable()->index();
            $table->string('attachment_path')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'scheduled_date'], 'treasury_payment_orders_status_date_index');
            $table->index(['type', 'status'], 'treasury_payment_orders_type_status_index');
        });
    }

    private function createRecurringExpenses(): void
    {
        if (Schema::hasTable('treasury_recurring_expenses')) {
            return;
        }

        Schema::create('treasury_recurring_expenses', function (Blueprint $table) {
            $table->id();
            $table->string('concept', 200);
            $table->string('type', 30)->default('service')
                ->comment('payroll | service | supplies | rent | tax | other');
            $table->foreignId('supplier_id')->nullable()->constrained('treasury_suppliers')->nullOnDelete();
            $table->foreignId('treasury_category_id')->nullable()->constrained('treasury_categories')->nullOnDelete();
            $table->string('currency', 3)->default('PEN');
            $table->decimal('amount', 14, 2);
            $table->foreignId('treasury_account_id')->nullable()->constrained('treasury_accounts')->nullOnDelete();
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->nullOnDelete();
            $table->string('frequency', 20)->default('monthly')
                ->comment('weekly | biweekly | monthly | quarterly | annual');
            $table->unsignedTinyInteger('day_of_month')->nullable()->comment('1-31; en meses cortos usa el último día');
            $table->unsignedTinyInteger('day_of_week')->nullable()->comment('1 lunes … 7 domingo');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('next_run_date');
            $table->boolean('auto_pay')->default(false)->comment('true paga solo; false genera orden pendiente');
            $table->boolean('status')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'next_run_date'], 'treasury_recurring_status_next_index');
        });
    }

    private function createRecurringRuns(): void
    {
        if (Schema::hasTable('treasury_recurring_runs')) {
            return;
        }

        Schema::create('treasury_recurring_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('treasury_recurring_expense_id')->constrained('treasury_recurring_expenses')->cascadeOnDelete();
            $table->string('period', 7)->comment('AAAA-MM de la ocurrencia ejecutada');
            $table->foreignId('treasury_payment_order_id')->nullable()
                ->constrained('treasury_payment_orders')->nullOnDelete();
            $table->timestamp('executed_at');
            $table->text('error')->nullable();
            $table->timestamps();

            $table->unique(['treasury_recurring_expense_id', 'period'], 'treasury_recurring_runs_period_unique');
        });
    }

    private function createForecastAdjustments(): void
    {
        if (Schema::hasTable('treasury_forecast_adjustments')) {
            return;
        }

        Schema::create('treasury_forecast_adjustments', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('type', 10)->default('expense')->comment('income | expense');
            $table->string('currency', 3)->default('PEN');
            $table->decimal('amount', 14, 2);
            $table->string('concept', 200);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['date', 'currency'], 'treasury_forecast_adjustments_date_index');
        });
    }

    public function down(): void
    {
        // Aditiva: no se eliminan datos de pagos ni de recurrentes.
    }
};
