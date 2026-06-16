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
            // Asia Oriental
            [
                'description'       => 'Japón',
                'country_code'      => 'JP',
                'country_code_phone'=> '81',
                'image'             => 'img/banderas/japon.png',
                'icon'              => 'img/banderas/japon32x32.png',
            ],
            [
                'description'       => 'Corea del Sur',
                'country_code'      => 'KR',
                'country_code_phone'=> '82',
                'image'             => 'img/banderas/coreadelsur.png',
                'icon'              => 'img/banderas/coreadelsur32x32.png',
            ],
            [
                'description'       => 'Taiwán',
                'country_code'      => 'TW',
                'country_code_phone'=> '886',
                'image'             => 'img/banderas/taiwan.png',
                'icon'              => 'img/banderas/taiwan32x32.png',
            ],
            [
                'description'       => 'Hong Kong',
                'country_code'      => 'HK',
                'country_code_phone'=> '852',
                'image'             => 'img/banderas/hongkong.png',
                'icon'              => 'img/banderas/hongkong32x32.png',
            ],

            // Sudeste Asiático
            [
                'description'       => 'Singapur',
                'country_code'      => 'SG',
                'country_code_phone'=> '65',
                'image'             => 'img/banderas/singapur.png',
                'icon'              => 'img/banderas/singapur32x32.png',
            ],
            [
                'description'       => 'Malasia',
                'country_code'      => 'MY',
                'country_code_phone'=> '60',
                'image'             => 'img/banderas/malasia.png',
                'icon'              => 'img/banderas/malasia32x32.png',
            ],
            [
                'description'       => 'Indonesia',
                'country_code'      => 'ID',
                'country_code_phone'=> '62',
                'image'             => 'img/banderas/indonesia.png',
                'icon'              => 'img/banderas/indonesia32x32.png',
            ],
            [
                'description'       => 'Filipinas',
                'country_code'      => 'PH',
                'country_code_phone'=> '63',
                'image'             => 'img/banderas/filipinas.png',
                'icon'              => 'img/banderas/filipinas32x32.png',
            ],
            [
                'description'       => 'Tailandia',
                'country_code'      => 'TH',
                'country_code_phone'=> '66',
                'image'             => 'img/banderas/tailandia.png',
                'icon'              => 'img/banderas/tailandia32x32.png',
            ],
            [
                'description'       => 'Vietnam',
                'country_code'      => 'VN',
                'country_code_phone'=> '84',
                'image'             => 'img/banderas/vietnam.png',
                'icon'              => 'img/banderas/vietnam32x32.png',
            ],
            [
                'description'       => 'Birmania',
                'country_code'      => 'MM',
                'country_code_phone'=> '95',
                'image'             => 'img/banderas/birmania.png',
                'icon'              => 'img/banderas/birmania32x32.png',
            ],

            // Asia Meridional
            [
                'description'       => 'India',
                'country_code'      => 'IN',
                'country_code_phone'=> '91',
                'image'             => 'img/banderas/india.png',
                'icon'              => 'img/banderas/india32x32.png',
            ],
            [
                'description'       => 'Pakistán',
                'country_code'      => 'PK',
                'country_code_phone'=> '92',
                'image'             => 'img/banderas/pakistan.png',
                'icon'              => 'img/banderas/pakistan32x32.png',
            ],
            [
                'description'       => 'Bangladés',
                'country_code'      => 'BD',
                'country_code_phone'=> '880',
                'image'             => 'img/banderas/banglades.png',
                'icon'              => 'img/banderas/banglades32x32.png',
            ],
            [
                'description'       => 'Sri Lanka',
                'country_code'      => 'LK',
                'country_code_phone'=> '94',
                'image'             => 'img/banderas/srilanka.png',
                'icon'              => 'img/banderas/srilanka32x32.png',
            ],
            [
                'description'       => 'Nepal',
                'country_code'      => 'NP',
                'country_code_phone'=> '977',
                'image'             => 'img/banderas/nepal.png',
                'icon'              => 'img/banderas/nepal32x32.png',
            ],

            // Asia Occidental / Medio Oriente
            [
                'description'       => 'Emiratos Árabes Unidos',
                'country_code'      => 'AE',
                'country_code_phone'=> '971',
                'image'             => 'img/banderas/emiratosarabes.png',
                'icon'              => 'img/banderas/emiratosarabes32x32.png',
            ],
            [
                'description'       => 'Arabia Saudita',
                'country_code'      => 'SA',
                'country_code_phone'=> '966',
                'image'             => 'img/banderas/arabiasaudita.png',
                'icon'              => 'img/banderas/arabiasaudita32x32.png',
            ],
            [
                'description'       => 'Israel',
                'country_code'      => 'IL',
                'country_code_phone'=> '972',
                'image'             => 'img/banderas/israel.png',
                'icon'              => 'img/banderas/israel32x32.png',
            ],
            [
                'description'       => 'Turquía',
                'country_code'      => 'TR',
                'country_code_phone'=> '90',
                'image'             => 'img/banderas/turquia.png',
                'icon'              => 'img/banderas/turquia32x32.png',
            ],
            [
                'description'       => 'Irán',
                'country_code'      => 'IR',
                'country_code_phone'=> '98',
                'image'             => 'img/banderas/iran.png',
                'icon'              => 'img/banderas/iran32x32.png',
            ],
            [
                'description'       => 'Irak',
                'country_code'      => 'IQ',
                'country_code_phone'=> '964',
                'image'             => 'img/banderas/irak.png',
                'icon'              => 'img/banderas/irak32x32.png',
            ],
            [
                'description'       => 'Catar',
                'country_code'      => 'QA',
                'country_code_phone'=> '974',
                'image'             => 'img/banderas/catar.png',
                'icon'              => 'img/banderas/catar32x32.png',
            ],
            [
                'description'       => 'Kuwait',
                'country_code'      => 'KW',
                'country_code_phone'=> '965',
                'image'             => 'img/banderas/kuwait.png',
                'icon'              => 'img/banderas/kuwait32x32.png',
            ],
            [
                'description'       => 'Omán',
                'country_code'      => 'OM',
                'country_code_phone'=> '968',
                'image'             => 'img/banderas/oman.png',
                'icon'              => 'img/banderas/oman32x32.png',
            ],

            // Oceanía
            [
                'description'       => 'Australia',
                'country_code'      => 'AU',
                'country_code_phone'=> '61',
                'image'             => 'img/banderas/australia.png',
                'icon'              => 'img/banderas/australia32x32.png',
            ],
            [
                'description'       => 'Nueva Zelanda',
                'country_code'      => 'NZ',
                'country_code_phone'=> '64',
                'image'             => 'img/banderas/nuevazelanda.png',
                'icon'              => 'img/banderas/nuevazelanda32x32.png',
            ],

            // África (adicionales relevantes)
            [
                'description'       => 'Sudáfrica',
                'country_code'      => 'ZA',
                'country_code_phone'=> '27',
                'image'             => 'img/banderas/sudafrica.png',
                'icon'              => 'img/banderas/sudafrica32x32.png',
            ],
            [
                'description'       => 'Egipto',
                'country_code'      => 'EG',
                'country_code_phone'=> '20',
                'image'             => 'img/banderas/egipto.png',
                'icon'              => 'img/banderas/egipto32x32.png',
            ],
            [
                'description'       => 'Marruecos',
                'country_code'      => 'MA',
                'country_code_phone'=> '212',
                'image'             => 'img/banderas/marruecos.png',
                'icon'              => 'img/banderas/marruecos32x32.png',
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
            'Japón', 'Corea del Sur', 'Taiwán', 'Hong Kong',
            'Singapur', 'Malasia', 'Indonesia', 'Filipinas',
            'Tailandia', 'Vietnam', 'Birmania',
            'India', 'Pakistán', 'Bangladés', 'Sri Lanka', 'Nepal',
            'Emiratos Árabes Unidos', 'Arabia Saudita', 'Israel',
            'Turquía', 'Irán', 'Irak', 'Catar', 'Kuwait', 'Omán',
            'Australia', 'Nueva Zelanda',
            'Sudáfrica', 'Egipto', 'Marruecos',
        ];

        DB::table('countries')
            ->whereIn('description', $descriptions)
            ->delete();
    }
};
