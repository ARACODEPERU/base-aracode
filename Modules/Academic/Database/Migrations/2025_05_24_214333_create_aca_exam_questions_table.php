<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('aca_exam_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('exam_id');
            $table->text('description')->comment('pregunta');
            $table->integer('score')->default(1);
            $table->enum('type_answers', ['Escribir', 'Alternativas', 'Varias respuestas', 'Subir Archivo'])->comment('tipos de respuestas');
            $table->timestamps();
            $table->foreign('exam_id')->references('id')->on('aca_exams')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aca_exam_questions');
    }
};
