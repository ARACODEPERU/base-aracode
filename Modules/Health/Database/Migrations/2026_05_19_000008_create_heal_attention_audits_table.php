<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('heal_attention_audits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('attention_id')->nullable();
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->unsignedBigInteger('actor_doctor_id')->nullable();
            $table->unsignedBigInteger('affected_doctor_id')->nullable();
            $table->string('event', 80);
            $table->json('before_data')->nullable();
            $table->json('after_data')->nullable();
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('heal_attention_audits');
    }
};
