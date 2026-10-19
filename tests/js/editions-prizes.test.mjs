import assert from 'node:assert/strict';
import test from 'node:test';

import { etiquetaPuestoPremio, normalizarPuestoPremio, obtenerPremios } from '../../Modules/Socialevents/Resources/assets/js/utils/editionPrizes.js';

test('normaliza las claves del formulario de edición a ordinales', () => {
    assert.equal(normalizarPuestoPremio('first'), 1);
    assert.equal(normalizarPuestoPremio('second'), 2);
    assert.equal(normalizarPuestoPremio('third'), 3);
    assert.equal(normalizarPuestoPremio('fourth'), 4);
    assert.equal(normalizarPuestoPremio('fifth'), 5);
});

test('acepta claves numéricas, ordinales con punto y posición explícita', () => {
    assert.equal(normalizarPuestoPremio('0'), 1);
    assert.equal(normalizarPuestoPremio('2.º'), 2);
    assert.equal(normalizarPuestoPremio('3rd'), 3);
    assert.equal(normalizarPuestoPremio('legacy', { position: 4 }), 4);
    assert.equal(normalizarPuestoPremio('legacy', { place: 5 }), 5);
});

test('muestra los premios en orden de puesto y no produce NaN para claves desconocidas', () => {
    const prizes = obtenerPremios({
        third: { money: 500 },
        first: { money: 5000 },
        second: { money: 3000 },
        unknown_place: { gift: 'Balón' },
    });

    assert.deepEqual(prizes.map(({ position }) => position), [1, 2, 3, null]);
    assert.deepEqual(prizes.map(({ position }) => etiquetaPuestoPremio(position)), ['1°', '2°', '3°', 'Premio']);
    assert.ok(prizes.every(({ position }) => !String(etiquetaPuestoPremio(position)).includes('NaN')));
});

test('ignora estructuras de premios inválidas y elementos sin contenido', () => {
    assert.deepEqual(obtenerPremios(null), []);
    assert.deepEqual(obtenerPremios([]), []);
    assert.deepEqual(obtenerPremios({ first: null, second: {}, third: { money: 0, gift: '' } }), []);
});
