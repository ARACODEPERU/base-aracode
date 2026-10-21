<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('aca_virtual_resources')) {
            return;
        }

        Schema::create('aca_virtual_resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('aca_virtual_courses')->cascadeOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('aca_virtual_units')->cascadeOnDelete();
            $table->foreignId('lesson_id')->nullable()->constrained('aca_virtual_lessons')->cascadeOnDelete();
            $table->enum('type', ['file', 'link', 'embed'])->default('file');
            $table->string('title', 200);
            $table->text('url')->nullable();
            $table->string('path')->nullable();
            $table->string('mime_type', 120)->nullable();
            $table->unsignedInteger('size_kb')->nullable();
            $table->enum('visibility', ['all', 'students'])->default('students');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->index(['course_id', 'unit_id', 'lesson_id', 'sort_order'], 'ix_aca_virtual_resource_scope_sort');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aca_virtual_resources');
    }
};
