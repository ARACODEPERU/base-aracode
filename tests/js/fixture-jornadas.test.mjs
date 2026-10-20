import assert from 'node:assert/strict';
import test from 'node:test';
import { readFileSync } from 'node:fs';

import { JORNADA_MAXIMA, jornadaVuelta, jornadaVueltaValida } from '../../Modules/Socialevents/Resources/assets/js/utils/fixtureJornadas.js';

/**
 * Jornadas de un cruce de ida y vuelta.
 *
 * El cruce ocupaba una sola jornada: la ida y la vuelta se creaban con el
 * mismo `round_number`, así que las dos piernas aparecían bajo la misma fecha
 * del fixture. Ahora la vuelta va a la jornada siguiente (editable) y el
 * formulario la propone sola.
 */

const CREATE_FORM = '../../Modules/Socialevents/Resources/assets/js/Pages/Fixtures/Partials/CreateForm.vue';

const read = (relativePath) => readFileSync(new URL(relativePath, import.meta.url), 'utf8');

test('la vuelta va a la jornada siguiente a la ida', () => {
    assert.equal(jornadaVuelta(1), 2);
    assert.equal(jornadaVuelta(4), 5);
    assert.equal(jornadaVuelta('7'), 8);
});

test('sin jornada de ida la vuelta cae en la segunda', () => {
    assert.equal(jornadaVuelta(null), 2);
    assert.equal(jornadaVuelta(undefined), 2);
    assert.equal(jornadaVuelta(''), 2);
    assert.equal(jornadaVuelta(0), 2);
});

test('la jornada de la vuelta nunca desborda el campo de dos dígitos', () => {
    assert.equal(jornadaVuelta(JORNADA_MAXIMA), JORNADA_MAXIMA);
    assert.equal(jornadaVuelta(150), JORNADA_MAXIMA);
});

test('la vuelta tiene que ser posterior a la ida y caber en el campo', () => {
    assert.equal(jornadaVueltaValida(1, 2), true);
    assert.equal(jornadaVueltaValida(3, 9), true);

    // Misma jornada o anterior: el cruce volvería a caer en una sola fecha.
    assert.equal(jornadaVueltaValida(2, 2), false);
    assert.equal(jornadaVueltaValida(3, 1), false);

    assert.equal(jornadaVueltaValida(1, 100), false);
    assert.equal(jornadaVueltaValida(null, 2), false);
    assert.equal(jornadaVueltaValida(1, null), false);
});

test('el formulario de crear pide la jornada de la vuelta y la calcula', () => {
    const source = read(CREATE_FORM);

    assert.match(
        source,
        /v-model="form\.round_number_return"/,
        'Falta el campo de jornada de la vuelta: no se podría programar la vuelta en otra fecha.'
    );

    assert.match(
        source,
        /import \{ jornadaVuelta \} from '\.\.\/\.\.\/\.\.\/utils\/fixtureJornadas\.js'/,
        'El formulario no usa la utilidad de jornadas de la vuelta.'
    );

    assert.match(
        source,
        /form\.round_number_return = jornadaVuelta\(form\.round_number\)/,
        'La jornada de la vuelta dejó de seguir a la de la ida.'
    );

    assert.match(
        source,
        /formMatch\.errors\.round_number_return|form\.errors\.round_number_return/,
        'Falta el error del campo de jornada de la vuelta.'
    );
});
