<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_edition_player_exclusions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained('event_editions')->cascadeOnDelete()->comment('Edición del torneo donde el jugador queda excluido');
            $table->foreignId('player_id')->constrained('people')->cascadeOnDelete()->comment('Jugador excluido (FK a people)');
            $table->string('reason')->comment('Motivo de la expulsión/exclusión');
            $table->string('excluded_by')->nullable()->comment('Quién tomó la decisión (nombres y apellidos)');
            $table->timestamp('excluded_at')->useCurrent()->comment('Fecha y hora de la exclusión');
            $table->timestamps();

            $table->unique(['edition_id', 'player_id'], 'edition_player_exclusion_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_edition_player_exclusions');
    }
};