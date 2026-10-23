<?php

/*
|--------------------------------------------------------------------------
| Animales del juego «Memoria de animales»
|--------------------------------------------------------------------------
|
| Fuente única de verdad del juego: cada animal declara su fotografía real,
| su sonido real y los créditos de ambos. Los archivos viven en el tema del
| colegio y se sirven localmente; la partida nunca consulta servicios externos.
|
| Las fotos y los sonidos provienen de Wikimedia Commons. Cada entrada enlaza
| a la ficha del archivo, donde se puede revisar la licencia completa. Los
| audios se convirtieron a MP3 para que funcionen en todos los navegadores.
|
*/

return [
    'source' => [
        'name' => 'Wikimedia Commons',
        'url' => 'https://commons.wikimedia.org/',
    ],

    'animals' => [
        'perro' => [
            'id' => 'perro',
            'name' => 'perro',
            'article' => 'el',
            'group' => 'casa',
            'photo' => 'themes/colegio/img/juegos/animales/perro.jpg',
            'audio' => 'themes/colegio/audio/animales/perro.mp3',
            'photo_credit' => [
                'author' => 'IDS.photos',
                'license' => 'CC BY-SA 2.0',
                'license_url' => 'https://creativecommons.org/licenses/by-sa/2.0/',
                'page' => 'https://commons.wikimedia.org/wiki/File:Labrador_on_Quantock_(2175262184).jpg',
            ],
            'audio_credit' => [
                'author' => 'Amada44',
                'license' => 'CC BY-SA 3.0',
                'license_url' => 'https://creativecommons.org/licenses/by-sa/3.0/',
                'page' => 'https://commons.wikimedia.org/wiki/File:Barking_of_a_dog.ogg',
            ],
        ],

        'gato' => [
            'id' => 'gato',
            'name' => 'gato',
            'article' => 'el',
            'group' => 'casa',
            'photo' => 'themes/colegio/img/juegos/animales/gato.jpg',
            'audio' => 'themes/colegio/audio/animales/gato.mp3',
            'photo_credit' => [
                'author' => 'Alvesgaspar',
                'license' => 'CC BY-SA 3.0',
                'license_url' => 'https://creativecommons.org/licenses/by-sa/3.0/',
                'page' => 'https://commons.wikimedia.org/wiki/File:Cat_November_2010-1a.jpg',
            ],
            'audio_credit' => [
                'author' => 'freemaster2',
                'license' => 'CC0',
                'license_url' => 'https://creativecommons.org/publicdomain/zero/1.0/',
                'page' => 'https://commons.wikimedia.org/wiki/File:Meow_of_a_Siamese_cat_-_freemaster2.wav',
            ],
        ],

        'gallina' => [
            'id' => 'gallina',
            'name' => 'gallina',
            'article' => 'la',
            'group' => 'granja',
            'photo' => 'themes/colegio/img/juegos/animales/gallina.jpg',
            'audio' => 'themes/colegio/audio/animales/gallina.mp3',
            'photo_credit' => [
                'author' => 'Pete Cooper',
                'license' => 'CC BY 2.0',
                'license_url' => 'https://creativecommons.org/licenses/by/2.0/',
                'page' => 'https://commons.wikimedia.org/wiki/File:Buff_Orpington_chicken,_UK.jpg',
            ],
            'audio_credit' => [
                'author' => 'alys',
                'license' => 'Dominio público',
                'license_url' => '',
                'page' => 'https://commons.wikimedia.org/wiki/File:Chickens_demanding_food.ogg',
            ],
        ],

        'vaca' => [
            'id' => 'vaca',
            'name' => 'vaca',
            'article' => 'la',
            'group' => 'granja',
            'photo' => 'themes/colegio/img/juegos/animales/vaca.jpg',
            'audio' => 'themes/colegio/audio/animales/vaca.mp3',
            'photo_credit' => [
                'author' => 'Kim Hansen',
                'license' => 'CC BY-SA 3.0',
                'license_url' => 'https://creativecommons.org/licenses/by-sa/3.0/',
                'page' => 'https://commons.wikimedia.org/wiki/File:Cow_(Fleckvieh_breed)_Oeschinensee_Slaunger_2009-07-07.jpg',
            ],
            'audio_credit' => [
                'author' => 'MichaeltheFox8621',
                'license' => 'CC BY-SA 4.0',
                'license_url' => 'https://creativecommons.org/licenses/by-sa/4.0/',
                'page' => 'https://commons.wikimedia.org/wiki/File:Single_Cow_Moo.ogg',
            ],
        ],

        'cerdo' => [
            'id' => 'cerdo',
            'name' => 'cerdo',
            'article' => 'el',
            'group' => 'granja',
            'photo' => 'themes/colegio/img/juegos/animales/cerdo.jpg',
            'audio' => 'themes/colegio/audio/animales/cerdo.mp3',
            'photo_credit' => [
                'author' => 'kallerna',
                'license' => 'CC BY-SA 4.0',
                'license_url' => 'https://creativecommons.org/licenses/by-sa/4.0/',
                'page' => 'https://commons.wikimedia.org/wiki/File:Pig_farm_Vampula_1.jpg',
            ],
            'audio_credit' => [
                'author' => 'erdie',
                'license' => 'CC BY 3.0',
                'license_url' => 'https://creativecommons.org/licenses/by/3.0/',
                'page' => 'https://commons.wikimedia.org/wiki/File:Pig_grunt_-_Erdie.ogg',
            ],
        ],

        'oveja' => [
            'id' => 'oveja',
            'name' => 'oveja',
            'article' => 'la',
            'group' => 'granja',
            'photo' => 'themes/colegio/img/juegos/animales/oveja.jpg',
            'audio' => 'themes/colegio/audio/animales/oveja.mp3',
            'photo_credit' => [
                'author' => 'CattleGirl',
                'license' => 'Dominio público',
                'license_url' => '',
                'page' => 'https://commons.wikimedia.org/wiki/File:Romneyram1.jpg',
            ],
            'audio_credit' => [
                'author' => 'earthcalling',
                'license' => 'Dominio público',
                'license_url' => '',
                'page' => 'https://commons.wikimedia.org/wiki/File:Sheep_bleating.ogg',
            ],
        ],

        'pato' => [
            'id' => 'pato',
            'name' => 'pato',
            'article' => 'el',
            'group' => 'granja',
            'photo' => 'themes/colegio/img/juegos/animales/pato.jpg',
            'audio' => 'themes/colegio/audio/animales/pato.mp3',
            'photo_credit' => [
                'author' => 'Richard Bartz',
                'license' => 'CC BY-SA 2.5',
                'license_url' => 'https://creativecommons.org/licenses/by-sa/2.5/',
                'page' => 'https://commons.wikimedia.org/wiki/File:Anas_platyrhynchos_male_female_quadrat.jpg',
            ],
            'audio_credit' => [
                'author' => 'Jonathon Jongsma',
                'license' => 'CC BY-SA 3.0',
                'license_url' => 'https://creativecommons.org/licenses/by-sa/3.0/',
                'page' => 'https://commons.wikimedia.org/wiki/File:Anas_platyrhynchos_-_Mallard_-_XC62258.ogg',
            ],
        ],

        'caballo' => [
            'id' => 'caballo',
            'name' => 'caballo',
            'article' => 'el',
            'group' => 'granja',
            'photo' => 'themes/colegio/img/juegos/animales/caballo.jpg',
            'audio' => 'themes/colegio/audio/animales/caballo.mp3',
            'photo_credit' => [
                'author' => 'Ealdgyth',
                'license' => 'CC BY-SA 3.0',
                'license_url' => 'https://creativecommons.org/licenses/by-sa/3.0/',
                'page' => 'https://commons.wikimedia.org/wiki/File:Halterstandingshotarabianone.jpg',
            ],
            'audio_credit' => [
                'author' => 'Hü.',
                'license' => 'Dominio público',
                'license_url' => '',
                'page' => 'https://commons.wikimedia.org/wiki/File:Wiehern.ogg',
            ],
        ],

        'gallo' => [
            'id' => 'gallo',
            'name' => 'gallo',
            'article' => 'el',
            'group' => 'granja',
            'photo' => 'themes/colegio/img/juegos/animales/gallo.jpg',
            'audio' => 'themes/colegio/audio/animales/gallo.mp3',
            'photo_credit' => [
                'author' => 'Sundaram Perumal',
                'license' => 'CC BY-SA 4.0',
                'license_url' => 'https://creativecommons.org/licenses/by-sa/4.0/',
                'page' => 'https://commons.wikimedia.org/wiki/File:Looking_my_camera!.jpg',
            ],
            'audio_credit' => [
                'author' => "Filo gèn'",
                'license' => 'CC BY-SA 4.0',
                'license_url' => 'https://creativecommons.org/licenses/by-sa/4.0/',
                'page' => 'https://commons.wikimedia.org/wiki/File:Rooster_crowing.ogg',
            ],
        ],

        'leon' => [
            'id' => 'leon',
            'name' => 'león',
            'article' => 'el',
            'group' => 'selva',
            'photo' => 'themes/colegio/img/juegos/animales/leon.jpg',
            'audio' => 'themes/colegio/audio/animales/leon.mp3',
            'photo_credit' => [
                'author' => 'Giles Laurent',
                'license' => 'CC BY-SA 4.0',
                'license_url' => 'https://creativecommons.org/licenses/by-sa/4.0/',
                'page' => 'https://commons.wikimedia.org/wiki/File:020_The_lion_king_Snyggve_in_the_Serengeti_National_Park_Photo_by_Giles_Laurent.jpg',
            ],
            'audio_credit' => [
                'author' => 'த*உழவன்',
                'license' => 'Dominio público',
                'license_url' => '',
                'page' => 'https://commons.wikimedia.org/wiki/File:Lion_raring-sound1TamilNadu178.ogg',
            ],
        ],

        'elefante' => [
            'id' => 'elefante',
            'name' => 'elefante',
            'article' => 'el',
            'group' => 'selva',
            'photo' => 'themes/colegio/img/juegos/animales/elefante.jpg',
            'audio' => 'themes/colegio/audio/animales/elefante.mp3',
            'photo_credit' => [
                'author' => 'Giles Laurent',
                'license' => 'CC BY-SA 4.0',
                'license_url' => 'https://creativecommons.org/licenses/by-sa/4.0/',
                'page' => 'https://commons.wikimedia.org/wiki/File:178_Male_African_bush_elephant_in_Etosha_National_Park_Photo_by_Giles_Laurent.jpg',
            ],
            'audio_credit' => [
                'author' => 'தகவலுழவன்',
                'license' => 'CC0',
                'license_url' => 'https://creativecommons.org/publicdomain/zero/1.0/',
                'page' => 'https://commons.wikimedia.org/wiki/File:Elephant_voice_-_trumpeting.ogg',
            ],
        ],

        'mono' => [
            'id' => 'mono',
            'name' => 'mono',
            'article' => 'el',
            'group' => 'selva',
            'photo' => 'themes/colegio/img/juegos/animales/mono.jpg',
            'audio' => 'themes/colegio/audio/animales/mono.mp3',
            'photo_credit' => [
                'author' => 'David M. Jensen (Storkk)',
                'license' => 'CC BY-SA 3.0',
                'license_url' => 'https://creativecommons.org/licenses/by-sa/3.0/',
                'page' => 'https://commons.wikimedia.org/wiki/File:Capuchin_Costa_Rica.jpg',
            ],
            'audio_credit' => [
                'author' => 'Kai Medina (Mk170101)',
                'license' => 'CC BY-SA 4.0',
                'license_url' => 'https://creativecommons.org/licenses/by-sa/4.0/',
                'page' => 'https://commons.wikimedia.org/wiki/File:Brown_woolly_monkey_alarm_call.wav',
            ],
        ],

        'rana' => [
            'id' => 'rana',
            'name' => 'rana',
            'article' => 'la',
            'group' => 'bosque',
            'photo' => 'themes/colegio/img/juegos/animales/rana.jpg',
            'audio' => 'themes/colegio/audio/animales/rana.mp3',
            'photo_credit' => [
                'author' => 'Richard Bartz',
                'license' => 'CC BY-SA 2.5',
                'license_url' => 'https://creativecommons.org/licenses/by-sa/2.5/',
                'page' => 'https://commons.wikimedia.org/wiki/File:European_Common_Frog_Rana_temporaria.jpg',
            ],
            'audio_credit' => [
                'author' => 'MichaeltheFox8621',
                'license' => 'CC BY-SA 4.0',
                'license_url' => 'https://creativecommons.org/licenses/by-sa/4.0/',
                'page' => 'https://commons.wikimedia.org/wiki/File:Single_Frog_Croak.oga',
            ],
        ],

        'pajaro' => [
            'id' => 'pajaro',
            'name' => 'pájaro',
            'article' => 'el',
            'group' => 'bosque',
            'photo' => 'themes/colegio/img/juegos/animales/pajaro.jpg',
            'audio' => 'themes/colegio/audio/animales/pajaro.mp3',
            'photo_credit' => [
                'author' => 'Francis C. Franklin',
                'license' => 'CC BY-SA 3.0',
                'license_url' => 'https://creativecommons.org/licenses/by-sa/3.0/',
                'page' => 'https://commons.wikimedia.org/wiki/File:Erithacus_rubecula_with_cocked_head.jpg',
            ],
            'audio_credit' => [
                'author' => 'ezwa',
                'license' => 'Dominio público',
                'license_url' => '',
                'page' => 'https://commons.wikimedia.org/wiki/File:Birds_singing_in_garden.ogg',
            ],
        ],

        'buho' => [
            'id' => 'buho',
            'name' => 'búho',
            'article' => 'el',
            'group' => 'bosque',
            'photo' => 'themes/colegio/img/juegos/animales/buho.jpg',
            'audio' => 'themes/colegio/audio/animales/buho.mp3',
            'photo_credit' => [
                'author' => 'Greg Hume',
                'license' => 'CC BY-SA 3.0',
                'license_url' => 'https://creativecommons.org/licenses/by-sa/3.0/',
                'page' => 'https://commons.wikimedia.org/wiki/File:Bubo_virginianus_06.jpg',
            ],
            'audio_credit' => [
                'author' => 'Vianney Bajart',
                'license' => 'CC BY-SA 4.0',
                'license_url' => 'https://creativecommons.org/licenses/by-sa/4.0/',
                'page' => 'https://commons.wikimedia.org/wiki/File:Strix_aluco_male.oga',
            ],
        ],
    ],
];
