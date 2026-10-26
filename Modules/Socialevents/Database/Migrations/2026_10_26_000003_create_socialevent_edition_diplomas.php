<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Diplomas emitidos.
     *
     * Guarda copias congeladas del ganador, el plantel y el layout para que una
     * reimpresión sea idéntica aunque después se edite el premio o el diseño.
     *
     * public_token ya se crea aunque la página pública de logros se implemente
     * en una fase posterior: evita una migración extra entonces.
     */
    public function up(): void
    {
        if (Schema::hasTable('socialevent_edition_diplomas')) {
            return;
        }

        Schema::create('socialevent_edition_diplomas', function (Blueprint $table) {
            $table->id();
            $table->string('public_token', 64)->unique('se_edition_diploma_public_token_unique');
            $table->foreignId('edition_id')
                ->constrained('event_editions')
                ->cascadeOnDelete();
            $table->foreignId('template_id')
                ->constrained('socialevent_edition_diploma_templates')
                ->cascadeOnDelete();
            $table->foreignId('award_id')
                ->constrained('socialevent_edition_awards')
                ->cascadeOnDelete();
            $table->enum('recipient_type', ['team', 'player', 'person'])->default('player');
            $table->foreignId('team_id')->nullable()->constrained('event_teams')->nullOnDelete();
            $table->foreignId('person_id')->nullable()->constrained('people')->nullOnDelete();
            $table->string('recipient_name', 180);
            $table->string('team_name', 150)->nullable();
            $table->string('award_label', 120);
            $table->string('edition_name', 150);
            $table->json('squad_snapshot')->nullable();
            $table->json('layout_snapshot')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('views')->default(0);
            $table->timestamps();

            $table->unique(
                ['edition_id', 'award_id', 'template_id'],
                'se_edition_diploma_unique'
            );
            $table->index(['edition_id', 'issued_at'], 'se_edition_diploma_edition_issued_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('socialevent_edition_diplomas');
    }
};
