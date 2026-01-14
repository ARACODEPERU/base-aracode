<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('event_edition_match_participations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id', 'participation_match_id_fk')->constrained('event_edition_matches')->onDelete('cascade');
            $table->foreignId('player_id','participation_player_id_fk')->constrained('people')->onDelete('cascade'); // El ID del jugador en la edición

            // Titular, Suplente, o No jugó (aunque si no se marca, simplemente no se registra)
            $table->enum('role', ['starter', 'substitute'])->default('starter')->comment('Titular, Suplente');

            $table->timestamps();

            // Evita que un jugador se registre dos veces en el mismo partido
            $table->unique(['match_id', 'player_id'], 'unique_participation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_edition_match_participations');
    }
};
