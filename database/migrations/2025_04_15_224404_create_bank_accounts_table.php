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
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bank_id');
            $table->string('description');
            $table->string('number');
            $table->string('cci')->nullable();
            $table->string('currency_type_id')->default('PEN');
            $table->boolean('status')->default(true);
            $table->boolean('invoice_show')->default(false)->comment('para que sea visualizado en las boletas y facturas');
            $table->timestamps();
            $table->foreign('currency_type_id')->references('id')
                ->on('sunat_currency_types')
                ->onDelete('cascade');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->boolean('show_accounts_invoice')->nullable()->comment('mostrar cuentas  bancarias en la factura');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('show_accounts_invoice');
        });
        Schema::dropIfExists('bank_accounts');
    }
};
