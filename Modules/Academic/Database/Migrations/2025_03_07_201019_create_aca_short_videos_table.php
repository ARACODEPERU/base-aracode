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
        Schema::create('aca_short_videos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('list_id')->nullable()->comment('si pertenese a una lista de reproduccion');
            $table->string('title', 200)->nullable();
            $table->text('video')->comment('se puede guardar codigo o enlase del video');
            $table->boolean('link')->default(true)->comment('1=es un enlase del video,0 es un iframe de video');
            $table->time('duration')->comment('tiempo de duracion del video');
            $table->integer('stars')->default(0)->comment('estrellas que le dan los usuarios al video');
            $table->unsignedBigInteger('author_id')->nullable()->comment('id de persona');
            $table->unsignedBigInteger('user_id')->nullable()->comment('id de usuario que registro');
            $table->json('keywords')->nullable()->comment('palabras clave para busqueda');
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('author_id')->references('id')->on('people')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aca_short_videos');
    }
};
