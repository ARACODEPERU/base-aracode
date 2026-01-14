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
        Schema::create('event_edition_match_player_stats', function (Blueprint $table) {
            $table->id();
            // Identificamos el partido
            $table->foreignId('match_id')->constrained('event_edition_matches')->onDelete('cascade');

            // Identificamos al jugador por su person_id (el ID de la tabla people)
            // Ya que la relación con el equipo y edición viene implícita en el partido
            $table->foreignId('player_id')->constrained('people')->onDelete('cascade');

            // Añadimos el team_id para facilitar estadísticas por equipo sin hacer tantos JOINs
            $table->foreignId('team_id')->constrained('event_teams')->onDelete('cascade');

            // Estadísticas
            $table->integer('goals')->default(0);
            $table->integer('assists')->default(0);
            $table->integer('saves')->default(0)->comment('atajadas directa');
            $table->integer('minutes_played')->default(0);
            $table->boolean('is_mvp')->default(false);
            $table->integer('clean_sheet')->default(0);

            $table->timestamps();

            // Creamos un índice único para que un jugador no tenga dos filas de stats en el mismo partido
            $table->unique(['match_id', 'person_id'], 'match_person_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_edition_match_player_stats');
    }
};
