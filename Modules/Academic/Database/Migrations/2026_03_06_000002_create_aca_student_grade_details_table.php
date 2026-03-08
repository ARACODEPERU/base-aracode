<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aca_student_grade_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grade_id')->constrained('aca_student_grades')->onDelete('cascade')->comment('FK a tabla padre');
            $table->foreignId('module_id')->constrained('aca_modules')->onDelete('cascade')->comment('FK a módulo');
            
            $table->decimal('exam_score', 5, 2)->nullable()->comment('Nota examen (0-20)');
            $table->decimal('attendance_score', 5, 2)->nullable()->comment('Nota asistencia (0-12)');
            $table->decimal('participation_score', 5, 2)->nullable()->comment('Nota participación (0-20)');
            $table->decimal('average', 5, 2)->nullable()->comment('Promedio módulo');
            $table->boolean('module_approved')->default(false)->comment('Aprobado si average >= 11');
            $table->text('observations')->nullable()->comment('Observaciones del módulo');
            
            $table->timestamps();
            
            $table->unique(['grade_id', 'module_id'], 'idx_details_grade_module');
            $table->index('module_id', 'idx_details_module');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aca_student_grade_details');
    }
};
