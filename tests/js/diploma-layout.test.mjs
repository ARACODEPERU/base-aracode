import test from 'node:test';
import assert from 'node:assert/strict';

import {
    cloneLayout,
    createElement,
    elementLabel,
    sampleTextsById,
    toNumber,
} from '../../Modules/Socialevents/Resources/assets/js/utils/diplomaLayout.js';

const canvas = { width: 1200, height: 800 };

test('crea un texto centrado con ancho relativo al lienzo', () => {
    const element = createElement('text', canvas, { text: '{{premio}}' });

    assert.equal(element.type, 'text');
    assert.equal(element.text, '{{premio}}');
    assert.equal(element.x, 600 - Math.round(1200 * 0.35));
    assert.equal(element.width, 840);
    assert.ok(element.id.startsWith('el_'));
});

test('el plantel nace con tres columnas y orden por camiseta', () => {
    const element = createElement('squad', canvas);

    assert.equal(element.type, 'squad');
    assert.equal(element.columns, 3);
    assert.equal(element.sortBy, 'number');
    assert.equal(element.showJerseyNumber, true);
    assert.equal(element.showPosition, false);
});

test('las líneas y marcos usan la paleta del tema', () => {
    const rect = createElement('rect', canvas);
    const line = createElement('line', canvas);

    assert.equal(rect.stroke, '#c8f169');
    assert.equal(rect.type, 'rect');
    assert.equal(line.type, 'line');
});

test('sampleTextsById toma los textos ya resueltos del payload', () => {
    const map = sampleTextsById({
        side: {
            elements: [
                { id: 'ganador', type: 'text', text: 'Deportivo Prueba' },
                { id: 'plantel', type: 'squad', title: 'PLANTEL' },
                { id: 'qr', type: 'qr' },
            ],
        },
    });

    assert.deepEqual(map, { ganador: 'Deportivo Prueba', plantel: 'PLANTEL' });
});

test('cloneLayout no comparte referencias con el layout original', () => {
    const layout = { version: 1, elements: [{ id: 'a', x: 10 }] };
    const copy = cloneLayout(layout);

    copy.elements[0].x = 99;

    assert.equal(layout.elements[0].x, 10);
    assert.equal(copy.elements[0].x, 99);
});

test('las capas muestran el nombre dado o una etiqueta por tipo', () => {
    assert.equal(elementLabel({ type: 'text', name: 'Ganador' }), 'Ganador');
    assert.equal(elementLabel({ type: 'squad' }), 'Plantel');
    assert.equal(elementLabel({ type: 'qr' }), 'QR');
});

test('toNumber ignora valores no numéricos', () => {
    assert.equal(toNumber('42'), 42);
    assert.equal(toNumber('', 7), 7);
    assert.equal(toNumber('abc', 3), 3);
});
