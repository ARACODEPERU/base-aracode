<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_edition_player_suspensions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained('event_editions')->cascadeOnDelete()->comment('Edición del torneo donde el jugador queda suspendido');
            $table->foreignId('player_id')->constrained('people')->cascadeOnDelete()->comment('Jugador suspendido (FK a people)');
            $table->enum('type', ['definitive', 'matches', 'date_range'])->default('definitive')->comment('definitive = todo el torneo | matches = por N partidos | date_range = entre fechas');
            $table->unsignedInteger('matches_count')->nullable()->comment('Número de partidos de la sanción (solo type = matches)');
            $table->unsignedInteger('matches_served')->default(0)->comment('Partidos ya cumplidos de la sanción (se incrementa al registrar actas donde NO participó)');
            $table->date('starts_at')->nullable()->comment('Inicio de la suspensión (solo type = date_range)');
            $table->date('ends_at')->nullable()->comment('Fin de la suspensión (solo type = date_range)');
            $table->string('reason')->comment('Motivo de la suspensión');
            $table->string('suspended_by')->nullable()->comment('Quién impuso la suspensión (nombres y apellidos)');
            $table->timestamp('suspended_at')->useCurrent()->comment('Fecha y hora en que se registró la suspensión');
            $table->timestamp('lifted_at')->nullable()->comment('Fecha en que se levantó manualmente la suspensión');
            $table->timestamps();

            $table->unique(['edition_id', 'player_id'], 'edition_player_suspension_unique');
            $table->index(['edition_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_edition_player_suspensions');
    }
};
