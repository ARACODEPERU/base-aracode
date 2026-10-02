<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Textos del bot de Telegram que el administrador puede reescribir.
     *
     * La tabla guarda solo los cambios: los mensajes y sus valores por defecto
     * viven en Support\TelegramMessages, asi que una fila con body en null
     * significa "usa el texto de fabrica" y borrarla equivale a restaurarlo.
     * Gracias a eso la migracion no necesita sembrar nada y se puede re-ejecutar
     * sin miedo (y los mensajes nuevos que se agreguen al catalogo aparecen solos
     * en la pantalla, sin migracion).
     *
     * Idempotente: si la tabla ya existe no se vuelve a crear.
     */
    public function up(): void
    {
        if (Schema::hasTable('integration_telegram_messages')) {
            return;
        }

        Schema::create('integration_telegram_messages', function (Blueprint $table) {
            $table->id();
            $table->string('code', 60)->unique()
                ->comment('Clave del mensaje en el catalogo (Support\\TelegramMessages)');
            $table->text('body')->nullable()
                ->comment('Texto reescrito; null = usar el texto de fabrica');
            $table->string('format', 10)->nullable()
                ->comment('html = interpreta etiquetas, text = texto plano, null = el del catalogo');
            $table->unsignedBigInteger('updated_by')->nullable()
                ->comment('Usuario que guardo el ultimo cambio');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_telegram_messages');
    }
};
