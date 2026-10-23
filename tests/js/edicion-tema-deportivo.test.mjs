import assert from 'node:assert/strict';
import test from 'node:test';
import { readFileSync } from 'node:fs';

/**
 * Tema azul noche y lima de las pantallas de la edición.
 *
 * Equipos, Actas y Partidos comparten la misma piel que el listado de ediciones:
 * tarjetas `#0b1f2b`, bordes `#234052` y acentos lima, pero SOLO con una
 * organización deportiva. Esta prueba fija las tres cosas que se rompen fácil:
 *
 * 1. Que cada pantalla siga leyendo `sportsOrganization.mode`.
 * 2. Que cada tarjeta conserve su rama original para el modo social.
 * 3. Que ninguna superficie navy quede como clase fija fuera de un ternario.
 */

const leer = (ruta) => readFileSync(new URL(ruta, import.meta.url), 'utf8');

const fixtures = leer('../../Modules/Socialevents/Resources/assets/js/Pages/Editions/Fixtures.vue');
const equipos = leer('../../Modules/Socialevents/Resources/assets/js/Pages/Editions/Teams.vue');
const actas = leer('../../Modules/Socialevents/Resources/assets/js/Pages/Editions/Minutes.vue');
const roundRobin = leer('../../Modules/Socialevents/Resources/assets/js/Pages/Editions/Partials/RoundRobinPlayoff.vue');
const eliminacion = leer('../../Modules/Socialevents/Resources/assets/js/Pages/Editions/Partials/SingleElimination.vue');

const PANTALLAS = { Fixtures: fixtures, Teams: equipos, Minutes: actas, RoundRobin: roundRobin, SingleElimination: eliminacion };

const sinSuperficiesFijas = (fuente, nombre) => {
    // `(?<!:)` evita confundir el comienzo de un `:class="..."` con un atributo
    // estático: lo que se busca es una clase navy aplicada siempre.
    assert.doesNotMatch(
        fuente,
        /(?<!:)class="[^"]*bg-\[#0b1f2b\]/,
        `${nombre}: la tarjeta navy quedó fija y se aplicaría al modo social.`
    );
    assert.doesNotMatch(
        fuente,
        /(?<!:)class="[^"]*bg-\[#c8f169\]/,
        `${nombre}: el botón lima quedó fijo y se aplicaría al modo social.`
    );
};

test('las tres pantallas siguen el modo deportivo compartido por el servidor', () => {
    for (const [nombre, fuente] of Object.entries({ Fixtures: fixtures, Teams: equipos, Minutes: actas })) {
        assert.match(
            fuente,
            /const sportsMode = computed\(\(\) => page\.props\.sportsOrganization\?\.mode === true\)/,
            `${nombre}: dejó de leer sportsOrganization.mode.`
        );
    }
});

test('las tres pantallas cambian el título del módulo en modo deportivo', () => {
    for (const [nombre, fuente] of Object.entries({ Fixtures: fixtures, Teams: equipos, Minutes: actas })) {
        assert.match(
            fuente,
            /:titleModule="sportsMode \? 'GESTIÓN DEPORTIVA' : 'Eventos sociales'"/,
            `${nombre}: el navegador debe anunciar Gestión Deportiva en el tema.`
        );
    }
});

test('las tres pantallas usan el hero navy con su rama original', () => {
    for (const [nombre, fuente] of Object.entries({ Fixtures: fixtures, Teams: equipos, Minutes: actas })) {
        assert.match(
            fuente,
            /sportsMode\s*\?\s*'border-white\/10 bg-\[#0b1f2b\] text-white [^']*'/,
            `${nombre}: falta la tarjeta del encabezado en modo deportivo.`
        );
        assert.match(
            fuente,
            /:\s*'border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900'/,
            `${nombre}: el encabezado del modo social cambió de aspecto.`
        );
    }
});

test('Partidos pinta el fixture con el tema y se lo pasa a ambos generadores', () => {
    assert.match(
        fixtures,
        /<SingleElimination[\s\S]{0,500}?:sports-mode="sportsMode"/,
        'La eliminación directa se quedaría con la tarjeta clara.'
    );
    assert.match(
        fixtures,
        /<RoundRobinPlayoff[\s\S]{0,500}?:sports-mode="sportsMode"/,
        'El todos contra todos se quedaría con la tarjeta clara.'
    );
    assert.match(fixtures, /: 'btn btn-success text-xs'/, 'El botón original de generar encuentros se perdió.');
    assert.match(fixtures, /: 'btn btn-primary text-xs'/, 'El botón original de nuevo partido se perdió.');
});

