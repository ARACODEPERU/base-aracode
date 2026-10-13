<?php

use App\Support\CatalogoPlantillas;
use App\Support\ServicioWeb;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Servicio de páginas web (/paginas-web y /plantillas)
|--------------------------------------------------------------------------
|
| El catálogo dice qué se puede publicar y esta página, cómo y por cuánto. Los
| planes viven en config/servicio_web.php y se publican en tres sitios a la vez
| (la tarjeta de la página, el JSON-LD, el bloque del catálogo y la ficha de
| cada plantilla): estas pruebas fijan lo que no puede desincronizarse.

|  - El año completo se vende como «dos meses gratis», así que el importe anual
|    tiene que ser exactamente diez mensualidades: si alguien sube el mensual y
|    olvida el anual, la página dejaría de decir la verdad.
|  - Todo formato del catálogo tiene su plan y todo plan tiene su formato: un
|    formato sin precio no se puede vender y un precio sin plantillas no se
|    puede comprar.
|  - La página responde 200, es canónica de sí misma y publica exactamente los
|    planes de la config (nunca un importe escrito a mano en la vista).
|  - El servicio no promete el panel de autogestión del cliente mientras no
|    exista (docs/SERVICIO_PAGINAS_WEB.md). El día que se construya, esta prueba
|    se actualiza a propósito.
|
| Corren como "Unit" y no como "Feature" por la misma razón que las del catálogo
| de plantillas: la suite Feature de este repositorio no arranca en sqlite
| (migraciones MySQL-only de otros módulos). crearTablasCmsDelSitio() vive en
| tests/Pest.php.
|
*/

uses(TestCase::class);

beforeEach(fn () => crearTablasCmsDelSitio());

it('vende el año como diez mensualidades, para que «dos meses gratis» sea cierto', function () {
    $planes = ServicioWeb::planes();

    expect($planes)->toHaveCount(3);

    foreach ($planes as $plan) {
        expect($plan['mensual'])->toBeGreaterThan(0);
        expect($plan['anual'])->toBe($plan['mensual'] * 10);
        expect($plan['meses_gratis'])->toBe(2);

        // Lo que se publica es el texto ya formateado, no el número crudo.
        expect($plan['precio_mensual'])->toBe(ServicioWeb::precio($plan['mensual']));
        expect($plan['precio_anual'])->toBe(ServicioWeb::precio($plan['anual']));

        expect($plan['incluye'])->not->toBeEmpty();
        expect($plan['entrega'])->not->toBe('');
        expect($plan['ideal_para'])->not->toBe('');
    }

    // Uno solo es el destacado: dos tarjetas marcadas como «Más elegido» no
    // resaltarían nada.
    expect(array_filter($planes, fn (array $plan) => $plan['destacado']))->toHaveCount(1);
});

it('formatea los importes como se leen en la web', function () {
    expect(ServicioWeb::precio(89))->toBe('S/ 89');
    expect(ServicioWeb::precio(890))->toBe('S/ 890');
    expect(ServicioWeb::precio(1490))->toBe('S/ 1,490');
    expect(ServicioWeb::precio(1990))->toBe('S/ 1,990');

    // Un plan sin importes no promete meses gratis en vez de dividir por cero.
    expect(ServicioWeb::mesesGratis(['mensual' => 0, 'anual' => 0]))->toBe(0);
    expect(ServicioWeb::mesesGratis(['mensual' => 100, 'anual' => 900]))->toBe(3);
    expect(ServicioWeb::mesesGratis(['mensual' => 100, 'anual' => 1000]))->toBe(2);

    expect(ServicioWeb::plan('no-existe'))->toBeNull();
});

it('le da un plan a cada formato del catálogo, y al revés', function () {
    $tipos = CatalogoPlantillas::tipos();

    expect($tipos)->not->toBeEmpty();

    // Un formato publicado sin plan sería una plantilla que no se puede vender.
    foreach (array_keys($tipos) as $tipo) {
        $plan = ServicioWeb::plan($tipo);

        expect($plan)->not->toBeNull();
        expect($plan['nombre'])->not->toBe('');
        expect($plan['tipo'])->toBe($tipo);
    }

    // Y un plan de un formato retirado sería un precio que no se puede comprar.
    foreach (ServicioWeb::planes() as $plan) {
        expect(array_keys($tipos))->toContain($plan['tipo']);
    }
});

