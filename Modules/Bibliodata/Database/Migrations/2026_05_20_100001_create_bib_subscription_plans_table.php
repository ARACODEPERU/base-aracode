<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bib_subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->enum('scope_type', ['single_book', 'limited_books', 'all_books'])->default('single_book');
            $table->unsignedInteger('max_books')->nullable();
            $table->enum('duration_type', ['monthly', 'yearly', 'lifetime'])->default('monthly');
            $table->unsignedInteger('duration_value')->nullable();
            $table->boolean('includes_ai_chatbot')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bib_subscription_plans');
    }
};
