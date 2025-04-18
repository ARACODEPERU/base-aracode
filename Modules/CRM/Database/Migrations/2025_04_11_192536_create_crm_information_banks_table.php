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
        Schema::create('crm_information_banks', function (Blueprint $table) {
            $table->id();
            $table->binary('question_text');
            $table->binary('response_text')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->integer('likes_count');
            $table->integer('shared_count');
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crm_information_banks');
    }
};
