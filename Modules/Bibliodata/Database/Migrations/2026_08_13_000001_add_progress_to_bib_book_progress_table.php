<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bib_book_progress', function (Blueprint $table) {
            $table->decimal('progress', 5, 2)->nullable()->after('page_id');
            $table->unique(['user_id', 'book_id'], 'bib_book_progress_user_book_unique');
        });
    }

    public function down(): void
    {
        Schema::table('bib_book_progress', function (Blueprint $table) {
            $table->dropUnique('bib_book_progress_user_book_unique');
            $table->dropColumn('progress');
        });
    }
};
