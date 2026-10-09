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

    /*
    |--------------------------------------------------------------------------
    | Avisos de citas (recordatorios a pacientes)
    |--------------------------------------------------------------------------
    | El modulo Salud envia recordatorios por SMS antes de cada cita de la
    | Agenda. Los textos y los tiempos se configuran desde Salud > Avisos y el
    | envio real sale por la cola (php artisan queue:work).
    |
    | Las credenciales de SMSGate son propias del modulo (parametros SC-00006,
    | SC-00007 y SC-00008) y el canal solo se usa cuando el parametro SC-00009
    | (interruptor Activo) esta encendido.
    */
    'notifications' => [
        'smsgate' => [
            // Parametro del sistema con la URL del servidor de SMSGate (Salud).
            'url_parameter' => env('HEALTH_SMSGATE_URL_PARAMETER', 'SC-00006'),
            // Parametro del sistema con el usuario de la cuenta.
            'username_parameter' => env('HEALTH_SMSGATE_USERNAME_PARAMETER', 'SC-00007'),
            // Parametro del sistema con la contrasena de la cuenta.
            'password_parameter' => env('HEALTH_SMSGATE_PASSWORD_PARAMETER', 'SC-00008'),
            // Parametro del sistema con el interruptor Activo del canal.
            'enabled_parameter' => env('HEALTH_NOTICES_SMS_PARAMETER', 'SC-00009'),
            // URL por defecto: la que usa la aplicacion movil de SMSGate.
            'default_url' => env('SMSGATE_URL', 'https://api.sms-gate.app/mobile/v1'),
            // Ruta del API externo de envio, sobre la URL base normalizada.
            'messages_path' => env('SMSGATE_MESSAGES_PATH', '/3rdparty/v1/messages'),
            // Codigo de pais que se antepone a los numeros guardados sin el: en
            // Salud los celulares se guardan con los 9 digitos del Peru (51).
            'country_code' => env('SMSGATE_COUNTRY_CODE', '51'),
            'timeout' => (int) env('HEALTH_SMSGATE_TIMEOUT', 30),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Google Calendar (sincronizacion de la Agenda)
    |--------------------------------------------------------------------------
    | Sincronizacion bidireccional entre las citas de la Agenda y el calendario
    | del consultorio: la Agenda crea, mueve y borra eventos, y los cambios
    | hechos en Google vuelven al sistema.
    |
    | Las credenciales son propias del modulo y viven en parametros del sistema:
    | SC-00010 (interruptor Activo), SC-00011 (Client ID), SC-00012 (Client
    | Secret), SC-00013 (refresh token), SC-00014 (calendario), SC-00015
    | (secreto del canal de push), SC-00016 (dias de ventana) y SC-00017
    | (recibir cambios de Google).
    |
    | El cliente HTTP se arma a mano con la fachada Http (Guzzle ya instalado):
    | no se requiere el paquete google/apiclient.
    |
    | Las credenciales (SC-00011, SC-00012 y SC-00013) son parametros
    | confidenciales: se registran una sola vez y la pantalla de Parametros del
    | sistema ya no las vuelve a mostrar. Al administrador solo le queda pulsar
    | el boton "Conectar con Google" y aceptar los permisos.
    */
    'google_calendar' => [
        // Parametro del sistema con el interruptor Activo del canal.
        'enabled_parameter' => env('HEALTH_GOOGLE_ENABLED_PARAMETER', 'SC-00010'),
        // Parametro del sistema con el Client ID de OAuth 2.0.
        'client_id_parameter' => env('HEALTH_GOOGLE_CLIENT_ID_PARAMETER', 'SC-00011'),
        // Parametro del sistema con el Client Secret de OAuth 2.0.
        'client_secret_parameter' => env('HEALTH_GOOGLE_CLIENT_SECRET_PARAMETER', 'SC-00012'),
        // Parametro del sistema con el refresh token (lo guarda el callback).
        'refresh_token_parameter' => env('HEALTH_GOOGLE_REFRESH_TOKEN_PARAMETER', 'SC-00013'),
        // Parametro del sistema con el calendario del consultorio.
        'calendar_id_parameter' => env('HEALTH_GOOGLE_CALENDAR_ID_PARAMETER', 'SC-00014'),
        // Parametro del sistema con el secreto del canal de notificaciones.
        'channel_token_parameter' => env('HEALTH_GOOGLE_CHANNEL_TOKEN_PARAMETER', 'SC-00015'),
        // Parametro del sistema con los dias de ventana de la primera lectura.
        'window_days_parameter' => env('HEALTH_GOOGLE_WINDOW_DAYS_PARAMETER', 'SC-00016'),
        // Parametro del sistema con el interruptor Google -> sistema.
        'inbound_parameter' => env('HEALTH_GOOGLE_INBOUND_PARAMETER', 'SC-00017'),
        // Calendario por defecto cuando el parametro esta vacio.
        'default_calendar_id' => env('HEALTH_GOOGLE_CALENDAR_ID', 'primary'),
        // Dias de ventana por defecto (hacia atras y hacia adelante).
        'default_window_days' => (int) env('HEALTH_GOOGLE_WINDOW_DAYS', 60),
        // Permiso OAuth: eventos del calendario del consultorio, mas `openid
        // email` (no sensibles) para poder mostrar con que cuenta quedo
        // conectado el calendario. Se pide en el mismo consentimiento.
        'scope' => env('HEALTH_GOOGLE_SCOPE', 'openid email https://www.googleapis.com/auth/calendar.events'),
        // Endpoints de Google.
        'auth_url' => env('HEALTH_GOOGLE_AUTH_URL', 'https://accounts.google.com/o/oauth2/v2/auth'),
        'token_url' => env('HEALTH_GOOGLE_TOKEN_URL', 'https://oauth2.googleapis.com/token'),
        'api_url' => env('HEALTH_GOOGLE_API_URL', 'https://www.googleapis.com/calendar/v3'),
        // Revocacion del permiso al desconectar la cuenta.
        'revoke_url' => env('HEALTH_GOOGLE_REVOKE_URL', 'https://oauth2.googleapis.com/revoke'),
        // Perfil de la cuenta conectada (respaldo si el token no trae id_token).
        'userinfo_url' => env('HEALTH_GOOGLE_USERINFO_URL', 'https://openidconnect.googleapis.com/v1/userinfo'),
        'timeout' => (int) env('HEALTH_GOOGLE_TIMEOUT', 30),
        'page_size' => (int) env('HEALTH_GOOGLE_PAGE_SIZE', 250),
        // Zona horaria con la que se envian y se leen los eventos.
        'timezone' => env('HEALTH_GOOGLE_TIMEZONE', 'America/Lima'),
        // URL publica HTTPS que Google usara para las notificaciones push. Si
        // queda vacia se arma con route(); en local (http) el push no funciona
        // y la reconciliacion programada hace el trabajo.
        'webhook_url' => env('HEALTH_GOOGLE_WEBHOOK_URL'),
        // Minutos entre reconciliaciones (red de seguridad del push).
        'reconcile_minutes' => (int) env('HEALTH_GOOGLE_RECONCILE_MINUTES', 5),
        // Dias de margen para renovar el canal de push antes de que expire.
        'channel_renew_days' => (int) env('HEALTH_GOOGLE_CHANNEL_RENEW_DAYS', 2),
    ],
];
