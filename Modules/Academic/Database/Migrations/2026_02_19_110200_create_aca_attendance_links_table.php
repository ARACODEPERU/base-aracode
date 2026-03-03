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
        Schema::create('aca_attendance_links', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('content_id')->nullable();
            $table->unsignedBigInteger('course_id')->nullable();
            $table->string('link_code', 20)->unique();
            $table->string('verification_code', 50)->nullable();
            $table->dateTime('valid_from');
            $table->dateTime('valid_until');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('content_id')->references('id')->on('aca_contents')->onDelete('cascade');
            $table->foreign('course_id')->references('id')->on('aca_courses')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aca_attendance_links');
    }
};
