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
            // África Occidental
            [
                'description'       => 'Nigeria',
                'country_code'      => 'NG',
                'country_code_phone'=> '234',
                'image'             => 'img/banderas/nigeria.png',
                'icon'              => 'img/banderas/nigeria32x32.png',
            ],
            [
                'description'       => 'Ghana',
                'country_code'      => 'GH',
                'country_code_phone'=> '233',
                'image'             => 'img/banderas/ghana.png',
                'icon'              => 'img/banderas/ghana32x32.png',
            ],
            [
                'description'       => 'Costa de Marfil',
                'country_code'      => 'CI',
                'country_code_phone'=> '225',
                'image'             => 'img/banderas/costademarfil.png',
                'icon'              => 'img/banderas/costademarfil32x32.png',
            ],
            [
                'description'       => 'Senegal',
                'country_code'      => 'SN',
                'country_code_phone'=> '221',
                'image'             => 'img/banderas/senegal.png',
                'icon'              => 'img/banderas/senegal32x32.png',
            ],
            [
                'description'       => 'Mali',
                'country_code'      => 'ML',
                'country_code_phone'=> '223',
                'image'             => 'img/banderas/mali.png',
                'icon'              => 'img/banderas/mali32x32.png',
            ],
            [
                'description'       => 'Burkina Faso',
                'country_code'      => 'BF',
                'country_code_phone'=> '226',
                'image'             => 'img/banderas/burkinafaso.png',
                'icon'              => 'img/banderas/burkinafaso32x32.png',
            ],
            [
                'description'       => 'Níger',
                'country_code'      => 'NE',
                'country_code_phone'=> '227',
                'image'             => 'img/banderas/niger.png',
                'icon'              => 'img/banderas/niger32x32.png',
            ],
            [
                'description'       => 'Benín',
                'country_code'      => 'BJ',
                'country_code_phone'=> '229',
                'image'             => 'img/banderas/benin.png',
                'icon'              => 'img/banderas/benin32x32.png',
            ],
            [
                'description'       => 'Togo',
                'country_code'      => 'TG',
                'country_code_phone'=> '228',
                'image'             => 'img/banderas/togo.png',
                'icon'              => 'img/banderas/togo32x32.png',
            ],
            [
                'description'       => 'Sierra Leona',
                'country_code'      => 'SL',
                'country_code_phone'=> '232',
                'image'             => 'img/banderas/sierraleona.png',
                'icon'              => 'img/banderas/sierraleona32x32.png',
            ],
            [
                'description'       => 'Liberia',
                'country_code'      => 'LR',
                'country_code_phone'=> '231',
                'image'             => 'img/banderas/liberia.png',
                'icon'              => 'img/banderas/liberia32x32.png',
            ],
            [
                'description'       => 'Mauritania',
                'country_code'      => 'MR',
                'country_code_phone'=> '222',
                'image'             => 'img/banderas/mauritania.png',
                'icon'              => 'img/banderas/mauritania32x32.png',
            ],
            [
                'description'       => 'Guinea',
                'country_code'      => 'GN',
                'country_code_phone'=> '224',
                'image'             => 'img/banderas/guinea.png',
                'icon'              => 'img/banderas/guinea32x32.png',
            ],

            // África Oriental
            [
                'description'       => 'Kenia',
                'country_code'      => 'KE',
                'country_code_phone'=> '254',
                'image'             => 'img/banderas/kenia.png',
                'icon'              => 'img/banderas/kenia32x32.png',
            ],
            [
                'description'       => 'Etiopía',
                'country_code'      => 'ET',
                'country_code_phone'=> '251',
                'image'             => 'img/banderas/etiopia.png',
                'icon'              => 'img/banderas/etiopia32x32.png',
            ],
            [
                'description'       => 'Tanzania',
                'country_code'      => 'TZ',
                'country_code_phone'=> '255',
                'image'             => 'img/banderas/tanzania.png',
                'icon'              => 'img/banderas/tanzania32x32.png',
            ],
            [
                'description'       => 'Uganda',
                'country_code'      => 'UG',
                'country_code_phone'=> '256',
                'image'             => 'img/banderas/uganda.png',
                'icon'              => 'img/banderas/uganda32x32.png',
            ],
            [
                'description'       => 'Somalia',
                'country_code'      => 'SO',
                'country_code_phone'=> '252',
                'image'             => 'img/banderas/somalia.png',
                'icon'              => 'img/banderas/somalia32x32.png',
            ],
            [
                'description'       => 'Ruanda',
                'country_code'      => 'RW',
                'country_code_phone'=> '250',
                'image'             => 'img/banderas/ruanda.png',
                'icon'              => 'img/banderas/ruanda32x32.png',
            ],
            [
                'description'       => 'Burundi',
                'country_code'      => 'BI',
                'country_code_phone'=> '257',
                'image'             => 'img/banderas/burundi.png',
                'icon'              => 'img/banderas/burundi32x32.png',
            ],
            [
                'description'       => 'Sudán del Sur',
                'country_code'      => 'SS',
                'country_code_phone'=> '211',
                'image'             => 'img/banderas/sudandelsur.png',
                'icon'              => 'img/banderas/sudandelsur32x32.png',
            ],
            [
                'description'       => 'Yibuti',
                'country_code'      => 'DJ',
                'country_code_phone'=> '253',
                'image'             => 'img/banderas/yibuti.png',
                'icon'              => 'img/banderas/yibuti32x32.png',
            ],

            // África Central
            [
                'description'       => 'Angola',
                'country_code'      => 'AO',
                'country_code_phone'=> '244',
                'image'             => 'img/banderas/angola.png',
                'icon'              => 'img/banderas/angola32x32.png',
            ],
            [
                'description'       => 'Camerún',
                'country_code'      => 'CM',
                'country_code_phone'=> '237',
                'image'             => 'img/banderas/camerun.png',
                'icon'              => 'img/banderas/camerun32x32.png',
            ],
            [
                'description'       => 'República Democrática del Congo',
                'country_code'      => 'CD',
                'country_code_phone'=> '243',
                'image'             => 'img/banderas/republicademocraticacongo.png',
                'icon'              => 'img/banderas/republicademocraticacongo32x32.png',
            ],
            [
                'description'       => 'Congo',
                'country_code'      => 'CG',
                'country_code_phone'=> '242',
                'image'             => 'img/banderas/congo.png',
                'icon'              => 'img/banderas/congo32x32.png',
            ],
            [
                'description'       => 'Gabón',
                'country_code'      => 'GA',
                'country_code_phone'=> '241',
                'image'             => 'img/banderas/gabon.png',
                'icon'              => 'img/banderas/gabon32x32.png',
            ],
            [
                'description'       => 'Chad',
                'country_code'      => 'TD',
                'country_code_phone'=> '235',
                'image'             => 'img/banderas/chad.png',
                'icon'              => 'img/banderas/chad32x32.png',
            ],
            [
                'description'       => 'Guinea Ecuatorial',
                'country_code'      => 'GQ',
                'country_code_phone'=> '240',
                'image'             => 'img/banderas/guineaecuatorial.png',
                'icon'              => 'img/banderas/guineaecuatorial32x32.png',
            ],

            // África Austral
            [
                'description'       => 'Mozambique',
                'country_code'      => 'MZ',
                'country_code_phone'=> '258',
                'image'             => 'img/banderas/mozambique.png',
                'icon'              => 'img/banderas/mozambique32x32.png',
            ],
            [
                'description'       => 'Zambia',
                'country_code'      => 'ZM',
                'country_code_phone'=> '260',
                'image'             => 'img/banderas/zambia.png',
                'icon'              => 'img/banderas/zambia32x32.png',
            ],
            [
                'description'       => 'Zimbabue',
                'country_code'      => 'ZW',
                'country_code_phone'=> '263',
                'image'             => 'img/banderas/zimbabue.png',
                'icon'              => 'img/banderas/zimbabue32x32.png',
            ],
            [
                'description'       => 'Malaui',
                'country_code'      => 'MW',
                'country_code_phone'=> '265',
                'image'             => 'img/banderas/malaui.png',
                'icon'              => 'img/banderas/malaui32x32.png',
            ],
            [
                'description'       => 'Madagascar',
                'country_code'      => 'MG',
                'country_code_phone'=> '261',
                'image'             => 'img/banderas/madagascar.png',
                'icon'              => 'img/banderas/madagascar32x32.png',
            ],
            [
                'description'       => 'Botsuana',
                'country_code'      => 'BW',
                'country_code_phone'=> '267',
                'image'             => 'img/banderas/botsuana.png',
                'icon'              => 'img/banderas/botsuana32x32.png',
            ],
            [
                'description'       => 'Namibia',
                'country_code'      => 'NA',
                'country_code_phone'=> '264',
                'image'             => 'img/banderas/namibia.png',
                'icon'              => 'img/banderas/namibia32x32.png',
            ],
            [
                'description'       => 'Mauricio',
                'country_code'      => 'MU',
                'country_code_phone'=> '230',
                'image'             => 'img/banderas/mauricio.png',
                'icon'              => 'img/banderas/mauricio32x32.png',
            ],
            [
                'description'       => 'Suazilandia',
                'country_code'      => 'SZ',
                'country_code_phone'=> '268',
                'image'             => 'img/banderas/suazilandia.png',
                'icon'              => 'img/banderas/suazilandia32x32.png',
            ],
            [
                'description'       => 'Lesoto',
                'country_code'      => 'LS',
                'country_code_phone'=> '266',
                'image'             => 'img/banderas/lesoto.png',
                'icon'              => 'img/banderas/lesoto32x32.png',
            ],

            // África del Norte (adicionales)
            [
                'description'       => 'Argelia',
                'country_code'      => 'DZ',
                'country_code_phone'=> '213',
                'image'             => 'img/banderas/argelia.png',
                'icon'              => 'img/banderas/argelia32x32.png',
            ],
            [
                'description'       => 'Túnez',
                'country_code'      => 'TN',
                'country_code_phone'=> '216',
                'image'             => 'img/banderas/tunez.png',
                'icon'              => 'img/banderas/tunez32x32.png',
            ],
            [
                'description'       => 'Libia',
                'country_code'      => 'LY',
                'country_code_phone'=> '218',
                'image'             => 'img/banderas/libia.png',
                'icon'              => 'img/banderas/libia32x32.png',
            ],
            [
                'description'       => 'Sudán',
                'country_code'      => 'SD',
                'country_code_phone'=> '249',
                'image'             => 'img/banderas/sudan.png',
                'icon'              => 'img/banderas/sudan32x32.png',
            ],
            [
                'description'       => 'Eritrea',
                'country_code'      => 'ER',
                'country_code_phone'=> '291',
                'image'             => 'img/banderas/eritrea.png',
                'icon'              => 'img/banderas/eritrea32x32.png',
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
            'Nigeria', 'Ghana', 'Costa de Marfil', 'Senegal', 'Mali',
            'Burkina Faso', 'Níger', 'Benín', 'Togo', 'Sierra Leona',
            'Liberia', 'Mauritania', 'Guinea',
            'Kenia', 'Etiopía', 'Tanzania', 'Uganda', 'Somalia',
            'Ruanda', 'Burundi', 'Sudán del Sur', 'Yibuti',
            'Angola', 'Camerún', 'República Democrática del Congo', 'Congo',
            'Gabón', 'Chad', 'Guinea Ecuatorial',
            'Mozambique', 'Zambia', 'Zimbabue', 'Malaui', 'Madagascar',
            'Botsuana', 'Namibia', 'Mauricio', 'Suazilandia', 'Lesoto',
            'Argelia', 'Túnez', 'Libia', 'Sudán', 'Eritrea',
        ];

        DB::table('countries')
            ->whereIn('description', $descriptions)
            ->delete();
    }
};
