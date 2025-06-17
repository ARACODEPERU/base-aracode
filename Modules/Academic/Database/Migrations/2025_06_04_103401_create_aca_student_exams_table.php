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
        Schema::create('aca_student_exams', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('exam_id');
            $table->unsignedBigInteger('student_id');
            $table->datetime('date_start')->nullable();
            $table->datetime('date_end')->nullable();
            $table->string('punctuation',2)->nullable();
            $table->string('status',10)->default('terminado')->comment('terminado,pendiente,calificado');
            $table->json('details')->nullable();
            $table->timestamps();
            $table->foreign('exam_id')->references('id')->on('aca_exams')->onDelete('cascade');
            $table->foreign('student_id')->references('id')->on('aca_students')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aca_student_exams');
    }
};
