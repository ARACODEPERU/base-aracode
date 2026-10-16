import assert from "node:assert/strict";
import test from "node:test";
import { readFileSync } from "node:fs";

/**
 * Negociacion gratuita: se entregan cursos o suscripciones que tienen costo, pero el
 * precio acordado es 0. El cliente entonces solo completa sus datos: no se le pide
 * ningun voucher de pago.
 *
 * Lo que se fija aqui:
 *  1. La regla que decide si una negociacion es gratuita (compartida por el formulario
 *     de creacion y la pagina publica, para que no se desincronicen).
 *  2. Que el boton de enviar del formulario publico NO dependa del bloque de voucher:
 *     antes vivia dentro de `v-if="showVoucherUpload"`, asi que apagar el voucher para
 *     una negociacion gratuita dejaba al cliente sin forma de enviar sus datos.
 */

const BASE_COMPONENTES = "../../Modules/Commercial/Resources/assets/js/Pages/Negotiations/";
const HELPERS = BASE_COMPONENTES + "Support/freeNegotiation.js";

const { isFreeNegotiation, totalPriceLabel, FREE_PAYMENT_METHOD } = await import(
    new URL(HELPERS, import.meta.url)
);

const leer = (ruta) => readFileSync(new URL(BASE_COMPONENTES + ruta, import.meta.url), "utf8");

test("el total en cero (numero, texto o con decimales) es una negociacion gratuita", () => {
    assert.equal(isFreeNegotiation(0), true);
    assert.equal(isFreeNegotiation(0.0), true);
    assert.equal(isFreeNegotiation("0"), true, "El input de precio llega como texto.");
    assert.equal(isFreeNegotiation("0.00"), true);
});

test("un total con monto sigue siendo una negociacion con cobro", () => {
    assert.equal(isFreeNegotiation(1200), false);
    assert.equal(isFreeNegotiation("1200"), false);
    assert.equal(isFreeNegotiation(0.01), false, "Hasta un centimo se cobra con voucher.");
    assert.equal(isFreeNegotiation(-1), false);
});

test("sin precio escrito la negociacion no es gratuita todavia", () => {
    assert.equal(isFreeNegotiation(null), false, "El campo vacio del formulario no es gratis.");
    assert.equal(isFreeNegotiation(undefined), false);
    assert.equal(isFreeNegotiation(""), false);
    assert.equal(isFreeNegotiation("   "), false);
});

test("un total ilegible no se toma como gratis", () => {
    assert.equal(isFreeNegotiation("abc"), false);
    assert.equal(isFreeNegotiation(NaN), false);
    assert.equal(isFreeNegotiation(Infinity), false);
});

test("la etiqueta del total muestra Gratis y no un monto en cero", () => {
    assert.equal(totalPriceLabel("PEN", 0), "Gratis");
    assert.equal(totalPriceLabel("PEN", "0"), "Gratis");
    assert.equal(totalPriceLabel("PEN", 150), "PEN 150.00");
    assert.equal(totalPriceLabel("USD", 99.5), "USD 99.50");
});

test("el medio de pago de una negociacion gratuita queda identificado", () => {
    assert.equal(FREE_PAYMENT_METHOD, "gratis");
});

test("la pagina publica usa la regla compartida y mantiene el boton de enviar visible", () => {
    const publico = leer("Public/Show.vue");

    assert.match(
        publico,
        /const isFree = computed\(\(\) => isFreeNegotiation\(props\.negotiation\.total_price\)\)/,
        "La pagina publica debe decidir con la misma regla que el formulario de creacion.",
    );

    // Sin voucher no hay nada que subir, pero el cliente igual tiene que poder enviar.
    assert.match(publico, /const showSubmitButton = computed\(\(\) => isFree\.value \|\| showVoucherUpload\.value\)/);
    assert.match(publico, /v-if="showSubmitButton"[\s\S]{0,400}Enviar confirmacion/);
    assert.doesNotMatch(
        publico,
        /<div v-if="showVoucherUpload" class="mt-6 flex justify-end">/,
        "El boton de enviar no puede quedar dentro del bloque de voucher.",
    );
});

test("la pantalla de proceso da el comprobante por no aplica en una negociacion gratuita", () => {
    const proceso = leer("Process.vue");

    assert.match(proceso, /const isFree = computed\(\(\) => isFreeNegotiation\(props\.negotiation\.total_price\)\)/);
    assert.match(
        proceso,
        /key: "document"[\s\S]{0,260}skipped: isFree\.value/,
        "El paso del comprobante no debe ejecutarse cuando el precio acordado es 0.",
    );
    assert.match(
        proceso,
        /<div v-if="!isFree" class="mt-5 panel">[\s\S]{0,200}Fecha de emision del comprobante/,
        "Sin comprobante no hay fecha de emision que ajustar.",
    );
});

test("el detalle del admin oculta el voucher y la fecha de emision en una negociacion gratuita", () => {
    const detalle = leer("Show.vue");

    assert.match(detalle, /const isFree = computed\(\(\) => isFreeNegotiation\(props\.negotiation\.total_price\)\)/);
    assert.match(detalle, /<div v-if="!isFree" class="panel">[\s\S]{0,120}isMercadoPago/);
    assert.match(detalle, /no hay voucher ni comprobante/);
});

test("el formulario de creacion usa la misma regla compartida", () => {
    const formulario = leer("Partials/NegotiationForm.vue");

    assert.match(formulario, /import \{ FREE_PAYMENT_METHOD, isFreeNegotiation \} from "\.\.\/Support\/freeNegotiation\.js"/);
    assert.match(formulario, /const isFree = computed\(\(\) => isFreeNegotiation\(form\.total_price\)\)/);
});

test("con total 0 el formulario inhabilita el tipo de pago, las cuotas y el medio de pago", () => {
    const formulario = leer("Partials/NegotiationForm.vue");

    // Los campos de cobro no se ocultan: quedan visibles pero inhabilitados.
    assert.match(formulario, /v-model="form\.payment_type"[\s\S]{0,400}:disabled="isFree"/);
    assert.match(formulario, /v-model="form\.single_payment_days"[\s\S]{0,200}:disabled="isFree"/);
    assert.match(formulario, /v-model:value="form\.payment_method"[\s\S]{0,200}:disabled="isFree"/);

    // 'gratis' no es una opcion elegible: solo aparece mientras la negociacion no tiene costo.
    assert.match(formulario, /const paymentMethodOptions = computed\(\(\) => props\.paymentMethods\s*\n?\s*\.filter\(\(item\) => isFree\.value \|\| item\.value !== FREE_PAYMENT_METHOD\)/);
});

test("el cliente de una negociacion gratuita no elige tipo de comprobante", () => {
    const publico = leer("Public/Show.vue");

    assert.match(
        publico,
        /<div v-if="!isFree" class="col-span-6 mt-4 border-t[^"]*">[\s\S]{0,120}Tipo de comprobante/,
        "Sin comprobante no se ofrece elegir boleta o factura.",
    );
    assert.match(publico, /<template v-if="!isFree && isBoleta">/);
    assert.match(publico, /<template v-if="!isFree && isFactura">/);
});

test("el resumen previo tampoco muestra la seccion de comprobante", () => {
    const resumen = leer("Public/Partials/SummaryModal.vue");

    assert.match(resumen, /import \{ isFreeNegotiation \} from "\.\.\/\.\.\/Support\/freeNegotiation\.js"/);
    assert.match(resumen, /if \(!isFree\.value\) \{[\s\S]{0,160}summarySection\("Comprobante"\)/);
});
