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
        Schema::create('event_edition_teams', function (Blueprint $table) {
            $table->foreignId('edition_id')->constrained('event_editions')->onDelete('cascade');
            $table->foreignId('team_id')->constrained('event_teams')->onDelete('cascade');
            // Partidos
            $table->unsignedSmallInteger('matches_played')->default(0)->comment('Partidos Jugados (PJ)');
            $table->unsignedSmallInteger('matches_won')->default(0)->comment('Partidos Ganados (PG)');
            $table->unsignedSmallInteger('matches_drawn')->default(0)->comment('Partidos Empatados (PE)');
            $table->unsignedSmallInteger('matches_lost')->default(0)->comment('Partidos Perdidos (PP)');
            // Goles / Puntos de Canasta
            $table->unsignedSmallInteger('goals_for')->default(0)->comment('Goles a Favor (GF) o Puntos a Favor.');
            $table->unsignedSmallInteger('goals_against')->default(0)->comment('Goles en Contra (GC) o Puntos en Contra.');
            $table->smallInteger('goal_difference')->default(0)->comment('Diferencia de Goles (DG): GF - GC.');
            $table->unsignedSmallInteger('points')->default(0)->comment('Puntos Acumulados (Pts) según las reglas de la competición.');
            // Extras
            $table->unsignedSmallInteger('rank')->nullable()->comment('Posición final o actual en la tabla.');
            $table->boolean('is_champion')->default(false)->comment('Indica si el equipo ganó la edición.');
            $table->timestamps();
            $table->primary(['edition_id', 'team_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_edition_teams');
    }
};
