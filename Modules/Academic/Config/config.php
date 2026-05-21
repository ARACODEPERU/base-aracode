<?php

return [
    'name' => 'Academic',

    'openai' => [
        'api_key' => env('API_KEY_IA', env('OPENAI_API_KEY')),
        'assistant_id' => env('ASSISTANT_ID_IA', env('OPENAI_ASSISTANT_ID')),
        'model' => env('OPENAI_MODEL', 'gpt-4.1-mini'),
        'instructions' => env('OPENAI_INSTRUCTIONS', 'Responde como docente de CPA Academy. Se claro, util y cuidadoso con datos personales.'),
        'timeout' => env('OPENAI_TIMEOUT', 60),
        'max_run_attempts' => env('OPENAI_MAX_RUN_ATTEMPTS', 120),
        'run_poll_delay_ms' => env('OPENAI_RUN_POLL_DELAY_MS', 500),
    ],
];
