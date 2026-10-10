<?php

use App\Support\SitiosProducto;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Contrato de config/productos.php
|--------------------------------------------------------------------------
|
| Estas pruebas fijan lo que promete config/productos.php: mientras los sitios
| de producto convivan en este repositorio la config guarda una ruta relativa y
| se resuelve contra el host actual; el día que un producto pase a su dominio se
| escribe su URL absoluta y se respeta tal cual. Es el único cambio que hay que
| hacer ese día (docs/SEO_ECOSISTEMA_PRODUCTOS.md), así que conviene que esté
| cubierto.
|
*/

uses(TestCase::class);

it('resuelve una ruta relativa contra el host actual', function () {
    config(['productos.kapta.sitio' => 'site/kapta']);

    expect(SitiosProducto::url('kapta'))->toBe(url('site/kapta'));
});

it('respeta la URL absoluta el día que el producto tenga dominio propio', function () {
    config(['productos.kapta.sitio' => 'https://kapta.pe']);
    config(['productos.kapta.planes' => 'https://kapta.pe/planes']);

    expect(SitiosProducto::url('kapta'))->toBe('https://kapta.pe');
    expect(SitiosProducto::planes('kapta'))->toBe('https://kapta.pe/planes');
});

it('devuelve null en vez de un enlace roto cuando la página no existe', function () {
    expect(SitiosProducto::planes('pichanguero'))->toBeNull();
    expect(SitiosProducto::descargas('kapta'))->toBeNull();
    expect(SitiosProducto::url('no-existe'))->toBeNull();
    expect(SitiosProducto::nombre('no-existe'))->toBe('');
});

it('expone los datos publicados de cada producto', function () {
    expect(SitiosProducto::nombre('kapta'))->toBe('KAPTA LMS');
    expect(SitiosProducto::nombre('kirafact'))->toBe('KIRAFACT');
    expect(SitiosProducto::url('kapta'))->toBe(url('site/kapta'));
    expect(SitiosProducto::planes('kapta'))->toBe(url('site/kapta/planes'));
    expect(SitiosProducto::planes('kirafact'))->toBe(url('site/kirafact/planes'));
    expect(SitiosProducto::descargas('pichanguero'))->toBe(url('site/pichanguero/descargas'));
});
