<?php

/*
|--------------------------------------------------------------------------
| Pantallas de registro
|--------------------------------------------------------------------------
|
| El registro tiene una sola ruta (/register) y una sola lógica de alta:
| RegisteredUserController@store crea el usuario, la organización y su prueba.
| Lo que cambia por tema es la PANTALLA que se muestra, no el flujo.
|
|  - por_portada  Relaciona el valor de PW00001 («Pagina web principal») con
|                 una variante. Así cada portada publicada usa su propia
|                 pantalla sin tocar el controlador.
|  - variantes    Cada pantalla declara su componente Inertia y si está
|                 habilitada. Mientras `habilitado` sea false, /register
|                 responde 404 en esa portada, que es el comportamiento actual
|                 fuera del modo torneos.
|
| Para publicar una variante nueva: crear el componente en
| resources/js/Pages/Auth, añadirlo aquí y poner `habilitado` en true. Todas
| las variantes comparten resources/js/Pages/Auth/Partials/RegistroOrganizadorForm.vue,
| así que el formulario y el envío no se duplican.
|
| Recuerda ejecutar `php artisan config:clear` (o `config:cache` en producción)
| después de editar este archivo.
|
*/

return [

    'por_portada' => [
        '1' => 'corporativo',
        '2' => 'torneos',
        '3' => 'kapta',
        '4' => 'kirafact',
        // La portada «Pichanguero» usa la misma pantalla que torneos, pero el
        // registro sigue cerrado ahí: hoy solo se permite en la portada 2.
        '5' => 'pichanguero',
    ],

    'variantes' => [
        'corporativo' => [
            'componente' => 'Auth/Register',
            'habilitado' => false,
        ],
        'torneos' => [
            'componente' => 'Auth/RegisterPichanguero',
            'habilitado' => true,
        ],
        'pichanguero' => [
            'componente' => 'Auth/RegisterPichanguero',
            'habilitado' => false,
        ],
        'kapta' => [
            'componente' => 'Auth/RegisterKapta',
            'habilitado' => false,
        ],
        'kirafact' => [
            'componente' => 'Auth/RegisterKirafact',
            'habilitado' => false,
        ],
    ],

];
