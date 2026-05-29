<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bib_books', function (Blueprint $table) {
            $table->enum('content_structure', ['chapter_subchapter', 'level_content'])
                ->default('chapter_subchapter')
                ->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('bib_books', function (Blueprint $table) {
            $table->dropColumn('content_structure');
        });
    }
};
