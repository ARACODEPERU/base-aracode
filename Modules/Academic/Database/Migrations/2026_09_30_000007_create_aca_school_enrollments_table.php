<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aca_school_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('aca_schools')->cascadeOnDelete();
            $table->foreignId('year_id')->constrained('aca_school_years')->cascadeOnDelete();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('section_id');
            $table->string('type', 20)->default('nueva')->comment('nueva|promovida|repitente|traslado|reingreso');
            $table->string('status', 20)->default('activo')->comment('activo|retirado|traslado_salida|anulado');
            $table->date('enrollment_date');
            $table->unsignedBigInteger('guardian_person_id')->nullable()->comment('Apoderado (people)');
            $table->string('guardian_relationship', 60)->nullable();
            $table->string('guardian_phone', 30)->nullable();
            $table->text('observations')->nullable();
            // Ganchos de cobro para fases futuras (patron aca_cap_registrations)
            $table->unsignedBigInteger('sale_note_id')->nullable();
            $table->unsignedBigInteger('document_id')->nullable();
            $table->unsignedBigInteger('user_id_registers')->nullable();
            $table->timestamps();

            $table->unique(['year_id', 'student_id'], 'aca_school_enrollments_year_student_unique');
            $table->index(['school_id', 'status']);
            $table->index('section_id');

            $table->foreign('student_id')->references('id')->on('aca_school_students')->cascadeOnDelete();
            $table->foreign('section_id')->references('id')->on('aca_school_sections')->cascadeOnDelete();
            $table->foreign('guardian_person_id')->references('id')->on('people')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('aca_school_enrollments', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
            $table->dropForeign(['section_id']);
            $table->dropForeign(['guardian_person_id']);
        });
        Schema::dropIfExists('aca_school_enrollments');
    }
};
