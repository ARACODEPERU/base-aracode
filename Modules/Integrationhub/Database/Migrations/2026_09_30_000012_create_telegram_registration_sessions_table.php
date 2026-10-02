<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sesion de registro dentro del chat del bot.
     *
     * El registro ya no usa un codigo por alumno: con el enlace unico del bot,
     * el alumno escribe /start y el bot le pide su documento. Esta tabla guarda
     * esa conversacion a medias (que chat esta esperando el documento, cuantos
     * intentos lleva y hasta cuando vale) para que el webhook sepa interpretar
     * el siguiente mensaje como el documento y no como un mensaje suelto.
     *
     * Idempotente: si la tabla ya existe no se vuelve a crear.
     */
    public function up(): void
    {
        if (Schema::hasTable('integration_telegram_registration_sessions')) {
            return;
        }

        Schema::create('integration_telegram_registration_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('chat_id', 32)->unique()
                ->comment('Chat privado que esta a medio registrar');
            $table->string('telegram_username', 100)->nullable()
                ->comment('Usuario (@) de Telegram, si lo tiene publico');
            $table->string('telegram_first_name', 150)->nullable()
                ->comment('Nombre con el que se presenta en Telegram');
            $table->string('step', 30)->default('awaiting_document')
                ->comment('Paso de la conversacion (awaiting_document)');
            $table->unsignedTinyInteger('attempts')->default(0)
                ->comment('Documentos escritos que no se pudieron validar');
            $table->timestamp('expires_at')->nullable()
                ->comment('Cuando se abandona la conversacion a medias');
            $table->timestamps();

            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_telegram_registration_sessions');
    }
};
