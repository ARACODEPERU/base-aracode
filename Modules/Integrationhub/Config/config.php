<?php

return [
    'name' => 'Integrationhub',

    /*
     * Bot de Telegram (integracion "Telegram_bot").
     *
     * El token del bot vive en el parametro del sistema (tabla parameters) y
     * los endpoints viajan con el token dentro de la ruta (bot<token>/...). Los
     * nombres de los endpoints deben coincidir con los que crea la migracion
     * 2026_09_30_000011_create_telegram_bot_integration.
     */
    'telegram' => [
        // Parametro del sistema que guarda el token que entrega BotFather.
        'parameter' => env('TELEGRAM_PARAMETER', 'SC-00002'),

        // Ruta publica a la que Telegram entrega los mensajes del bot.
        'webhook_path' => env('TELEGRAM_WEBHOOK_PATH', 'api/integrationhub/telegram/webhook'),

        // Minutos de espera antes de volver a consultar getMe por el usuario del bot.
        'username_cache_minutes' => (int) env('TELEGRAM_USERNAME_CACHE_MINUTES', 1440),

        // Minutos que el bot espera el documento despues de /start antes de
        // abandonar la conversacion a medias.
        'registration_session_minutes' => (int) env('TELEGRAM_REGISTRATION_SESSION_MINUTES', 30),

        // Documentos errados que se toleran antes de cerrar la conversacion.
        'registration_max_attempts' => (int) env('TELEGRAM_REGISTRATION_MAX_ATTEMPTS', 5),

        // Minutos que el bot espera el correo/documento de una consulta (/cursos,
        // /certificados) antes de abandonar la conversacion a medias.
        'query_session_minutes' => (int) env('TELEGRAM_QUERY_SESSION_MINUTES', 30),

        // Verificaciones erradas que se toleran en una consulta antes de cerrarla.
        'query_max_attempts' => (int) env('TELEGRAM_QUERY_MAX_ATTEMPTS', 5),

        // Cursos que se listan como maximo en un mensaje (Telegram limita el
        // largo del texto); si hay mas, se indica cuantos quedaron fuera.
        'courses_max_items' => (int) env('TELEGRAM_COURSES_MAX_ITEMS', 25),

        'endpoints' => [
            'get_me' => 'telegram_get_me',
            'send_message' => 'telegram_send_message',
            'set_webhook' => 'telegram_set_webhook',
            'delete_webhook' => 'telegram_delete_webhook',
            'get_updates' => 'telegram_get_updates',
            'set_my_commands' => 'telegram_set_my_commands',
        ],
    ],
];
