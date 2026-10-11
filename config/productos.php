<?php

/*
|--------------------------------------------------------------------------
| Sitios web de los productos ARACODE
|--------------------------------------------------------------------------
|
| Mapa único de la web propia de cada producto del ecosistema (KAPTA LMS,
| KIRAFACT y Pichanguero). Lo consumen las páginas del sitio corporativo que
| enlazan a esos sitios: /soluciones (botón «Conocer Más») y las fichas
| /kapta, /kirafact y /pichanguero.
|
| Los sitios de producto viven en este repositorio bajo el prefijo site/* para
| convivir con el sitio corporativo; en producción cada uno tendrá su propio
| dominio. Por eso aquí se escribe una RUTA RELATIVA mientras conviven
| ('site/kapta') y una URL ABSOLUTA el día del dominio propio
| ('https://kapta.pe'). Cambiando esa línea, todos los enlaces del corporativo
| apuntan al dominio nuevo sin tocar ninguna vista.
|
| Los sitios de producto NO leen este archivo: se copian a su dominio con su
| propio código. Aquí solo vive lo que el corporativo necesita saber de ellos.
|
| Antes de mover un producto a su dominio, revisa
| docs/SEO_ECOSISTEMA_PRODUCTOS.md: ahí está el reparto de intenciones entre
| la ficha corporativa y el sitio del producto, y el procedimiento completo
| (canónicas, sitemap y redirecciones de las URLs antiguas).
|
| Recuerda ejecutar `php artisan config:clear` (o `config:cache` en producción)
| después de editar este archivo.
|
*/

return [

    'kapta' => [
        'nombre' => 'KAPTA LMS',
        // Página de planes del producto: es la única tabla de precios pública.
        'sitio' => 'site/kapta',
        'planes' => 'site/kapta/planes',
    ],

    'kirafact' => [
        'nombre' => 'KIRAFACT',
        // Su página de planes existe, pero no publica importes: explica cómo se
        // confirman las tarifas con el equipo comercial.
        'sitio' => 'site/kirafact',
        'planes' => 'site/kirafact/planes',
    ],

    'pichanguero' => [
        'nombre' => 'Pichanguero',
        'sitio' => 'site/pichanguero',
        // Su sitio no tiene página de planes; sí la de descargas de la app, que
        // es la que publica la versión vigente del APK.
        'planes' => null,
        'descargas' => 'site/pichanguero/descargas',
    ],

];
