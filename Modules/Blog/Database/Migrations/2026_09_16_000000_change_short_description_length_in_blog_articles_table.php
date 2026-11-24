<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_articles', function (Blueprint $table) {
            $table->string('short_description', 1000)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('blog_articles', function (Blueprint $table) {
            $table->string('short_description', 255)->nullable()->change();
        });
    }
};
