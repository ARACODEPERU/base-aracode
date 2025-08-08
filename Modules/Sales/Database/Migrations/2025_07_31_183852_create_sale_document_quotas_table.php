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
        Schema::create('sale_document_quotas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_document_id')
                  ->constrained('sale_documents')
                  ->onDelete('cascade');

            $table->integer('quota_number')->comment('Número de la cuota (ej. 1, 2, 3)');
            $table->decimal('amount', 12, 2)->comment('Monto total esperado para esta cuota');
            $table->date('due_date')->comment('Fecha de vencimiento de la cuota');

            $table->decimal('balance', 12, 2)->default(0.00)->comment('Saldo pendiente de esta cuota'); // MUY IMPORTANTE
            $table->string('status')->default('Pendiente')->comment('Estado actual de la cuota'); // 'Pendiente', 'Pagada', 'Parcialmente Pagada', 'Vencida'

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_document_quotas');
    }
};
