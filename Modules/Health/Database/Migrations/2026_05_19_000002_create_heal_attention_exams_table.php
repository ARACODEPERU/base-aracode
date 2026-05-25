<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('heal_attention_exams', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('attention_id');
            $table->string('exam_type', 80);
            $table->string('name', 150)->nullable();
            $table->text('description')->nullable();
            $table->text('result')->nullable();
            $table->string('file_path')->nullable();
            $table->timestamps();

            $table->foreign('attention_id')->references('id')->on('heal_attentions')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('heal_attention_exams');
    }
};
