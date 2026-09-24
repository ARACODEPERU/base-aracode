import assert from 'node:assert/strict';
import test from 'node:test';

import {
    evaluateGate,
    kindForTag,
    labelForText,
    parseCondition,
    permissionOf,
} from '../../Modules/Security/Resources/assets/js/Plugins/superEditorGateEvaluation.js';

/**
 * El Modo Super Editor sustituye las directivas v-can / v-permission de
 * vue-gates. La promesa que se verifica aquí: con el modo APAGADO el
 * comportamiento es el mismo de vue-gates (mismos métodos de gate, mismos
 * modificadores, misma eliminación del elemento) y NO se marca nada en el DOM;
 * con el modo ENCENDIDO nunca se elimina un elemento.
 */

const gate = {
    hasPermission: (value) => ['integrationhub_listado', 'dashboard'].includes(value),
    hasRole: (value) => value === 'admin',
    hasAllPermissions: () => false,
};

const evaluateOff = (overrides = {}) =>
    evaluateGate({ gate, name: 'permission', arg: null, value: 'integrationhub_listado', modifiers: {}, editorActive: false, ...overrides });

const evaluateOn = (overrides = {}) =>
    evaluateGate({ gate, name: 'permission', arg: null, value: 'integrationhub_listado', modifiers: {}, editorActive: true, ...overrides });

test('parseCondition resuelve el mismo método de gate que vue-gates', () => {
    assert.equal(parseCondition('can', null), 'hasPermission');
    assert.equal(parseCondition('permission', null), 'hasPermission');
    assert.equal(parseCondition('permission', 'has'), 'hasPermission');
    assert.equal(parseCondition('permission', 'unless'), 'unlessPermission');
    assert.equal(parseCondition('permission', 'all'), 'hasAllPermissions');
    assert.equal(parseCondition('role', null), 'hasRole');
    assert.equal(parseCondition('role', 'all'), 'hasAllRoles');
});

test('con el modo apagado, un permiso concedido deja el elemento intacto y sin marcas', () => {
    const decision = evaluateOff();

    assert.equal(decision.action, 'keep');
    assert.equal(decision.decorate, undefined, 'No se decora nada: el modo apagado es inerte.');
    assert.equal(decision.attributes, undefined);
});

test('con el modo apagado, un permiso denegado elimina el elemento (vue-gates)', () => {
    assert.equal(evaluateOff({ value: 'treasury_cuentas' }).action, 'remove');
});

test('con el modo apagado, los modificadores se copian en lugar de eliminar', () => {
    const decision = evaluateOff({ value: 'treasury_cuentas', modifiers: { disabled: true } });

    assert.equal(decision.action, 'assign');
    assert.deepEqual(decision.attributes, { disabled: true });
});

test('con el modo apagado, un método de gate inexistente falla cerrado (no rompe)', () => {
    const decision = evaluateGate({
        gate: {},
        name: 'permission',
        arg: null,
        value: 'cualquiera',
        modifiers: {},
        editorActive: false,
    });

    assert.equal(decision.action, 'remove');
});

test('con el modo encendido NUNCA se elimina el elemento', () => {
    for (const value of ['integrationhub_listado', 'treasury_cuentas', 'inexistente', ['a', 'b']]) {
        assert.equal(evaluateOn({ value }).action, 'keep', `No debe eliminarse con ${JSON.stringify(value)}`);
    }
});

test('con el modo encendido se decora el elemento con su permiso', () => {
    const decision = evaluateOn();

    assert.equal(decision.action, 'keep');
    assert.deepEqual(decision.decorate, {
        permission: 'integrationhub_listado',
        kind: null,
        label: null,
    });
});

test('con el modo encendido se respetan las etiquetas explícitas del binding', () => {
    const decision = evaluateOn({
        value: { perm: 'integrationhub_listado', label: 'Centro de Integraciones', kind: 'opción de menú' },
    });

    assert.deepEqual(decision.decorate, {
        permission: 'integrationhub_listado',
        kind: 'opción de menú',
        label: 'Centro de Integraciones',
    });
});

test('un elemento sin permiso no se decora, pero tampoco se elimina', () => {
    const decision = evaluateOn({ value: { label: 'sin permiso' } });

    assert.equal(decision.action, 'keep');
    assert.equal(decision.decorate, null);
});

test('permissionOf entiende string, array y objeto', () => {
    assert.equal(permissionOf('integrationhub_listado'), 'integrationhub_listado');
    assert.equal(permissionOf(['comm_clientes_listado', 'otro']), 'comm_clientes_listado');
    assert.equal(permissionOf({ perm: 'treasury_cuentas' }), 'treasury_cuentas');
    assert.equal(permissionOf({ permission: 'dashboard' }), 'dashboard');
    assert.equal(permissionOf([]), null);
    assert.equal(permissionOf(null), null);
});

test('kindForTag traduce el tipo de elemento como en la referencia', () => {
    assert.equal(kindForTag('BUTTON'), 'botón');
    assert.equal(kindForTag('a'), 'enlace');
    assert.equal(kindForTag('div'), 'elemento');
});

test('labelForText usa el texto visible y cae al permiso humanizado', () => {
    assert.equal(labelForText('  Lista   de\nAlumnos ', null), 'Lista de Alumnos');
    assert.equal(labelForText('', 'comm_negociaciones_verificar'), 'comm negociaciones verificar');
    assert.equal(labelForText(null, 'treasury_cuentas'), 'treasury cuentas');
    assert.equal(labelForText('x'.repeat(120), null).length, 80);
});
