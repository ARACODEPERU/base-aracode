import assert from 'node:assert/strict';
import test from 'node:test';

import {
    gearOwnsClick,
    pointInsideRect,
    pointerOnGear,
} from '../../Modules/Security/Resources/assets/js/Plugins/superEditorGearHit.js';

/**
 * El engrane NO captura el puntero (pointer-events: none), así que la promesa
 * que se verifica aquí es la que hace posible la paridad de clicks: un click
 * solo es del engrane cuando el puntero está dentro de su rectángulo; en
 * cualquier otro punto el click pertenece al elemento de debajo (el que decora
 * o el que tiene al lado). El click de teclado, que llega sin coordenadas, se
 * reconoce por el destino del evento.
 */

const rect = { left: 397, top: 393, right: 425, bottom: 421 };

const pointerClick = (x, y) => ({ clientX: x, clientY: y, detail: 1, target: null });

const keyboardClick = (esElBotonDelEngrane) => ({
    clientX: 0,
    clientY: 0,
    detail: 0,
    target:
        esElBotonDelEngrane === null
            ? null
            : {
                  closest: (selector) =>
                      esElBotonDelEngrane && selector === '[data-super-gear]' ? {} : null,
              },
});

test('el puntero dentro del engrane (incluidos los bordes) es suyo', () => {
    assert.equal(gearOwnsClick(pointerClick(411, 407), rect, true), true, 'centro');
    assert.equal(gearOwnsClick(pointerClick(397, 393), rect, true), true, 'esquina superior izquierda');
    assert.equal(gearOwnsClick(pointerClick(425, 421), rect, true), true, 'esquina inferior derecha');
});

test('un click del vecino no es del engrane, aunque su rectángulo lo solape', () => {
    // El rectángulo del engrane de «editar» (397..425) cae sobre el botón
    // «eliminar» (407..443): el click en su centro debe seguir siendo del botón.
    const eliminarCentro = { x: 425, y: 407 };

    assert.equal(pointInsideRect(rect, eliminarCentro.x, eliminarCentro.y), true, 'el borde derecho aún entra');
    assert.equal(gearOwnsClick(pointerClick(426, 407), rect, true), false, 'un pixel a la derecha ya es del vecino');
    assert.equal(gearOwnsClick(pointerClick(396, 407), rect, true), false, 'a la izquierda es del elemento decorado');
    assert.equal(gearOwnsClick(pointerClick(411, 392), rect, true), false, 'arriba del engrane');
    assert.equal(gearOwnsClick(pointerClick(411, 422), rect, true), false, 'abajo del engrane');
});

test('sin engrane visible o sin rectángulo, ningún click es suyo', () => {
    assert.equal(gearOwnsClick(pointerClick(411, 407), rect, false), false, 'engrane oculto');
    assert.equal(gearOwnsClick(pointerClick(411, 407), null, true), false, 'sin rectángulo');
    assert.equal(gearOwnsClick(null, rect, true), false, 'sin evento');
});

test('el click de teclado pertenece al engrane solo cuando su botón es el destino', () => {
    assert.equal(gearOwnsClick(keyboardClick(true), rect, true), true);
    assert.equal(gearOwnsClick(keyboardClick(false), rect, true), false, 'el destino es otro botón');
    assert.equal(gearOwnsClick(keyboardClick(null), rect, true), false, 'sin destino');
    assert.equal(gearOwnsClick(keyboardClick(true), rect, false), false, 'modo apagado');
});

test('un click de puntero no se apropia por el destino del evento', () => {
    // Contrato inverso al del teclado: con coordenadas manda la geometría.
    const enElBoton = { clientX: 10, clientY: 10, detail: 1, target: { closest: () => ({}) } };

    assert.equal(gearOwnsClick(enElBoton, rect, true), false);
});

test('pointerOnGear tolera eventos sin coordenadas', () => {
    assert.equal(pointerOnGear({}, rect), false);
    assert.equal(pointerOnGear(pointerClick(411, 407), rect), true);
    assert.equal(pointerOnGear(pointerClick(411, 407), null), false);
});
