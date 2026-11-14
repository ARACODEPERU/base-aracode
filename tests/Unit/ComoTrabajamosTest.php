<?php

use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Cómo trabajamos: los cuatro pasos del home
|--------------------------------------------------------------------------
|
| La franja de cifras salió del home porque los cuatro números ya están en el
| hero de la misma página. En su lugar, esta sección explica qué pasa cuando
| alguien escribe: cuatro pasos numerados, cada uno con lo que entrega, y una
| única llamada a la acción que abre el modal de asesoría.
|
| Estas pruebas fijan ese patrón: el orden de los pasos, que las etiquetas
| digan qué se entrega y no cuánto tarda (nada de plazos inventados), que el
| botón use el modal (`data-asesoria`) y que la franja anterior no quede
| conviviendo con la nueva.
|
*/

uses(TestCase::class);

beforeEach(fn () => crearTablasCmsDelSitio());

/**
 * HTML de la sección de proceso del home, o cadena vacía si no se pintó.
 */
function seccionProceso(string $html): string
{
    preg_match('#<section class="ara-proceso".*?</section>#s', $html, $coincidencia);

    return $coincidencia[0] ?? '';
}

it('publica el proceso como cuatro pasos numerados en orden', function () {
    $seccion = seccionProceso($this->get('/')->getContent());

    expect($seccion)->not->toBeEmpty('El home no publica la sección de proceso');

    // Una lista ordenada: el orden de los pasos es parte del mensaje.
    expect(substr_count($seccion, 'ara-proceso__pasos'))->toBe(1);
    expect(preg_match('#<ol class="ara-proceso__pasos">#', $seccion))->toBe(1);
    expect(substr_count($seccion, 'class="ara-proceso__paso"'))->toBe(4);

    // Los títulos aparecen en el orden en que se recorren los pasos.
    preg_match_all('#<h3 class="ara-proceso__paso-titulo">(.*?)</h3>#s', $seccion, $titulos);

    expect($titulos[1])->toBe(['Diagnóstico', 'Propuesta', 'Implementación', 'Acompañamiento']);

    // Los números van escritos, no los pone solo el CSS.
    foreach ([1, 2, 3, 4] as $numero) {
        expect($seccion)->toContain('>0'.$numero.'<');
    }
});

it('etiqueta cada paso con lo que entrega, sin plazos', function () {
    $seccion = seccionProceso($this->get('/')->getContent());

    preg_match_all('#<span class="ara-proceso__chip">(.*?)</span>#s', $seccion, $chips);

    expect($chips[1])->toBe([
        'Procesos mapeados',
        'Propuesta cerrada',
        'Entregas por etapas',
        'Soporte continuo',
    ]);

    $texto = strip_tags($seccion);

    // Ninguna etiqueta promete una duración: los tiempos dependen del proyecto
    // y no se anuncian desde el home.
    expect(preg_match('/\b\d+\s*(min|mins|minutos?|horas?|d[ií]as?|semanas?|mes(?:es)?)\b/iu', $texto))->toBe(0);
    expect($texto)->not->toContain('45 min');
    expect($texto)->not->toContain('semanal');
    expect($texto)->not->toContain('primera semana');
});

it('cierra con el botón de asesoría que abre el modal', function () {
    $seccion = seccionProceso($this->get('/')->getContent());

    // Un solo botón en la sección, y es el del modal: `data-asesoria` lo
    // intercepta; sin JavaScript queda el enlace a /contacto.
    expect(substr_count($seccion, 'data-asesoria'))->toBe(1);
    expect(substr_count($seccion, 'Solicitar asesoría'))->toBe(1);
    expect($seccion)->toContain('href="'.route('contacto').'"');
});

it('reemplaza la franja de cifras y deja los números solo en el hero', function () {
    $html = $this->get('/')->getContent();

    expect($html)->not->toContain('ara-stats-section');
    expect($html)->not->toContain('ara-stats-panel');
    expect($html)->not->toContain('ara-stat-number');

    // Una sola sección de proceso: si quedara la anterior, habría dos.
    expect(substr_count($html, 'id="proceso"'))->toBe(1);

    // Los cuatro contadores siguen en el hero, que es donde se leen primero
    // (el quinto `data-counter` del HTML es el selector del script del layout).
    expect(substr_count($html, 'data-counter data-target'))->toBe(4);
});

it('usa el mismo container que el resto de secciones del home', function () {
    $seccion = seccionProceso($this->get('/')->getContent());

    // Servicios y productos envuelven su contenido en el container estándar.
    // Esta sección tiene que hacer lo mismo: si trae su propio ancho, el borde
    // izquierdo no coincide con el de las demás.
    expect($seccion)->toContain('ara-proceso__envoltura max-w-7xl mx-auto px-4 sm:px-6 lg:px-8');
});

it('deja el estilo en la hoja que carga el navegador', function () {
    $css = file_get_contents(public_path('css/webpage-v2.css'));

    expect($css)->toContain('.ara-proceso__pasos');
    expect($css)->toContain('.ara-proceso__paso::after');
    expect($css)->toContain('.ara-proceso__btn');

    // Nada de copias en resources: la hoja que se sirve es la de public.
    expect(file_exists(resource_path('css/webpage-v2.css')))->toBeFalse();
});
