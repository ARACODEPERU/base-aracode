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

    /*
     * Notificaciones masivas de cursos (SMS via Vonage, WhatsApp y Telegram por
     * Integrationhub) enviadas desde la pestana Notificaciones del Academico.
     */
    'notifications' => [
        // Milisegundos entre envios: evita saturar el servidor y las APIs externas.
        'interval_ms' => (int) env('ACA_NOTIFICATION_INTERVAL_MS', 280),

        // Codigo de pais por defecto cuando la persona no tiene pais registrado.
        'country_code' => env('ACA_DEFAULT_COUNTRY_CODE', '51'),

        'vonage' => [
            // Parametro del sistema (tabla parameters) con el API Secret de Vonage.
            'parameter' => env('VONAGE_PARAMETER', 'SC-00001'),
            /*
             * API Key del panel de Vonage (la pareja del API Secret que vive en
             * el parametro del sistema). Puede reemplazarse con VONAGE_API_KEY o
             * enviar "api_key:api_secret" dentro del propio parametro.
             */
            'api_key' => env('VONAGE_API_KEY', 'fbaaac02'),
            // Alternativa JWT: solo si se configura una aplicacion de Vonage.
            'application_id' => env('VONAGE_APPLICATION_ID'),
            // Remitente (from) aprobado en Vonage: numero o sender ID alfanumerico.
            'sender' => env('VONAGE_SENDER', 'Vonage APIs'),
            'timeout' => (int) env('VONAGE_TIMEOUT', 30),
            /*
             * Costo por SMS (USD) para numeros de Peru, que se muestra en el pie
             * de pagina de la pantalla de notificaciones. En otros paises la
             * tarifa varia segun el destino.
             */
            'sms_price_peru_usd' => (float) env('VONAGE_SMS_PRICE_PE', 0.23369),
        ],

        'whatsapp' => [
            // Clave del ID de flujo configurable en Plantillas / Flujos.
            'flow_key' => 'aca_course_notification',
            /*
             * Endpoint que crea (o actualiza) el contacto antes de iniciar el
             * flujo: sin contacto la API no arranca la conversacion.
             */
            'create_contact_endpoint' => 'create_contact',
            'endpoint' => 'Inicio_contacto_con_flow_id',
        ],

        /*
         * SMSGate: el sistema envia por el API externo del servidor de SMSGate
         * (POST {servidor}/3rdparty/v1/messages) con Basic auth (usuario y
         * contrasena). La aplicacion movil se conecta al mismo servidor por
         * /mobile/v1; guardamos su URL y se normaliza al path externo.
         */
        'smsgate' => [
            // Parametro del sistema con la URL del servidor de SMSGate.
            'url_parameter' => env('SMSGATE_URL_PARAMETER', 'SC-00003'),
            // Parametro del sistema con el usuario de la cuenta.
            'username_parameter' => env('SMSGATE_USERNAME_PARAMETER', 'SC-00004'),
            // Parametro del sistema con la contrasena de la cuenta.
            'password_parameter' => env('SMSGATE_PASSWORD_PARAMETER', 'SC-00005'),
            // URL por defecto: la que usa la aplicacion movil de SMSGate.
            'default_url' => env('SMSGATE_URL', 'https://api.sms-gate.app/mobile/v1'),
            // Ruta del API externo de envio, sobre la URL base normalizada.
            'messages_path' => env('SMSGATE_MESSAGES_PATH', '/3rdparty/v1/messages'),
            // Codigo de pais que se antepone a los numeros guardados sin el.
            'country_code' => env('SMSGATE_COUNTRY_CODE', '51'),
            'timeout' => (int) env('SMSGATE_TIMEOUT', 30),
        ],

        /*
         * Telegram: se envia por la integracion Telegram_bot de Integrationhub
         * usando el chat_id que cada alumno registro con el bot. El token y los
         * endpoints viven en la configuracion del modulo Integrationhub
         * (integrationhub.telegram), para no duplicar el parametro del sistema.
         */
    ],
];
