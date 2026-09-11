<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Libro de bancos: una fila por cada movimiento de dinero de una cuenta.
     * El saldo NUNCA se guarda aquí; se calcula como
     * opening_balance de la cuenta + SUM(ingresos) - SUM(egresos).
     */
    public function up(): void
    {
        Schema::create('treasury_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('treasury_account_id')->constrained('treasury_accounts')->restrictOnDelete();
            $table->date('transaction_date')->comment('Fecha valor del movimiento');
            $table->enum('type', ['income', 'expense'])->comment('Dirección del movimiento');
            $table->foreignId('treasury_category_id')->nullable()->constrained('treasury_categories')->nullOnDelete();

            // Origen del movimiento (venta, cuota, contrato, etc.)
            $table->string('origin_type')->nullable();
            $table->unsignedBigInteger('origin_id')->nullable();
            $table->index(['origin_type', 'origin_id'], 'treasury_transactions_origin_index');

            $table->decimal('amount', 12, 2)->comment('Monto siempre positivo; la dirección la da type');
            $table->string('reference')->nullable()->comment('N° de operación o referencia bancaria');
            $table->string('description')->nullable();
            $table->string('attachment_path')->nullable()->comment('Comprobante adjunto (storage)');

            // Con qué método de pago se movió el dinero (útil en egresos manuales)
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->nullOnDelete();

            $table->enum('source', ['auto', 'manual', 'backfill'])->default('manual')->comment('auto: enganche de ventas, manual: alta manual, backfill: importación histórica');
            $table->timestamp('reconciled_at')->nullable()->comment('Fecha de conciliación contra el banco');
            $table->timestamp('voided_at')->nullable()->comment('Anulación (nunca se borra, para trazabilidad)');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['treasury_account_id', 'transaction_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treasury_transactions');
    }
};
