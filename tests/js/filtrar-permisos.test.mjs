import assert from 'node:assert/strict';
import test from 'node:test';
import { readFileSync } from 'node:fs';

import {
    contarPermisos,
    contarPorEstado,
    ESTADO_HABILITADOS,
    ESTADO_SIN_HABILITADOS,
    ESTADO_TODOS,
    filtrarPermisos,
    normalizarBusqueda,
} from '../../resources/js/utils/filtrarPermisos.js';

/**
 * Buscador de permisos en la edición de un rol.
 *
 * El caso que fija esta prueba: un rol como «admin» tiene cientos de permisos
 * repartidos en carpetas por módulo (Académico, Facturación, ...) y para quitar
 * o agregar uno hay que abrir carpeta por carpeta. El buscador debe mostrar solo
 * los permisos encontrados, en todas las carpetas a la vez, sin tocar la lista
 * original que se guarda al actualizar el rol; y los botones «Habilitados» y
 * «No habilitados» deben partir esa misma lista según lo que el rol ya tiene.
 */

// Permisos que el rol tiene marcados (lo que envía el formulario al guardar).
const habilitados = ['aca_school_nuevo', 'fac_notas_credito_listado', 'usuarios_nuevo'];

// Igual que llegan del servidor: agrupados por módulo y ordenados por
// descripción del módulo (Spatie deja «Otros permisos» al final).
const modulos = [
    {
        modulo_id: 'academico',
        modulo_description: 'Académico',
        permissions: [
            { id: 1, name: 'aca_cursos_examen_final_crear', guard_name: 'web' },
            { id: 2, name: 'aca_estudiante_nuevo', guard_name: 'web' },
            { id: 3, name: 'aca_school_nuevo', guard_name: 'web' },
            { id: 4, name: 'parametros_nuevo', guard_name: 'web' },
        ],
    },
    {
        modulo_id: 'facturacion',
        modulo_description: 'Facturación',
        permissions: [
            { id: 5, name: 'fac_comprobantes_nuevo', guard_name: 'web' },
            { id: 6, name: 'fac_notas_credito_listado', guard_name: 'web' },
        ],
    },
    {
        modulo_id: 'Otros',
        modulo_description: 'Otros permisos',
        permissions: [{ id: 7, name: 'usuarios_nuevo', guard_name: 'web' }],
    },
];

test('el buscador pegajoso se posiciona bajo el header y conserva 4 px de separacion', () => {
    const form = readFileSync(new URL('../../Modules/Security/Resources/assets/js/Pages/Roles/Pertials/editForm.vue', import.meta.url), 'utf8');

    assert.match(form, /const stickyTop = ref\(4\)/);
    assert.match(form, /getBoundingClientRect\(\)\.height[^;]*\+ 4/);
    assert.match(form, /ResizeObserver\(updateStickyTop\)/);
    assert.match(form, /class="permissions-search-sticky sticky z-30[^"]*"\s+:style="\{ top: `\$\{stickyTop\}px` \}"/);
});

test('sin texto devuelve las carpetas tal como llegan', () => {
    assert.equal(filtrarPermisos(modulos, ''), modulos);
    assert.equal(filtrarPermisos(modulos, '   '), modulos);
    assert.deepEqual(filtrarPermisos(undefined, '   '), []);
});

test('normaliza el texto de búsqueda', () => {
    assert.equal(normalizarBusqueda('  ACA_School  '), 'aca_school');
    assert.equal(normalizarBusqueda(null), '');
});

test('muestra solo los permisos encontrados en todas las carpetas', () => {
    const resultado = filtrarPermisos(modulos, 'nuevo');

    assert.deepEqual(
        resultado.map((modulo) => modulo.modulo_description),
        ['Académico', 'Facturación', 'Otros permisos']
    );
    assert.deepEqual(
        resultado.map((modulo) => modulo.permissions.map((permiso) => permiso.name)),
        [
            ['aca_estudiante_nuevo', 'aca_school_nuevo', 'parametros_nuevo'],
            ['fac_comprobantes_nuevo'],
            ['usuarios_nuevo'],
        ]
    );
});

