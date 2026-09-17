<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajustes administrativos de puntos (sanciones de comisión de justicia).
     *
     * Mantienen el resultado deportivo intacto y solo modifican los puntos de la
     * tabla de posiciones: points con signo (negativo = quita, positivo = otorga).
     */
    public function up(): void
    {
        Schema::create('event_edition_point_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained('event_editions')->onDelete('cascade');
            $table->foreignId('team_id')->constrained('event_teams')->onDelete('cascade');
            $table->foreignId('match_id')->nullable()->constrained('event_edition_matches')->onDelete('set null');
            $table->foreignId('report_id')->nullable()->constrained('event_edition_match_reports')->onDelete('set null');
            $table->integer('points')->comment('Puntos ajustados con signo: negativo quita, positivo otorga');
            $table->string('reason')->comment('Motivo de la resolución (falta administrativa, apelación, etc.)');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('edition_id', 'point_adj_edition_idx');
            $table->index('team_id', 'point_adj_team_idx');
            $table->index('report_id', 'point_adj_report_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_edition_point_adjustments');
    }
};
