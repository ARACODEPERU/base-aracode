<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $countries = [
            [
                'description'       => 'Argentina',
                'country_code'      => 'AR',
                'country_code_phone'=> '54',
                'image'             => 'img/banderas/argentina.png',
                'icon'              => 'img/banderas/argentina32x32.png',
            ],
            [
                'description'       => 'Brasil',
                'country_code'      => 'BR',
                'country_code_phone'=> '55',
                'image'             => 'img/banderas/brasil.png',
                'icon'              => 'img/banderas/brasil32x32.png',
            ],
            [
                'description'       => 'Chile',
                'country_code'      => 'CL',
                'country_code_phone'=> '56',
                'image'             => 'img/banderas/chile.png',
                'icon'              => 'img/banderas/chile32x32.png',
            ],
            [
                'description'       => 'Costa Rica',
                'country_code'      => 'CR',
                'country_code_phone'=> '506',
                'image'             => 'img/banderas/costarica.png',
                'icon'              => 'img/banderas/costarica32x32.png',
            ],
            [
                'description'       => 'Cuba',
                'country_code'      => 'CU',
                'country_code_phone'=> '53',
                'image'             => 'img/banderas/cuba.png',
                'icon'              => 'img/banderas/cuba32x32.png',
            ],
            [
                'description'       => 'República Dominicana',
                'country_code'      => 'DO',
                'country_code_phone'=> '1',
                'image'             => 'img/banderas/republicadominicana.png',
                'icon'              => 'img/banderas/republicadominicana32x32.png',
            ],
            [
                'description'       => 'El Salvador',
                'country_code'      => 'SV',
                'country_code_phone'=> '503',
                'image'             => 'img/banderas/elsalvador.png',
                'icon'              => 'img/banderas/elsalvador32x32.png',
            ],
            [
                'description'       => 'Guatemala',
                'country_code'      => 'GT',
                'country_code_phone'=> '502',
                'image'             => 'img/banderas/guatemala.png',
                'icon'              => 'img/banderas/guatemala32x32.png',
            ],
            [
                'description'       => 'Haití',
                'country_code'      => 'HT',
                'country_code_phone'=> '509',
                'image'             => 'img/banderas/haiti.png',
                'icon'              => 'img/banderas/haiti32x32.png',
            ],
            [
                'description'       => 'Honduras',
                'country_code'      => 'HN',
                'country_code_phone'=> '504',
                'image'             => 'img/banderas/honduras.png',
                'icon'              => 'img/banderas/honduras32x32.png',
            ],
            [
                'description'       => 'Nicaragua',
                'country_code'      => 'NI',
                'country_code_phone'=> '505',
                'image'             => 'img/banderas/nicaragua.png',
                'icon'              => 'img/banderas/nicaragua32x32.png',
            ],
            [
                'description'       => 'Panamá',
                'country_code'      => 'PA',
                'country_code_phone'=> '507',
                'image'             => 'img/banderas/panama.png',
                'icon'              => 'img/banderas/panama32x32.png',
            ],
            [
                'description'       => 'Paraguay',
                'country_code'      => 'PY',
                'country_code_phone'=> '595',
                'image'             => 'img/banderas/paraguay.png',
                'icon'              => 'img/banderas/paraguay32x32.png',
            ],
            [
                'description'       => 'Uruguay',
                'country_code'      => 'UY',
                'country_code_phone'=> '598',
                'image'             => 'img/banderas/uruguay.png',
                'icon'              => 'img/banderas/uruguay32x32.png',
            ],
            [
                'description'       => 'Venezuela',
                'country_code'      => 'VE',
                'country_code_phone'=> '58',
                'image'             => 'img/banderas/venezuela.png',
                'icon'              => 'img/banderas/venezuela32x32.png',
            ],
        ];

        foreach ($countries as $country) {
            // Solo insertar si no existe ya un país con esa descripción
            $exists = DB::table('countries')
                ->where('description', $country['description'])
                ->exists();

            if (!$exists) {
                DB::table('countries')->insert($country + ['status' => true, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $descriptions = [
            'Argentina', 'Brasil', 'Chile', 'Costa Rica', 'Cuba',
            'República Dominicana', 'El Salvador', 'Guatemala', 'Haití',
            'Honduras', 'Nicaragua', 'Panamá', 'Paraguay', 'Uruguay', 'Venezuela',
        ];

        DB::table('countries')
            ->whereIn('description', $descriptions)
            ->delete();
    }
};
