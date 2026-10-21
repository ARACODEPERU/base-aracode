<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('aca_virtual_submissions')) {
            return;
        }

        Schema::create('aca_virtual_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained('aca_virtual_activities')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('aca_school_students')->cascadeOnDelete();
            $table->longText('content')->nullable();
            $table->string('path')->nullable();
            $table->string('mime_type', 120)->nullable();
            $table->unsignedInteger('size_kb')->nullable();
            $table->enum('status', ['submitted', 'graded'])->default('submitted');
            $table->decimal('score', 5, 2)->nullable();
            $table->string('grade_text', 50)->nullable();
            $table->text('feedback')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('graded_at')->nullable();
            $table->foreignId('graded_by_person_id')->nullable()->constrained('people')->nullOnDelete();
            $table->timestamps();

            $table->unique(['activity_id', 'student_id'], 'uq_aca_virtual_submission_activity_student');
            $table->index(['status', 'submitted_at'], 'ix_aca_virtual_submission_status_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aca_virtual_submissions');
    }
};
