<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('aca_virtual_courses')) {
            return;
        }

        Schema::create('aca_virtual_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('aca_schools')->cascadeOnDelete();
            $table->foreignId('year_id')->constrained('aca_school_years')->cascadeOnDelete();
            $table->foreignId('section_id')->constrained('aca_school_sections')->cascadeOnDelete();
            $table->foreignId('area_id')->constrained('aca_school_areas')->cascadeOnDelete();
            $table->foreignId('teacher_person_id')->nullable()->constrained('people')->nullOnDelete();
            $table->string('name', 150)->nullable();
            $table->text('description')->nullable();
            $table->boolean('status')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['school_id', 'year_id', 'section_id', 'area_id'], 'uq_aca_virtual_course_scope');
            $table->index(['school_id', 'year_id', 'teacher_person_id'], 'ix_aca_virtual_course_school_year_teacher');
            $table->index(['section_id', 'area_id'], 'ix_aca_virtual_course_section_area');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aca_virtual_courses');
    }
};
