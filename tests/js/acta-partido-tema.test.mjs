import assert from 'node:assert/strict';
import test from 'node:test';
import { readFileSync } from 'node:fs';

/**
 * Tema azul noche y lima del acta del partido.
 *
 * El acta comparte la piel de Equipos, Partidos y Actas solo cuando la
 * organización está en modo deportivo. El riesgo que fija esta prueba es que la
 * paleta se filtre al modo social: toda superficie navy debe vivir dentro de un
 * ternario sobre `sportsMode`, y los modales oscuros deben activarse con un
 * contenedor condicional, no con una clase fija.
 */

const RUTA = '../../Modules/Socialevents/Resources/assets/js/Pages/Editions/MatchScore.vue';

const fuente = readFileSync(new URL(RUTA, import.meta.url), 'utf8');

test('el acta reconoce el modo deportivo con la prop compartida por el servidor', () => {
    assert.match(
        fuente,
        /const sportsMode = computed\(\(\) => page\.props\.sportsOrganization\?\.mode === true\)/,
        'Sin la prop compartida el acta dejaría de seguir el tema de la organización.'
    );
});

test('las superficies navy y el botón lima existen en el tema del acta', () => {
    assert.match(fuente, /bg-\[#0b1f2b\]/, 'Falta la tarjeta azul noche.');
    assert.match(fuente, /border-\[#234052\]/, 'Faltan los bordes acero de las tarjetas.');
    assert.match(fuente, /bg-\[#c8f169\]/, 'Falta el botón lima principal.');
    assert.match(fuente, /text-lime-300/, 'Falta el acento lima para marcadores y enlaces.');
});

test('los controles oscuros se pintan con los tokens del tema, no con verdes fijos', () => {
    assert.match(fuente, /'--torneos-control-bg': '#0e2532'/);
    assert.match(fuente, /'--torneos-control-text': '#e2e8f0'/);
    assert.match(fuente, /'--torneos-control-border': '#2b4b5d'/);
    assert.match(fuente, /'--torneos-dropdown-bg': '#122c3a'/);
    assert.match(
        fuente,
        /:style="sportsMode \? sportsControlVars : null"/,
        'Los tokens deben aplicarse solo en modo deportivo.'
    );
});

test('ninguna superficie navy se pinta fuera de un enlace a sportsMode', () => {
    // Una clase literal `bg-[#0b1f2b]` en el atributo class se aplicaría al modo
    // social; en el tema siempre va dentro de `:class` con el ternario.
    assert.doesNotMatch(
        fuente,
        /(?<!:)class="[^"]*bg-\[#0b1f2b\]/,
        'La tarjeta navy quedó fija en el modo social.'
    );
    assert.doesNotMatch(
        fuente,
        /(?<!:)class="[^"]*bg-\[#c8f169\]/,
        'El botón lima quedó fijo en el modo social.'
    );
});

test('los tres modales viven en un contenedor que solo se activa en modo deportivo', () => {
    assert.match(
        fuente,
        /<div :class="sportsMode \? 'acta-modals' : ''">/,
        'Los modales deben oscurecerse solo con una organización deportiva.'
    );
    assert.doesNotMatch(fuente, /<div class="acta-modals">/, 'El contenedor de modales quedó fijo.');

    const inicio = fuente.indexOf("sportsMode ? 'acta-modals' : ''");
    const cierre = fuente.indexOf('</AppLayout>');

    assert.ok(inicio > -1 && cierre > inicio, 'No se encontró el bloque de modales del acta.');

    const modales = fuente.slice(inicio, cierre).match(/<ModalLargeXX/g) || [];

    assert.equal(modales.length, 3, 'Se esperaban los modales de goles, atajadas y cambios.');
});

test('los modales oscuros usan estilos scoped y no tocan el tema global', () => {
    assert.match(fuente, /<style scoped>/);
    assert.match(
        fuente,
        /\.acta-modals :deep\(\.modal-torneos__body\)/,
        'El cuerpo del modal debe heredar la piel navy.'
    );
    assert.match(
        fuente,
        /\.acta-modals :deep\(\.modal-torneos__body\)\s*\{[^}]*--torneos-control-bg:\s*#0e2532/,
        'Los campos del modal quedarían claros sobre el fondo oscuro.'
    );
    assert.match(
        fuente,
        /\.acta-modals :deep\(\.modal-torneos__body \.text-blue-600\)\s*\{\s*color:\s*#bef264/,
        'Los enlaces internos del modal deben pasar al lima del tema.'
    );

    // Ninguna regla del modal puede quedar suelta: si se cae el prefijo
    // `.acta-modals`, el tema se aplicaría a todos los modales del sistema.
    const estilos = fuente.slice(fuente.indexOf('<style scoped>'));

    assert.doesNotMatch(
        estilos,
        /^\s*\.modal-torneos/m,
        'Una regla del modal quedó sin el prefijo .acta-modals y afectaría al modo social.'
    );
});

test('el modo social conserva sus estilos originales', () => {
    assert.match(
        fuente,
        /sportsMode\s*\?\s*'border-\[#234052\] bg-\[#0b1f2b\][^']*'\s*:\s*'border-default dark:border-blue-900'/,
        'La rama del modo social debe mantener los bordes originales.'
    );
    assert.match(
        fuente,
        /: 'btn btn-warning text-xs'/,
        'El botón de volver del modo social debe seguir usando su clase original.'
    );
});
