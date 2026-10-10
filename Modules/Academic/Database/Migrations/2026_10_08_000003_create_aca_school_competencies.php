<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catalogo de competencias CNEB por area curricular del colegio.
     * Codigo SIAGIE (01, 02, ...) dentro de cada area. area_name guarda el
     * nombre del area para sobrevivir renombres; area_id la vincula cuando
     * existe. Idempotente y sin eliminacion de tablas (skill no-drop-tables).
     */
    public function up(): void
    {
        if (Schema::hasTable('aca_school_competencies')) {
            return;
        }

        Schema::create('aca_school_competencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('aca_schools')->cascadeOnDelete();
            $table->foreignId('area_id')->nullable()->constrained('aca_school_areas')->nullOnDelete();
            $table->string('area_name', 120)->comment('Nombre del area al momento de la carga');
            $table->string('level', 20)->comment('Nivel CNEB: inicial|primaria|secundaria');
            $table->string('code', 4)->comment('Codigo SIAGIE de la competencia dentro del area (01, 02...)');
            $table->string('name', 250)->comment('Denominacion oficial de la competencia');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->unique(['school_id', 'area_name', 'code'], 'uq_school_competency_area_code');
            $table->index(['school_id', 'level', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aca_school_competencies');
    }
};
