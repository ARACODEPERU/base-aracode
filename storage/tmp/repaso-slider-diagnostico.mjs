/*
 * Diagnóstico del slider de la demo: vuelca el estado real del DOM y prueba el
 * botón «siguiente» con un clic de verdad (ratón por el protocolo), no con
 * `element.click()`, para distinguir «el manejador no está» de «el botón está
 * tapado».
 *
 * Uso: node storage/tmp/repaso-slider-diagnostico.mjs
 */

import { spawn } from 'node:child_process';
import { mkdir } from 'node:fs/promises';

const URL_DEMO = process.argv[2] ?? 'http://127.0.0.1:8123/demos/portafolio-creativo/';
const SALIDA = new URL('./repaso/', import.meta.url).pathname.replace(/^\//, '');
const CHROME = 'C:/Program Files/Google/Chrome/Application/chrome.exe';
const PUERTO = 9336;

await mkdir(SALIDA, { recursive: true });

const chrome = spawn(CHROME, [
    '--headless=new',
    `--remote-debugging-port=${PUERTO}`,
    '--user-data-dir=' + SALIDA.replace(/\//g, '\\') + 'perfil-slider',
    '--hide-scrollbars',
    '--no-first-run',
    '--force-device-scale-factor=1',
    'about:blank',
], { stdio: 'ignore' });

const esperar = (ms) => new Promise((r) => setTimeout(r, ms));

async function urlDepuracion() {
    for (let intento = 0; intento < 40; intento++) {
        try {
            const respuesta = await fetch(`http://127.0.0.1:${PUERTO}/json/version`);

            if (respuesta.ok) {
                return (await respuesta.json()).webSocketDebuggerUrl;
            }
        } catch {
            /* Todavía no está: se reintenta. */
        }

        await esperar(250);
    }

    throw new Error('Chrome no publicó el puerto de depuración');
}

const ws = new WebSocket(await urlDepuracion());
await new Promise((r) => (ws.onopen = r));

let siguienteId = 0;
const pendientes = new Map();
const consola = [];

ws.onmessage = (mensaje) => {
    const datos = JSON.parse(mensaje.data);

    if (datos.method === 'Runtime.consoleAPICalled') {
        consola.push(datos.params.args.map((a) => a.value ?? a.description).join(' '));
    }

    if (datos.method === 'Runtime.exceptionThrown') {
        consola.push('EXCEPCIÓN: ' + (datos.params.exceptionDetails.exception?.description
            ?? datos.params.exceptionDetails.text));
    }

    if (datos.id && pendientes.has(datos.id)) {
        const { resolver, rechazar } = pendientes.get(datos.id);
        pendientes.delete(datos.id);

        datos.error ? rechazar(new Error(JSON.stringify(datos.error))) : resolver(datos.result);
    }
};

function cdp(metodo, params = {}, sessionId) {
    const id = ++siguienteId;

    return new Promise((resolver, rechazar) => {
        pendientes.set(id, { resolver, rechazar });
        ws.send(JSON.stringify({ id, method: metodo, params, ...(sessionId ? { sessionId } : {}) }));
    });
}

const { targetId } = await cdp('Target.createTarget', { url: 'about:blank' });
const { sessionId } = await cdp('Target.attachToTarget', { targetId, flatten: true });

await cdp('Page.enable', {}, sessionId);
await cdp('Runtime.enable', {}, sessionId);
await cdp('Log.enable', {}, sessionId);
await cdp('Emulation.setDeviceMetricsOverride', {
    width: 1440, height: 900, deviceScaleFactor: 1, mobile: false,
}, sessionId);
await cdp('Page.navigate', { url: URL_DEMO }, sessionId);
await esperar(3000);

async function evaluar(expresion) {
    const { result, exceptionDetails } = await cdp('Runtime.evaluate', {
        expression: `(() => { ${expresion} })()`,
        returnByValue: true,
        awaitPromise: true,
    }, sessionId);

    if (exceptionDetails) {
        return { error: exceptionDetails.text };
    }

    return result.value;
}

const volcado = () => evaluar(`
    const pista = document.querySelector('.portada__pista');
    const diapositivas = [...document.querySelectorAll('[data-diapositiva]')];
    const puntos = [...document.querySelectorAll('[data-carrusel-punto]')];
    const siguiente = document.querySelector('[data-carrusel-siguiente]');
    const caja = siguiente ? siguiente.getBoundingClientRect() : null;

    return {
        listo: document.readyState,
        clasesHtml: document.documentElement.className,
        transform: pista ? pista.style.transform : '(sin pista)',
        ocultas: diapositivas.map((d) => d.getAttribute('aria-hidden')),
        puntos: puntos.length,
        activos: puntos.filter((p) => p.getAttribute('aria-current') === 'true').length,
        boton: siguiente ? {
            visible: getComputedStyle(siguiente).display !== 'none',
            centro: [Math.round(caja.x + caja.width / 2), Math.round(caja.y + caja.height / 2)],
            enLaVentana: caja.width > 0 && caja.top >= 0 && caja.bottom <= innerHeight,
            elementoEncima: document.elementFromPoint(caja.x + caja.width / 2, caja.y + caja.height / 2)?.className,
        } : '(no hay botón)',
        scripts: [...document.querySelectorAll('script[src]')].map((s) => s.getAttribute('src')),
    };
`);

const antes = await volcado();
console.log('== estado inicial ==');
console.log(JSON.stringify(antes, null, 2));
console.log('consola:', consola.length ? consola.join(' | ') : '(sin mensajes)');

// Clic de verdad con el ratón, en el centro del botón.
if (antes.boton && antes.boton.centro) {
    const [x, y] = antes.boton.centro;

    await cdp('Input.dispatchMouseEvent', { type: 'mouseMoved', x, y }, sessionId);
    await cdp('Input.dispatchMouseEvent', { type: 'mousePressed', x, y, button: 'left', clickCount: 1 }, sessionId);
    await cdp('Input.dispatchMouseEvent', { type: 'mouseReleased', x, y, button: 'left', clickCount: 1 }, sessionId);
    await esperar(900);

    console.log('\n== tras el clic de ratón en «siguiente» ==');
    console.log(JSON.stringify(await volcado(), null, 2));
}

ws.close();
chrome.kill();
process.exit(0);
