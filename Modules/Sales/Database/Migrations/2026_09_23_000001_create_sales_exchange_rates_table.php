<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Guarda el tipo de cambio oficial de SUNAT consultado via Migo (o ingreso manual).
     * Un registro por moneda y fecha (indice unico) para uso en ventas y facturacion electronica.
     */
    public function up(): void
    {
        if (Schema::hasTable('sales_exchange_rates')) {
            return;
        }

        Schema::create('sales_exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->char('currency_code', 3)->comment('Codigo SUNAT de la moneda extranjera (ej. USD)');
            $table->date('rate_date')->comment('Fecha del tipo de cambio segun SUNAT');
            $table->decimal('purchase_rate', 10, 4)->comment('Precio de compra');
            $table->decimal('sale_rate', 10, 4)->comment('Precio de venta');
            $table->string('source', 20)->default('migo')->comment('Origen del dato: migo | manual');
            $table->unsignedBigInteger('fetched_by')->nullable()->comment('Usuario que disparo la consulta manual');
            $table->timestamps();

            $table->unique(['currency_code', 'rate_date'], 'sales_exchange_rates_currency_date_unique');
            $table->index('rate_date', 'sales_exchange_rates_rate_date_index');
        });
    }

    public function down(): void
    {
        // Intencionalmente no elimina la tabla (regla del proyecto: jamas drop table).
    }
};
