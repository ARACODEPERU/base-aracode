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

        Schema::create('bib_books', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('code_name')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('author_id')->constrained('people')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('bib_categories')->onDelete('cascade');
            $table->string('cover_image')->nullable();
            $table->string('file_path')->nullable(); // PDF, ePub, etc.
            $table->string('isbn')->nullable();
            $table->integer('pages')->nullable();
            $table->enum('status', ['available', 'restricted', 'archived'])->default('available');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bib_books');
    }
};
