<?php

use App\Support\PaginaPrincipal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Migración de la portada SALUD
|--------------------------------------------------------------------------
|
| El desplegable de «Pagina web principal» no lee config/pagina_principal.php
| en cada carga: las opciones viven en json_query_data del parámetro PW00001.
| Por eso agregar SALUD al catálogo necesita una migración que refresque esa
| lista. Estas pruebas cuidan las dos reglas de esa migración:
|
|   * publica la opción SALUD sin cambiar la portada que ya estaba elegida;
|   * al revertir deja las cinco opciones anteriores y tampoco toca el valor.
|
*/

uses(TestCase::class);

/**
 * Tabla de parámetros con las columnas que toca la migración.
 */
function crearTablaParametrosDeSalud(): void
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

/**
 * Instancia de la migración, tal como la ejecuta artisan.
 */
function migracionDeLaPortadaSalud(): object
{
    return require base_path('database/migrations/2026_10_31_000001_agregar_portada_salud.php');
}

/**
 * Valor y opciones guardados en el parámetro PW00001.
 *
 * @return array{valor: ?string, opciones: array<int, mixed>, filas: int}
 */
function portadaSaludGuardada(): array
{
    $filas = DB::table('parameters')->where('parameter_code', PaginaPrincipal::PARAMETRO)->get();

    return [
        'filas' => $filas->count(),
        'valor' => $filas->first()->value_default ?? null,
        'opciones' => json_decode((string) ($filas->first()->json_query_data ?? ''), true) ?? [],
    ];
}

it('agrega la opción SALUD al desplegable sin cambiar la portada elegida', function () {
    crearTablaParametrosDeSalud();

    DB::table('parameters')->insert([
        'parameter_code' => PaginaPrincipal::PARAMETRO,
        'value_default' => PaginaPrincipal::POR_DEFECTO,
        'json_query_data' => '[]',
    ]);

    migracionDeLaPortadaSalud()->up();

    $guardado = portadaSaludGuardada();

    expect($guardado['valor'])->toBe(PaginaPrincipal::POR_DEFECTO);
    expect($guardado['opciones'])->toBe(PaginaPrincipal::opciones());
    expect(array_column($guardado['opciones'], 'label'))->toContain('SALUD');
});

it('crea el parámetro con el catálogo completo cuando todavía no existe', function () {
    crearTablaParametrosDeSalud();

    migracionDeLaPortadaSalud()->up();

    $guardado = portadaSaludGuardada();

    expect($guardado['filas'])->toBe(1);
    expect($guardado['valor'])->toBe(PaginaPrincipal::POR_DEFECTO);
    expect($guardado['opciones'])->toBe(PaginaPrincipal::opciones());
});

it('es idempotente: volver a ejecutarla no cambia nada', function () {
    crearTablaParametrosDeSalud();

    $migracion = migracionDeLaPortadaSalud();
    $migracion->up();
    $primera = portadaSaludGuardada();

    $migracion->up();

    expect(portadaSaludGuardada())->toBe($primera);
});

it('al revertir deja las cinco portadas anteriores y no toca la elegida', function () {
    crearTablaParametrosDeSalud();

    $migracion = migracionDeLaPortadaSalud();
    $migracion->up();
    DB::table('parameters')->where('parameter_code', PaginaPrincipal::PARAMETRO)
        ->update(['value_default' => '6']);

    $migracion->down();

    $guardado = portadaSaludGuardada();

    expect(array_column($guardado['opciones'], 'value'))->toBe(['1', '2', '3', '4', '5']);
    expect($guardado['valor'])->toBe('6');
});
