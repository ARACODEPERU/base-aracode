<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_articles', function (Blueprint $table) {
            $table->foreign('category_id', 'blog_article_category_id_fk')
                ->references('id')
                ->on('blog_categories')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('blog_articles', function (Blueprint $table) {
            $table->dropForeign('blog_article_category_id_fk');
        });
    }
};
