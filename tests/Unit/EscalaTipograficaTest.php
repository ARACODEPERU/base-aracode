<?php

use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Escala tipográfica compartida
|--------------------------------------------------------------------------
|
| Servicios marca la pauta: título de sección 30/36/48 px según el ancho,
| bajada 18 px, título de tarjeta 20 px, texto 16 px y etiquetas 12 px.
|
| Estas pruebas fijan que esa escala viva en un solo sitio (los tokens de
| webpage-v2.css) y que las secciones que no se maquetaron con Tailwind —valor,
| proceso, CTA y las tarjetas— la usen en vez de traer tamaños propios.
|
*/

uses(TestCase::class);

/**
 * Declaraciones de una regla concreta de la hoja.
 */
function bloqueCss(string $css, string $selector): string
{
    preg_match('/'.preg_quote($selector, '/').'\s*\{([^}]*)\}/', $css, $coincidencia);

    return $coincidencia[1] ?? '';
}

it('define la escala de Servicios como referencia', function () {
    $css = file_get_contents(public_path('css/webpage-v2.css'));

    expect($css)->toContain('--ara-titulo-seccion: 1.875rem');
    expect($css)->toContain('--ara-bajada: 1.125rem');
    expect($css)->toContain('--ara-titulo-tarjeta: 1.25rem');
    expect($css)->toContain('--ara-texto: 1rem');
    expect($css)->toContain('--ara-etiqueta: 0.75rem');

    // Los tres escalones del título: 30 px por defecto, 36 desde 640 y 48
    // desde 1024, igual que text-3xl / sm:text-4xl / lg:text-5xl.
    expect($css)->toContain('--ara-titulo-seccion: 2.25rem');
    expect($css)->toContain('--ara-titulo-seccion: 3rem');
    expect($css)->toContain('@media (min-width: 640px)');
    expect($css)->toContain('@media (min-width: 1024px)');
});

it('usa esa escala en los títulos y bajadas de las secciones', function () {
    $css = file_get_contents(public_path('css/webpage-v2.css'))
        .file_get_contents(public_path('css/aracode-evolucion.css'));

    // Títulos de sección: valor, proceso, CTA y sobre la empresa.
    foreach (['.ara-valor__titulo', '.ara-proceso__titulo', '.ara-cta-auto__titulo', '.ara-about__titulo'] as $selector) {
        expect(bloqueCss($css, $selector))->toContain('font-size: var(--ara-titulo-seccion)');
    }

    // Bajadas: valor, proceso, CTA, hero y sobre la empresa.
    foreach (['.ara-valor__bajada', '.ara-proceso__bajada', '.ara-cta-auto__lead', '.ara-hero p', '.ara-about__lead'] as $selector) {
        expect(bloqueCss($css, $selector))->toContain('font-size: var(--ara-bajada)');
    }

    // Ya no quedan tamaños sueltos con clamp en los títulos: si alguien vuelve
    // a meter uno, el título deja de coincidir con el de servicios.
    expect($css)->not->toContain('clamp(2rem, 5vw, 3.6rem)');
    expect($css)->not->toContain('clamp(2rem, 4.4vw, 3.25rem)');
});

it('igual a 20 px los títulos de tarjeta y a 16 px sus textos', function () {
    $css = file_get_contents(public_path('css/webpage-v2.css'));

    // Título de tarjeta: pestaña de valor, paso del proceso, tarjeta de
    // producto y tarjetas de misión y visión.
    foreach ([
        '.ara-valor__tab-titulo',
        '.ara-proceso__paso-titulo',
        '.ara-product-card--sobrepuesta .ara-product-title',
        '.ara-mv__nombre',
    ] as $selector) {
        expect(bloqueCss($css, $selector))->toContain('font-size: var(--ara-titulo-tarjeta)');
    }

    // Texto de tarjeta: descripción de valor, texto del paso, frase del
    // producto y párrafo de misión y visión.
    foreach ([
        '.ara-valor__desc',
        '.ara-proceso__paso-texto',
        '.ara-product-card--sobrepuesta .ara-product-text',
        '.ara-mv__texto',
    ] as $selector) {
        expect(bloqueCss($css, $selector))->toContain('font-size: var(--ara-texto)');
    }

    expect($css)->not->toContain('font-size: 1.35rem');
});
