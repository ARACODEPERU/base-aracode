<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commercial_negotiations', function (Blueprint $table) {
            // Cuenta bancaria recomendada por el asesor para la transferencia (se muestra destacada al cliente).
            $table->unsignedBigInteger('recommended_bank_account_id')->nullable()->after('payment_link')->comment('Cuenta bancaria recomendada para la transferencia');
            // Banco de origen declarado por el cliente al enviar su evidencia de transferencia.
            $table->string('transfer_origin_bank', 100)->nullable()->after('voucher_path')->comment('Banco desde el que el cliente hizo la transferencia');
        });
    }

    public function down(): void
    {
        Schema::table('commercial_negotiations', function (Blueprint $table) {
            $table->dropColumn(['recommended_bank_account_id', 'transfer_origin_bank']);
        });
    }
};
