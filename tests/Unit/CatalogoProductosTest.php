<?php

use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Tarjetas de producto del catálogo (/soluciones y home)
|--------------------------------------------------------------------------
|
| La imagen diseñada del producto es lo primero que se ve en la tarjeta: va en
| su propia franja, arriba, con la proporción reservada por CSS y sin ningún
| degradado encima. El texto corto de apoyo dejó de pintarse —repetía lo que ya
| dicen el nombre, las prestaciones y la imagen— y vive como texto alternativo
| de la imagen.
|
| Estas pruebas fijan esas dos reglas: que la imagen siga siendo la protagonista
| (franja propia + texto alternativo, y siempre antes del bloque de texto) y que
| la frase corta no vuelva a ocupar la tarjeta. La parte puramente visual
| —tamaño, proporción, contraste— se comprueba en webpage-v2.css.
|
*/

uses(TestCase::class);

beforeEach(fn () => crearTablasCmsDelSitio());

/**
 * Devuelve el HTML de las tarjetas de producto de una página.
 *
 * @return array<int, string>
 */
function tarjetasProducto(string $html): array
{
    preg_match_all('#<article class="ara-product-card[^"]*".*?</article>#s', $html, $coincidencias);

    return $coincidencias[0];
}

it('pone la imagen del producto antes que el texto, con su texto alternativo', function (string $ruta, int $esperadas) {
    $tarjetas = tarjetasProducto($this->get($ruta)->getContent());

    expect($tarjetas)->toHaveCount($esperadas);

    foreach ($tarjetas as $tarjeta) {
        // La franja de la imagen va primero: es lo que se ve al llegar a la
        // tarjeta, no el texto.
        expect(strpos($tarjeta, 'ara-product-media'))->toBeLessThan(strpos($tarjeta, 'ara-product-content'));

        // Los servicios no tienen imagen diseñada: su franja lleva degradado e
        // icono, y su frase de apoyo sigue ahí porque no hay imagen que explique
        // el servicio. Se comprueban en su propia prueba.
        if (str_contains($tarjeta, 'ara-product-card--plano')) {
            expect($tarjeta)->not->toContain('<img');

            continue;
        }

        // La imagen se describe (alt no vacío): es la que presenta el producto
        // desde que su frase corta no se pinta.
        preg_match('#<img\b[^>]*\balt="([^"]+)"#', $tarjeta, $alt);

        expect($alt)->not->toBeEmpty();
        expect(mb_strlen($alt[1]))->toBeGreaterThan(10);

        // Nada de párrafos dentro de la tarjeta de producto: el texto es la
        // lista de prestaciones.
        expect($tarjeta)->not->toContain('ara-product-text');
    }
})->with([
    ['/soluciones', 6],
    ['/', 3],
]);

it('no pinta la frase corta del producto: la imagen es la que lo presenta', function (string $ruta, string $frase) {
    $html = $this->get($ruta)->getContent();

    // `strip_tags` deja fuera el contenido de los atributos, así que si la frase
    // aparece es porque se está pintando como texto visible.
    expect(strip_tags($html))->not->toContain($frase);

    // Y sigue presente donde aporta: en el texto alternativo de la imagen.
    expect($html)->toContain('alt="'.$frase.'"');
})->with([
    ['/soluciones', 'Plataforma SaaS para gestión y formación educativa completa.'],
    ['/', 'Plataforma SaaS para gestión y formación educativa.'],
]);

it('incorpora los servicios al mismo esquema, con su franja y sin imagen', function () {
    $tarjetas = tarjetasProducto($this->get('/soluciones')->getContent());

    // Las dos últimas tarjetas son servicios de ARACODE: no tienen web propia ni
    // imagen diseñada, así que su franja lleva degradado e icono; conservan su
    // frase de apoyo porque no hay imagen que explique el servicio.
    foreach (array_slice($tarjetas, 4) as $servicio) {
        expect($servicio)->toContain('ara-product-card--plano');
        expect($servicio)->toContain('ara-product-media');
        expect($servicio)->not->toContain('<img');
        expect($servicio)->toContain('ara-product-text');
        expect($servicio)->toContain('ara-product-cta');
    }
});
