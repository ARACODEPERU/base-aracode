<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('aca_virtual_activities')) {
            return;
        }

        Schema::create('aca_virtual_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('aca_virtual_courses')->cascadeOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('aca_virtual_units')->cascadeOnDelete();
            $table->foreignId('lesson_id')->nullable()->constrained('aca_virtual_lessons')->cascadeOnDelete();
            $table->enum('type', ['task', 'quiz', 'forum'])->default('task');
            $table->string('title', 200);
            $table->longText('instructions')->nullable();
            $table->dateTime('open_at')->nullable();
            $table->dateTime('close_at')->nullable();
            $table->boolean('allow_late')->default(false);
            $table->decimal('max_score', 5, 2)->default(20.00);
            $table->enum('grading_scale', ['numeric', 'qualitative'])->default('numeric');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->index(['course_id', 'unit_id', 'lesson_id', 'sort_order'], 'ix_aca_virtual_activity_scope_sort');
            $table->index(['open_at', 'close_at'], 'ix_aca_virtual_activity_dates');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aca_virtual_activities');
    }
};
