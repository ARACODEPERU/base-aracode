<?php

use App\Support\PaginaPrincipal;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Portada Dental (módulo de clínicas dentales)
|--------------------------------------------------------------------------
|
| Dental es una portada de este mismo sitio —no un sitio de producto aparte—
| que se puede publicar en la raíz del dominio desde Parámetros → «Pagina web
| principal». Estas pruebas fijan lo que esa decisión promete:
|
|   1. la opción existe en el catálogo y su vista Blade existe;
|   2. al elegirla, la raíz responde con la portada dental y no con la
|      corporativa;
|   3. la página publica la agenda odontológica, las atenciones y las
|      características reales del módulo (calendario por doctor, horas
|      ocupadas, estados de la cita, odontograma, próxima cita desde la
|      atención y cobros);
|   4. publica las promociones;
|   5. las fotos viajan con el proyecto y no se enlazan desde internet.
|
| Se ejecutan sin RefreshDatabase —y por eso viven en Unit y no en Feature—
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
 * Publica la portada Dental como página principal, como si se hubiera elegido
 * desde la pantalla de Parámetros.
 */
function elegirPortadaDental(): void
{
    // Query builder y no Eloquent: la tabla mínima de estas pruebas no tiene
    // las columnas de timestamps que Eloquent escribiría.
    DB::table('parameters')->updateOrInsert(
        ['parameter_code' => PaginaPrincipal::PARAMETRO],
        ['value_default' => '7']
    );
}

it('ofrece Dental como portada y la vista que declara existe', function () {
    expect(PaginaPrincipal::nombre('7'))->toBe('Dental');
    expect(PaginaPrincipal::vista('7'))->toBe('dental.pages.home');
    expect(view()->exists(PaginaPrincipal::vista('7')))->toBeTrue();

    // Es una página de este sitio: no tiene sitio de producto al que enlazar.
    expect(PaginaPrincipal::url('7'))->toBeNull();
});

it('publica la portada dental en la raíz cuando se elige', function () {
    elegirPortadaDental();

    expect(PaginaPrincipal::seleccionada())->toBe('7');

    $respuesta = $this->get('/');

    $respuesta->assertOk();
    $respuesta->assertSee('Clínicas dentales con agenda, atenciones y odontograma', false);
    $respuesta->assertSee('Dental', false);
    // Ya no es la portada corporativa.
    $respuesta->assertDontSee('Software Empresarial, IA y Automatización', false);
});

it('publica la agenda, las atenciones y las características del módulo', function (string $frase) {
    elegirPortadaDental();

    // `strip_tags` deja fuera los atributos: si la frase aparece es porque se
    // está pintando como texto visible.
    $html = strip_tags($this->get('/')->getContent());

    expect($html)->toContain($frase);
})->with([
    'Calendario general del consultorio y agenda de cada doctor',
    'Horas ocupadas consultadas por fecha y doctor antes de agendar',
    'Estados de la cita: confirmada, en sala, atendida o ausente',
    'Próxima cita programada desde la propia atención, con fecha, hora y doctor.',
    'Registro de condiciones y tratamientos en cada diente',
    'Calendario de citas',
    'Horas ocupadas',
    'Estados e importancia',
    'Atenciones odontológicas',
    'Odontograma',
    'Cobros y comprobantes',
]);

it('publica las promociones del módulo', function () {
    elegirPortadaDental();

    $html = strip_tags($this->get('/')->getContent());

    expect($html)->toContain('Promociones');
    expect($html)->toContain('Demo guiada sin costo');
    expect($html)->toContain('12 meses al precio de 10');
    expect($html)->toContain('Capacitación para tu equipo');
});

it('usa fotos guardadas en el proyecto y no enlaces a internet', function () {
    elegirPortadaDental();

    foreach (['hero.jpg', 'consultorio.jpg', 'diagnostico.jpg'] as $foto) {
        $ruta = public_path('themes/dental/images/' . $foto);

        expect(is_file($ruta))->toBeTrue("Falta la foto {$ruta}");
    }

    $html = $this->get('/')->getContent();

    expect($html)->toContain('themes/salud/css/salud.css');
    expect(is_file(public_path('themes/salud/css/salud.css')))->toBeTrue();
    expect($html)->toContain('themes/dental/images/hero.jpg');
    expect($html)->toContain('themes/dental/images/consultorio.jpg');
    expect($html)->toContain('themes/dental/images/diagnostico.jpg');
});