test('Equipos conserva la tabla, sus acciones y el modal de puntos extra', () => {
    assert.match(
        equipos,
        /:class="sportsMode \? 'rounded-2xl border border-\[#234052\] bg-\[#0b1f2b\][^']*' : 'panel'"/,
        'La tabla de posiciones debe seguir usando panel en el modo social.'
    );
    assert.match(
        equipos,
        /sportsMode \? 'from-\[#122c3a\] to-\[#0e2532\] text-lime-200' : 'from-gray-800 to-gray-700'/,
        'La cabecera de la tabla perdió su alternativa original.'
    );
    assert.match(
        equipos,
        /sportsMode \? 'bg-\[#c8f169\] text-\[#08251a\]' : 'bg-blue-600 text-white'/,
        'El marcador de puntos debe conservar el azul original en modo social.'
    );
    assert.match(
        equipos,
        /sportsMode \? 'border border-\[#234052\] bg-\[#0b1f2b\] text-slate-100' : 'bg-white dark:bg-slate-800'/,
        'El modal de puntos extra se quedó sin su variante clara.'
    );
});

test('Actas pinta la tabla y sus acciones sin perder la variante clara', () => {
    assert.match(
        actas,
        /sportsMode \? 'overflow-hidden rounded-2xl border border-\[#234052\] bg-\[#0b1f2b\][^']*' : 'panel p-0'/,
        'La tabla de actas debe volver al panel en el modo social.'
    );
    assert.match(
        actas,
        /sportsMode \? 'bg-\[#122c3a\] text-lime-200' : 'bg-gray-100 text-gray-600'/,
        'La cabecera de actas perdió su alternativa original.'
    );
    assert.match(
        actas,
        /sportsMode \? 'text-slate-400 hover:text-lime-300 hover:bg-\[#17394a\]' : 'text-gray-500 hover:text-indigo-600 hover:bg-indigo-50'/,
        'Los iconos de acción deben conservar el hover original.'
    );
    assert.match(actas, /: 'btn btn-primary text-xs'/, 'El botón Nueva acta perdió su clase original.');
});

test('el todos contra todos recibe el tema como prop y no lo inventa por su cuenta', () => {
    assert.match(roundRobin, /sportsMode: \{\s*type: Boolean,\s*default: false,?\s*\}/);
    assert.match(roundRobin, /props\.sportsMode\s*\?\s*\{\s*'pending': 'text-amber-300'/, 'Los estados deben aclararse sobre el navy.');
    assert.match(
        roundRobin,
        /sportsMode \? 'bg-\[#0b1f2b\] border-\[#234052\] text-slate-100 shadow-black\/30' : 'bg-white dark:bg-zinc-800 dark:border-zinc-800'/,
        'Las tarjetas de partido perdieron su variante clara.'
    );
});

test('la eliminación directa no arrastra el panel claro en modo deportivo', () => {
    assert.match(eliminacion, /sportsMode: \{\s*type: Boolean,\s*default: false,?\s*\}/);
    assert.match(
        eliminacion,
        /:class="sportsMode \? 'min-h-screen text-slate-100' : 'panel min-h-screen'"/,
        'El bracket debe abandonar el panel solo en modo deportivo.'
    );
    assert.match(
        eliminacion,
        /sportsMode \? 'bg-\[#0b1f2b\] border-\[#234052\] text-slate-100 shadow-black\/40' : 'bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-600'/,
        'Las tarjetas del bracket perdieron su variante clara.'
    );
    assert.match(eliminacion, /sportsMode \? 'bg-\[#2b4b5d\]' : 'bg-slate-400 dark:bg-slate-500'/, 'Los conectores originales se perdieron.');
});

test('ninguna pantalla de la edición fija el navy o el lima fuera del tema', () => {
    for (const [nombre, fuente] of Object.entries(PANTALLAS)) {
        sinSuperficiesFijas(fuente, nombre);
    }
});
