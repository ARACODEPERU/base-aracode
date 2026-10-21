<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('aca_virtual_units')) {
            return;
        }

        Schema::create('aca_virtual_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('aca_virtual_courses')->cascadeOnDelete();
            $table->string('title', 200);
            $table->string('purpose', 500)->nullable()->comment('Propósito de la unidad didáctica');
            $table->text('significant_situation')->nullable()->comment('Situación significativa (CNEB)');
            $table->text('competencies')->nullable()->comment('Competencias');
            $table->text('capabilities')->nullable()->comment('Capacidades');
            $table->text('performances')->nullable()->comment('Desempeños esperados');
            $table->unsignedSmallInteger('duration_hours')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->index(['course_id', 'sort_order'], 'ix_aca_virtual_unit_course_sort');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aca_virtual_units');
    }
};
