<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Formato del cruce y piernas de ida y vuelta.
 *
 * - tie_format: 'single' (un solo partido) o 'home_and_away' (ida y vuelta).
 * - decider: cómo se define un empate: 'normal' (prórroga y penales) o
 *   'sudden_death' (muerte súbita / gol de oro, sin penales).
 * - tie_id: id del partido de ida; null cuando el partido no pertenece a un cruce.
 * - leg: 1 = ida, 2 = vuelta.
 *
 * Los partidos existentes quedan como 'single'/'normal' por los valores por defecto,
 * así que la migración no necesita tocar datos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_edition_matches', function (Blueprint $table) {
            $table->string('tie_format', 20)->default('single')->index()->after('group_name');
            $table->string('decider', 20)->default('normal')->index()->after('tie_format');
            $table->unsignedBigInteger('tie_id')->nullable()->index()->after('decider')
                ->comment('Id del partido de ida; null si el partido no pertenece a un cruce');
            $table->unsignedTinyInteger('leg')->nullable()->after('tie_id')
                ->comment('1 = ida, 2 = vuelta');
        });
    }

    public function down(): void
    {
        Schema::table('event_edition_matches', function (Blueprint $table) {
            $table->dropIndex(['tie_format']);
            $table->dropIndex(['decider']);
            $table->dropIndex(['tie_id']);
            $table->dropColumn(['tie_format', 'decider', 'tie_id', 'leg']);
        });
    }
};
