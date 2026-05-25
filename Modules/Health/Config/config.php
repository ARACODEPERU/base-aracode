<?php

return [
    'name' => 'Health',

    /*
    |--------------------------------------------------------------------------
    | Tipos de servicio / especialidades de atención
    |--------------------------------------------------------------------------
    | Valores usados tanto en validación como en frontend.
    | Los labels se definen en resources/js/Components/Health/healthOptions.js
    */
    'attention_service_types' => [
        'general',
        'medicina_general',
        'medicina_interna',
        'pediatria',
        'ginecologia',
        'cardiologia',
        'dermatologia',
        'traumatologia',
        'neurologia',
        'oftalmologia',
        'otorrinolaringologia',
        'gastroenterologia',
        'endocrinologia',
        'urologia',
        'psicologia',
        'nutricion',
        'dental',
        'odontologia_general',
        'ortodoncia',
        'endodoncia',
        'periodoncia',
        'rehabilitacion_oral',
        'cirugia_bucal',
        'odontopediatria',
        'implantologia',
    ],

    'dental_service_types' => [
        'dental',
        'odontologia_general',
        'ortodoncia',
        'endodoncia',
        'periodoncia',
        'rehabilitacion_oral',
        'cirugia_bucal',
        'odontopediatria',
        'implantologia',
    ],
];
