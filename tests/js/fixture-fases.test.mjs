import assert from 'node:assert/strict';
import test from 'node:test';
import { readFileSync } from 'node:fs';

/**
 * Fases del fixture e ida y vuelta.
 *
 * Las fases salen de un solo catálogo del servidor (TournamentPhaseLabels) y se
 * pintan con `phaseOptions`. Antes cada vista tenía su propio diccionario: el
 * combo de crear ofrecía solo tres fases y el de editar no existía, así que las
 * fases nuevas (octavos, cuartos, semifinales, tercer y cuarto puesto) no se
 * podían elegir. Este test fija las tres piezas que tienen que seguir juntas.
 */

const CREATE_FORM = '../../Modules/Socialevents/Resources/assets/js/Pages/Fixtures/Partials/CreateForm.vue';
const ROUND_ROBIN = '../../Modules/Socialevents/Resources/assets/js/Pages/Editions/Partials/RoundRobinPlayoff.vue';
const SINGLE = '../../Modules/Socialevents/Resources/assets/js/Pages/Editions/Partials/SingleElimination.vue';
const FIXTURES_PAGE = '../../Modules/Socialevents/Resources/assets/js/Pages/Editions/Fixtures.vue';

const read = (relativePath) => readFileSync(new URL(relativePath, import.meta.url), 'utf8');

test('el combo de Fase del formulario de crear se alimenta del catálogo del servidor', () => {
    const source = read(CREATE_FORM);

    assert.match(
        source,
        /v-for="option in phaseOptions"[\s\S]{0,120}?:value="option\.value"/,
        'El combo de Fase volvió a tener opciones escritas a mano: se saltean las fases del catálogo.'
    );

    // Las tres opciones fijas que tenía el formulario desaparecieron.
    assert.doesNotMatch(source, /<option value="quarterfinals">/);
    assert.doesNotMatch(source, /<option value="final">/);
});

test('el formulario de crear ofrece el formato del cruce y la fecha de la vuelta', () => {
    const source = read(CREATE_FORM);

    assert.match(source, /v-model="form\.tie_format"/, 'Falta el selector de formato del cruce.');
    // Los formatos (partido único, muerte súbita e ida y vuelta) llegan del servidor.
    assert.match(source, /v-for="format in tieFormats"/, 'El selector de formato no usa el catálogo del servidor.');
    assert.match(source, /home_and_away/, 'El formulario no reconoce el formato de ida y vuelta.');
    assert.match(source, /v-model="form\.match_date_return"/, 'Falta la fecha de la vuelta.');
    assert.match(source, /v-model="form\.location_return"/, 'Falta la cancha de la vuelta.');
});

test('la pantalla del fixture recibe el catálogo y lo pasa a los dos parciales', () => {
    const page = read(FIXTURES_PAGE);

    assert.match(page, /phaseOptions: \{\s*type: Array/s, 'La pantalla no declara phaseOptions.');
    assert.match(page, /phase-options="phaseOptions"/, 'La pantalla no pasa phaseOptions a los parciales.');
    assert.match(page, /tie-formats="tieFormats"/, 'La pantalla no pasa tieFormats a los parciales.');

    for (const partial of [ROUND_ROBIN, SINGLE]) {
        const source = read(partial);

        assert.match(
            source,
            /phaseOptions: \{ type: Array, default: \(\) => \[\] \}|phaseOptions: \{\s*type: Array/,
            `${partial}: el parcial no declara phaseOptions.`
        );
        assert.match(
            source,
            /v-for="option in phaseOptions"/,
            `${partial}: el selector de Fase no usa el catálogo del servidor.`
        );
    }
});

test('el modal de editar incluye el campo Fase y el formato del cruce', () => {
    for (const partial of [ROUND_ROBIN, SINGLE]) {
        const source = read(partial);

        assert.match(source, /v-model="formMatch\.phase"/, `${partial}: el modal no permite cambiar la fase.`);
        assert.match(source, /v-model="formMatch\.tie_format"/, `${partial}: el modal no permite cambiar el formato del cruce.`);
        assert.match(source, /formMatch\.errors\.phase/, `${partial}: falta el error del campo Fase.`);
    }
});

test('las etiquetas de fase ya no se duplican en las vistas', () => {
    const roundRobin = read(ROUND_ROBIN);

    assert.match(
        roundRobin,
        /props\.phaseLabels\?\.\[phase\]/,
        'RoundRobinPlayoff.vue volvió a tener un diccionario propio de etiquetas de fase.'
    );

    assert.doesNotMatch(
        roundRobin,
        /'quarterfinals': 'Cuartos de Final'/,
        'RoundRobinPlayoff.vue tiene otra vez la lista de etiquetas escrita a mano.'
    );
});

test('las tarjetas del fixture muestran la pierna y el marcador global', () => {
    const roundRobin = read(ROUND_ROBIN);

    assert.match(roundRobin, /const tieLegLabel = \(match\)/, 'Falta el chip Ida/Vuelta.');
    assert.match(roundRobin, /const tieGlobalLabel = \(match\)/, 'Falta el marcador global del cruce.');
    assert.match(roundRobin, /tieLegLabel\(match\)/, 'El chip no se pinta en la tarjeta.');
    assert.match(roundRobin, /tieGlobalLabel\(match\)/, 'El global no se pinta en la tarjeta.');
});
