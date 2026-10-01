<?php

return [
    'name' => 'Security',

    /*
    |--------------------------------------------------------------------------
    | Modo Super Editor
    |--------------------------------------------------------------------------
    |
    | Editor visual de permisos por roles. Con el modo activo cada elemento de
    | la interfaz (menú, botones, enlaces con v-can) muestra un engrane desde
    | el que se elige qué rol puede verlo / crearlo / editarlo. Los cambios se
    | dejan en borrador y se aplican (y auditan) al salir confirmando la
    | contraseña.
    |
    | 'role'                 rol autorizado a entrar al modo.
    | 'ttl_minutes'          vigencia de una sesión de edición; al expirar el
    |                        borrador se descarta y queda auditado.
    | 'protected_permissions' permisos que NUNCA se le pueden quitar al rol
    |                        autorizado (evita el auto-bloqueo). Los que
    |                        empiezan con "super_editor" siempre son protegidos.
    | 'max_group_size'       máximo de permisos hermanos que muestra el panel de
    |                        un elemento (mismo prefijo del permiso clicado).
    |
    */

    'super_editor' => [
        'enabled' => env('SUPER_EDITOR_ENABLED', true),
        'role' => env('SUPER_EDITOR_ROLE', 'admin'),
        'ttl_minutes' => env('SUPER_EDITOR_TTL_MINUTES', 30),
        'max_group_size' => 20,
        'protected_permissions' => [
            'dashboard',
            'configuracion',
            'empresa',
            'modulos',
            'roles',
            'permisos',
            'usuarios',
            'parametros',
            'conf_historial_actividades',
            'super_editor',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Alertas de error por Telegram
    |--------------------------------------------------------------------------
    |
    | Cuando un error se registra en los logs (nivel mínimo configurable en la
    | pestaña Alertas), se envía un aviso por la integración Telegram_bot a los
    | chat_id activos. Aquí viven los valores de fábrica que la pantalla no
    | expone.
    |
    | 'template'       plantilla del mensaje; las líneas cuya variable llegue
    |                  vacía no se envían.
    | 'trace_lines'    líneas de la traza que se incluyen (0 para omitirla).
    | 'message_limit'  recorte del mensaje/traza (límite de Telegram: 4096).
    | 'cache_minutes'  minutos que se cachean ajustes y destinatarios para no
    |                  consultar la base en cada línea de log.
    | 'timeout'        segundos de espera de la llamada a Telegram.
    |
    */

    'alerts' => [
        'template' => env('SECURITY_ALERT_TEMPLATE', <<<'TXT'
        🚨 <b>Alerta de error</b>

        <b>Nivel:</b> {nivel}
        <b>Mensaje:</b> {mensaje}

        <b>Origen:</b> {clase}
        <b>Archivo:</b> {archivo}:{linea}

        <b>Entorno:</b> {entorno}
        <b>Ruta:</b> {metodo} {ruta}
        <b>Usuario:</b> {usuario}
        <b>IP:</b> {ip}
        <b>Fecha:</b> {fecha}
        TXT),
        'trace_lines' => (int) env('SECURITY_ALERT_TRACE_LINES', 0),
        'message_limit' => (int) env('SECURITY_ALERT_MESSAGE_LIMIT', 3000),
        'cache_minutes' => (int) env('SECURITY_ALERT_CACHE_MINUTES', 5),
        'timeout' => (int) env('SECURITY_ALERT_TIMEOUT', 15),
    ],
];
