<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aca_student_participations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('course_id')->nullable();
            $table->unsignedBigInteger('module_id')->nullable();
            $table->unsignedBigInteger('theme_id')->nullable();
            $table->unsignedBigInteger('content_id')->nullable();
            $table->decimal('participation_score', 5, 2)->nullable()->comment('Nota de participación (0-20)');
            $table->text('teacher_comment')->nullable()->comment('Comentario del docente');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->json('edited_by')->nullable()->comment('Historial de ediciones [{user_id, updated_at}]');
            $table->timestamps();

            $table->foreign('student_id', 'fk_student_participation')->references('id')->on('aca_students')->onDelete('cascade');
            $table->foreign('course_id', 'fk_course_participation')->references('id')->on('aca_courses')->onDelete('cascade');
            $table->foreign('module_id', 'fk_module_participation')->references('id')->on('aca_modules')->onDelete('cascade');
            $table->foreign('theme_id', 'fk_theme_participation')->references('id')->on('aca_themes')->onDelete('cascade');
            $table->foreign('content_id', 'fk_content_participation')->references('id')->on('aca_contents')->onDelete('cascade');
            $table->foreign('created_by', 'fk_created_by_participation')->references('id')->on('users')->onDelete('set null');

            $table->index(['course_id', 'module_id', 'theme_id', 'content_id'], 'part_course_mod_theme_cont_idx');
            $table->index('student_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aca_student_participations');
    }
};
