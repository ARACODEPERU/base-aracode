<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aca_student_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->constrained('aca_cap_registrations')->onDelete('cascade')->comment('ID de la matrícula');
            $table->foreignId('student_id')->constrained('aca_students')->onDelete('cascade')->comment('ID del estudiante');
            $table->foreignId('course_id')->constrained('aca_courses')->onDelete('cascade')->comment('ID del curso');
            
            $table->decimal('final_average', 5, 2)->nullable()->comment('Promedio final (0-20)');
            $table->boolean('approved')->default(false)->comment('Aprobado si final_average >= 11');
            $table->text('observations')->nullable()->comment('Observaciones generales');
            
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->comment('Usuario que crea');
            $table->timestamp('registered_at')->useCurrent()->comment('Fecha de registro');
            $table->json('edited_by')->nullable()->comment('Historial de ediciones: [{user_id, updated_at}]');
            
            $table->timestamps();
            
            $table->unique(['registration_id'], 'idx_grades_registration');
            $table->index(['student_id', 'course_id'], 'idx_grades_student_course');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aca_student_grades');
    }
};
