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
        Schema::create('cigle_place_evangelizations', function (Blueprint $table) {
            $table->id();
            $table->string('description');
            $table->timestamps();
        });

        DB::table('cigle_place_evangelizations')->insert([
            ['description' => 'El Altar'],
            ['description' => 'Evangelismo en la Calle'],
            ['description' => 'Casa de Paz'],
            ['description' => 'Cruzada Evangelística'],
            ['description' => 'TMC'],
            ['description' => 'Evento']
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cigle_place_evangelizations');
    }
};
