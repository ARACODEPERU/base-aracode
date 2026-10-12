<?php

use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Imagen destacada del artículo del blog
|--------------------------------------------------------------------------
|
| La imagen del artículo se muestra entera, con su propia proporción. Las que
| sube el editor llegan en 3/2 (1536 × 1024), así que meterlas en una caja 16/9
| con `object-fit: cover` —como estaba antes— recortaba cerca del 15 % de la
| altura: el diseño trae su logotipo arriba y remates en los bordes, y se perdían.
|
| En las tarjetas del listado (`blog.blade.php`) y en las miniaturas de
| «Populares» sí se mantiene la caja de proporción fija: ahí la imagen presenta
| el artículo y la alineación de la rejilla importa más.
|
| La prueba mira el marcado de la vista porque la página real necesita la base de
| datos del blog. La comprobación equivalente contra un servidor de verdad es
| storage/tmp/revisar-blog-articulo.mjs: mide en Chrome que la proporción en
| pantalla sea la del archivo, en 1440 y en 390 px, y deja capturas.
|
*/

uses(TestCase::class);

it('publica la imagen destacada sin recortarla', function () {
    $vista = file_get_contents(resource_path('views/aracode/pages/blog-articulo.blade.php'));

    expect($vista)->not->toBeEmpty();

    // El bloque de la imagen destacada: la que sale de `$article->imagen`.
    // El patrón deja pasar la flecha de `->` de los atributos —el `alt` la
    // lleva— sin cerrar la etiqueta antes de tiempo en ese `>`.
    preg_match('#<img\s+src="\{\{ \$article->imagen \}\}"(?:->|[^>])*>#s', $vista, $imagen);

    expect($imagen)->not->toBeEmpty('No se encontró la imagen destacada del artículo');

    $etiqueta = $imagen[0];

    // `h-auto` deja que la altura la fije la proporción del archivo; sin caja de
    // proporción ni `object-cover` no hay nada que recortar.
    expect($etiqueta)->toContain('h-auto')
        ->and($etiqueta)->not->toContain('object-cover')
        ->and($etiqueta)->not->toContain('aspect-');

    // Y la vista sigue describiendo la imagen: sin alt, el cambio sería a medias.
    expect($etiqueta)->toContain('alt="{{ $article->title }}"');
});
