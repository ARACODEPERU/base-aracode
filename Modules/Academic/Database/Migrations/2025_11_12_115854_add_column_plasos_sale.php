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
        Schema::table('aca_cap_registrations', function (Blueprint $table) {
            $table->boolean('payment_installments')->default(false)->comment('si esta en true la matricula se pagare en cuotas');
            $table->decimal('advancement', 12, 2)->default(0)->comment('para saber si el alumno a pago algun monto');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aca_cap_registrations', function (Blueprint $table) {
            $table->dropColumn('advancement');
            $table->dropColumn('payment_installments');
        });
    }
};
