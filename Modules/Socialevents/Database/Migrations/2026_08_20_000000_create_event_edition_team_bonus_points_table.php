<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_edition_team_bonus_points', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('edition_id');
            $table->unsignedBigInteger('team_id');
            $table->integer('points')->default(0)->comment('Puntos extra otorgados');
            $table->string('reason')->comment('Motivo del punto extra (p. ej. buena conducta, inauguracion)');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            // Nombres cortos para evitar el limite de 64 chars en MySQL.
            $table->index('edition_id', 'bonus_edition_idx');
            $table->index('team_id', 'bonus_team_idx');
        });

        Schema::table('event_edition_teams', function (Blueprint $table) {
            $table->unsignedSmallInteger('bonus_points')->default(0)->after('points')->comment('Puntos extra acumulados');
        });
    }

    public function down(): void
    {
        Schema::table('event_edition_teams', function (Blueprint $table) {
            $table->dropColumn('bonus_points');
        });
        Schema::dropIfExists('event_edition_team_bonus_points');
    }
};
