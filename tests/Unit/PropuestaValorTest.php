<?php

use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Propuesta de valor del home: las cuatro ventajas en pestañas
|--------------------------------------------------------------------------
|
| La sección presenta las ventajas como un patrón de pestañas: una sola activa,
| un panel visual compartido que cambia con ella (imagen, chip y contador) y la
| descripción completa de cada ventaja, no un resumen de una línea.
|
| Estas pruebas fijan ese patrón: la estructura accesible (tablist, tab y
| tabpanel enlazados por `aria-controls`), que haya exactamente una ventaja
| activa, que los cuatro textos completos sigan en la página y que el estilo y
| el comportamiento vivan en los archivos que carga el navegador —no en una
| copia de resources que nadie enlaza—.
|
*/

uses(TestCase::class);

beforeEach(fn () => crearTablasCmsDelSitio());

/**
 * HTML de la sección de valor del home, o cadena vacía si no se pintó.
 */
function seccionValor(string $html): string
{
    preg_match('#<section class="ara-valor".*?</section>#s', $html, $coincidencia);

    return $coincidencia[0] ?? '';
}

it('publica las cuatro ventajas como pestañas con una sola activa', function () {
    $seccion = seccionValor($this->get('/')->getContent());

    expect($seccion)->not->toBeEmpty('El home no publica la sección de valor');

    expect(substr_count($seccion, 'role="tablist"'))->toBe(1);
    expect(substr_count($seccion, 'role="tab"'))->toBe(4);
    expect(substr_count($seccion, 'role="tabpanel"'))->toBe(1);

    // Una sola ventaja abierta: la que tiene el foco del teclado dentro del
    // grupo de pestañas.
    expect(substr_count($seccion, 'aria-selected="true"'))->toBe(1);
    expect(substr_count($seccion, 'aria-selected="false"'))->toBe(3);
    expect(substr_count($seccion, 'tabindex="0"'))->toBe(1);
    expect(substr_count($seccion, 'tabindex="-1"'))->toBe(3);

    // Las cuatro pestañas controlan el mismo panel: es un panel compartido que
    // se repinta, no cuatro paneles a la vez.
    expect(substr_count($seccion, 'aria-controls="valor-panel"'))->toBe(4);
    expect($seccion)->toContain('id="valor-panel"');
});

it('etiqueta el panel con la ventaja activa y numera las cuatro', function () {
    $seccion = seccionValor($this->get('/')->getContent());

    expect($seccion)->toContain('aria-labelledby="valor-tab-1"');

    foreach ([1, 2, 3, 4] as $numero) {
        expect($seccion)->toContain('id="valor-tab-'.$numero.'"');
        expect($seccion)->toContain('data-valor="'.$numero.'"');
        expect($seccion)->toContain('>0'.$numero.'<');
    }

    // Una imagen por ventaja, y la primera es la que se ve sin JavaScript.
    expect(substr_count($seccion, '<img'))->toBe(4);
    expect(substr_count($seccion, 'class="is-activa"'))->toBe(1);

    // Las otras tres imágenes no se anuncian: solo se lee la que está a la
    // vista (`aria-hidden` también lo llevan iconos y flechas, de ahí el filtro).
    preg_match_all('#<img[^>]*aria-hidden="true"#', $seccion, $ocultas);
    expect($ocultas[0])->toHaveCount(3);
});

it('muestra las cuatro descripciones completas, no resúmenes', function () {
    $texto = strip_tags(seccionValor($this->get('/')->getContent()));

    $descripciones = [
        'Integramos inteligencia artificial en cada una de nuestras soluciones para automatizar procesos y optimizar decisiones.',
        'Ofrecemos productos listos como LMS, CMS y Facturación Electrónica, junto con servicios a medida.',
        'Software que se adapta a tu empresa, con arquitectura modular que permite añadir funciones conforme evolucionen tus necesidades.',
        'Brindamos capacitación y soporte post-implementación, con una relación cercana y profesional con cada cliente.',
    ];

    foreach ($descripciones as $descripcion) {
        expect($texto)->toContain($descripcion);
    }
});

it('reemplaza la lista anterior de ventajas en el home', function () {
    $html = $this->get('/')->getContent();

    // Una sola sección de valor en la portada: si quedara la anterior, habría
    // dos versiones conviviendo.
    expect(substr_count($html, 'class="ara-valor"'))->toBe(1);

    // La sección previa pintaba cada ventaja con `feature-item`, cuya caja de
    // icono lleva `flex-shrink-0` (la tarjeta de servicios usa otras clases).
    expect($html)->not->toContain('ara-icon-box flex-shrink-0');
});

it('usa el mismo container que el resto de secciones del home', function () {
    $seccion = seccionValor($this->get('/')->getContent());

    // El ancho y el padding lateral vienen del container de Tailwind, como en
    // servicios y productos; la sección no trae su propio ancho.
    expect($seccion)->toContain('ara-valor__envoltura max-w-7xl mx-auto px-4 sm:px-6 lg:px-8');
});

it('deja el estilo y el comportamiento en los archivos que carga el navegador', function () {
    $css = file_get_contents(public_path('css/webpage-v2.css'));

    expect($css)->toContain('.ara-valor__lista');
    expect($css)->toContain('.ara-valor__panel');
    expect($css)->toContain("@media (max-width: 900px)");

    // Nada de copias en resources: la hoja que se sirve es la de public.
    expect(file_exists(resource_path('css/webpage-v2.css')))->toBeFalse();

    $js = file_get_contents(public_path('js/aracode-evolucion.js'));

    expect($js)->toContain('valorEnPestanas');
    expect($js)->toContain('valor-panel');
    expect($js)->toContain('aria-labelledby');

    // El CTA de correo desapareció del home: su código no debe quedar colgado.
    expect($js)->not->toContain('ctaEmail');
});
