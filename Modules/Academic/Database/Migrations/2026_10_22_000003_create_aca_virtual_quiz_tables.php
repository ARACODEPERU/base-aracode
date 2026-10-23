<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('aca_virtual_activity_questions')) {
            Schema::create('aca_virtual_activity_questions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('activity_id')->constrained('aca_virtual_activities')->cascadeOnDelete();
                $table->enum('type', ['single_choice', 'multiple_choice', 'true_false', 'short_answer'])->default('single_choice');
                $table->text('question');
                $table->decimal('points', 5, 2)->default(1.00);
                // Respuestas aceptadas para preguntas abiertas, separadas por "|".
                $table->text('answer_text')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->index(['activity_id', 'sort_order'], 'ix_aca_virtual_question_activity_sort');
            });
        }

        if (! Schema::hasTable('aca_virtual_activity_options')) {
            Schema::create('aca_virtual_activity_options', function (Blueprint $table) {
                $table->id();
                $table->foreignId('question_id')->constrained('aca_virtual_activity_questions')->cascadeOnDelete();
                $table->string('text', 500);
                $table->boolean('is_correct')->default(false);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->index(['question_id', 'sort_order'], 'ix_aca_virtual_option_question_sort');
            });
        }

        if (! Schema::hasTable('aca_virtual_quiz_attempts')) {
            Schema::create('aca_virtual_quiz_attempts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('activity_id')->constrained('aca_virtual_activities')->cascadeOnDelete();
                $table->foreignId('student_id')->constrained('aca_school_students')->cascadeOnDelete();
                $table->enum('status', ['in_progress', 'finished'])->default('in_progress');
                $table->dateTime('started_at')->nullable();
                $table->dateTime('finished_at')->nullable();
                $table->decimal('points_earned', 8, 2)->default(0);
                $table->decimal('points_total', 8, 2)->default(0);
                $table->decimal('score', 5, 2)->nullable();
                $table->timestamps();

                // Un intento por estudiante y actividad: la nota se calcula una sola vez.
                $table->unique(['activity_id', 'student_id'], 'uq_aca_virtual_attempt_activity_student');
            });
        }

        if (! Schema::hasTable('aca_virtual_quiz_answers')) {
            Schema::create('aca_virtual_quiz_answers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('attempt_id')->constrained('aca_virtual_quiz_attempts')->cascadeOnDelete();
                $table->foreignId('question_id')->constrained('aca_virtual_activity_questions')->cascadeOnDelete();
                // Opciones elegidas (solo para preguntas de alternativas).
                $table->json('option_ids')->nullable();
                $table->text('answer_text')->nullable();
                $table->decimal('points_earned', 8, 2)->default(0);
                $table->boolean('is_correct')->default(false);
                $table->timestamps();

                $table->unique(['attempt_id', 'question_id'], 'uq_aca_virtual_answer_attempt_question');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('aca_virtual_quiz_answers');
        Schema::dropIfExists('aca_virtual_quiz_attempts');
        Schema::dropIfExists('aca_virtual_activity_options');
        Schema::dropIfExists('aca_virtual_activity_questions');
    }
};
