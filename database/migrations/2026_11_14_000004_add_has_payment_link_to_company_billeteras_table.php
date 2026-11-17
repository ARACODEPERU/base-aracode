<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_billeteras', function (Blueprint $table) {
            // Indica si esta billetera acepta pago mediante enlace; el enlace concreto se registra en cada negociacion.
            $table->boolean('has_payment_link')->default(false)->after('qr_image')->comment('La billetera permite pago mediante enlace');
        });
    }

    public function down(): void
    {
        Schema::table('company_billeteras', function (Blueprint $table) {
            $table->dropColumn('has_payment_link');
        });
    }
};
