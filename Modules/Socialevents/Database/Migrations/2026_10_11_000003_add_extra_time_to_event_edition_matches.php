<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Prórroga y override de tiempos por partido.
     *
     * score_h / score_a siguen siendo el marcador final (incluye la prórroga);
     * score_regular_h / score_regular_a guardan el marcador al terminar el
     * tiempo reglamentario, que es el que obliga a jugar la prórroga.
     */
    public function up(): void
    {
        Schema::table('event_edition_matches', function (Blueprint $table) {
            // Override de tiempos solo para este partido (null = usa la edición)
            $table->unsignedSmallInteger('half_minutes_override')->nullable()->after('location');
            $table->unsignedTinyInteger('halves_override')->nullable()->after('half_minutes_override');
            $table->unsignedSmallInteger('extra_half_minutes_override')->nullable()->after('halves_override');

            // ¿Se jugó la prórroga? y el marcador al final del tiempo reglamentario
            $table->boolean('extra_time_played')->default(false)->after('penalties');
            $table->unsignedTinyInteger('score_regular_h')->nullable()->after('extra_time_played');
            $table->unsignedTinyInteger('score_regular_a')->nullable()->after('score_regular_h');
        });
    }

    public function down(): void
    {
        Schema::table('event_edition_matches', function (Blueprint $table) {
            $table->dropColumn([
                'half_minutes_override',
                'halves_override',
                'extra_half_minutes_override',
                'extra_time_played',
                'score_regular_h',
                'score_regular_a',
            ]);
        });
    }
};
