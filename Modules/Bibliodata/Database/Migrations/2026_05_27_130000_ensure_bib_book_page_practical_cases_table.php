<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bib_book_page_practical_cases') || ! Schema::hasTable('bib_book_pages')) {
            return;
        }

        Schema::create('bib_book_page_practical_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained('bib_book_pages')->cascadeOnDelete();
            $table->string('title');
            $table->string('type', 20);
            $table->longText('content_html')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->string('file_mime')->nullable();
            $table->unsignedInteger('sort_order')->default(1);
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->index(['page_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        // Reparación idempotente; no revertir.
    }
};
