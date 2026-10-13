import assert from 'node:assert/strict';
import test from 'node:test';

import {
    canalesRgb,
    tinteClaro,
    variablesDeMarca,
} from '../../resources/js/utils/colorMarca.js';

/**
 * Utilidades de marca (Configuraciones → Branding).
 *
 * Los colores se guardan como hex (#0188EE) pero el tema los consume como
 * canales «R G B» dentro de `rgb(var(--brand-primary) / <alpha-value>)`, que es
 * lo que hace posible que `bg-primary/10`, `border-primary/60` y compañía sigan
 * respetando su opacidad. Lo que se verifica aquí: la conversión es exacta, un
 * valor inválido no genera variable (el sistema conserva el color de fábrica) y
 * el tinte claro acompaña al color elegido.
 */

test('canalesRgb convierte el hex a canales «R G B»', () => {
    assert.equal(canalesRgb('#0188EE'), '1 136 238');
    assert.equal(canalesRgb('#4EAE33'), '78 174 51');
    assert.equal(canalesRgb('0188ee'), '1 136 238', 'Sin # y en minúsculas también vale.');
    assert.equal(canalesRgb('  #060E2D  '), '6 14 45', 'Los espacios se ignoran.');
});

test('canalesRgb rechaza lo que no es un hex de seis dígitos', () => {
    assert.equal(canalesRgb('#0188E'), null);
    assert.equal(canalesRgb('#0188EEE'), null);
    assert.equal(canalesRgb('rgb(1, 136, 238)'), null);
    assert.equal(canalesRgb(''), null);
    assert.equal(canalesRgb(null), null);
    assert.equal(canalesRgb(undefined), null);
});

test('tinteClaro aclara el color mezclándolo con blanco', () => {
    assert.equal(tinteClaro('1 136 238'), '230 243 253');
    assert.equal(tinteClaro('78 174 51'), '237 247 235');
    assert.equal(tinteClaro(null), null);
});

test('variablesDeMarca genera las variables que espera el tema', () => {
    assert.deepEqual(variablesDeMarca({ primary: '#0188EE', secondary: '#4EAE33' }), {
        '--brand-primary': '1 136 238',
        '--brand-primary-light': '230 243 253',
        '--brand-secondary': '78 174 51',
        '--brand-secondary-light': '237 247 235',
    });
});

test('sin color válido no hay variables: queda la paleta de fábrica', () => {
    assert.deepEqual(variablesDeMarca({}), {});
    assert.deepEqual(variablesDeMarca(), {});
    assert.deepEqual(variablesDeMarca({ primary: null, secondary: null }), {});
    assert.deepEqual(
        variablesDeMarca({ primary: 'azul', secondary: '#4EAE33' }),
        {
            '--brand-secondary': '78 174 51',
            '--brand-secondary-light': '237 247 235',
        },
        'Un color inválido se ignora y el otro sigue aplicándose.'
    );
});
