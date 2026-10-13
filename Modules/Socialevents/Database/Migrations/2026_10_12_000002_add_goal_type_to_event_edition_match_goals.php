<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tipo de gol (jugada, penal, cabeza, tiro libre, etc.).
     *
     * El detalle del gol ya guardaba quién, cuándo y en qué tiempo; este campo
     * responde "cómo" para los reportes detallados (goles de cabeza, de penal,
     * de jugada). Es opcional: un gol sin tipo registrado se muestra como jugada.
     */
    public function up(): void
    {
        if (! Schema::hasTable('event_edition_match_goals')) {
            return;
        }

        if (! Schema::hasColumn('event_edition_match_goals', 'goal_type')) {
            Schema::table('event_edition_match_goals', function (Blueprint $table) {
                // normal | penalty | header | free_kick | volley | other
                $table->string('goal_type', 20)->nullable()->after('period');
            });
        }
    }

    public function down(): void
    {
        // No se elimina información registrada (skill no-drop-tables); la columna
        // se queda para no perder los tipos de gol capturados.
    }
};
