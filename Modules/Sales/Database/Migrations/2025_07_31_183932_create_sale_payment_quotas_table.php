<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sale_payment_quotas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_method_id') // El tipo de pago (Efectivo, Tarjeta, etc.)
                  ->constrained('payment_methods') // Asume que esta tabla ya existe
                  ->onDelete('restrict'); // No permitir borrar un tipo de pago si está en uso
            $table->foreignId('sale_document_quota_id')
                  ->constrained('sale_document_quotas')
                  ->onDelete('cascade'); // Si se borra una cuota, se borran sus asociaciones

            $table->string('reference')->nullable()->comment('Número de operación, referencia bancaria, etc.');
            $table->date('payment_date')->comment('Fecha en que se recibió el pago');
            $table->decimal('amount_applied', 12, 2)->comment('Monto del pago aplicado a esta cuota específica');
            $table->string('description')->nullable()->comment('Notas adicionales sobre el pago');
            $table->boolean('estado')->default(true)->comment('cuando es un pago normal falso cuando es de relleno porque se pago la totalidad de la deuda');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_payment_quotas');
    }
};
