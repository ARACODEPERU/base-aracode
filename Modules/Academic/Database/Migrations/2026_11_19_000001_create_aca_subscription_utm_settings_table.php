<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Configuración de los enlaces UTM de la landing pública de suscripciones.
 *
 * La landing es una sola página (no una por plan), así que la campaña y los
 * canales viven en una única fila de esta tabla en lugar de repetirse en
 * aca_subscription_types.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('aca_subscription_utm_settings')) {
            Schema::create('aca_subscription_utm_settings', function (Blueprint $table) {
                $table->id();
                $table->json('utm_config')->nullable()->comment('Campaña y canales (term/content/activo) por red social');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('aca_subscription_utm_settings');
    }
};
