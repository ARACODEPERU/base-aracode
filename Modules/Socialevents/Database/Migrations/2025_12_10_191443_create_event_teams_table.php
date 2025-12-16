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
        Schema::create('event_teams', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique()->comment('Nombre del equipo');
            $table->string('short_name', 10)->nullable()->comment('Abreviatura (ej: REAL)');
            $table->string('ubigeo')->nullable();
            $table->string('ubigeo_description')->nullable();
            $table->string('logo_path')->nullable()->comment('Ruta al logo o escudo del equipo');
            // Campos de contacto/información general del equipo
            $table->unsignedBigInteger('manager_id')->nullable()->comment('representante o delegado del equipo');
            $table->foreign('manager_id')->references('id')->on('people')->nullOnDelete();
            $table->boolean('champion')->default(false);
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_teams');
    }
};