test('descarta las carpetas sin coincidencias y conserva el orden del servidor', () => {
    const resultado = filtrarPermisos(modulos, 'aca_school');

    assert.deepEqual(
        resultado.map((modulo) => modulo.modulo_description),
        ['Académico']
    );
    assert.deepEqual(
        resultado[0].permissions.map((permiso) => permiso.name),
        ['aca_school_nuevo']
    );
});

test('ignora mayúsculas y espacios al buscar', () => {
    const resultado = filtrarPermisos(modulos, '  ACA_CURSOS  ');

    assert.deepEqual(
        resultado.map((modulo) => modulo.permissions.map((permiso) => permiso.name)),
        [['aca_cursos_examen_final_crear']]
    );
});

test('cuenta los permisos encontrados en todas las carpetas', () => {
    assert.equal(contarPermisos(filtrarPermisos(modulos, 'nuevo')), 5);
    assert.equal(contarPermisos(filtrarPermisos(modulos, 'no_existe')), 0);
});

test('no modifica la lista original de carpetas y permisos', () => {
    const copia = JSON.parse(JSON.stringify(modulos));

    filtrarPermisos(modulos, 'nuevo', ESTADO_HABILITADOS, habilitados);

    assert.deepEqual(modulos, copia);
    assert.equal(modulos[0].permissions.length, 4);
});

test('el botón «Habilitados» deja solo los permisos que tiene el rol', () => {
    const resultado = filtrarPermisos(modulos, '', ESTADO_HABILITADOS, habilitados);

    assert.deepEqual(
        resultado.map((modulo) => modulo.modulo_description),
        ['Académico', 'Facturación', 'Otros permisos']
    );
    assert.deepEqual(
        resultado.map((modulo) => modulo.permissions.map((permiso) => permiso.name)),
        [['aca_school_nuevo'], ['fac_notas_credito_listado'], ['usuarios_nuevo']]
    );
});

test('el botón «No habilitados» deja solo los permisos que le faltan al rol', () => {
    const resultado = filtrarPermisos(modulos, '', ESTADO_SIN_HABILITADOS, habilitados);

    assert.deepEqual(
        resultado.map((modulo) => modulo.permissions.map((permiso) => permiso.name)),
        [
            ['aca_cursos_examen_final_crear', 'aca_estudiante_nuevo', 'parametros_nuevo'],
            ['fac_comprobantes_nuevo'],
        ]
    );
});

test('el buscador y el filtro de estado se combinan', () => {
    assert.deepEqual(
        filtrarPermisos(modulos, 'nuevo', ESTADO_HABILITADOS, habilitados).map((modulo) =>
            modulo.permissions.map((permiso) => permiso.name)
        ),
        [['aca_school_nuevo'], ['usuarios_nuevo']]
    );

    assert.deepEqual(
        filtrarPermisos(modulos, 'nuevo', ESTADO_SIN_HABILITADOS, habilitados).map((modulo) =>
            modulo.permissions.map((permiso) => permiso.name)
        ),
        [['aca_estudiante_nuevo', 'parametros_nuevo'], ['fac_comprobantes_nuevo']]
    );
});

test('sin estado o con el estado «todos» no se filtra por habilitación', () => {
    assert.equal(filtrarPermisos(modulos, '', ESTADO_TODOS, habilitados), modulos);
    assert.equal(filtrarPermisos(modulos, '', undefined, habilitados), modulos);
});

test('cuenta los permisos habilitados y no habilitados de cada botón', () => {
    assert.deepEqual(contarPorEstado(modulos, habilitados), {
        habilitados: 3,
        sinHabilitados: 4,
        total: 7,
    });

    // Con las carpetas ya filtradas por el buscador los números coinciden con
    // lo que se ve en pantalla.
    assert.deepEqual(contarPorEstado(filtrarPermisos(modulos, 'nuevo'), habilitados), {
        habilitados: 2,
        sinHabilitados: 3,
        total: 5,
    });
});
