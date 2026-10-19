import assert from 'node:assert/strict';
import test from 'node:test';

import {
    claveParEquipos,
    crucesCompletos,
    faltanCruces,
    paresDeFases,
} from '../../Modules/Socialevents/Resources/assets/js/utils/roundRobinPares.js';

/**
 * Botón «Crear» del modal «Generar encuentros».
 *
 * Cuando el todos contra todos ya está completo no hay nada que generar ni
 * cobrar: la pantalla debe avisar y cerrar el modal en vez de mandar la
 * petición. Esta prueba fija la comparación: los cruces ya registrados, sin
 * importar el orden local/visitante, y los que faltan según los equipos
 * marcados en el modal.
 */

// Fixture de 4 equipos (6 cruces) tal como llega en props.fixture.league:
// una clave por jornada con sus partidos.
const jornadas = {
    1: [
        { team_h_id: 14, team_a_id: 20 },
        { team_h_id: 15, team_a_id: 18 },
    ],
    2: [
        { team_h_id: 14, team_a_id: 15 },
        { team_h_id: 18, team_a_id: 20 },
    ],
    3: [
        { team_h_id: 14, team_a_id: 18 },
        { team_h_id: 20, team_a_id: 15 },
    ],
};

const equipos = [14, 15, 18, 20];

test('el cruce es el mismo sin importar quién es local', () => {
    assert.equal(claveParEquipos(20, 14), claveParEquipos(14, 20));
    assert.equal(claveParEquipos('7', 3), '3-7');
});

test('los pares registrados salen de todas las jornadas juntas', () => {
    const pares = paresDeFases(jornadas);

    assert.equal(pares.size, 6);
    assert.ok(pares.has(claveParEquipos(20, 15)), 'El par 15-20 debería contarse igual invertido.');
});

test('con el todos contra todos completo no falta ningún cruce', () => {
    assert.deepEqual(faltanCruces(equipos, paresDeFases(jornadas)), []);
    assert.equal(crucesCompletos(equipos, paresDeFases(jornadas)), true);
});

test('si falta un cruce no se considera completo y se informa cuál falta', () => {
    const incompletas = { ...jornadas, 3: [{ team_h_id: 14, team_a_id: 18 }] };
    const pares = paresDeFases(incompletas);

    assert.deepEqual(faltanCruces(equipos, pares), [[15, 20]]);
    assert.equal(crucesCompletos(equipos, pares), false);
});

test('un equipo agregado después deja cruces nuevos por generar', () => {
    const pares = paresDeFases(jornadas);
    const faltantes = faltanCruces([...equipos, 21], pares);

    assert.equal(faltantes.length, 4, 'El quinto equipo agrega un cruce con cada uno de los cuatro.');
    assert.equal(crucesCompletos([...equipos, 21], pares), false);
});

test('la selección del modal no se rompe con ids repetidos ni con un solo equipo', () => {
    const pares = paresDeFases(jornadas);

    assert.deepEqual(faltanCruces([14, 14, 15], pares), [], 'Repetir un equipo no inventa cruces.');
    assert.deepEqual(faltanCruces([14], pares), [], 'Con un solo equipo no hay cruces que pedir.');
    assert.equal(crucesCompletos([14], pares), false, 'Un solo equipo nunca completa el todos contra todos.');
    assert.equal(crucesCompletos([], pares), false);
});

test('los partidos sin ambos equipos no cuentan como cruce registrado', () => {
    const pares = paresDeFases({ 1: [{ team_h_id: 14, team_a_id: null }, { team_h_id: null, team_a_id: 20 }] });

    assert.equal(pares.size, 0);
    assert.equal(faltanCruces([14, 20], pares).length, 1);
});
