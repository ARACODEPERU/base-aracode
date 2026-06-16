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
            // Norteamérica
            [
                'description'       => 'Estados Unidos',
                'country_code'      => 'US',
                'country_code_phone'=> '1',
                'image'             => 'img/banderas/estadosunidos.png',
                'icon'              => 'img/banderas/estadosunidos32x32.png',
            ],
            [
                'description'       => 'Canadá',
                'country_code'      => 'CA',
                'country_code_phone'=> '1',
                'image'             => 'img/banderas/canada.png',
                'icon'              => 'img/banderas/canada32x32.png',
            ],

            // Asia
            [
                'description'       => 'China',
                'country_code'      => 'CN',
                'country_code_phone'=> '86',
                'image'             => 'img/banderas/china.png',
                'icon'              => 'img/banderas/china32x32.png',
            ],

            // Europa Occidental
            [
                'description'       => 'España',
                'country_code'      => 'ES',
                'country_code_phone'=> '34',
                'image'             => 'img/banderas/espana.png',
                'icon'              => 'img/banderas/espana32x32.png',
            ],
            [
                'description'       => 'Portugal',
                'country_code'      => 'PT',
                'country_code_phone'=> '351',
                'image'             => 'img/banderas/portugal.png',
                'icon'              => 'img/banderas/portugal32x32.png',
            ],
            [
                'description'       => 'Francia',
                'country_code'      => 'FR',
                'country_code_phone'=> '33',
                'image'             => 'img/banderas/francia.png',
                'icon'              => 'img/banderas/francia32x32.png',
            ],
            [
                'description'       => 'Italia',
                'country_code'      => 'IT',
                'country_code_phone'=> '39',
                'image'             => 'img/banderas/italia.png',
                'icon'              => 'img/banderas/italia32x32.png',
            ],
            [
                'description'       => 'Alemania',
                'country_code'      => 'DE',
                'country_code_phone'=> '49',
                'image'             => 'img/banderas/alemania.png',
                'icon'              => 'img/banderas/alemania32x32.png',
            ],
            [
                'description'       => 'Reino Unido',
                'country_code'      => 'GB',
                'country_code_phone'=> '44',
                'image'             => 'img/banderas/reinounido.png',
                'icon'              => 'img/banderas/reinounido32x32.png',
            ],
            [
                'description'       => 'Irlanda',
                'country_code'      => 'IE',
                'country_code_phone'=> '353',
                'image'             => 'img/banderas/irlanda.png',
                'icon'              => 'img/banderas/irlanda32x32.png',
            ],
            [
                'description'       => 'Países Bajos',
                'country_code'      => 'NL',
                'country_code_phone'=> '31',
                'image'             => 'img/banderas/paisesbajos.png',
                'icon'              => 'img/banderas/paisesbajos32x32.png',
            ],
            [
                'description'       => 'Bélgica',
                'country_code'      => 'BE',
                'country_code_phone'=> '32',
                'image'             => 'img/banderas/belgica.png',
                'icon'              => 'img/banderas/belgica32x32.png',
            ],
            [
                'description'       => 'Suiza',
                'country_code'      => 'CH',
                'country_code_phone'=> '41',
                'image'             => 'img/banderas/suiza.png',
                'icon'              => 'img/banderas/suiza32x32.png',
            ],
            [
                'description'       => 'Austria',
                'country_code'      => 'AT',
                'country_code_phone'=> '43',
                'image'             => 'img/banderas/austria.png',
                'icon'              => 'img/banderas/austria32x32.png',
            ],

            // Europa Nórdica
            [
                'description'       => 'Suecia',
                'country_code'      => 'SE',
                'country_code_phone'=> '46',
                'image'             => 'img/banderas/suecia.png',
                'icon'              => 'img/banderas/suecia32x32.png',
            ],
            [
                'description'       => 'Noruega',
                'country_code'      => 'NO',
                'country_code_phone'=> '47',
                'image'             => 'img/banderas/noruega.png',
                'icon'              => 'img/banderas/noruega32x32.png',
            ],
            [
                'description'       => 'Dinamarca',
                'country_code'      => 'DK',
                'country_code_phone'=> '45',
                'image'             => 'img/banderas/dinamarca.png',
                'icon'              => 'img/banderas/dinamarca32x32.png',
            ],
            [
                'description'       => 'Finlandia',
                'country_code'      => 'FI',
                'country_code_phone'=> '358',
                'image'             => 'img/banderas/finlandia.png',
                'icon'              => 'img/banderas/finlandia32x32.png',
            ],

            // Europa del Sur
            [
                'description'       => 'Grecia',
                'country_code'      => 'GR',
                'country_code_phone'=> '30',
                'image'             => 'img/banderas/grecia.png',
                'icon'              => 'img/banderas/grecia32x32.png',
            ],

            // Europa del Este
            [
                'description'       => 'Rusia',
                'country_code'      => 'RU',
                'country_code_phone'=> '7',
                'image'             => 'img/banderas/rusia.png',
                'icon'              => 'img/banderas/rusia32x32.png',
            ],
            [
                'description'       => 'Polonia',
                'country_code'      => 'PL',
                'country_code_phone'=> '48',
                'image'             => 'img/banderas/polonia.png',
                'icon'              => 'img/banderas/polonia32x32.png',
            ],
            [
                'description'       => 'República Checa',
                'country_code'      => 'CZ',
                'country_code_phone'=> '420',
                'image'             => 'img/banderas/republicacheca.png',
                'icon'              => 'img/banderas/republicacheca32x32.png',
            ],
            [
                'description'       => 'Hungría',
                'country_code'      => 'HU',
                'country_code_phone'=> '36',
                'image'             => 'img/banderas/hungria.png',
                'icon'              => 'img/banderas/hungria32x32.png',
            ],
            [
                'description'       => 'Rumania',
                'country_code'      => 'RO',
                'country_code_phone'=> '40',
                'image'             => 'img/banderas/rumania.png',
                'icon'              => 'img/banderas/rumania32x32.png',
            ],
            [
                'description'       => 'Ucrania',
                'country_code'      => 'UA',
                'country_code_phone'=> '380',
                'image'             => 'img/banderas/ucrania.png',
                'icon'              => 'img/banderas/ucrania32x32.png',
            ],
        ];

        foreach ($countries as $country) {
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
            'Estados Unidos', 'Canadá', 'China',
            'España', 'Portugal', 'Francia', 'Italia', 'Alemania',
            'Reino Unido', 'Irlanda', 'Países Bajos', 'Bélgica',
            'Suiza', 'Austria', 'Suecia', 'Noruega', 'Dinamarca',
            'Finlandia', 'Grecia', 'Rusia', 'Polonia',
            'República Checa', 'Hungría', 'Rumania', 'Ucrania',
        ];

        DB::table('countries')
            ->whereIn('description', $descriptions)
            ->delete();
    }
};
