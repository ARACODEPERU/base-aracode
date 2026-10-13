/*
 * Comprobación corta del rediseño de la tienda: no mira capturas, mira valores
 * calculados por el navegador.
 *
 * Los números que decide el diseño —el acento, el color del lienzo, el tamaño y
 * el peso del precio, el descuento, el botón de añadir y el radio de las
 * tarjetas— se leen de `getComputedStyle` en Chrome sin interfaz, que es la
 * única forma de comprobarlos de verdad (el panel de vista previa no compone
 * fotogramas en esta máquina).
 *
 * Uso: node storage/tmp/tienda-probar-diseno.mjs
 */

import { spawn } from 'node:child_process';

const BASE = 'http://127.0.0.1:8123/demos/tienda-online';
const CHROME = 'C:/Program Files/Google/Chrome/Application/chrome.exe';
const PUERTO = 9346;

const chrome = spawn(CHROME, [
    '--headless=new',
    `--remote-debugging-port=${PUERTO}`,
    '--user-data-dir=' + process.cwd().replace(/\//g, '\\') + '\\storage\\tmp\\perfil-probar-diseno',
    '--hide-scrollbars', '--no-first-run', '--force-device-scale-factor=1', 'about:blank',
], { stdio: 'ignore' });

const esperar = (ms) => new Promise((r) => setTimeout(r, ms));

async function urlDepuracion() {
    for (let i = 0; i < 40; i++) {
        try {
            const r = await fetch(`http://127.0.0.1:${PUERTO}/json/version`);

            if (r.ok) { return (await r.json()).webSocketDebuggerUrl; }
        } catch { /* todavía no está */ }

        await esperar(250);
    }

    throw new Error('Chrome no publicó el puerto de depuración');
}

const ws = new WebSocket(await urlDepuracion());
await new Promise((r) => (ws.onopen = r));

let id = 0;
const pendientes = new Map();

ws.onmessage = (m) => {
    const d = JSON.parse(m.data);

    if (d.id && pendientes.has(d.id)) {
        const { resolver, rechazar } = pendientes.get(d.id);
        pendientes.delete(d.id);
        d.error ? rechazar(new Error(JSON.stringify(d.error))) : resolver(d.result);
    }
};

const cdp = (metodo, params = {}, sessionId) => new Promise((resolver, rechazar) => {
    const n = ++id;
    pendientes.set(n, { resolver, rechazar });
    ws.send(JSON.stringify({ id: n, method: metodo, params, ...(sessionId ? { sessionId } : {}) }));
});

async function abrir(url) {
    const { targetId } = await cdp('Target.createTarget', { url: 'about:blank' });
    const { sessionId } = await cdp('Target.attachToTarget', { targetId, flatten: true });

    await cdp('Page.enable', {}, sessionId);
    await cdp('Runtime.enable', {}, sessionId);
    await cdp('Emulation.setDeviceMetricsOverride', { width: 1440, height: 900, deviceScaleFactor: 1, mobile: false }, sessionId);
    await cdp('Emulation.setEmulatedMedia', { features: [{ name: 'prefers-color-scheme', value: 'light' }] }, sessionId);
    await cdp('Page.navigate', { url }, sessionId);
    await esperar(2200);

    return sessionId;
}

async function evaluar(sessionId, expresion) {
    const { result, exceptionDetails } = await cdp('Runtime.evaluate', {
        expression: `(() => { ${expresion} })()`, returnByValue: true, awaitPromise: true,
    }, sessionId);

    if (exceptionDetails) {
        throw new Error(exceptionDetails.text);
    }

    return result.value;
}

const sonda = `
    const raiz = getComputedStyle(document.documentElement);
    const estilo = (selector) => {
        const e = document.querySelector(selector);

        if (! e) { return null; }

        const c = getComputedStyle(e);

        return {
            color: c.color, fondo: c.backgroundColor, fuente: c.fontSize, peso: c.fontWeight,
            radio: c.borderRadius, tamano: Math.round(e.getBoundingClientRect().width) + 'x' + Math.round(e.getBoundingClientRect().height),
        };
    };
    const media = (selector) => {
        const e = document.querySelector(selector);
        const c = e && getComputedStyle(e);
        return e ? { display: c.display, tamano: Math.round(e.getBoundingClientRect().width) + 'x' + Math.round(e.getBoundingClientRect().height) } : null;
    };

    return {
        paleta: {
            papel: raiz.getPropertyValue('--papel').trim(),
            tinta: raiz.getPropertyValue('--tinta').trim(),
            acento: raiz.getPropertyValue('--acento').trim(),
            verde: raiz.getPropertyValue('--verde').trim(),
        },
        cuerpo: { fondo: getComputedStyle(document.body).backgroundColor, color: getComputedStyle(document.body).color },
        tarjeta: estilo('.pieza'),
        precio: estilo('.pieza__precio strong'),
        precioAntes: estilo('.pieza__precio s'),
        descuento: estilo('.pieza__oferta'),
        etiqueta: estilo('.pieza__insignia'),
        boton: estilo('.pieza__agregar'),
        foto: media('.pieza__marco img'),
        rejilla: media('.productos'),
        destacados: document.querySelectorAll('.productos--destacados .pieza').length,
        precios: [...document.querySelectorAll('.pieza__precio strong')].map((e) => e.textContent),
    };
`;

const escaparate = await abrir(BASE + '/index.html');
console.log('== Escaparate 1440 ==');
console.log(JSON.stringify(await evaluar(escaparate, sonda), null, 2));

const catalogo = await abrir(BASE + '/tienda.html');
console.log('\n== Catálogo 1440 ==');
const datos = await evaluar(catalogo, sonda);
console.log(JSON.stringify({ paleta: datos.paleta, tarjeta: datos.tarjeta, precio: datos.precio, descuento: datos.descuento, boton: datos.boton, rejilla: datos.rejilla }, null, 2));

await cdp('Browser.close').catch(() => {});
chrome.kill();
