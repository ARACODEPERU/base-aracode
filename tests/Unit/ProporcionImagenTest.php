<?php

use App\Support\ProporcionImagen;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Proporción de la imagen de las tarjetas
|--------------------------------------------------------------------------
|
| La franja de la tarjeta de producto reserva el alto de la imagen con
| `aspect-ratio`, así que necesita la proporción REAL del archivo: si se deja la
| proporción por defecto, los banners del catálogo (960 × 600) dejan una banda de
| relleno arriba y abajo; si se deja que la decida el navegador, la tarjeta salta
| al cargar la imagen.
|
| De ahí que se devuelva el valor listo para CSS (`960 / 600`) y que, cuando no
| se puede medir —URL externa o archivo que no está en esta copia—, devuelva null
| para que la franja caiga a su proporción por defecto.
|
*/

uses(TestCase::class);

it('devuelve la proporción del archivo que hay detrás de una URL del sitio', function () {
    $url = asset('themes/webpage/images/misc/s1.jpg');
    $medidas = getimagesize(public_path('themes/webpage/images/misc/s1.jpg'));

    expect(ProporcionImagen::de($url))->toBe($medidas[0].' / '.$medidas[1])
        ->and(ProporcionImagen::de($url))->toMatch('/^\d+ \/ \d+$/');
});

it('acepta también la ruta sin el host', function () {
    expect(ProporcionImagen::de('themes/webpage/images/misc/s2.jpg'))
        ->toBe(ProporcionImagen::de(asset('themes/webpage/images/misc/s2.jpg')));
});

it('devuelve null cuando el archivo no está en esta copia del proyecto', function () {
    // El caso real: las subidas de la empresa viven en el servidor y la base de
    // datos sí apunta a ellas, así que la ruta llega pero el archivo no existe.
    expect(ProporcionImagen::de(asset('storage/uploads/company/no-existe.png')))->toBeNull();
});

it('devuelve null para una imagen externa, sin pedirla por red', function () {
    expect(ProporcionImagen::de('https://cdn.example.com/producto.jpg'))->toBeNull()
        ->and(ProporcionImagen::de('/temas/que-no-existe.png'))->toBeNull()
        ->and(ProporcionImagen::de(null))->toBeNull()
        ->and(ProporcionImagen::de(''))->toBeNull();
});
