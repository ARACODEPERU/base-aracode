<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('heal_attentions', function (Blueprint $table) {
            $table->id();
            $table->dateTime('attention_at');
            $table->string('service_type', 50)->default('general');
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('history_id');
            $table->unsignedBigInteger('user_id');
            $table->text('patient_story')->nullable();
            $table->text('doctor_observation')->nullable();
            $table->text('clinical_findings')->nullable();
            $table->text('diagnosis')->nullable();
            $table->text('treatment_plan')->nullable();
            $table->text('observations')->nullable();
            $table->unsignedSmallInteger('blood_pressure_systolic')->nullable();
            $table->unsignedSmallInteger('blood_pressure_diastolic')->nullable();
            $table->unsignedSmallInteger('heart_rate')->nullable();
            $table->unsignedSmallInteger('respiratory_rate')->nullable();
            $table->decimal('height', 5, 2)->nullable();
            $table->decimal('weight', 5, 2)->nullable();
            $table->decimal('body_mass_index', 5, 2)->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('heal_patients')->onDelete('cascade');
            $table->foreign('doctor_id')->references('id')->on('heal_doctors')->onDelete('cascade');
            $table->foreign('history_id')->references('id')->on('heal_histories')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('heal_attentions');
    }
};
