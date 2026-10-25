import assert from 'node:assert/strict';
import test from 'node:test';
import { readFileSync } from 'node:fs';

/**
 * Tema azul noche y lima de la vista de pagos.
 *
 * La vista es la única puerta para recargar saldo incluso con el acceso
 * vencido, así que la prueba cuida dos cosas a la vez:
 *
 * 1. Que el tema deportivo pinte tarjetas sólidas y formularios oscuros.
 * 2. Que el modo social conserve los paneles y botones originales.
 *
 * También fija los IDs de los campos de Mercado Pago: si cambian, el SDK deja
 * de montar el formulario de tarjeta y la recarga se rompe sin error visible.
 */

const RUTA = '../../Modules/Socialevents/Resources/assets/js/Pages/Organizations/TopUp.vue';

const fuente = readFileSync(new URL(RUTA, import.meta.url), 'utf8');

const CAMPOS_MERCADO_PAGO = [
    'topup-card-form',
    'topup-card-number',
    'topup-card-expiration',
    'topup-card-cvv',
    'topup-card-name',
    'topup-card-issuer',
    'topup-card-installments',
    'topup-card-document-type',
    'topup-card-document-number',
    'topup-card-email',
];

test('la vista de pagos reconoce el modo deportivo compartido', () => {
    assert.match(
        fuente,
        /const sportsMode = computed\(\(\) => page\.props\.sportsOrganization\?\.mode === true\)/,
        'Sin la prop compartida la vista no puede seguir el tema de la organización.'
    );
});

test('el hero usa el navy y deja el panel original para el modo social', () => {
    assert.match(
        fuente,
        /sportsMode\s*\?\s*'border-white\/10 bg-\[#0b1f2b\] text-white[^']*'\s*:\s*'panel space-y-4'/,
        'El encabezado debe alternar entre navy y el panel de siempre.'
    );
});

test('las tarjetas de recarga e historial alternan entre navy y panel', () => {
    const tarjetas = fuente.match(/: 'panel space-y-4'/g) || [];

    assert.equal(tarjetas.length, 5, 'Se esperaban las cinco tarjetas con su rama social (hero, equipo, recarga, comprobantes e historial).');
    assert.match(
        fuente,
        /sportsMode \? 'border-\[#234052\] bg-\[#0b1f2b\] text-slate-100 shadow-\[0_26px_70px_rgba\(0,0,0,0\.38\)\]' : 'panel space-y-4'/,
        'Recarga e historial deben compartir la tarjeta azul noche.'
    );
});

test('los campos oscuros usan los tokens del tema en lugar de verdes fijos', () => {
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

test('ninguna superficie navy o lima quedó fija fuera del tema', () => {
    // `(?<!:)` descarta el comienzo de un `:class="..."`: lo que se busca es
    // una clase aplicada siempre, que se filtraría al modo social.
    assert.doesNotMatch(
        fuente,
        /(?<!:)class="[^"]*(?:bg-\[#0b1f2b\]|bg-\[#122c3a\]|bg-\[#c8f169\])/,
        'Una superficie navy o lima quedó fija y se filtraría al modo social.'
    );
});

test('los botones conservan su variante original en el modo social', () => {
    assert.match(fuente, /: 'btn btn-success'/, 'Los botones de pago perdieron la clase original.');
    assert.match(fuente, /: 'btn btn-outline-primary text-xs'/, 'El enlace de volver perdió su clase original.');
    assert.match(
        fuente,
        /: 'border-red-300 bg-red-50 text-red-700'/,
        'El aviso de error del modo social cambió de estilo.'
    );
    assert.match(
        fuente,
        /: 'bg-gray-50 text-gray-600 dark:bg-gray-800 dark:text-gray-300'/,
        'La cabecera del historial perdió su estilo original.'
    );
});

test('los chips de estado y del historial tienen su rama clara y su rama navy', () => {
    // Chip del hero: navy en deportivo y la clase original en social.
    assert.match(fuente, /if \(sportsMode\.value\) \{[\s\S]{0,220}border border-lime-300\/25 bg-lime-400\/15 text-lime-200/);
    assert.match(fuente, /if \(props\.access\.sponsored\) return 'bg-primary\/10 text-primary';/);

    // Chip del historial: acreditado en lima, rechazado en rosa, ambos con salida clara.
    assert.match(
        fuente,
        /sportsMode\.value \? 'border border-lime-300\/25 bg-lime-400\/15 text-lime-200' : 'bg-success\/10 text-success'/,
        'El estado acreditado debe conservar el verde de éxito en modo social.'
    );
    assert.match(
        fuente,
        /sportsMode\.value \? 'border border-rose-300\/25 bg-rose-400\/15 text-rose-200' : 'bg-danger\/10 text-danger'/,
        'El estado rechazado debe conservar el rojo original en modo social.'
    );
});

test('los atajos de recarga y el resumen no dependen del tema para funcionar', () => {
    assert.match(fuente, /const atajos = computed\(\(\) => \{/);
    assert.match(fuente, /const saldoDespues = computed\(\(\) => Number\(props\.balance\) \+ \(Number\(amount\.value\) \|\| 0\)\)/);
    assert.match(fuente, /const partidosConSaldo = computed\(\(\) => \{/);
    assert.match(fuente, /setAmount\(monto\)/, 'Los atajos deben escribir el monto elegido.');
});

test('los campos de Mercado Pago conservan todos sus IDs', () => {
    for (const id of CAMPOS_MERCADO_PAGO) {
        assert.match(
            fuente,
            new RegExp(`id="${id}"`),
            `Falta el campo ${id}: el SDK de Mercado Pago no montaría el formulario.`
        );
    }
});

test('los slots de tarjeta son oscuros solo en el tema y mantienen el input original en social', () => {
    const slots = fuente.match(/:class="sportsMode \? 'topup-card-slot overflow-hidden rounded-xl border border-\[#2b4b5d\] bg-\[#0e2532\]' : 'form-input'"/g) || [];

    assert.equal(slots.length, 3, 'Número, vencimiento y CVV deben conservar el slot condicional.');
    assert.match(fuente, /\.topup-card-slot :deep\(iframe\)/, 'El slot debe ajustar el iframe del SDK.');
    assert.doesNotMatch(
        fuente.slice(fuente.indexOf('<style scoped>')),
        /^\s*iframe\s*\{/m,
        'Un estilo de iframe sin slot afectaría a cualquier iframe de la vista.'
    );
});
