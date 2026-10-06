<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Registro de notas del colegio por docente/tutor de seccion.
     * Escala dual segun normas de Peru: vigesimal (0-20) o literal (A/B/C/AD).
     * Idempotente y sin eliminacion de tablas (skill no-drop-tables).
     */
    public function up(): void
    {
        if (Schema::hasTable('aca_school_grade_records')) {
            return;
        }

        Schema::create('aca_school_grade_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained('aca_school_enrollments')->cascadeOnDelete();
            $table->foreignId('section_id')->constrained('aca_school_sections')->cascadeOnDelete();
            $table->unsignedBigInteger('year_id')->nullable();
            $table->foreign('year_id')->references('id')->on('aca_school_years')->nullOnDelete();
            $table->string('area', 120)->comment('Area curricular (Matematica, Comunicacion, CC.SS...)');
            $table->unsignedTinyInteger('bimester')->comment('1..4 bimestres');
            $table->string('scale_type', 10)->comment('vigesimal|literal');
            $table->unsignedTinyInteger('score_number')->nullable()->comment('Nota 0-20 en escala vigesimal');
            $table->string('score_letter', 2)->nullable()->comment('Nota literal A/B/C/AD (Inicial)');
            $table->text('observations')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['enrollment_id', 'area', 'bimester'], 'uq_grade_record_area_bim');
            $table->index(['section_id', 'bimester']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aca_school_grade_records');
    }
};
