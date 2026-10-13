<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Detalle de las atajadas del arquero: una fila por atajada.
     *
     * El contador de `event_edition_match_player_stats.saves` dice cuántas
     * atajadas hizo un arquero, pero no cómo fueron: con eso no se puede saber
     * si fueron de un penal, de un mano a mano, de cabeza o de tiro olímpico,
     * ni si ocurrieron en la prórroga o en la tanda de penales.
     *
     * El tiempo (`period`) y el minuto son opcionales a propósito: las actas
     * registradas antes de esta tabla no tienen esa información y no se inventa
     * (quedan en null y el detalle los muestra como "sin tiempo registrado").
     */
    public function up(): void
    {
        if (! Schema::hasTable('event_edition_match_saves')) {
            Schema::create('event_edition_match_saves', function (Blueprint $table) {
                $table->id();

                $table->foreignId('match_id')->constrained('event_edition_matches')->onDelete('cascade');

                // Equipo del arquero que atajó (local o visitante).
                $table->foreignId('team_id')->constrained('event_teams')->onDelete('cascade');

                // Arquero que atajó (people.id, igual que las estadísticas y los cambios)
                $table->foreignId('player_id')->constrained('people')->onDelete('cascade');

                // Minuto absoluto del partido (0..duración efectiva). Lo calcula el backend.
                $table->unsignedSmallInteger('minute')->nullable();

                // Minuto dentro del tiempo jugado, tal como lo escribe el operador.
                $table->unsignedSmallInteger('minute_in_period')->nullable();

                // first | second | extra_first | extra_second | penalty_shootout (null = sin registrar)
                $table->string('period', 20)->nullable();

                // Cómo llegó el remate: shot | one_on_one | header | olympic | other
                $table->string('save_type', 20)->nullable();

                // La atajada fue a un penal (de juego o de la tanda).
                $table->boolean('is_penalty')->default(false);

                // Quién pateó: jugador del equipo rival (people.id).
                $table->foreignId('shooter_player_id')->nullable()->constrained('people')->nullOnDelete();

                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

                $table->timestamps();

                $table->index(['match_id', 'team_id', 'minute'], 'match_save_team_minute_index');
                $table->index('player_id');
            });

            return;
        }

        // La tabla ya existe por un intento anterior interrumpido (no se elimina,
        // skill no-drop-tables): se completa el estado faltante de forma idempotente.
        foreach ([
            'minute' => fn (Blueprint $table) => $table->unsignedSmallInteger('minute')->nullable()->after('player_id'),
            'minute_in_period' => fn (Blueprint $table) => $table->unsignedSmallInteger('minute_in_period')->nullable()->after('minute'),
            'period' => fn (Blueprint $table) => $table->string('period', 20)->nullable()->after('minute_in_period'),
            'save_type' => fn (Blueprint $table) => $table->string('save_type', 20)->nullable()->after('period'),
            'is_penalty' => fn (Blueprint $table) => $table->boolean('is_penalty')->default(false)->after('save_type'),
            'shooter_player_id' => fn (Blueprint $table) => $table->unsignedBigInteger('shooter_player_id')->nullable()->after('is_penalty'),
            'created_by' => fn (Blueprint $table) => $table->unsignedBigInteger('created_by')->nullable()->after('shooter_player_id'),
        ] as $column => $definition) {
            if (! Schema::hasColumn('event_edition_match_saves', $column)) {
                Schema::table('event_edition_match_saves', $definition);
            }
        }
    }

    public function down(): void
    {
        // No se elimina la tabla (skill no-drop-tables); dejarla intacta.
    }
};
