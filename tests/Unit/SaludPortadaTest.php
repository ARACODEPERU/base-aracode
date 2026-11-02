<?php

use App\Support\PaginaPrincipal;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Portada SALUD (módulo de salud)
|--------------------------------------------------------------------------
|
| SALUD es una portada de este mismo sitio —no un sitio de producto aparte— que
| se puede publicar en la raíz del dominio desde Parámetros → «Pagina web
| principal». Estas pruebas fijan lo que esa decisión promete:
|
|   1. la opción existe en el catálogo y su vista Blade existe;
|   2. al elegirla, la raíz responde con la portada de salud y no con la
|      corporativa;
|   3. la página publica la agenda de pacientes, la historia clínica y las
|      características reales del módulo (CIE-10, SMS, Google Calendar,
|      odontograma, firma con PIN y cobros);
|   4. publica las promociones;
|   5. las fotos viajan con el proyecto y no se enlazan desde internet.
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
 * Publica la portada SALUD como página principal, como si se hubiera elegido
 * desde la pantalla de Parámetros.
 */
function elegirPortadaSalud(): void
{
    // Query builder y no Eloquent: la tabla mínima de estas pruebas no tiene
    // las columnas de timestamps que Eloquent escribiría.
    DB::table('parameters')->updateOrInsert(
        ['parameter_code' => PaginaPrincipal::PARAMETRO],
        ['value_default' => '6']
    );
}

it('ofrece SALUD como portada y la vista que declara existe', function () {
    expect(PaginaPrincipal::nombre('6'))->toBe('SALUD');
    expect(PaginaPrincipal::vista('6'))->toBe('salud.pages.home');
    expect(view()->exists(PaginaPrincipal::vista('6')))->toBeTrue();

    // Es una página de este sitio: no tiene sitio de producto al que enlazar.
    expect(PaginaPrincipal::url('6'))->toBeNull();
});

it('publica la portada de salud en la raíz cuando se elige', function () {
    elegirPortadaSalud();

    expect(PaginaPrincipal::seleccionada())->toBe('6');

    $respuesta = $this->get('/');

    $respuesta->assertOk();
    $respuesta->assertSee('Historia clínica y agenda de pacientes', false);
    $respuesta->assertSee('SALUD', false);
    // Ya no es la portada corporativa.
    $respuesta->assertDontSee('Software Empresarial, IA y Automatización', false);
});

it('publica la agenda, la historia clínica y las características del módulo', function (string $frase) {
    elegirPortadaSalud();

    // `strip_tags` deja fuera los atributos: si la frase aparece es porque se
    // está pintando como texto visible.
    $html = strip_tags($this->get('/')->getContent());

    expect($html)->toContain($frase);
})->with([
    'Agenda del día por doctor y calendario general de citas',
    'Espacios libres calculados según la duración del servicio',
    'Sincronización en dos vías con Google Calendar',
    'Diagnóstico con el catálogo CIE-10',
    'Odontograma por pieza dental',
    'Recordatorios por SMS',
    'Firma de la atención con el PIN del doctor',
    'Cobros y facturación',
    'Historia clínica y receta en PDF con el logo y los datos del consultorio',
]);

it('publica las promociones del módulo', function () {
    elegirPortadaSalud();

    $html = strip_tags($this->get('/')->getContent());

    expect($html)->toContain('Promociones');
    expect($html)->toContain('Demo guiada sin costo');
    expect($html)->toContain('12 meses al precio de 10');
    expect($html)->toContain('Capacitación para tu equipo');
});

it('usa fotos guardadas en el proyecto y no enlaces a internet', function () {
    elegirPortadaSalud();

    foreach (['hero.jpg', 'agenda.jpg', 'historia-clinica.jpg', 'consultorio.jpg'] as $foto) {
        $ruta = public_path('themes/salud/images/' . $foto);

        expect(is_file($ruta))->toBeTrue("Falta la foto {$ruta}");
    }

    $html = $this->get('/')->getContent();

    expect($html)->toContain('themes/salud/css/salud.css');
    expect(is_file(public_path('themes/salud/css/salud.css')))->toBeTrue();
    expect($html)->toContain('themes/salud/images/hero.jpg');
    expect($html)->toContain('themes/salud/images/agenda.jpg');
    expect($html)->toContain('themes/salud/images/historia-clinica.jpg');
});
