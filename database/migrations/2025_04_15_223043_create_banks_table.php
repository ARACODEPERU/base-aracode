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
        Schema::create('banks', function (Blueprint $table) {
            $table->id();
            $table->string('image')->nullable();
            $table->string('short_name');
            $table->string('full_name');
            $table->string('office_telephone')->nullable();
            $table->string('office_email')->nullable();
            $table->string('office_address')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        DB::table('banks')->insert([
            // Bancos
            [
                'image'       => '/img/bancos/bcp.png',
                'short_name'  => 'BCP',
                'full_name'   => 'Banco de Crédito del Perú',
                'status'      => true,
            ],
            [
                'image'       => '/img/bancos/bbva.png',
                'short_name'  => 'BBVA',
                'full_name'   => 'BBVA Perú',
                'status'      => true,
            ],
            [
                'image'       => '/img/bancos/interbank.png',
                'short_name'  => 'INTERBANK',
                'full_name'   => 'Interbank Perú',
                'status'      => true,
            ],
            [
                'image'       => '/img/bancos/scotiabank.png',
                'short_name'  => 'SCOTIABANK',
                'full_name'   => 'Scotiabank Perú',
                'status'      => true,
            ],
            [
                'image'       => '/img/bancos/santander.jpg',
                'short_name'  => 'SANTANDER',
                'full_name'   => 'Banco Santander Perú',
                'status'      => true,
            ],
            [
                'image'       => '/img/bancos/banbif.png',
                'short_name'  => 'BANBIF',
                'full_name'   => 'Banco Interamericano de Finanzas',
                'status'      => true,
            ],
            [
                'image'       => '/img/bancos/bcperu.png',
                'short_name'  => 'BC',
                'full_name'   => 'Banco de la Nación del Perú',
                'status'      => true,
            ],
            [
                'image'       => '/img/bancos/banco-pichincha.png',
                'short_name'  => 'PICHINCHA',
                'full_name'   => 'Banco Pichincha',
                'status'      => true,
            ],
            [
                'image'       => '/img/bancos/compartamos.png',
                'short_name'  => 'COMPARTAMOS',
                'full_name'   => 'Compartamos Banco',
                'status'      => true,
            ],
            [
                'image'       => '/img/bancos/mibanco.png',
                'short_name'  => 'MIBANCO',
                'full_name'   => 'Banco de la Microempresa',
                'status'      => true,
            ],
            [
                'image'       => '/img/bancos/alfin.png',
                'short_name'  => 'ALFIN',
                'full_name'   => 'Alfin Banco',
                'status'      => true,
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banks');
    }
};
