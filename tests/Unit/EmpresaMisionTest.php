<?php

use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Misión y visión (/empresa)
|--------------------------------------------------------------------------
|
| La sección abría directamente con dos tarjetas, sin titular que la nombrara.
| Ahora tiene cabecera propia y las tarjetas usan la escala del sitio, con un
| icono más chico para que en móvil no queden tan altas.
|
*/

uses(TestCase::class);

beforeEach(fn () => crearTablasCmsDelSitio());

function seccionMision(string $html): string
{
    preg_match('#<section class="ara-mv".*?</section>#s', $html, $coincidencia);

    return $coincidencia[0] ?? '';
}

it('abre con su titular y las dos tarjetas', function () {
    $seccion = seccionMision($this->get('/empresa')->getContent());

    expect($seccion)->not->toBeEmpty('/empresa no publica la sección');

    // La sección se nombra a sí misma: antes empezaba con las tarjetas.
    expect(substr_count($seccion, '<h2'))->toBe(1);
    expect($seccion)->toContain('Misión y visión');

    expect(substr_count($seccion, 'ara-mv__tarjeta'))->toBe(2);

    foreach (['Nuestra misión', 'Nuestra visión'] as $titulo) {
        expect($seccion)->toContain($titulo);
    }

    // Los textos siguen completos.
    expect($seccion)->toContain('Desarrollar soluciones de software innovadoras y eficientes');
    expect($seccion)->toContain('Ser reconocidos como una empresa líder en desarrollo de software');
});

it('usa el container y la escala del sitio', function () {
    $seccion = seccionMision($this->get('/empresa')->getContent());

    expect($seccion)->toContain('max-w-7xl mx-auto px-4 sm:px-6 lg:px-8');

    $css = file_get_contents(public_path('css/webpage-v2.css'));

    expect($css)->toContain('.ara-mv__rejilla');
    expect(preg_match('#\.ara-mv__titulo \{[^}]*font-size: var\(--ara-titulo-seccion\)#', $css))->toBe(1);
    expect(preg_match('#\.ara-mv__nombre \{[^}]*font-size: var\(--ara-titulo-tarjeta\)#', $css))->toBe(1);
    expect(preg_match('#\.ara-mv__texto \{[^}]*font-size: var\(--ara-texto\)#', $css))->toBe(1);

    // Una sola columna en móvil.
    expect($css)->toContain('@media (max-width: 767px)');
});
