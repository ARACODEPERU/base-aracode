<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aca_student_attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('attendance_link_id')->nullable();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('content_id')->nullable();
            $table->unsignedBigInteger('user_edit_id')->nullable()->comment('Administrador que modifico la asistencia');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('device_type', 20)->nullable();
            $table->string('browser', 50)->nullable();
            $table->string('platform', 50)->nullable();
            $table->text('observation')->nullable();
            $table->timestamp('registered_at');
            $table->timestamps();

            $table->foreign('attendance_link_id')->references('id')->on('aca_attendance_links')->onDelete('set null');
            $table->foreign('student_id')->references('id')->on('aca_students')->onDelete('cascade');
            $table->foreign('course_id')->references('id')->on('aca_courses')->onDelete('cascade');
            $table->foreign('content_id')->references('id')->on('aca_contents')->onDelete('set null');
            $table->foreign('user_edit_id')->references('id')->on('users')->onDelete('set null');
            $table->unique(['content_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aca_student_attendances');
    }
};