it('responde la página del servicio, canónica de sí misma y con los tres planes', function () {
    $html = $this->get('/paginas-web')->assertOk()->getContent();

    // El layout no arrastra query string: la canónica es la página limpia.
    expect($html)->toContain('<link rel="canonical" href="'.url('/paginas-web').'">');

    foreach (ServicioWeb::planes() as $plan) {
        expect($html)->toContain($plan['nombre']);
        expect($html)->toContain($plan['precio_mensual']);
        expect($html)->toContain($plan['precio_anual']);
        expect($html)->toContain($plan['entrega']);
    }

    // Lo común a todos los planes y cómo se paga, en un solo bloque.
    foreach (ServicioWeb::incluyeSiempre() as $linea) {
        expect($html)->toContain($linea);
    }

    expect($html)->toContain(ServicioWeb::notaPago());

    // Cada plan se presenta con el formato del catálogo que le toca.
    foreach (CatalogoPlantillas::tipos() as $formato) {
        expect($html)->toContain($formato['hashtag']);
    }

    // Datos estructurados: un Service con una oferta mensual y otra anual por
    // plan (de ahí las seis), más el FAQPage.
    expect(substr_count($html, '"@type": "Service"'))->toBe(1);
    expect(substr_count($html, '"@type": "Offer"'))->toBe(count(ServicioWeb::planes()) * 2);
    expect(substr_count($html, '"@type": "Question"'))->toBe(substr_count($html, '"@type": "Answer"'));

    // Las preguntas se pintan en el acordeón y en el marcado a la vez: el texto
    // que ve el visitante y el que lee Google salen del mismo arreglo.
    expect($html)->toContain('¿Qué diferencia hay entre una landing y un sitio web?');
    expect(substr_count($html, '¿Qué diferencia hay entre una landing y un sitio web?'))->toBe(2);
});

it('no promete el panel de autogestión mientras no exista', function () {
    $html = $this->get('/paginas-web')->getContent();

    // Decisión registrada en docs/SERVICIO_PAGINAS_WEB.md: el panel del cliente
    // se anuncia el día que esté construido, no antes. Si esta prueba falla, o
    // hay una promesa que no se puede cumplir o el panel ya existe y hay que
    // actualizar la prueba y el documento.
    expect(stripos($html, 'panel'))->toBeFalse();
});

it('enlaza al servicio desde el catálogo y desde la ficha, con el precio del formato', function () {
    $catalogo = $this->get('/plantillas')->getContent();

    expect($catalogo)->toContain(ServicioWeb::url());

    foreach (ServicioWeb::planes() as $plan) {
        expect($catalogo)->toContain($plan['precio_mensual']);
        expect($catalogo)->toContain($plan['precio_anual']);
    }

    // La ficha muestra el plan de su propio formato: una landing no puede
    // anunciar el precio de la tienda.
    $ficha = $this->get('/plantillas/clinica-dental')->getContent();
    $planLanding = ServicioWeb::plan('landing');

    expect($ficha)->toContain(ServicioWeb::url());
    expect($ficha)->toContain($planLanding['precio_mensual']);

    $planTienda = ServicioWeb::plan('tienda');

    expect($ficha)->not->toContain($planTienda['precio_mensual']);
});

it('presenta el servicio en el ecosistema de /soluciones, con su precio de entrada', function () {
    $html = $this->get('/soluciones')->getContent();

    // La tarjeta del servicio, junto a los productos y a los otros dos servicios.
    // Se aísla la tarjeta antes de mirar lo que promete: en la misma página la
    // tarjeta de KAPTA habla de su panel administrativo, que no es lo mismo que
    // el panel del cliente que este servicio todavía no tiene.
    preg_match_all('#<article class="ara-product-card[^"]*".*?</article>#s', $html, $coincidencias);

    $tarjeta = collect($coincidencias[0])->first(fn (string $t) => str_contains($t, 'Páginas Web'));

    expect($tarjeta)->not->toBeNull();
    expect($tarjeta)->toContain(ServicioWeb::url());
    expect(stripos($tarjeta, 'panel'))->toBeFalse();
    expect($tarjeta)->not->toContain('<img');

    // El precio de entrada lo pone App\Support\ServicioWeb::precioDesde(): el
    // plan más barato de los publicados, no uno escrito en la vista.
    expect($html)->toContain('Desde '.ServicioWeb::precioDesde().' al mes');
    expect(ServicioWeb::precioDesde())->toBe(ServicioWeb::plan('landing')['precio_mensual']);
});

it('inscribe la página del servicio en el mapa del sitio', function () {
    $sitemap = file_get_contents(public_path('sitemap.xml'));

    expect($sitemap)->toContain('aracodeperu.com/paginas-web</loc>');
});
