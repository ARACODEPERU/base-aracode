<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Notificaciones recibidas de MercadoPago.
 *
 * Se guarda el cuerpo crudo para poder reprocesar una notificación sin perder
 * información; la deduplicación real se hace por el id del cobro.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mercadopago_events', function (Blueprint $table) {
            $table->id();
            $table->string('topic', 40)->comment('topic de la notificación (payment, subscription_authorized_payment, etc.)');
            $table->string('action', 40)->nullable();
            $table->string('data_id', 64)->nullable()->comment('data.id de la notificación');
            $table->json('payload')->nullable()->comment('Cuerpo o query cruda de la notificación');
            $table->timestamp('processed_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['topic', 'data_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mercadopago_events');
    }
};
