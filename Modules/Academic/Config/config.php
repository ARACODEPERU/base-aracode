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

    /*
     * Avisos de asistencia del colegio (porteria): cuando el alumno registra
     * su entrada o su salida escaneando el QR del carné, el sistema avisa al
     * padre o apoderado por SMS.
     *
     * El canal es propio del modulo escolar: usa credenciales de SMSGate
     * distintas a las de las campanas de especializacion (SC-00003/04/05) y a
     * las de Salud (SC-00006/07/08). Se administran desde Academico >
     * Parametros / SMS (parametros SC-00020 a SC-00031) y solo envia cuando el
     * interruptor general (SC-00023) esta encendido.
     */
    'school_notices' => [
        'smsgate' => [
            // Parametro del sistema con la URL del servidor de SMSGate.
            'url_parameter' => env('ACA_SCHOOL_SMS_URL_PARAMETER', 'SC-00020'),
            // Parametro del sistema con el usuario de la cuenta.
            'username_parameter' => env('ACA_SCHOOL_SMS_USERNAME_PARAMETER', 'SC-00021'),
            // Parametro del sistema con la contrasena de la cuenta.
            'password_parameter' => env('ACA_SCHOOL_SMS_PASSWORD_PARAMETER', 'SC-00022'),
            // Parametro del sistema con el interruptor general del canal.
            'enabled_parameter' => env('ACA_SCHOOL_SMS_ENABLED_PARAMETER', 'SC-00023'),
            // Interruptores por evento (entrada, salida, tardanza y salida anticipada).
            'entry_parameter' => env('ACA_SCHOOL_SMS_ENTRY_PARAMETER', 'SC-00024'),
            'exit_parameter' => env('ACA_SCHOOL_SMS_EXIT_PARAMETER', 'SC-00025'),
            'late_parameter' => env('ACA_SCHOOL_SMS_LATE_PARAMETER', 'SC-00026'),
            'early_exit_parameter' => env('ACA_SCHOOL_SMS_EARLY_EXIT_PARAMETER', 'SC-00027'),
            // Plantillas editables del texto de cada evento.
            'entry_message_parameter' => env('ACA_SCHOOL_SMS_ENTRY_MESSAGE_PARAMETER', 'SC-00028'),
            'exit_message_parameter' => env('ACA_SCHOOL_SMS_EXIT_MESSAGE_PARAMETER', 'SC-00029'),
            // Avisar solo al apoderado principal.
            'primary_only_parameter' => env('ACA_SCHOOL_SMS_PRIMARY_ONLY_PARAMETER', 'SC-00030'),
            // Maximo de avisos por alumno y dia (0 = sin limite).
            'daily_limit_parameter' => env('ACA_SCHOOL_SMS_DAILY_LIMIT_PARAMETER', 'SC-00031'),
            // URL por defecto: la que usa la aplicacion movil de SMSGate.
            'default_url' => env('SMSGATE_URL', 'https://api.sms-gate.app/mobile/v1'),
            // Textos por defecto si la plantilla queda vacia.
            'default_entry_message' => 'Estimado(a) {apoderado}: le informamos que {alumno} registró su INGRESO al colegio {colegio} el {fecha} a las {hora} ({estado}).',
            'default_exit_message' => 'Estimado(a) {apoderado}: le informamos que {alumno} registró su SALIDA del colegio {colegio} el {fecha} a las {hora} ({estado}).',
            // Ruta del API externo de envio, sobre la URL base normalizada.
            'messages_path' => env('SMSGATE_MESSAGES_PATH', '/3rdparty/v1/messages'),
            // Codigo de pais que se antepone a los numeros guardados sin el.
            'country_code' => env('SMSGATE_COUNTRY_CODE', '51'),
            'timeout' => (int) env('ACA_SCHOOL_SMS_TIMEOUT', 30),
        ],
    ],
];
