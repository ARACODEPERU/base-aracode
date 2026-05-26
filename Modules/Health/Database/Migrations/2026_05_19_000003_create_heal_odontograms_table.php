<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('heal_odontograms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('attention_id');
            $table->json('teeth')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('attention_id')->references('id')->on('heal_attentions')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('heal_odontograms');
    }
};
