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
        Schema::create('event_edition_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained('event_editions')->onDelete('cascade');

            // Equipos (pueden ser NULL para fases de eliminación como Semifinales)
            $table->foreignId('team_h_id')->nullable()->constrained('event_teams')->onDelete('set null')->comment('Equipo Local');
            $table->foreignId('team_a_id')->nullable()->constrained('event_teams')->onDelete('set null')->comment('Equipo Visitante');

            // --- Información del Encuentro ---
            $table->string('phase')->default('league')->comment('league, groups, round_16, quarter, semi, final, third_place');
            $table->integer('round_number')->default(1)->comment('Número de fecha o jornada (Ej: Fecha 1, Fecha 2)');
            $table->string('group_name')->nullable()->comment('En caso de ser por grupos (Ej: Grupo A)');

            // Programación (pueden ser null al inicio)
            $table->dateTime('match_date')->nullable();
            $table->string('location')->nullable()->comment('Cancha o lugar');

            // --- Resultados ---
            $table->integer('score_h')->default(0)->comment('goles local');
            $table->integer('score_a')->default(0)->comment('goles visitante');
            $table->enum('status', ['pending', 'live', 'finished', 'cancelled', 'walk_over', 'closed'])->default('pending');

            // --- Lógica de Eliminatorias (Cruces) ---
            // Estos campos guardan texto como "Ganador Partido 10" hasta que se defina el equipo
            $table->string('placeholder_h')->nullable();
            $table->string('placeholder_a')->nullable();
            $table->string('original_score', 10)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_edition_matches');
    }
};
