import assert from 'node:assert/strict';
import test from 'node:test';
import { readFileSync } from 'node:fs';

/**
 * Botón «Generar todos contra todos» de la pantalla de partidos.
 *
 * El botón vive en Editions/Fixtures.vue y abre el modal de equipos que está
 * dentro de Editions/Partials/RoundRobinPlayoff.vue. La pantalla lo hacía a
 * través del `ref` del parcial, pero un componente con <script setup> es
 * cerrado por defecto: sin `defineExpose` el ref no expone nada, la llamada
 * caía en el respaldo silencioso y el clic no hacía nada — sin ningún error
 * en consola. Este test cubre las tres piezas que tienen que estar juntas
 * (ref en el parcial, ref en la pantalla y llamada al método) más la
 * visibilidad del botón, para que un desajuste vuelva a fallar en CI.
 */

const PARTIAL = '../../Modules/Socialevents/Resources/assets/js/Pages/Editions/Partials/RoundRobinPlayoff.vue';
const PAGE = '../../Modules/Socialevents/Resources/assets/js/Pages/Editions/Fixtures.vue';

const read = (relativePath) => readFileSync(new URL(relativePath, import.meta.url), 'utf8');

test('RoundRobinPlayoff.vue expone el abridor del modal', () => {
    const source = read(PARTIAL);

    assert.match(
        source,
        /defineExpose\(\{[\s\S]*?openModalTeams[\s\S]*?\}\)/,
        'Sin defineExpose el ref de la pantalla no expone openModalTeams y el botón no abre nada.'
    );
});

test('Fixtures.vue obtiene el parcial por ref', () => {
    const source = read(PAGE);

    assert.match(
        source,
        /<RoundRobinPlayoff[\s\S]{0,200}?ref="roundRobinRef"/,
        'El parcial se monta sin ref: no habría forma de abrir su modal.'
    );
});

test('Fixtures.vue llama al abridor del modal', () => {
    const source = read(PAGE);

    assert.match(
        source,
        /roundRobinRef\.value[\s\S]{0,90}?openModalTeams/,
        'El botón dejó de llamar a openModalTeams en el parcial: el clic no haría nada.'
    );
});

test('el botón solo se pinta cuando la edición usa el parcial de todos contra todos', () => {
    const source = read(PAGE);

    assert.match(
        source,
        /<button[^>]*v-if="usesRoundRobinPartial"[^>]*>[\s\S]{0,120}?Generar todos contra todos/,
        'Sin la condición el botón se pinta también en las ediciones eliminatorias, donde no tiene parcial que abrir.'
    );

    assert.match(
        source,
        /const usesRoundRobinPartial = computed\([\s\S]{0,180}?single_elimination[\s\S]{0,80}?relampago/,
        'Los formatos que usan SingleElimination tienen que quedar fuera de usesRoundRobinPartial.'
    );
});
