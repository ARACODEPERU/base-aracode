<?php

use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Tarjetas de producto del catálogo (/soluciones y home)
|--------------------------------------------------------------------------
|
| La imagen diseñada del producto ocupa TODA la tarjeta (a sangre, `cover`) y el
| texto se apoya encima, sobre un velo navy que asegura la lectura.
|
| El home y /soluciones presentan el ecosistema con la MISMA tarjeta: la
| variante `sobrepuesta`, tres por fila, con el rubro en píldora, el nombre, la
| frase corta (que es además el texto alternativo de la imagen) y la píldora
| blanca de la acción. La lista de prestaciones no se pinta en ninguna de las
| dos: alargaba las tarjetas y dejaba el catálogo distinto de la portada.
|
| Estas pruebas fijan esas reglas: la imagen va primero en el marcado y llena la
| tarjeta, el resumen va por la frase corta y las dos páginas comparten rejilla
| y tarjeta. El aspecto que no cabe aquí se comprueba contra webpage-v2.css.
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

/**
 * Clase de la rejilla que envuelve las tarjetas: el <div> con la rejilla que va
 * justo antes de la primera tarjeta.
 */
function rejillaProductos(string $html): string
{
    $antes = substr($html, 0, strpos($html, 'ara-product-card'));
    $inicio = strrpos($antes, '<div class="');

    preg_match('#^<div class="([^"]+)"#', substr($antes, $inicio), $clase);

    return $clase[1] ?? '';
}

it('pone la franja de la imagen antes que el texto, con su texto alternativo', function (string $ruta, int $esperadas) {
    $tarjetas = tarjetasProducto($this->get($ruta)->getContent());

    expect($tarjetas)->toHaveCount($esperadas);

    foreach ($tarjetas as $tarjeta) {
        // La franja de la imagen va primero: es lo que se ve al llegar a la
        // tarjeta, no el texto.
        expect(strpos($tarjeta, 'ara-product-media'))->toBeLessThan(strpos($tarjeta, 'ara-product-content'));

        // Los servicios no tienen imagen diseñada: su franja lleva degradado e
        // icono, así que no hay texto alternativo que revisar. Se comprueban en
        // su propia prueba.
        if (str_contains($tarjeta, 'ara-product-card--plano')) {
            expect($tarjeta)->not->toContain('<img');

            continue;
        }

        // La imagen se describe (alt no vacío): es la que presenta el producto.
        preg_match('#<img\b[^>]*\balt="([^"]+)"#', $tarjeta, $alt);

        expect($alt)->not->toBeEmpty();
        expect(mb_strlen($alt[1]))->toBeGreaterThan(10);
    }
})->with([
    // Siete en /soluciones desde que el servicio de páginas web (`/paginas-web`)
    // se sumó al ecosistema: cuatro productos y tres servicios.
    ['/soluciones', 7],
    ['/', 3],
]);

it('usa la misma tarjeta sobrepuesta en la portada y en el catálogo', function (string $ruta, int $esperadas) {
    $tarjetas = tarjetasProducto($this->get($ruta)->getContent());

    expect($tarjetas)->toHaveCount($esperadas);

    foreach ($tarjetas as $tarjeta) {
        expect($tarjeta)->toContain('ara-product-card--sobrepuesta');
        expect($tarjeta)->toContain('ara-product-chip');
        expect($tarjeta)->toContain('ara-product-text');
        expect($tarjeta)->toContain('ara-product-cta');
        expect($tarjeta)->not->toContain('ara-product-features');
    }
})->with([
    ['/soluciones', 7],
    ['/', 3],
]);

it('resume el producto con la frase corta, visible y como texto alternativo', function (string $ruta, string $frase) {
    $html = $this->get($ruta)->getContent();

    // `strip_tags` deja fuera el contenido de los atributos, así que si la frase
    // aparece es porque se está pintando como texto visible.
    expect(strip_tags($html))->toContain($frase);

    // Y sigue describiendo la imagen del producto.
    expect($html)->toContain('alt="'.$frase.'"');
})->with([
    ['/', 'Plataforma SaaS para gestión y formación educativa.'],
    ['/soluciones', 'Plataforma SaaS para gestión y formación educativa.'],
]);

it('presenta las tarjetas con la rejilla del home: tres por fila', function () {
    $catalogo = rejillaProductos($this->get('/soluciones')->getContent());
    $portada = rejillaProductos($this->get('/')->getContent());

    // La misma rejilla en las dos páginas: tres columnas desde `lg` y dos desde
    // `md`, con la misma separación.
    expect($catalogo)->toBe($portada);
    expect($catalogo)->toContain('lg:grid-cols-3');
});

