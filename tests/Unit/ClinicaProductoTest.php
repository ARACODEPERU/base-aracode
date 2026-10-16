<?php

use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Ficha de Gestión de Clínica (/clinica)
|--------------------------------------------------------------------------
|
| Gestión de Clínica es el producto del módulo Salud y, a diferencia de KAPTA,
| KIRAFACT y Pichanguero, todavía no tiene web propia: su página pública es la
| de este sitio. Estas pruebas fijan lo que esa decisión promete, para que un
| cambio posterior no la deshaga sin darse cuenta:
|
|   1. la página responde 200 y es canónica de sí misma;
|   2. publica un solo SoftwareApplication, firmado por ARACODE;
|   3. el pie la enlaza como una solución más del ecosistema, en la misma
|      pestaña (no es otra web);
|   4. el texto vende lo que el módulo Salud hace de verdad —agenda, historia
|      clínica, CIE-10, odontograma, firma con PIN, avisos por SMS, Google
|      Calendar y cobros con comprobante electrónico— y no una lista de deseos;
|   5. la URL entra en el mapa del sitio.
|
| Se ejecuta sin RefreshDatabase —y por eso vive en Unit y no en Feature—
| porque la suite Feature de este repositorio no arranca en sqlite: hay
| migraciones que solo funcionan en MySQL (ALTER ... MODIFY, information_schema)
| y abortan la creación del esquema. Esta página solo necesita las tablas del
| CMS, que se crean aquí (ver tests/Pest.php).
|
*/

uses(TestCase::class);

// WebPageController resuelve el listado de cursos del CMS en su constructor: sin
// estas tablas la página ni siquiera se renderiza.
beforeEach(fn () => crearTablasCmsDelSitio());

/**
 * Extrae los bloques JSON-LD del HTML y devuelve los que son de un tipo dado.
 *
 * @return array<int, array<string, mixed>>
 */
function jsonLdClinicaDeTipo(string $html, string $tipo): array
{
    preg_match_all('#<script type="application/ld\+json">(.*?)</script>#si', $html, $bloques);

    $encontrados = [];

    foreach ($bloques[1] as $bloque) {
        $datos = json_decode(trim($bloque), true);

        // Un JSON-LD ilegible es un fallo en sí mismo: se devuelve el error
        // para que la prueba pueda detectarlo en vez de ignorarlo.
        if ($datos === null) {
            $encontrados[] = ['error' => json_last_error_msg(), 'crudo' => $bloque];

            continue;
        }

        if (($datos['@type'] ?? null) === $tipo) {
            $encontrados[] = $datos;
        }
    }

    return $encontrados;
}

it('publica la página de Gestión de Clínica con canónica propia', function () {
    $respuesta = $this->get('/clinica');

    $respuesta->assertStatus(200);

    $html = $respuesta->getContent();

    expect($html)->toContain('<link rel="canonical" href="'.url('/clinica').'">');

    // El título de la ficha dice quién la desarrolla, como en el resto del
    // ecosistema: es lo que aporta la página de ARACODE.
    preg_match('#<title>(.*?)</title>#s', $html, $titulo);

    expect($titulo[1])->toContain('Gestión de Clínica');
    expect($titulo[1])->toContain('ARACODE');
});

it('describe el producto con un solo SoftwareApplication firmado por ARACODE', function () {
    $html = $this->get('/clinica')->getContent();

    $aplicaciones = jsonLdClinicaDeTipo($html, 'SoftwareApplication');

    expect($aplicaciones)->toHaveCount(1);
    expect($aplicaciones[0])->not->toHaveKey('error');

    $aplicacion = $aplicaciones[0];

    expect($aplicacion['name'])->toBe('Gestión de Clínica');
    expect($aplicacion['mainEntityOfPage'])->toBe(url('/clinica'));

    // Sin web propia todavía: el producto se declara en esta misma página.
    expect($aplicacion['url'])->toBe(url('/clinica'));

    expect($aplicacion['author']['name'])->toBe('ARACODE Smart Solutions');
    expect($aplicacion['publisher']['name'])->toBe('ARACODE Smart Solutions');
});

it('enlaza la ficha desde el pie, junto al resto de soluciones', function () {
    // El enlace vive en el pie de todo el sitio: se revisa en la portada, que
    // es donde lo ve cualquier visitante.
    $html = $this->get('/')->getContent();

    expect($html)->toContain('href="'.route('solucion_clinica').'"');
    expect($html)->toContain('>Gestión de Clínica</a>');

    // El pie lista el ecosistema y no abre pestañas nuevas: Gestión de Clínica
    // es una página de este mismo sitio (a diferencia de KAPTA, KIRAFACT y
    // Pichanguero, que sí tienen web propia).
    preg_match('#<li><a href="'.preg_quote(route('solucion_clinica'), '#').'"[^>]*>(.*?)</a></li>#s', $html, $enlace);

    expect($enlace)->not->toBeEmpty('El pie no enlaza la ficha de Gestión de Clínica');
    expect($enlace[0])->not->toContain('target="_blank"');
});

it('cuenta lo que el módulo Salud hace, no una lista de deseos', function (string $frase) {
    // Frases tomadas de la implementación: agenda y disponibilidad
    // (HealAgendaController), avisos por SMS (AppointmentNoticeService),
    // sincronización con Google Calendar (GoogleCalendarSyncService), atención
    // con CIE-10, signos vitales, odontograma y firma con PIN
    // (HealAttentionController), cobros preparados para su comprobante
    // (HealProcedureChargeController).
    $html = $this->get('/clinica')->getContent();

    // `strip_tags` deja fuera los atributos: si la frase aparece es porque se
    // está pintando como texto visible.
    expect(strip_tags($html))->toContain($frase);
})->with([
    'Espacios libres calculados según la duración del servicio',
    'Recordatorios automáticos por SMS antes de la cita',
    'Diagnóstico con el catálogo CIE-10',
    'Odontograma por pieza dental',
    'Firma de la atención con el PIN del doctor',
    'Google Calendar',
    'Cobros que terminan en comprobante electrónico',
]);

it('inscribe la página en el mapa del sitio', function () {
    $sitemap = file_get_contents(public_path('sitemap.xml'));

    expect($sitemap)->toContain('aracodeperu.com/clinica</loc>');
});
