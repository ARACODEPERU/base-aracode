<?php

use App\Support\SitiosProducto;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Contrato SEO de las fichas /kapta, /kirafact y /pichanguero
|--------------------------------------------------------------------------
|
| Las tres URLs se MANTIENEN como ficha de producto del sitio corporativo (no
| se retiran ni se redirigen): la decisión y su porqué están en
| docs/SEO_ECOSISTEMA_PRODUCTOS.md. Estas pruebas fijan lo que esa decisión
| promete para que un cambio posterior no la deshaga sin darse cuenta: cada
| ficha responde 200, se declara canónica de sí misma, publica un solo
| SoftwareApplication que apunta al sitio del producto, no publica importes y
| enlaza al sitio oficial. Además, las URLs retiradas siguen llegando con 301.
|
| Se ejecutan sin RefreshDatabase —y por eso viven en Unit y no en Feature—
| porque la suite Feature de este repositorio no arranca en sqlite: hay
| migraciones que solo funcionan en MySQL (ALTER ... MODIFY, information_schema)
| y abortan la creación del esquema. Estas fichas solo necesitan tres tablas, que
| se crean aquí; el resto del layout no toca la base de datos.
|
*/

uses(TestCase::class);

// WebPageController resuelve el listado de cursos del CMS en su constructor: sin
// estas tablas la ficha ni siquiera se renderiza (ver tests/Pest.php).
beforeEach(fn () => crearTablasCmsDelSitio());

/**
 * Extrae los bloques JSON-LD del HTML y devuelve los que son de un tipo dado.
 *
 * @return array<int, array<string, mixed>>
 */
function jsonLdDeTipo(string $html, string $tipo): array
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

it('mantiene las tres fichas publicadas con canónica propia', function (string $producto) {
    $ruta = "/{$producto}";

    $respuesta = $this->get($ruta);

    $respuesta->assertStatus(200);

    $html = $respuesta->getContent();

    // Canónica propia: la ficha no se declara copia de la web del producto
    // (serían dos URLs compitiendo por la misma página).
    expect($html)->toContain('<link rel="canonical" href="'.url($ruta).'">');

    // El sitio del producto NO es canónico de la ficha: se enlaza, no se declara.
    expect($html)->not->toContain('<link rel="canonical" href="'.SitiosProducto::url($producto).'"');
})->with(['kapta', 'kirafact', 'pichanguero']);

it('publica un solo SoftwareApplication que apunta al sitio del producto', function (string $producto) {
    $ruta = "/{$producto}";

    $html = $this->get($ruta)->getContent();

    $aplicaciones = jsonLdDeTipo($html, 'SoftwareApplication');

    expect($aplicaciones)->toHaveCount(1);
    expect($aplicaciones[0])->not->toHaveKey('error');

    $aplicacion = $aplicaciones[0];

    // El nombre comercial sale del mapa único (config/productos.php): si algún
    // día cambia la grafía, cambia en las tres fichas a la vez.
    expect($aplicacion['name'])->toBe(SitiosProducto::nombre($producto));
    expect($aplicacion['url'])->toBe(SitiosProducto::url($producto));
    expect($aplicacion['mainEntityOfPage'])->toBe(url($ruta));

    // La autoría es lo que aporta la ficha: ARACODE como quien desarrolla.
    expect($aplicacion['author']['name'])->toBe('ARACODE Smart Solutions');
    expect($aplicacion['publisher']['name'])->toBe('ARACODE Smart Solutions');
})->with(['kapta', 'kirafact', 'pichanguero']);

it('no publica importes ni compite por las consultas del producto', function (string $producto) {
    $html = $this->get("/{$producto}")->getContent();

    // Los importes viven en un solo lugar: config/kapta.php o config/kirafact.php
    // y su página de planes. Llegó a haber tres tablas de precios distintas.
    // Se mira solo el texto visible: en el HTML hay rutas de assets con «s/»
    // que no son precios.
    expect(strip_tags($html))->not->toMatch('#S/\s*\d#');

    // El título de la ficha habla de quién la desarrolla, no de planes ni de
    // descargas: eso es lo que evita que la ficha y el sitio del producto
    // compitan por las mismas consultas.
    preg_match('#<title>(.*?)</title>#s', $html, $titulo);

    expect($titulo[1])->toContain('ARACODE');
    expect($titulo[1])->not->toMatch('#(planes|precios|descargar)#i');

    // El texto visible del botón hacia el sitio oficial, con el aviso para
    // lectores de pantalla (el destino abre en otra pestaña).
    expect($html)->toContain('Ir al sitio de '.SitiosProducto::nombre($producto));
})->with(['kapta', 'kirafact', 'pichanguero']);

it('enlaza al sitio oficial y a su página de planes o descargas', function (string $producto) {
    // La segunda página del producto: los planes (KAPTA y KIRAFACT) o las
    // descargas (Pichanguero, que no publica página de planes).
    $paginaSecundaria = SitiosProducto::planes($producto) ?? SitiosProducto::descargas($producto);

    expect($paginaSecundaria)->not->toBeNull();

    $html = $this->get("/{$producto}")->getContent();

    expect($html)->toContain('href="'.SitiosProducto::url($producto).'"');
    expect($html)->toContain('href="'.$paginaSecundaria.'"');

    // Los enlaces salientes no llevan nofollow: es el vendedor recomendando el
    // producto, no contenido patrocinado.
    expect($html)->not->toContain('rel="nofollow"');
})->with(['kapta', 'kirafact', 'pichanguero']);

it('sigue redirigiendo con 301 las URLs antiguas que apuntan a cada ficha', function (string $antigua, string $destino) {
    $this->get($antigua)->assertStatus(301)->assertRedirect($destino);
})->with([
    ['/e-learning', '/kapta'],
    ['/sitios-webs', '/kapta'],
    ['/facturador', '/kirafact'],
    ['/soluciones/kapta', '/kapta'],
    ['/soluciones/facturacion', '/kirafact'],
]);
