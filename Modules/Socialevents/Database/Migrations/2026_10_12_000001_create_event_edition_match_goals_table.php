<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Goles del partido: una fila por gol, con el jugador y el tiempo en que se
     * hizo.
     *
     * El contador de `event_edition_match_player_stats.goals` dice cuántos goles
     * hizo un jugador, pero no cuándo: con eso no se puede separar en los
     * reportes los goles del tiempo reglamentario de los de la prórroga.
     *
     * El tiempo (`period`) y el minuto son opcionales a propósito: las actas
     * registradas antes de esta tabla no tienen esa información y no se inventa
     * (quedan en null y el reporte los muestra como "sin minuto registrado").
     */
    public function up(): void
    {
        if (! Schema::hasTable('event_edition_match_goals')) {
            Schema::create('event_edition_match_goals', function (Blueprint $table) {
                $table->id();

                $table->foreignId('match_id')->constrained('event_edition_matches')->onDelete('cascade');

                // Equipo al que se le acredita el gol (local o visitante).
                $table->foreignId('team_id')->constrained('event_teams')->onDelete('cascade');

                // Autor del gol (people.id, igual que las estadísticas y los cambios)
                $table->foreignId('player_id')->constrained('people')->onDelete('cascade');

                // Minuto absoluto del partido (0..duración efectiva). Lo calcula el backend.
                $table->unsignedSmallInteger('minute')->nullable();

                // Minuto dentro del tiempo jugado, tal como lo escribe el operador.
                $table->unsignedSmallInteger('minute_in_period')->nullable();

                // first | second | extra_first | extra_second (null = sin registrar)
                $table->string('period', 20)->nullable();

                // Gol en propia puerta (se acredita al equipo que juega, no al rival).
                $table->boolean('is_own_goal')->default(false);

                // Asistente del gol (people.id). Reservado: la interfaz todavía no lo pide.
                $table->foreignId('assist_player_id')->nullable()->constrained('people')->nullOnDelete();

                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

                $table->timestamps();

                $table->index(['match_id', 'team_id', 'minute'], 'match_goal_team_minute_index');
                $table->index('player_id');
            });

            return;
        }

        // La tabla ya existe por un intento anterior interrumpido (no se elimina,
        // skill no-drop-tables): se completa el estado faltante de forma idempotente.

        if (! Schema::hasColumn('event_edition_match_goals', 'minute')) {
            Schema::table('event_edition_match_goals', function (Blueprint $table) {
                $table->unsignedSmallInteger('minute')->nullable()->after('player_id');
            });
        }

        if (! Schema::hasColumn('event_edition_match_goals', 'minute_in_period')) {
            Schema::table('event_edition_match_goals', function (Blueprint $table) {
                $table->unsignedSmallInteger('minute_in_period')->nullable()->after('minute');
            });
        }

        if (! Schema::hasColumn('event_edition_match_goals', 'period')) {
            Schema::table('event_edition_match_goals', function (Blueprint $table) {
                $table->string('period', 20)->nullable()->after('minute_in_period');
            });
        }

        if (! Schema::hasColumn('event_edition_match_goals', 'is_own_goal')) {
            Schema::table('event_edition_match_goals', function (Blueprint $table) {
                $table->boolean('is_own_goal')->default(false)->after('period');
            });
        }

        if (! Schema::hasColumn('event_edition_match_goals', 'assist_player_id')) {
            Schema::table('event_edition_match_goals', function (Blueprint $table) {
                $table->unsignedBigInteger('assist_player_id')->nullable()->after('is_own_goal');
            });
        }

        if (! Schema::hasColumn('event_edition_match_goals', 'created_by')) {
            Schema::table('event_edition_match_goals', function (Blueprint $table) {
                $table->unsignedBigInteger('created_by')->nullable()->after('assist_player_id');
            });
        }
    }

    public function down(): void
    {
        // No se elimina la tabla (skill no-drop-tables); dejarla intacta.
    }
};
