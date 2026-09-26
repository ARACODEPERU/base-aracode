<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Moneda de la compra y tipo de cambio aplicado el dia de la compra:
     * el comprobante (boleta/factura) se emite en esa moneda usando ese TC,
     * aunque el documento se genere dias despues.
     */
    public function up(): void
    {
        Schema::table('onli_sales', function (Blueprint $table) {
            $table->string('currency', 5)->default('PEN')->after('traffic_source');
            $table->decimal('exchange_rate', 10, 4)->nullable()->after('currency');
        });
    }

    public function down(): void
    {
        Schema::table('onli_sales', function (Blueprint $table) {
            $table->dropColumn(['currency', 'exchange_rate']);
        });
    }
};
