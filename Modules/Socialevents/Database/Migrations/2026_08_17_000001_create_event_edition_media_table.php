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
        Schema::create('event_edition_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained('event_editions')->onDelete('cascade');
            $table->foreignId('match_id')->nullable()->constrained('event_edition_matches')->onDelete('set null')->comment('Partido al que pertenece el medio (opcional)');
            $table->date('media_date')->comment('Fecha de la jornada a la que pertenece el medio');
            $table->enum('type', ['image', 'video'])->default('image')->comment('Tipo de archivo subido');
            $table->string('file_path')->comment('Ruta del archivo en storage público');
            $table->string('file_name')->nullable()->comment('Nombre original del archivo');
            $table->string('mime_type')->nullable();
            $table->timestamps();

            $table->index(['edition_id', 'media_date']);
            $table->index('match_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_edition_media');
    }
};
