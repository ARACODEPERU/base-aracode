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
        Schema::create('event_editions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('even_events')->comment('FK al evento padre (ej: "Torneo de Fútbol")');

            // Información básica de la edición
            $table->string('year', 4)->nullable();
            $table->string('name')->comment('Nombre de la edición (ej: "Apertura 2026", "Edición 2025")');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->enum('competition_format',['round_robin','group_stage_and_playoffs','single_elimination'])->nullable()->comment('round_robin (todos contra todos), group_stage_and_playoffs (grupos y eliminación), single_elimination, etc.');
            $table->integer('score_points_win')->default(3)->comment('Puntos por victoria (ej. 3)');
            $table->integer('score_points_draw')->default(1)->comment('Puntos por empate (ej. 1)');
            $table->integer('score_points_loss')->default(0)->comment('Puntos por derrota (ej. 0)');
            // --- NUEVOS CAMPOS ADMINISTRATIVOS Y REGLAS ---
            $table->decimal('inscription_fee', 8, 2)->default(0)->comment('Costo de inscripción por equipo.');
            $table->unsignedSmallInteger('min_players_per_team')->default(5)->comment('Mínimo de jugadores por equipo (para validación).');
            $table->unsignedSmallInteger('max_players_per_team')->default(15)->comment('Máximo de jugadores por equipo (para validación).');
            $table->binary('prize_details')->nullable()->comment('Detalle de los premios: 1er lugar, 2do lugar, goleador, etc.');
            // ---------------------------------------------
            $table->string('path_database_file', 300)->nullable('ruta del archivo de las bases');
            $table->string('name_database_file', 255)->nullable('nombre del archivo de las bases');
            $table->binary('details')->nullable()->comment('Detalles generales de la edición.');
            $table->string('status', 50)->default('pending');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_editions');
    }
};
