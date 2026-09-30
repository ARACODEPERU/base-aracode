<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Limpieza del registro con codigo por alumno.
     *
     * El registro ahora es con un unico enlace del bot y el documento que la
     * persona escribe en el chat, asi que los codigos personales ya no se
     * emiten: se retiran sus dos columnas (ambas nullable y sin datos utiles).
     *
     * Idempotente: solo actua si la columna sigue ahi, y no toca las filas.
     */
    public function up(): void
    {
        if (! Schema::hasTable('integration_telegram_contacts')) {
            return;
        }

        Schema::table('integration_telegram_contacts', function (Blueprint $table) {
            if (Schema::hasColumn('integration_telegram_contacts', 'registration_code')) {
                $table->dropUnique(['registration_code']);
                $table->dropColumn('registration_code');
            }

            if (Schema::hasColumn('integration_telegram_contacts', 'code_expires_at')) {
                $table->dropColumn('code_expires_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('integration_telegram_contacts')) {
            return;
        }

        Schema::table('integration_telegram_contacts', function (Blueprint $table) {
            if (! Schema::hasColumn('integration_telegram_contacts', 'registration_code')) {
                $table->string('registration_code', 64)->nullable()->unique();
            }

            if (! Schema::hasColumn('integration_telegram_contacts', 'code_expires_at')) {
                $table->timestamp('code_expires_at')->nullable();
            }
        });
    }
};
