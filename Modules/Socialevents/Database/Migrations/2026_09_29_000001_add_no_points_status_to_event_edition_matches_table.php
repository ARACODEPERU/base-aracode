<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Agrega el estado 'no_points' ("Jugado sin puntos" por mutuo acuerdo)
     * al enum de estados de partido. El partido se cuenta como jugado en la
     * tabla de posiciones, pero no otorga goles, resultados ni puntos.
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE event_edition_matches
            MODIFY status ENUM('pending', 'live', 'finished', 'cancelled', 'walk_over', 'closed', 'no_points')
            NOT NULL DEFAULT 'pending'
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE event_edition_matches
            MODIFY status ENUM('pending', 'live', 'finished', 'cancelled', 'walk_over', 'closed')
            NOT NULL DEFAULT 'pending'
        ");
    }
};
