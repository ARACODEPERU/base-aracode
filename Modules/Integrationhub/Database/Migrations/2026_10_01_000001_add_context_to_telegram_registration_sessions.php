<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Contexto de la conversacion del bot de Telegram.
     *
     * Las sesiones empezaron guardando solo el registro (/start + documento).
     * Ahora tambien sostienen la consulta de cursos y certificados, que necesita
     * recordar entre un mensaje y el siguiente que se pidio (intent) y el correo
     * ya escrito. Se guarda como JSON para no sumar una columna por cada dato.
     *
     * Idempotente: si la columna ya existe no se vuelve a crear.
     */
    public function up(): void
    {
        if (! Schema::hasTable('integration_telegram_registration_sessions')) {
            return;
        }

        if (Schema::hasColumn('integration_telegram_registration_sessions', 'context')) {
            return;
        }

        Schema::table('integration_telegram_registration_sessions', function (Blueprint $table) {
            $table->text('context')->nullable()->after('step')
                ->comment('Datos de la conversacion en curso (intent, email, ...), en JSON');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('integration_telegram_registration_sessions')) {
            return;
        }

        if (! Schema::hasColumn('integration_telegram_registration_sessions', 'context')) {
            return;
        }

        Schema::table('integration_telegram_registration_sessions', function (Blueprint $table) {
            $table->dropColumn('context');
        });
    }
};
