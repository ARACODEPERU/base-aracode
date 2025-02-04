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
        Schema::create('aca_brochures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('course_id');
            $table->binary('resolution')->nullable();
            $table->binary('presentation')->nullable();
            $table->binary('benefits')->nullable();
            $table->binary('frequent_questions')->nullable();
            $table->string('path_file')->nullable();
            $table->timestamps();
            $table->foreign('course_id')->references('id')->on('aca_courses')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aca_brochures');
    }
};
