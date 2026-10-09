<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Banco de frases sugeridas para la conclusion descriptiva, por
     * competencia y nivel de logro. El docente elige una y puede editarla.
     * Idempotente y sin eliminacion de tablas (skill no-drop-tables).
     */
    public function up(): void
    {
        if (Schema::hasTable('aca_school_competency_phrases')) {
            return;
        }

        Schema::create('aca_school_competency_phrases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competency_id')->constrained('aca_school_competencies')->cascadeOnDelete();
            $table->string('score', 2)->comment('Nivel de logro asociado: AD|A|B|C');
            $table->text('phrase')->comment('Frase sugerida para la conclusion descriptiva');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['competency_id', 'score']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aca_school_competency_phrases');
    }
};
