<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Destinatarios de las alertas de error por Telegram.
 *
 * Cada fila es una persona (o cuenta) que recibe los avisos: el chat_id es el
 * dato que usa la integración Telegram_bot para entregar el mensaje, y el
 * nombre es solo una etiqueta para reconocerlo en la pantalla. is_active
 * permite silenciar a alguien sin borrarlo.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('security_alert_recipients')) {
            return;
        }

        Schema::create('security_alert_recipients', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120)->nullable();
            $table->string('chat_id', 60)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active', 'security_alert_recipients_active_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_alert_recipients');
    }
};
