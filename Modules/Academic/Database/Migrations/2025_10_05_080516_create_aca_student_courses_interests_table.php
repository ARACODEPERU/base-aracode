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
        Schema::create('aca_student_courses_interests', function (Blueprint $table) {
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('course_id');
            $table->tinyInteger('status')->default(0)
                ->comment('0=pendiente,1=atendido,2=se concreto la matricula');
            $table->timestamps();

            $table->primary(['student_id', 'course_id']); // 🔹 clave compuesta

            $table->foreign('student_id')
                ->references('id')
                ->on('aca_students')
                ->onDelete('cascade');

            $table->foreign('course_id')
                ->references('id')
                ->on('aca_courses')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aca_student_courses_interests');
    }
};
