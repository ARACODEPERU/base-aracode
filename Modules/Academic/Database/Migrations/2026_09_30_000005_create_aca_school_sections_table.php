<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aca_school_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('aca_schools')->cascadeOnDelete();
            $table->foreignId('grade_id')->constrained('aca_school_grades')->cascadeOnDelete();
            $table->string('name', 20)->comment('Ej: A, B, C');
            $table->unsignedSmallInteger('capacity')->default(30)->comment('Vacantes totales de la seccion');
            $table->string('shift', 20)->default('manana')->comment('manana|tarde|noche|jornada');
            $table->unsignedBigInteger('tutor_person_id')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->unique(['grade_id', 'name', 'shift'], 'aca_school_sections_grade_name_shift_unique');
            $table->index('school_id');
            $table->foreign('tutor_person_id')->references('id')->on('people')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('aca_school_sections', function (Blueprint $table) {
            $table->dropForeign(['tutor_person_id']);
        });
        Schema::dropIfExists('aca_school_sections');
    }
};
