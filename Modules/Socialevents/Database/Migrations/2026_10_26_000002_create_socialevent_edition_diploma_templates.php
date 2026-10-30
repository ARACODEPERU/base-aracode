<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Diseños de diploma por edición y por tipo de premio.
     *
     * Sólo existe anverso: el tamaño del lienzo lo definen background_width y
     * background_height de la imagen subida. El diseño se guarda como JSON
     * (elements[]) porque el editor Konva trabaja con una lista de elementos.
     */
    public function up(): void
    {
        if (Schema::hasTable('socialevent_edition_diploma_templates')) {
            return;
        }

        Schema::create('socialevent_edition_diploma_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')
                ->constrained('event_editions')
                ->cascadeOnDelete();
            $table->string('award_key', 60);
            $table->string('name', 120);
            $table->string('background_path', 255);
            $table->unsignedInteger('background_width')->default(1550);
            $table->unsignedInteger('background_height')->default(1096);
            $table->json('layout')->nullable();
            // Sólo un modelo vigente por (edición, premio); lo garantiza el
            // servicio dentro de una transacción.
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['edition_id', 'award_key', 'is_active'], 'se_diploma_tpl_edition_key_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('socialevent_edition_diploma_templates');
    }
};
