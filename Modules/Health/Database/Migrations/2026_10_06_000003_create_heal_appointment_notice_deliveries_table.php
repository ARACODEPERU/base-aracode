<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Registro de cada aviso enviado (o intentado) para una cita.
     *
     * La clave unica (appointment_id, notice_id, channel) es la garantia de no
     * duplicar: el comando que corre cada minuto solo encola la entrega si esa
     * combinacion no existe todavia, aunque el worker aun no haya terminado.
     *
     * Estados: pending (encolada), processing (el worker la esta enviando),
     * sent (enviada), skipped (no se puede enviar: sin telefono) y failed
     * (agoto los reintentos del job).
     */
    public function up(): void
    {
        Schema::create('heal_appointment_notice_deliveries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('appointment_id')->index();
            $table->unsignedBigInteger('notice_id')->index();
            $table->string('channel', 30)->default('smsgate');
            $table->string('status', 20)->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['appointment_id', 'notice_id', 'channel'], 'heal_notice_delivery_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('heal_appointment_notice_deliveries');
    }
};
