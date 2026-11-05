<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla puente entre los módulos que originan dinero (hoy Ventas) y el
     * libro de tesorería.
     *
     * No reemplaza a treasury_transactions: es el registro de la operación
     * financiera tal como la reporta el módulo de origen, con su clave de
     * idempotencia y su estado de procesamiento. treasury_transactions sigue
     * siendo el único libro de bancos.
     *
     * Sin claves foráneas hacia las tablas de origen a propósito: la capa debe
     * poder recibir movimientos de Compras, Caja Chica, Académico o Contratos
     * sin acoplarse a sus esquemas.
     */
    public function up(): void
    {
        Schema::create('treasury_source_movements', function (Blueprint $table) {
            $table->id();

            // Origen de la operación financiera
            $table->string('module', 40)->comment('Módulo que reporta el movimiento (sales, purchases, petty_cash...)');
            $table->string('source_type', 60)->comment('Entidad de origen (sale, sale_payment_quota, sale_document...)');
            $table->unsignedBigInteger('source_id');
            $table->unsignedInteger('source_line')->nullable()->comment('Línea dentro del origen (p. ej. índice del pago en el JSON)');
            $table->string('operation', 60)->comment('Operación financiera reportada (sale_income, quota_payment, refund...)');

            // Dinero
            $table->enum('type', ['income', 'expense'])->comment('Dirección del movimiento');
            $table->decimal('amount', 12, 2)->comment('Monto en la moneda del documento');
            $table->string('currency', 3)->default('PEN');
            $table->decimal('exchange_rate', 12, 4)->nullable()->comment('Tipo de cambio usado cuando la moneda no es PEN');
            $table->decimal('amount_base', 12, 2)->comment('Monto equivalente en la moneda base (PEN)');
            $table->date('movement_date')->comment('Fecha valor del dinero, no la de registro');
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->nullOnDelete();
            $table->foreignId('treasury_account_id')->nullable()->constrained('treasury_accounts')->nullOnDelete()
                ->comment('Cuenta resuelta; null mientras está pendiente o si no aplica');

            // Resultado del procesamiento
            $table->enum('status', ['pending', 'processing', 'processed', 'failed', 'ignored', 'reversed'])
                ->default('pending');
            $table->foreignId('treasury_transaction_id')->nullable()->constrained('treasury_transactions')->nullOnDelete()
                ->comment('Movimiento del libro creado por esta operación');
            $table->foreignId('reversal_of_id')->nullable()->constrained('treasury_source_movements')->nullOnDelete()
                ->comment('Movimiento original cuando esta fila es su reversión');
            $table->timestamp('processed_at')->nullable();
            $table->text('error_message')->nullable();

            // Idempotencia garantizada por la base de datos
            $table->string('idempotency_key', 191)->unique();

            $table->string('reference')->nullable();
            $table->string('description')->nullable();
            $table->enum('source', ['auto', 'manual', 'backfill'])->default('auto')
                ->comment('auto: enganche en línea, manual: alta administrativa, backfill: histórico');
            $table->json('meta')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['module', 'source_type', 'source_id'], 'treasury_source_movements_origin_index');
            $table->index(['status', 'movement_date'], 'treasury_source_movements_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treasury_source_movements');
    }
};
