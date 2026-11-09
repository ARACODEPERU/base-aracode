<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Activado: usa Aracodito. Desactivado: usa SweetAlert2 (swal).
            $table->boolean('aracodito_enabled')->default(false);
            // Activado: el personaje reproduce audio y mueve la boca.
            $table->boolean('aracodito_audio_enabled')->default(false);
            // Activado: permite interacción con IA para preguntas del sistema.
            $table->boolean('aracodito_ia_enabled')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'aracodito_enabled',
                'aracodito_audio_enabled',
                'aracodito_ia_enabled',
            ]);
        });
    }
};