it('muestra el diseño entero dentro de la tarjeta, sin recortarlo', function () {
    // La tarjeta mantiene su proporción (4/3) y el diseño entra completo
    // (`contain`): lo que sobra arriba y abajo lo rellena el mismo diseño
    // desenfocado que publica la tarjeta en `--ara-product-media-bg`. Con
    // `cover` el arte se recortaba por los lados y se perdían el titular y el
    // logo del producto.
    //
    // Se mira la hoja que carga el navegador (`public/css`): es la que publica
    // el sitio.
    $css = file_get_contents(public_path('css/webpage-v2.css'));

    preg_match('#\.ara-product-card--sobrepuesta \{(?<cuerpo>[^}]*)\}#', $css, $tarjeta);

    expect($tarjeta)->not->toBeEmpty('No se encontró la variante sobrepuesta');
    expect($tarjeta['cuerpo'])->toContain('aspect-ratio: 4 / 3');

    preg_match('#\.ara-product-card--sobrepuesta \.ara-product-media img \{(?<cuerpo>[^}]*)\}#', $css, $imagen);

    expect($imagen)->not->toBeEmpty('No se encontró la imagen de la variante sobrepuesta');
    expect($imagen['cuerpo'])->toContain('object-fit: contain');
    expect($imagen['cuerpo'])->not->toContain('object-fit: cover');

    // Y cada tarjeta publica el relleno desenfocado de su propio diseño.
    foreach (tarjetasProducto($this->get('/soluciones')->getContent()) as $tarjetaHtml) {
        expect($tarjetaHtml)->toContain('--ara-product-media-bg');
        expect($tarjetaHtml)->toContain('--ara-product-media-ratio');
    }
});

it('no recorta el diseño del producto en su franja', function () {
    // Los diseños del ecosistema no comparten proporción —s1, s3 y s4 son
    // cuadrados (1:1) y s2 es panorámico (2:1)—, así que una franja de
    // proporción fija solo puede enseñarlos enteros ajustándolos dentro: con
    // `cover` se perdía el 44 % del alto de los cuadrados y los laterales del
    // panorámico.
    //
    // Se miran las DOS copias de la hoja: la de resources es el original y la de
    // public la que carga el navegador. Si la regla viviera solo en una, en
    // producción el diseño seguiría recortándose.
    foreach (['css/webpage-v2.css', '../resources/css/webpage-v2.css'] as $hoja) {
        $css = file_get_contents(public_path($hoja));

        preg_match('#\.ara-product-card \.ara-product-media img \{(?<cuerpo>[^}]*)\}#', $css, $regla);

        expect($regla)->not->toBeEmpty('No se encontró la regla de la imagen de la tarjeta');

        expect($regla['cuerpo'])->toContain('object-fit: contain')
            ->and($regla['cuerpo'])->not->toContain('object-fit: cover');

        // Y al pasar el ratón la imagen no se amplía: dentro de una franja que
        // ajusta el diseño, un `scale` lo recortaría otra vez por los cuatro
        // lados —justo lo que se acaba de corregir—.
        preg_match('#\.ara-product-card:hover \.ara-product-media img \{(?<cuerpo>[^}]*)\}#', $css, $realce);

        expect($realce)->not->toBeEmpty('No se encontró el realce de la imagen al pasar el ratón');
        expect($realce['cuerpo'])->not->toContain('scale');
    }
});

it('incorpora los servicios a la misma tarjeta, con su propia imagen', function () {
    $tarjetas = tarjetasProducto($this->get('/soluciones')->getContent());

    // Las tres últimas tarjetas son servicios de ARACODE: no tienen web propia,
    // pero sí diseño, y usan la misma tarjeta del resto del ecosistema.
    expect(array_slice($tarjetas, 4))->toHaveCount(3);

    foreach (array_slice($tarjetas, 4) as $servicio) {
        expect($servicio)->toContain('ara-product-card--sobrepuesta');
        expect($servicio)->toContain('ara-product-media');
        expect($servicio)->toContain('<img');
        expect($servicio)->toContain('ara-product-chip');
        expect($servicio)->toContain('ara-product-text');
        expect($servicio)->not->toContain('ara-product-features');
        expect($servicio)->toContain('ara-product-cta');
    }
});
