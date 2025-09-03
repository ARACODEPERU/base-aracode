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
        Schema::create('sedes', function (Blueprint $table) {
            $table->id();
            $table->string('description', 255);
            $table->text('map_script')->nullable()->comment('codigo iframe del mapa');
            $table->string('address', 400)->nullable()->comment('direccion de la sede');
            $table->unsignedBigInteger('person_id')->nullable()->comment('persona responsable');
            $table->string('ubigeo')->nullable()->comment('codigo de la ciudad');
            $table->string('ubigeo_description')->nullable()->comment('si no tiene codigo puede escribir solo el nombre de la ciudad');
            $table->unsignedBigInteger('country_id')->nullable()->comment('identificador del pais');
            $table->string('postal_code', 10)->nullable()->comment('en caso de ser del extranjero');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sedes');
    }
};
