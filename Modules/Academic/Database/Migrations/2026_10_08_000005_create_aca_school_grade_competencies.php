<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nota por competencia del registro de evaluacion CNEB: un registro por
     * matricula + competencia + bimestre con nivel de logro (AD/A/B/C) o
     * nota vigesimal segun la escala configurada, y la conclusion
     * descriptiva de la competencia (formato SIAGIE).
     * Idempotente y sin eliminacion de tablas (skill no-drop-tables).
     */
    public function up(): void
    {
        if (Schema::hasTable('aca_school_grade_competencies')) {
            return;
        }

        Schema::create('aca_school_grade_competencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('aca_schools')->cascadeOnDelete();
            $table->foreignId('year_id')->constrained('aca_school_years')->cascadeOnDelete();
            $table->foreignId('section_id')->constrained('aca_school_sections')->cascadeOnDelete();
            $table->foreignId('enrollment_id')->constrained('aca_school_enrollments')->cascadeOnDelete();
            $table->foreignId('competency_id')->constrained('aca_school_competencies')->cascadeOnDelete();
            $table->unsignedTinyInteger('bimester')->comment('1 a 4');
            $table->string('scale_type', 20)->comment('Escala usada al registrar: literal|vigesimal');
            $table->string('score_letter', 2)->nullable()->comment('AD|A|B|C cuando la escala es literal');
            $table->unsignedTinyInteger('score_number')->nullable()->comment('0-20 cuando la escala es vigesimal');
            $table->text('conclusion')->nullable()->comment('Conclusion descriptiva de la competencia');
            $table->unsignedBigInteger('user_id_registers')->nullable();
            $table->timestamps();

            $table->unique(['enrollment_id', 'competency_id', 'bimester'], 'uq_grade_competency_unique');
            $table->index(['section_id', 'bimester']);
            $table->foreign('user_id_registers')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('aca_school_grade_competencies', function (Blueprint $table) {
            $table->dropForeign(['user_id_registers']);
        });
        Schema::dropIfExists('aca_school_grade_competencies');
    }
};
