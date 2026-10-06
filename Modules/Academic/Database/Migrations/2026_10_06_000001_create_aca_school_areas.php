<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catalogo de areas curriculares del colegio, mantenible desde el CRUD
     * "Areas Curriculares". Alimenta el registro de notas del docente por
     * nivel CNEB (inicial/primaria/secundaria).
     * Idempotente y sin eliminacion de tablas (skill no-drop-tables).
     */
    public function up(): void
    {
        if (Schema::hasTable('aca_school_areas')) {
            return;
        }

        Schema::create('aca_school_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('aca_schools')->cascadeOnDelete();
            $table->string('name', 120)->comment('Area curricular (Matematica, Comunicacion, CC.SS...)');
            $table->string('level', 20)->comment('Nivel CNEB: inicial|primaria|secundaria');
            $table->unsignedSmallInteger('sort_order')->default(0)->comment('Orden de visualizacion');
            $table->boolean('status')->default(true)->comment('Activa para el registro de notas');
            $table->timestamps();

            $table->unique(['school_id', 'level', 'name'], 'uq_school_area_level_name');
            $table->index(['school_id', 'level', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aca_school_areas');
    }
};
