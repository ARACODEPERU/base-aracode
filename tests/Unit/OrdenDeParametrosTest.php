<?php

use App\Support\OrdenDeParametros;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Orden de los parámetros por prefijo
|--------------------------------------------------------------------------
|
| La pantalla de Parámetros del sistema agrupa los códigos por su prefijo
| (P, PC, PHD, PN, PTM, PW, SC…) y ordena por el número dentro de cada grupo.
| Estas pruebas fijan esa regla: el prefijo manda y no se ordena por el código
| como texto, donde los ceros de relleno deciden antes que el número real
| ('P000023' iría antes que 'P4').
|
*/

uses(TestCase::class);

it('saca el prefijo de las letras con las que empieza el código', function () {
    expect(OrdenDeParametros::prefijo('P000023'))->toBe('P');
    expect(OrdenDeParametros::prefijo('PW00001'))->toBe('PW');
    expect(OrdenDeParametros::prefijo('SC-00001'))->toBe('SC');
    expect(OrdenDeParametros::prefijo('pc00004'))->toBe('PC');
    expect(OrdenDeParametros::prefijo(' P000026'))->toBe('P');
});

it('deja el prefijo vacío cuando el código no empieza por letras', function () {
    expect(OrdenDeParametros::prefijo('000123'))->toBe('');
    expect(OrdenDeParametros::prefijo(''))->toBe('');
    expect(OrdenDeParametros::prefijo(null))->toBe('');
});

it('saca el número del código para ordenar dentro de un prefijo', function () {
    expect(OrdenDeParametros::numero('P000023'))->toBe(23);
    expect(OrdenDeParametros::numero('PW00001'))->toBe(1);
    expect(OrdenDeParametros::numero('SC-00001'))->toBe(1);
    expect(OrdenDeParametros::numero('PHD00004'))->toBe(4);
    expect(OrdenDeParametros::numero('SIN-NUMERO'))->toBe(0);
});

it('agrupa por prefijo y ordena por número dentro de cada grupo', function () {
    $codigos = ['SC-00002', 'PW00001', 'P000026', 'PC00001', 'PN00003', 'P000004'];

    expect(codigosOrdenados($codigos))->toBe([
        'P000004', 'P000026', 'PC00001', 'PN00003', 'PW00001', 'SC-00002',
    ]);
});

it('no ordena por el código como texto: el número real va antes que los ceros de relleno', function () {
    // Como texto, 'P000023' iría antes que 'P4' (el '0' pesa menos que el '4').
    expect(codigosOrdenados(['P000023', 'P4']))->toBe(['P4', 'P000023']);
});

it('ordena también modelos, no solo arreglos', function () {
    $modelos = collect(['PW00001', 'P000023', 'SC-00001'])->map(function ($codigo) {
        $modelo = new stdClass();
        $modelo->parameter_code = $codigo;

        return $modelo;
    });

    expect(array_map(
        fn ($modelo) => $modelo->parameter_code,
        OrdenDeParametros::ordenar($modelos)
    ))->toBe(['P000023', 'PW00001', 'SC-00001']);
});

it('no pierde ninguna fila al ordenar', function () {
    $codigos = ['SC-00003', 'P000001', 'PW00001', 'SC-00001', 'P000002'];

    $ordenados = codigosOrdenados($codigos);

    expect($ordenados)->toHaveCount(count($codigos));
    expect($ordenados)->toEqualCanonicalizing($codigos);
});

/**
 * Códigos ordenados como los deja el orden por prefijo.
 *
 * @param  array<int, string>  $codigos
 * @return array<int, string>
 */
function codigosOrdenados(array $codigos): array
{
    $filas = array_map(fn ($codigo) => ['parameter_code' => $codigo], $codigos);

    return array_column(OrdenDeParametros::ordenar($filas), 'parameter_code');
}
