<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('aca_virtual_lessons')) {
            return;
        }

        Schema::create('aca_virtual_lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('aca_virtual_units')->cascadeOnDelete();
            $table->string('title', 200);
            $table->text('purpose')->nullable()->comment('Propósito de la sesión');
            $table->longText('intro_activity')->nullable()->comment('Inicio: propósitos, saberes previos');
            $table->longText('development')->nullable()->comment('Desarrollo: gestión y acompañamiento');
            $table->longText('closure')->nullable()->comment('Cierre: socialización');
            $table->longText('metacognition')->nullable()->comment('Metacognición');
            $table->longText('evaluation')->nullable()->comment('Evaluación');
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->index(['unit_id', 'sort_order'], 'ix_aca_virtual_lesson_unit_sort');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aca_virtual_lessons');
    }
};
