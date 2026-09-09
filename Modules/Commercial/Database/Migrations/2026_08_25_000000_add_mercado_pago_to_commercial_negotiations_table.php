<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commercial_negotiations', function (Blueprint $table) {
            $table->string('mercado_payment_id')->nullable()->after('payment_link')->comment('ID del pago en Mercado Pago');
            $table->string('mercado_payment_status')->nullable()->after('mercado_payment_id')->comment('Estado del pago (approved, rejected, in_process, pending)');
            $table->json('mercado_payment_data')->nullable()->after('mercado_payment_status')->comment('Respuesta completa de Mercado Pago');
        });
    }

    public function down(): void
    {
        Schema::table('commercial_negotiations', function (Blueprint $table) {
            $table->dropColumn(['mercado_payment_data', 'mercado_payment_status', 'mercado_payment_id']);
        });
    }
};
