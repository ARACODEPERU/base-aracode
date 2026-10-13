<?php

use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Tarjetas de producto del catálogo (/soluciones y home)
|--------------------------------------------------------------------------
|
| La imagen diseñada del producto es lo primero que se ve en la tarjeta: va en
| su propia franja, arriba, con la proporción reservada por CSS y sin ningún
| degradado encima.
|
| El resumen del producto se cuenta por una sola vía, y cada página tiene la
| suya: en /soluciones la tarjeta lista las prestaciones (es la que compara) y en
| el home, tres por fila, la tarjeta es compacta y muestra la frase corta. Las
| dos a la vez repetían lo mismo y alargaban la tarjeta; la frase sigue siendo,
| además, el texto alternativo de la imagen.
|
| Estas pruebas fijan esas reglas: la imagen es la protagonista (franja propia +
| texto alternativo, y siempre antes del bloque de texto) y el resumen no se
| duplica. La parte puramente visual —tamaño, proporción, tres por fila— se
| comprueba en webpage-v2.css y con la revisión en Chrome.
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

        // La imagen se describe (alt no vacío): es la que presenta el producto.
        preg_match('#<img\b[^>]*\balt="([^"]+)"#', $tarjeta, $alt);

        expect($alt)->not->toBeEmpty();
        expect(mb_strlen($alt[1]))->toBeGreaterThan(10);

        // El resumen va por una sola vía: la lista de prestaciones en el
        // catálogo, la frase corta en la tarjeta compacta del home. Nunca las
        // dos, que era lo que repetía el producto y alargaba la tarjeta.
        if (str_contains($tarjeta, 'ara-product-card--compacta')) {
            expect($tarjeta)->toContain('ara-product-text');
            expect($tarjeta)->not->toContain('ara-product-features');
        } else {
            expect($tarjeta)->toContain('ara-product-features');
            expect($tarjeta)->not->toContain('ara-product-text');
        }
    }
})->with([
    // Siete desde que se sumó el servicio de páginas web (`/paginas-web`) al
    // ecosistema de /soluciones: cuatro productos y tres servicios.
    ['/soluciones', 7],
    ['/', 3],
]);

it('comparte la frase del producto en la tarjeta compacta del home, sin lista de prestaciones', function () {
    $html = $this->get('/')->getContent();
    $tarjetas = tarjetasProducto($html);

    expect($tarjetas)->toHaveCount(3);

    foreach ($tarjetas as $tarjeta) {
        expect($tarjeta)->toContain('ara-product-card--compacta');
        expect($tarjeta)->toContain('ara-product-text');
        expect($tarjeta)->not->toContain('ara-product-features');
    }

    // La frase de la tarjeta es la misma que describe la imagen: no hay un texto
    // inventado solo para el home.
    $frase = 'Plataforma SaaS para gestión y formación educativa.';

    expect($html)->toContain('alt="'.$frase.'"');
    expect(strip_tags($html))->toContain($frase);
});

it('no repite la frase corta en las tarjetas que listan prestaciones', function () {
    $html = $this->get('/soluciones')->getContent();
    $frase = 'Plataforma SaaS para gestión y formación educativa completa.';

    // `strip_tags` deja fuera el contenido de los atributos, así que si la frase
    // aparece es porque se está pintando como texto visible. En el catálogo la
    // tarjeta detalla con la lista: la frase se quedaría repitiendo.
    expect(strip_tags($html))->not->toContain($frase);

    // Y sigue presente donde aporta: en el texto alternativo de la imagen.
    expect($html)->toContain('alt="'.$frase.'"');
});

it('incorpora los servicios al mismo esquema, con su franja y sin imagen', function () {
    $tarjetas = tarjetasProducto($this->get('/soluciones')->getContent());

    // Las tres últimas tarjetas son servicios de ARACODE: no tienen web propia
    // ni imagen diseñada, así que su franja lleva degradado e icono; conservan su
    // frase de apoyo porque no hay imagen que explique el servicio.
    expect(array_slice($tarjetas, 4))->toHaveCount(3);

    foreach (array_slice($tarjetas, 4) as $servicio) {
        expect($servicio)->toContain('ara-product-card--plano');
        expect($servicio)->toContain('ara-product-media');
        expect($servicio)->not->toContain('<img');
        expect($servicio)->toContain('ara-product-text');
        expect($servicio)->toContain('ara-product-cta');
    }
});
