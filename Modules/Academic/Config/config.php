<?php

return [
    'name' => 'Academic',

    'openai' => [
        'api_key' => env('API_KEY_IA', env('OPENAI_API_KEY')),
        // Parametro del sistema (tabla parameters) que contiene la API Key de OpenAI.
        'api_key_parameter' => env('OPENAI_API_KEY_PARAMETER', 'P000025'),
        'model' => env('OPENAI_MODEL', 'gpt-4.1-mini'),
        'instructions' => env('OPENAI_INSTRUCTIONS', 'Responde como docente de CPA Academy. Se claro, util y cuidadoso con datos personales.'),
        'timeout' => env('OPENAI_TIMEOUT', 60),
    ],
];
