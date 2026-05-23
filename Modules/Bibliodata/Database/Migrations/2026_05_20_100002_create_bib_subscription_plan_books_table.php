<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bib_subscription_plan_books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('bib_subscription_plans')->cascadeOnDelete();
            $table->foreignId('book_id')->constrained('bib_books')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['plan_id', 'book_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bib_subscription_plan_books');
    }
};
