<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Premiados de una edición: jugadores, equipos o personas con un premio
     * concreto (campeón, goleador, mejor arquero, premios libres, etc.).
     *
     * La tabla es idempotente: si ya existe (producción migrada antes), no se
     * vuelve a crear.
     */
    public function up(): void
    {
        if (Schema::hasTable('socialevent_edition_awards')) {
            return;
        }

        Schema::create('socialevent_edition_awards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')
                ->constrained('event_editions')
                ->cascadeOnDelete();
            // Clave estable del premio: champion, mvp, top_scorer, custom_xxx...
            $table->string('award_key', 60);
            $table->string('label', 120);
            // team = diploma único del equipo con su plantel.
            $table->enum('recipient_type', ['team', 'player', 'person'])->default('player');
            $table->foreignId('team_id')->nullable()->constrained('event_teams')->nullOnDelete();
            $table->foreignId('person_id')->nullable()->constrained('people')->nullOnDelete();
            // Copias congeladas: el diploma emitido no debe cambiar si luego se
            // renombra la persona o el equipo.
            $table->string('recipient_name', 180);
            $table->string('team_name', 150)->nullable();
            $table->enum('source', ['auto', 'manual'])->default('manual');
            $table->unsignedTinyInteger('place')->nullable();
            $table->json('meta')->nullable();
            $table->smallInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            // Nombres explícitos: los autogenerados superan el límite de 64
            // caracteres de MySQL en tablas con prefijo largo.
            $table->unique(['edition_id', 'award_key'], 'se_edition_award_edition_key_unique');
            $table->index(['edition_id', 'sort_order'], 'se_edition_award_edition_order_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('socialevent_edition_awards');
    }
};
