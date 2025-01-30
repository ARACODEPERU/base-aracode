<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('industries', function (Blueprint $table) {
            $table->id();
            $table->string('description', 200);
            $table->string('icon', 50)->nullable();
            $table->timestamps();
        });

        DB::table('industries')->insert([
            ['description' => 'Tecnología y Software'],
            ['description' => 'Telecomunicaciones'],
            ['description' => 'Educación'],
            ['description' => 'Salud y Farmacéutica'],
            ['description' => 'Financiera y Banca'],
            ['description' => 'Agricultura y Ganadería'],
            ['description' => 'Construcción'],
            ['description' => 'Transporte y Logística'],
            ['description' => 'Retail y Comercio'],
            ['description' => 'Entretenimiento y Medios'],
            ['description' => 'Alimentos y Bebidas'],
            ['description' => 'Manufactura'],
            ['description' => 'Biotecnología'],
            ['description' => 'Energías Renovables'],
            ['description' => 'Textil y Moda'],
            ['description' => 'Química'],
            ['description' => 'Seguros'],
            ['description' => 'Consultoría y Servicios Profesionales'],
            ['description' => 'Turismo y Viajes'],
            ['description' => 'Aeroespacial y Defensa'],
            ['description' => 'Otros'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('industries');
    }
};
