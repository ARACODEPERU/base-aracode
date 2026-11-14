<?php

use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Sobre la empresa (/empresa)
|--------------------------------------------------------------------------
|
| La sección es un bloque de dos columnas: el texto y los cuatro pilares a la
| izquierda, la foto recortada a un cuadrado a la derecha. Antes el texto se
| centraba contra una foto vertical y quedaba medio lienzo vacío arriba.
|
| Estas pruebas fijan lo que hace legible la sección: un titular de verdad
| (sin el badge «Quiénes Somos», que ya lo dice el hero), los cuatro pilares,
| una foto con descripción y crédito de lugar, y la salida hacia el equipo.
|
*/

uses(TestCase::class);

beforeEach(fn () => crearTablasCmsDelSitio());

function seccionEmpresa(string $html): string
{
    preg_match('#<section class="ara-about".*?</section>#s', $html, $coincidencia);

    return $coincidencia[0] ?? '';
}

it('publica el bloque con su titular y sin el badge repetido', function () {
    $seccion = seccionEmpresa($this->get('/empresa')->getContent());

    expect($seccion)->not->toBeEmpty('/empresa no publica la sección');

    expect(substr_count($seccion, 'ara-about__titulo'))->toBe(1);
    expect($seccion)->toContain('Tecnología que impulsa negocios');

    // El hero ya dice «Sobre Nosotros»: repetirlo en un badge encima del
    // titular solo agrega ruido.
    expect($seccion)->not->toContain('Quiénes Somos');
});

it('presenta los cuatro pilares, el diagrama y la salida al equipo', function () {
    $seccion = seccionEmpresa($this->get('/empresa')->getContent());

    expect(substr_count($seccion, 'class="ara-about__pilar"'))->toBe(4);

    foreach (['Procesos internos', 'Menos errores', 'Mejor experiencia', 'Crecimiento'] as $titulo) {
        expect($seccion)->toContain($titulo);
    }

    // El lugar de la foto lo ocupa un diagrama dibujado: tres etapas con su
    // etiqueta, sin imágenes que cargar.
    expect(substr_count($seccion, '<img'))->toBe(0);
    expect($seccion)->toContain('data-flujo');
    expect(substr_count($seccion, 'data-flujo-paso'))->toBe(3);

    foreach (['Entrada', 'Motor', 'Salida', 'Formulario web', 'IA + reglas del negocio', 'Documento listo'] as $texto) {
        expect($seccion)->toContain($texto);
    }

    expect($seccion)->toContain('href="'.route('equipo').'"');
    expect($seccion)->toContain('Conoce al equipo');
});

it('usa el mismo container y la escala del sitio', function () {
    $seccion = seccionEmpresa($this->get('/empresa')->getContent());

    expect($seccion)->toContain('max-w-7xl mx-auto px-4 sm:px-6 lg:px-8');

    $css = file_get_contents(public_path('css/webpage-v2.css'));

    expect($css)->toContain('.ara-about__grid');
    expect($css)->toContain('font-size: var(--ara-titulo-seccion)');
    expect($css)->toContain('.ara-flujo__pasos');
    expect($css)->toContain('@media (max-width: 1023px)');

    // El dibujo no trae proporción propia: toma la altura del contenido de la
    // izquierda. Si alguien le devuelve un `aspect-ratio`, vuelve a crecer más
    // que el texto en pantallas anchas.
    expect($css)->toContain('align-items: stretch');
    expect(preg_match('#\.ara-flujo \{[^}]*aspect-ratio#', $css))->toBe(0);

    // El diagrama se mueve desde el JS del sitio, no desde una librería.
    $js = file_get_contents(public_path('js/aracode-evolucion.js'));

    expect($js)->toContain('flujoAutomatizado');
    expect($js)->toContain('data-flujo-paso');
    expect($js)->toContain('prefers-reduced-motion');
});
