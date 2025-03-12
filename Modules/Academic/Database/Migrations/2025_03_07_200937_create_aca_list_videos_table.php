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
        Schema::create('aca_list_videos', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->string('description', 500)->nullable();
            $table->unsignedBigInteger('course_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable()->comment('id de usuario que registro');
            $table->time('total_duration')->nullable()->comment('campo que se actualizara segun se vincules los videos');
            $table->unsignedBigInteger('author_id')->nullable()->comment('id de persona');
            $table->boolean('status')->default(true);
            $table->integer('total_views')->default(0);
            $table->integer('total_videos')->default(0);
            $table->integer('total_stars')->default(0);
            $table->json('keywords')->nullable()->comment('palabras clave para busqueda');
            $table->timestamps();
            $table->foreign('course_id')->references('id')->on('aca_courses')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('author_id')->references('id')->on('people')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aca_list_videos');
    }
};
