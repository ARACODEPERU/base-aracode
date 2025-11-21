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
        Schema::create('bib_book_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('bib_book_sections')->onDelete('cascade');
            $table->integer('page_number')->nullable();
            $table->longText('content')->nullable(); // Texto, HTML, Markdown o JSON
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bib_book_pages');
    }
};
