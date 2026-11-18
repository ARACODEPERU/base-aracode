<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Visibilidad del precio en dólares por plan.
 *
 * El monto en USD se registra siempre en el campo `prices`; esta bandera solo
 * decide si la vista de suscripciones lo muestra. Así el precio se puede
 * ocultar por plan sin borrar el dato ni afectar la venta en soles.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aca_subscription_types', function (Blueprint $table) {
            $table->boolean('show_dollar_price')
                ->default(true)
                ->after('prices')
                ->comment('Muestra el precio en dólares en la vista de suscripciones');
        });
    }

    public function down(): void
    {
        Schema::table('aca_subscription_types', function (Blueprint $table) {
            $table->dropColumn('show_dollar_price');
        });
    }
};
