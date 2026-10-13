<?php

use App\Support\PaginaPrincipal;
use App\Support\SitiosProducto;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Página principal del dominio
|--------------------------------------------------------------------------
|
| Fija el contrato de config/pagina_principal.php: la portada elegida en el
| parámetro PW00001 (Parámetros → «Pagina web principal») es la que responde en
| la raíz del dominio, el desplegable ofrece todas las portadas disponibles y un
| valor guardado que ya no existe no rompe el sitio: cae a la portada por
| defecto.
|
| Se ejecutan sin RefreshDatabase —y por eso viven en Unit y no en Feature—
| porque la suite Feature de este repositorio no arranca en sqlite: hay
| migraciones que solo funcionan en MySQL (ALTER ... MODIFY, information_schema)
| y abortan la creación del esquema. Aquí solo hacen falta las tablas del CMS
| que necesita WebPageController (ver tests/Pest.php).
|
*/

uses(TestCase::class);

beforeEach(fn () => crearTablasCmsDelSitio());

/**
 * Deja el parámetro de la página principal con un valor concreto, como si se
 * hubiera elegido desde la pantalla de Parámetros.
 */
function elegirPortadaPrincipal(string $valor): void
{
    // Query builder y no Eloquent: la tabla mínima de estas pruebas no tiene
    // las columnas de timestamps que Eloquent escribiría.
    DB::table('parameters')->updateOrInsert(
        ['parameter_code' => PaginaPrincipal::PARAMETRO],
        ['value_default' => $valor]
    );
}

it('ofrece una portada por cada vista que existe en el proyecto', function () {
    $paginas = PaginaPrincipal::paginas();

    expect($paginas)->not->toBeEmpty();

    foreach ($paginas as $valor => $pagina) {
        expect((string) $valor)->toMatch('/^[0-9]+$/');
        expect(trim((string) $pagina['nombre']))->not->toBe('');
        expect(view()->exists($pagina['vista']))->toBeTrue(
            "La portada {$valor} apunta a una vista que no existe: {$pagina['vista']}"
        );
    }
});

it('publica en la raíz la portada elegida en el parámetro', function () {
    elegirPortadaPrincipal('4');

    expect(PaginaPrincipal::seleccionada())->toBe('4');
    expect(PaginaPrincipal::vista())->toBe('kirafact.pages.home');
    expect(PaginaPrincipal::esTorneos())->toBeFalse();
});

it('reconoce la portada de torneos, que es la única que necesita datos', function () {
    elegirPortadaPrincipal(PaginaPrincipal::TORNEOS);

    expect(PaginaPrincipal::esTorneos())->toBeTrue();
    expect(PaginaPrincipal::vista())->toBe('aracode.pages.torneos');
});

it('vuelve a la portada por defecto cuando el valor guardado no existe', function () {
    elegirPortadaPrincipal('99');

    expect(PaginaPrincipal::seleccionada())->toBe(PaginaPrincipal::POR_DEFECTO);
    expect(PaginaPrincipal::vista())->toBe('aracode.pages.home');
});

it('publica la portada por defecto cuando todavía no hay parámetro', function () {
    expect(PaginaPrincipal::seleccionada())->toBe(PaginaPrincipal::POR_DEFECTO);
});

it('pone en el desplegable una opción por cada portada del catálogo', function () {
    $opciones = PaginaPrincipal::opciones();

    expect(array_column($opciones, 'value'))->toBe(array_map('strval', array_keys(PaginaPrincipal::paginas())));
    expect(json_decode(PaginaPrincipal::opcionesJson(), true))->toBe($opciones);
});

it('nombra los sitios de producto igual que config/productos.php', function () {
    foreach (PaginaPrincipal::paginas() as $valor => $pagina) {
        if (($pagina['producto'] ?? null) === null) {
            continue;
        }

        expect(PaginaPrincipal::nombre((string) $valor))->toBe(SitiosProducto::nombre($pagina['producto']));
    }
});

it('enlaza la portada de un producto a su sitio y no inventa URL para las del corporativo', function () {
    expect(PaginaPrincipal::url('3'))->toBe(url('site/kapta'));
    expect(PaginaPrincipal::url('4'))->toBe(url('site/kirafact'));
    expect(PaginaPrincipal::url('5'))->toBe(url('site/pichanguero'));

    expect(PaginaPrincipal::url('1'))->toBeNull();
    expect(PaginaPrincipal::url('2'))->toBeNull();
});

it('responde en la raíz del dominio con la portada elegida', function () {
    elegirPortadaPrincipal('5');

    $enLaRaiz = $this->get('/');

    $enLaRaiz->assertOk();
    // El <title> y el pie del sitio de producto, no los del corporativo.
    $enLaRaiz->assertSee('Organiza tu torneo de fútbol sin Excel', false);
    $enLaRaiz->assertDontSee('Software Empresarial, IA y Automatización', false);

    // Y al cambiar la elección, la raíz cambia con ella.
    elegirPortadaPrincipal(PaginaPrincipal::POR_DEFECTO);

    $corporativa = $this->get('/');

    $corporativa->assertOk();
    $corporativa->assertSee('Software Empresarial, IA y Automatización', false);
    $corporativa->assertDontSee('Organiza tu torneo de fútbol sin Excel', false);
});
