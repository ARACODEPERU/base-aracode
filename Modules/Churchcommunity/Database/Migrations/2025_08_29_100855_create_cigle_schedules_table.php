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
        Schema::create('cigle_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('description')->comment('descripcion del horario');
            $table->time('hour_from')->nullable()->comment('hora de inicio');
            $table->time('hour_to')->nullable()->comment('hora de hasta');
            $table->timestamps();
        });

        DB::table('cigle_schedules')->insert([
            ['description' => 'Primer Servicio'],
            ['description' => 'Segundo Servicio'],
            ['description' => 'Tercer Servicio'],
            ['description' => 'Evangelismo'],
            ['description' => 'Noche de Avivamiento'],
            ['description' => 'CDP']
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cigle_schedules');
    }
};
