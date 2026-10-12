<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agenda de Google Calendar de cada doctor.
     *
     * Cada doctor puede enlazar SU propia cuenta de Google: sus citas se
     * escriben en su calendario y no en la agenda principal del consultorio.
     * El refresh token se guarda cifrado (cast `encrypted` del modelo) y nunca
     * sale hacia la pantalla.
     *
     * `calendar_id` es el identificador real del calendario de esa cuenta (su
     * correo) y es unico: dos doctores no pueden compartir el mismo calendario.
     */
    public function up(): void
    {
        Schema::create('heal_doctor_google_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('doctor_id')->unique();
            $table->string('calendar_id', 190)->unique();
            $table->text('refresh_token');
            $table->string('account_email', 190)->nullable();
            $table->string('account_name', 190)->nullable();
            $table->string('account_picture', 500)->nullable();
            $table->unsignedBigInteger('connected_by_user_id')->nullable();
            $table->timestamp('connected_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_sync_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->foreign('doctor_id')->references('id')->on('heal_doctors')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('heal_doctor_google_accounts');
    }
};
