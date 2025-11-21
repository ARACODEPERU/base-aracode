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
        Schema::create('bib_book_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained('bib_books')->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('bib_book_sections')->onDelete('cascade');
            $table->string('title'); // Ejemplo: "Capítulo 1: Introducción"
            $table->integer('order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bib_book_sections');
    }
};
