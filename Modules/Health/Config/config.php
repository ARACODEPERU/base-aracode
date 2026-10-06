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
            'timeout' => (int) env('HEALTH_SMSGATE_TIMEOUT', 30),
        ],
    ],
];
