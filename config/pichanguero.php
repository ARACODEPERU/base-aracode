<?php

/*
|--------------------------------------------------------------------------
| Sitio web de Pichanguero — enlaces y avisos del producto
|--------------------------------------------------------------------------
|
| El sitio de Pichanguero (resources/views/pichanguero) es Blade puro y se copia
| a su propio dominio junto con sus vistas, su CSS y su JS. Por eso lo que
| apunta fuera del sitio no se escribe dentro de las vistas: se declara aquí y
| las vistas lo leen con config('pichanguero.*').
|
|  - registro_url    Vista de «crear cuenta» de la plataforma de torneos. Es la
|                    ruta /register del subdominio de la aplicación, donde se
|                    registra la organización y su usuario responsable. Cambiar
|                    esta línea mueve todos los botones «Crear cuenta» del
|                    sitio (cabecera, portada, pie y llamada final).
|
|  - app_disponible  La app Android todavía no está publicada. Mientras sea
|                    false, la landing oculta TODAS las opciones de descarga
|                    (enlaces «Descargas» de la cabecera y del pie, sección de
|                    descarga, insignias y llamadas a la acción) y muestra en su
|                    lugar el botón «Crear cuenta». Al lanzar la app basta con
|                    poner PICHANGUERO_APP_DISPONIBLE=true en el .env para que
|                    el contenido de descargas vuelva a aparecer tal como está
|                    escrito: no hay que recuperar código de ningún sitio.
|
| Recuerda ejecutar `php artisan config:clear` (o `config:cache` en producción)
| después de editar este archivo.
|
*/

return [

    // El `?:` evita que una variable vacía en el .env deje los botones sin destino.
    'registro_url' => env('PICHANGUERO_REGISTRO_URL') ?: 'https://avc.pichanguero.com/register',

    'app_disponible' => (bool) env('PICHANGUERO_APP_DISPONIBLE', false),

];
