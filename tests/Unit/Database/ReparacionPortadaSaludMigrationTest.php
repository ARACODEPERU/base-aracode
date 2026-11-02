<?php

use App\Support\PaginaPrincipal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Reparación de la opción SALUD en el desplegable
|--------------------------------------------------------------------------
|
| La migración que agregó SALUD escribe en json_query_data las opciones que ve
| en config/pagina_principal.php. Si en producción se ejecutó con el config
| cacheado de una versión anterior, guardó las opciones 1 a 5 y la opción
| quedó fuera de la base de datos (aunque la migración figure como ejecutada).
|
| Esta migración repara ese caso escribiendo la opción de forma explícita. Las
| pruebas cubren los tres escenarios: catálogo viejo, catálogo ya correcto
| (idempotencia) y reversión.
|
*/

uses(TestCase::class);

function crearTablaParametrosDeSaludReparacion(): void
{
    Schema::create('parameters', function ($tabla) {
        $tabla->id();
        $tabla->string('parameter_code')->nullable();
        $tabla->string('description')->nullable();
        $tabla->string('control_type')->nullable();
        $tabla->text('json_query_data')->nullable();
        $tabla->string('value_default')->nullable();
        $tabla->timestamps();
    });
}

function migracionQueAseguraLaPortadaSalud(): object
{
    return require base_path('database/migrations/2026_10_31_000002_asegurar_opcion_portada_salud.php');
}

/**
 * Opciones guardadas en PW00001.
 *
 * @return array<int, array<string, string>>
 */
function opcionesDeLaPortadaSalud(): array
{
    $json = DB::table('parameters')
        ->where('parameter_code', PaginaPrincipal::PARAMETRO)
        ->value('json_query_data');

    return json_decode((string) $json, true) ?? [];
}

it('agrega SALUD cuando la migración anterior guardó el catálogo viejo', function () {
    crearTablaParametrosDeSaludReparacion();

    // Catálogo que dejó la migración anterior si corrió con config cacheado.
    DB::table('parameters')->insert([
        'parameter_code' => PaginaPrincipal::PARAMETRO,
        'value_default' => '3',
        'json_query_data' => '[{"value":"1","label":"Aracode Principal"},{"value":"2","label":"Aracode torneos"},{"value":"3","label":"KAPTA LMS"},{"value":"4","label":"KIRAFACT"},{"value":"5","label":"Pichanguero"}]',
    ]);

    migracionQueAseguraLaPortadaSalud()->up();

    $opciones = opcionesDeLaPortadaSalud();

    expect(array_column($opciones, 'value'))->toBe(['1', '2', '3', '4', '5', '6']);
    expect(array_column($opciones, 'label'))->toContain('SALUD');

    // La portada publicada no cambia.
    expect(DB::table('parameters')->where('parameter_code', PaginaPrincipal::PARAMETRO)->value('value_default'))->toBe('3');
});

it('no duplica la opción si SALUD ya estaba publicada', function () {
    crearTablaParametrosDeSaludReparacion();

    $conSalud = PaginaPrincipal::opcionesJson();

    DB::table('parameters')->insert([
        'parameter_code' => PaginaPrincipal::PARAMETRO,
        'value_default' => '6',
        'json_query_data' => $conSalud,
    ]);

    migracionQueAseguraLaPortadaSalud()->up();

    expect(DB::table('parameters')->where('parameter_code', PaginaPrincipal::PARAMETRO)->value('json_query_data'))->toBe($conSalud);
});

it('al revertir quita SALUD y conserva el resto del catálogo', function () {
    crearTablaParametrosDeSaludReparacion();

    DB::table('parameters')->insert([
        'parameter_code' => PaginaPrincipal::PARAMETRO,
        'value_default' => '1',
        'json_query_data' => PaginaPrincipal::opcionesJson(),
    ]);

    $migracion = migracionQueAseguraLaPortadaSalud();
    $migracion->down();

    expect(array_column(opcionesDeLaPortadaSalud(), 'value'))->toBe(['1', '2', '3', '4', '5']);
    expect(DB::table('parameters')->where('parameter_code', PaginaPrincipal::PARAMETRO)->value('value_default'))->toBe('1');
});
