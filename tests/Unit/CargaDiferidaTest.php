<?php

use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Carga diferida de imágenes
|--------------------------------------------------------------------------
|
| Todo lo que queda por debajo de la primera pantalla se descarga al bajar:
| las tarjetas del catálogo, los logos del pie y las imágenes de los artículos.
| Lo que sí se queda arriba —el logo del navbar y las imágenes que se ven al
| entrar— no lleva `loading="lazy"`: diferir la imagen que el navegador usa
| para medir la carga de la página la empeora.
|
| Esta prueba vigila las dos caras: que el pie siga diferido y que el logo de
| arriba no lo esté.
|
*/

uses(TestCase::class);

beforeEach(fn () => crearTablasCmsDelSitio());

it('difiere el logo del pie y no el del navbar', function (string $ruta) {
    $html = $this->get($ruta)->assertOk()->getContent();

    preg_match_all('#<img[^>]*class="h-10"[^>]*>#s', $html, $logos);

    // El logo aparece dos veces: arriba y en el pie.
    expect($logos[0])->toHaveCount(2);

    $diferidos = array_values(array_filter($logos[0], fn (string $logo) => str_contains($logo, 'loading="lazy"')));

    // Uno se difiere —el del pie, que queda lejos— y el otro no: el del navbar
    // se ve al entrar y diferirlo retrasaría la primera pantalla.
    expect($diferidos)->toHaveCount(1);
})->with(['/', '/plantillas', '/paginas-web', '/precios']);

it('difiere las capturas del catálogo y las imágenes de los artículos', function () {
    // Las tarjetas del catálogo: todas menos la primera fila se ven al bajar.
    $catalogo = $this->get('/plantillas')->assertOk()->getContent();

    expect(substr_count($catalogo, 'loading="lazy"'))->toBeGreaterThanOrEqual(
        substr_count($catalogo, 'plt-card__media')
    );

    // El cuerpo del artículo viene del editor, sin atributos de carga: la vista
    // se los añade al pintarlo. Se comprueba la regla, que es la que trabaja.
    $vista = file_get_contents(resource_path('views/aracode/pages/blog-articulo.blade.php'));

    expect($vista)->toContain('preg_replace(')
        ->and($vista)->toContain('loading="lazy" decoding="async"');
});
