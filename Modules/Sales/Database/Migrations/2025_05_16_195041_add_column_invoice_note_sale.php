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
        Schema::table('sales', function (Blueprint $table) {
            $table->string('invoice_razon_social', 300)->nullable();
            $table->string('invoice_ruc', 15)->nullable();
            $table->string('invoice_direccion', 300)->nullable();
            $table->string('invoice_ubigeo', 20)->nullable();
            $table->string('invoice_type', 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('invoice_type');
            $table->dropColumn('invoice_ubigeo');
            $table->dropColumn('invoice_direccion');
            $table->dropColumn('invoice_ruc');
            $table->dropColumn('invoice_razon_social');
        });
    }
};
