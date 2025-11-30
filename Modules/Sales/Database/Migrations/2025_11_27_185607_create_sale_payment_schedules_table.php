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
        Schema::create('sale_payment_schedules', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('sale_id');          // ID de la venta
            $table->integer('installment_number');          // Número de cuota (1,2,3...)
            $table->date('payment_date');                   // Fecha programada de pago
            $table->integer('amount_to_pay');               // Monto de la cuota (entero, sin decimales)
            $table->integer('amount_paid')->default(0);     // Lo que ha pagado
            $table->integer('remaining_amount');            // Saldo pendiente de esa cuota

            $table->unsignedBigInteger('document_id')->nullable(); // Factura/boleta generada, si existe

            $table->boolean('is_paid')->default(false);     // Cuota cancelada sí/no

            $table->timestamps();

            // FK
            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('cascade');
            $table->foreign('document_id')->references('id')->on('sale_documents')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_payment_schedules');
    }
};
