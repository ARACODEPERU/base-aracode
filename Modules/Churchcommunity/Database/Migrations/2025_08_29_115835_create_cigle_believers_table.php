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
        Schema::create('cigle_believers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sede_id')->constrained('sedes')->onDelete('cascade');
            $table->foreignId('member_id')->constrained('cigle_members')->onDelete('cascade');
            $table->foreignId('person_id')->constrained('people')->onDelete('cascade');
            $table->enum('accept_Christ', ['SI','NO','Reconciliación'])->nullable();
            $table->foreignId('schedule_id')->constrained('cigle_schedules')->onDelete('cascade')->commet('horario');
            $table->string('name_red')->nullable();
            $table->text('prayer_reason')->nullable();
            $table->foreignId('evangelization_id')->constrained('cigle_place_evangelizations')->onDelete('cascade')->commet('lugar de evangelizacion donde se gano');
            $table->json('well_known_place')->commet('como se entero de la iglesia');
            $table->unsignedBigInteger('contact_person_id')->nullable();
            $table->string('contact_full_names', 300)->nullable();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->commet('usuario que registra');
            $table->timestamps();
            $table->foreign('contact_person_id')->references('id')->on('people')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cigle_believers');
    }
};
