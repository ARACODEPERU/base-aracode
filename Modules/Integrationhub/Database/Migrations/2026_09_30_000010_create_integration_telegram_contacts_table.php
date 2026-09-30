<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Vinculacion entre una persona del sistema y su chat de Telegram.
     *
     * Se crea una fila cuando el administrador emite un enlace de registro (con
     * registration_code y sin chat_id) y se completa cuando la persona abre el
     * deep link y le da Iniciar al bot (el webhook guarda chat_id, usuario y
     * fecha de alta). El estado permite las bajas: quien escribe /baja queda
     * como "inactive" y deja de recibir campanas.
     *
     * Idempotente: si la tabla ya existe no se vuelve a crear.
     */
    public function up(): void
    {
        if (Schema::hasTable('integration_telegram_contacts')) {
            return;
        }

        Schema::create('integration_telegram_contacts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('person_id')->nullable()
                ->comment('Persona (people.id) a la que pertenece el chat');
            $table->string('chat_id', 32)->nullable()
                ->comment('Identificador del chat privado que entrega Telegram');
            $table->string('telegram_username', 100)->nullable()
                ->comment('Usuario (@) de Telegram, si lo tiene publico');
            $table->string('telegram_first_name', 150)->nullable()
                ->comment('Nombre con el que se presenta en Telegram');
            $table->string('status', 20)->default('active')
                ->comment('active = recibe mensajes, inactive = dio de baja el bot');
            $table->string('registration_code', 64)->nullable()
                ->comment('Codigo de un solo uso del enlace de registro');
            $table->timestamp('code_expires_at')->nullable()
                ->comment('Vigencia del codigo de registro');
            $table->timestamp('registered_at')->nullable()
                ->comment('Momento en que la persona dio Iniciar al bot');
            $table->timestamp('last_seen_at')->nullable()
                ->comment('Ultimo mensaje recibido del chat');
            $table->timestamps();

            $table->unique('person_id');
            $table->unique('chat_id');
            $table->unique('registration_code');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_telegram_contacts');
    }
};
