<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('heal_activities', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('actor');
            $table->unsignedBigInteger('actor_person_id')->nullable();
            $table->string('actor_display', 255)->nullable();
            $table->string('activity_type', 80);
            $table->nullableMorphs('subject');
            $table->unsignedBigInteger('patient_id')->nullable();
            $table->string('patient_display', 255)->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index('activity_type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('heal_activities');
    }
};
