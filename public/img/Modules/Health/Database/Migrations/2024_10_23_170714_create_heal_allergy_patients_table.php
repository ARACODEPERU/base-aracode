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
        Schema::create('heal_allergy_patients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('allergy_id');
            $table->unsignedBigInteger('patient_id');
            $table->string('description', 500);
            $table->string('additional')->nullable();
            $table->string('additional1')->nullable();
            $table->timestamps();
            $table->foreign('patient_id')->references('id')->on('heal_patients')->onDelete('cascade');
            $table->foreign('allergy_id')->references('id')->on('heal_allergies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('heal_allergy_patients');
    }
};
