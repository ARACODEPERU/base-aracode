<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Campanas de notificaciones masivas del modulo Academico.
     *
     * aca_notification_campaigns guarda la cabecera y el avance (para la barra
     * de progreso) y aca_notification_campaign_recipients el padron congelado
     * al momento de lanzar la campana: ese snapshot hace la campana reanudable
     * (el job retoma unicamente las filas pending y nunca reenvia las sent) y
     * permite mostrar al final que numeros fallaron.
     */
    public function up(): void
    {
        Schema::create('aca_notification_campaigns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->comment('Usuario que lanzo la campana');
            $table->unsignedBigInteger('course_id')->nullable()->comment('Programa de especializacion notificado (null en modo prueba)');
            $table->string('channel', 20)->comment('sms = SMS via Vonage, whatsapp = flujo de Integrationhub');
            $table->boolean('is_test')->default(false)->comment('true = envio de prueba a numeros escritos a mano');
            $table->text('message')->nullable()->comment('Mensaje escrito por el administrador');
            $table->string('time_label', 60)->nullable()->comment('Tiempo a informar en la plantilla de WhatsApp');
            $table->unsignedInteger('total_recipients')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->string('current_phone', 20)->nullable()->comment('Numero al que se esta enviando ahora');
            $table->string('status', 20)->default('pending')->comment('pending, processing, completed o failed');
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index(['status', 'created_at']);
            $table->foreign('course_id')->references('id')->on('aca_courses')->onDelete('cascade');
        });

        Schema::create('aca_notification_campaign_recipients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campaign_id');
            $table->unsignedBigInteger('student_id')->nullable();
            $table->unsignedBigInteger('person_id')->nullable();
            $table->string('name')->nullable();
            $table->string('phone', 20)->comment('Telefono normalizado en E.164 sin signo +');
            $table->string('source', 20)->nullable()->comment('programa = matriculado en el programa, suscripcion = suscripcion activa');
            $table->string('status', 20)->default('pending')->comment('pending, sent o failed');
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['campaign_id', 'status']);
            $table->foreign('campaign_id')->references('id')->on('aca_notification_campaigns')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aca_notification_campaign_recipients');
        Schema::dropIfExists('aca_notification_campaigns');
    }
};
