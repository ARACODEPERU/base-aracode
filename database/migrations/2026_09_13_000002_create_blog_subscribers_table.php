<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email');
            $table->enum('status', ['active', 'unsubscribed'])->default('active');
            $table->string('source')->nullable()->default('blog');
            $table->timestamps();
            
            $table->unique('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_subscribers');
    }
};
