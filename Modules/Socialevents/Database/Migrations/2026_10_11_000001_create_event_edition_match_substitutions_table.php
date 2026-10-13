<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cambios de jugadores: un cambio = una fila (sale / entra / minuto).
     */
    public function up(): void
    {
        Schema::create('event_edition_match_substitutions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('match_id')->constrained('event_edition_matches')->onDelete('cascade');

            // El equipo del cambio (local o visitante) para no depender de un JOIN extra.
            $table->foreignId('team_id')->constrained('event_teams')->onDelete('cascade');

            // Jugador que sale y jugador que entra (people.id, igual que las stats)
            $table->foreignId('player_out_id')->constrained('people')->onDelete('cascade');
            $table->foreignId('player_in_id')->constrained('people')->onDelete('cascade');

            // Minuto absoluto del partido (0..duración efectiva). Lo calcula el backend.
            $table->unsignedSmallInteger('minute')->default(0);

            // Minuto dentro del tiempo jugado, tal como lo escribe el operador.
            $table->unsignedSmallInteger('minute_in_period')->default(0);

            // first | second | extra_first | extra_second
            $table->string('period', 20)->nullable();

            // tactical | injury | red_card | other
            $table->string('reason', 20)->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // Un jugador no puede salir dos veces en el mismo partido.
            $table->unique(['match_id', 'player_out_id'], 'match_substitution_out_unique');

            $table->index(['match_id', 'team_id', 'minute'], 'match_substitution_team_minute_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_edition_match_substitutions');
    }
};
