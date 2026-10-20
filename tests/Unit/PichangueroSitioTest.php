<?php

use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Sitio web de Pichanguero
|--------------------------------------------------------------------------
|
| Fija el contrato de config/pichanguero.php: mientras la app Android no esté
| publicada (`app_disponible` en false) la portada no ofrece ninguna opción de
| descarga y la acción disponible es crear cuenta en la plataforma de torneos.
| Al lanzar la app basta con poner el interruptor en true para que el contenido
| de descargas vuelva a aparecer, sin recuperar código de ningún sitio.
|
| Vive en Unit —y no en Feature— por la misma razón que el resto de pruebas de
| sitios: la suite Feature no arranca en sqlite (ver tests/Unit/PaginaPrincipalTest.php).
| Estas páginas son Route::view sin consultas, así que no necesitan esquema.
|
*/

uses(TestCase::class);

it('oculta todas las descargas de la portada mientras la app no está publicada', function () {
    config(['pichanguero.app_disponible' => false]);

    $respuesta = $this->get(route('pichanguero.home'));

    $respuesta->assertOk();

    // La acción disponible es crear cuenta, en el registro de la plataforma.
    $respuesta->assertSee('Crear cuenta', false);
    $respuesta->assertSee(config('pichanguero.registro_url'), false);

    // Ninguna llamada a la descarga ni la sección que la ofrecía.
    $respuesta->assertDontSee('Descargar la app', false);
    $respuesta->assertDontSee('Descargar Pichanguero', false);
    $respuesta->assertDontSee('Descargar APK', false);
    $respuesta->assertDontSee('App Android', false);
    $respuesta->assertDontSee('Lleva el torneo en el bolsillo', false);
    $respuesta->assertDontSee(route('pichanguero.descargas'), false);
});

it('no anuncia la app en la cabecera ni en el pie de la portada', function () {
    config(['pichanguero.app_disponible' => false]);

    $respuesta = $this->get(route('pichanguero.home'));

    $respuesta->assertOk();

    // «Descargas» solo se enlaza cuando la app está publicada.
    $respuesta->assertDontSee('>Descargas<', false);
    $respuesta->assertSee('>Contacto<', false);
});

it('vuelve a ofrecer las descargas cuando la app está publicada', function () {
    config(['pichanguero.app_disponible' => true]);

    $respuesta = $this->get(route('pichanguero.home'));

    $respuesta->assertOk();
    $respuesta->assertSee('Descargar la app', false);
    $respuesta->assertSee('Descargar APK', false);
    $respuesta->assertSee('App Android', false);
    $respuesta->assertSee(route('pichanguero.descargas'), false);
});

it('usa el registro configurado en los botones de crear cuenta', function () {
    config([
        'pichanguero.app_disponible' => false,
        'pichanguero.registro_url' => 'https://avc.pichanguero.com/register',
    ]);

    $respuesta = $this->get(route('pichanguero.home'));

    $respuesta->assertOk();
    expect(substr_count($respuesta->getContent(), 'https://avc.pichanguero.com/register'))->toBeGreaterThan(1);
});
