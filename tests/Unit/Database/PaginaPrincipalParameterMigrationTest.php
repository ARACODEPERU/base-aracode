<?php

use App\Support\PaginaPrincipal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Migración de la página principal
|--------------------------------------------------------------------------
|
| La migración que amplía el catálogo de portadas tiene una regla delicada: los
| valores 3, 4 y 5 son NUEVOS (antes el panel solo ofrecía 1 y 2), así que un
| valor distinto de 1 y 2 que ya estuviera guardado no podía ser una elección
| deliberada: con el código anterior el sitio mostraba la portada corporativa.
| Por eso la migración escribe el 1 en ese caso, en lugar de dejar que un valor
| heredado pase a publicar la portada de otro producto sin que nadie lo pida.
|
| No usa las tablas mínimas de tests/Pest.php porque la migración escribe
| description, control_type y json_query_data.
|
*/

uses(TestCase::class);

/**
 * Tabla de parámetros con las columnas que toca la migración.
 */
function crearTablaParametrosDeLaPortada(): void
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
function migracionDeLaPaginaPrincipal(): object
{
    return require base_path('database/migrations/2026_10_13_000001_ampliar_opciones_pagina_principal.php');
}

/**
 * Valor y opciones guardados en el parámetro PW00001.
 *
 * @return array{valor: ?string, opciones: array<int, mixed>, filas: int}
 */
function portadaGuardada(): array
{
    $filas = DB::table('parameters')->where('parameter_code', PaginaPrincipal::PARAMETRO)->get();

    return [
        'filas' => $filas->count(),
        'valor' => $filas->first()->value_default ?? null,
        'opciones' => json_decode((string) ($filas->first()->json_query_data ?? ''), true) ?? [],
    ];
}

it('crea el parámetro con todas las portadas cuando no existe', function () {
    crearTablaParametrosDeLaPortada();

    migracionDeLaPaginaPrincipal()->up();

    $guardado = portadaGuardada();

    expect($guardado['filas'])->toBe(1);
    expect($guardado['opciones'])->toBe(PaginaPrincipal::opciones());
    expect($guardado['valor'])->toBe(PaginaPrincipal::POR_DEFECTO);
});

it('conserva la portada elegida que ya existía en el catálogo anterior', function () {
    crearTablaParametrosDeLaPortada();

    DB::table('parameters')->insert([
        'parameter_code' => PaginaPrincipal::PARAMETRO,
        'value_default' => PaginaPrincipal::TORNEOS,
        'json_query_data' => '[]',
    ]);

    migracionDeLaPaginaPrincipal()->up();

    $guardado = portadaGuardada();

    expect($guardado['valor'])->toBe(PaginaPrincipal::TORNEOS);
    expect($guardado['opciones'])->toBe(PaginaPrincipal::opciones());
});

it('devuelve a la portada corporativa un valor que antes no significaba nada', function () {
    crearTablaParametrosDeLaPortada();

    // 4 hoy es KIRAFACT, pero cuando se guardó (catálogo de 1 y 2) el sitio
    // mostraba la portada corporativa: eso es lo que la migración conserva.
    DB::table('parameters')->insert([
        'parameter_code' => PaginaPrincipal::PARAMETRO,
        'value_default' => '4',
        'json_query_data' => '[]',
    ]);

    migracionDeLaPaginaPrincipal()->up();

    expect(portadaGuardada()['valor'])->toBe(PaginaPrincipal::POR_DEFECTO);
});

it('es idempotente: volver a ejecutarla no cambia nada', function () {
    crearTablaParametrosDeLaPortada();

    $migracion = migracionDeLaPaginaPrincipal();
    $migracion->up();
    $primera = portadaGuardada();

    $migracion->up();

    expect(portadaGuardada())->toBe($primera);
});

it('al revertir deja las dos portadas del sitio corporativo y no toca la elegida', function () {
    crearTablaParametrosDeLaPortada();

    $migracion = migracionDeLaPaginaPrincipal();
    $migracion->up();
    DB::table('parameters')->where('parameter_code', PaginaPrincipal::PARAMETRO)
        ->update(['value_default' => '3']);

    $migracion->down();

    $guardado = portadaGuardada();

    expect(array_column($guardado['opciones'], 'value'))->toBe(['1', '2']);
    expect($guardado['valor'])->toBe('3');
});
