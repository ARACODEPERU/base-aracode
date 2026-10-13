import assert from 'node:assert/strict';
import test from 'node:test';

import {
    agruparPorPrefijo,
    filtrarPorPrefijo,
    ordenarPorCodigo,
    prefijoDe,
} from '../../resources/js/utils/agruparParametros.js';

/**
 * La pantalla de Parámetros del sistema arma una pestaña por prefijo con el
 * prefijo que calcula el servidor (`prefix`), y el botón «Ordenar» alterna el
 * orden por prefijo (el que llega) con el alfabético por el código completo. La
 * promesa que se verifica aquí: las pestañas cubren todos los parámetros
 * exactamente una vez, el grupo «Sin prefijo» existe, y el orden alternativo no
 * toca el arreglo que recibe.
 */

// Igual que llegan del servidor: ordenados por prefijo y con los códigos sin
// letras primero (el prefijo vacío va antes que cualquier letra).
const parametros = [
    { id: 6, parameter_code: '000123', prefix: '' },
    { id: 1, parameter_code: 'P000001', prefix: 'P' },
    { id: 2, parameter_code: 'P000002', prefix: 'P' },
    { id: 3, parameter_code: 'PC00001', prefix: 'PC' },
    { id: 4, parameter_code: 'PW00001', prefix: 'PW' },
    { id: 5, parameter_code: 'SC-00001', prefix: 'SC' },
];

test('prefijoDe usa el prefijo del servidor y lo pone en mayúsculas', () => {
    assert.equal(prefijoDe({ prefix: 'pw' }), 'PW');
    assert.equal(prefijoDe({ prefix: '' }), '');
    assert.equal(prefijoDe({}), '', 'Sin prefijo no revienta: se agrupa como vacío.');
});

test('agrupa por prefijo con el total de cada grupo y en el orden recibido', () => {
    assert.deepEqual(agruparPorPrefijo(parametros), [
        { prefijo: '', total: 1 },
        { prefijo: 'P', total: 2 },
        { prefijo: 'PC', total: 1 },
        { prefijo: 'PW', total: 1 },
        { prefijo: 'SC', total: 1 },
    ]);
});

test('las pestañas cubren todos los parámetros exactamente una vez', () => {
    const grupos = agruparPorPrefijo(parametros);
    const totalEnPestanas = grupos.reduce((suma, grupo) => suma + grupo.total, 0);

    assert.equal(totalEnPestanas, parametros.length);

    const ids = grupos.flatMap((grupo) => filtrarPorPrefijo(parametros, grupo.prefijo).map((p) => p.id));
    assert.deepEqual([...ids].sort(), parametros.map((p) => p.id).sort());
});

test('filtra por un prefijo concreto y no confunde PC con P', () => {
    assert.deepEqual(filtrarPorPrefijo(parametros, 'P').map((p) => p.id), [1, 2]);
    assert.deepEqual(filtrarPorPrefijo(parametros, 'PC').map((p) => p.id), [3]);
    assert.deepEqual(filtrarPorPrefijo(parametros, 'PW').map((p) => p.id), [4]);
    assert.deepEqual(filtrarPorPrefijo(parametros, '').map((p) => p.id), [6]);
});

test('el orden alternativo es alfabético por el código y no muta el arreglo', () => {
    const original = [...parametros];

    const ordenados = ordenarPorCodigo(parametros);

    assert.deepEqual(ordenados.map((p) => p.parameter_code), [
        '000123', 'P000001', 'P000002', 'PC00001', 'PW00001', 'SC-00001',
    ]);
    assert.deepEqual(parametros, original, 'El arreglo recibido no se reordena en su sitio.');
});

test('sin parámetros no hay pestañas ni errores', () => {
    assert.deepEqual(agruparPorPrefijo([]), []);
    assert.deepEqual(agruparPorPrefijo(undefined), []);
    assert.deepEqual(ordenarPorCodigo(undefined), []);
    assert.deepEqual(filtrarPorPrefijo(undefined, 'P'), []);
});
