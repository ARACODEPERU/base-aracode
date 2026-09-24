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
];
