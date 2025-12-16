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
        Schema::create('event_edition_team_players', function (Blueprint $table) {
            $table->foreignId('edition_id')->constrained('event_editions')->onDelete('cascade');
            $table->foreignId('team_id')->constrained('event_teams')->onDelete('cascade');
            $table->foreignId('person_id')->constrained('people')->onDelete('cascade')->comment('FK a la tabla de personas (el jugador)');
            $table->string('jersey_number')->nullable()->comment('Número de camiseta en esta edición');
            $table->string('position')->nullable()->comment('Posición principal (ej: Delantero, Defensa)');
            $table->string('role_in_team')->nullable()->comment('Rol (ej: Capitán, Sub-Capitán)');
            $table->date('registration_date');
            $table->boolean('booster')->nullable()->default(false)->comment('para saber si es jale o parte');
            $table->timestamps();
            $table->primary(['edition_id', 'team_id', 'person_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_edition_team_players');
    }
};
