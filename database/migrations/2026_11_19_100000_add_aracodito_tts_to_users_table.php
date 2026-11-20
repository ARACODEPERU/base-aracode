<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Preferencias de voz del TTS de Aracodito.
     *
     * El nombre de la voz lo pone el navegador o el sistema operativo; si en
     * otro equipo no existe, el TTS usa la primera voz en español.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('aracodito_tts_voice', 120)->nullable()->after('aracodito_style');
            $table->decimal('aracodito_tts_rate', 3, 2)->default(1.00)->after('aracodito_tts_voice');
            $table->decimal('aracodito_tts_pitch', 3, 2)->default(1.00)->after('aracodito_tts_rate');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'aracodito_tts_voice',
                'aracodito_tts_rate',
                'aracodito_tts_pitch',
            ]);
        });
    }
};
