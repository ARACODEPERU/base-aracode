/*
 * Captura el plano de la demo «Portafolio Creativo» en los dos temas, a 1440 px,
 * para revisar por números (no por captura de pantalla) si el dibujo se sigue
 * leyendo cuando el CSS lo invierte.
 *
 * Uso: node storage/tmp/repaso-plano.mjs
 * Salida: storage/tmp/repaso/plano-claro.png y plano-oscuro.png
 */

import { spawn } from 'node:child_process';
import { mkdir, writeFile } from 'node:fs/promises';

const URL_DEMO = process.argv[2] ?? 'http://127.0.0.1:8123/demos/portafolio-creativo/';
const SALIDA = new URL('./repaso/', import.meta.url).pathname.replace(/^\//, '');
const CHROME = 'C:/Program Files/Google/Chrome/Application/chrome.exe';
const PUERTO = 9334;

await mkdir(SALIDA, { recursive: true });

const chrome = spawn(CHROME, [
    '--headless=new',
    `--remote-debugging-port=${PUERTO}`,
    '--user-data-dir=' + SALIDA.replace(/\//g, '\\') + 'perfil-plano',
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

ws.onmessage = (mensaje) => {
    const datos = JSON.parse(mensaje.data);

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
await cdp('Emulation.setDeviceMetricsOverride', {
    width: 1440, height: 900, deviceScaleFactor: 1, mobile: false,
}, sessionId);
await cdp('Page.navigate', { url: URL_DEMO }, sessionId);
await esperar(2500);

async function evaluar(expresion) {
    const { result } = await cdp('Runtime.evaluate', {
        expression: `(() => { ${expresion} })()`,
        returnByValue: true,
        awaitPromise: true,
    }, sessionId);

    return result.value;
}

async function capturarPlano(archivo) {
    const caja = await evaluar(`
        const img = document.querySelector('.ubicacion__plano img');
        const r = img.getBoundingClientRect();

        return { x: r.x + scrollX, y: r.y + scrollY, width: r.width, height: r.height };
    `);

    const { data } = await cdp('Page.captureScreenshot', {
        format: 'png',
        captureBeyondViewport: true,
        clip: { ...caja, scale: 1 },
    }, sessionId);

    await writeFile(`${SALIDA}${archivo}.png`, Buffer.from(data, 'base64'));
    console.log(`${archivo}: ${Math.round(caja.width)} × ${Math.round(caja.height)} px`);
}

await evaluar(`
    document.querySelectorAll('.revelar').forEach((b) => b.classList.add('visible'));
    document.documentElement.setAttribute('data-tema', 'claro');
`);

// El plano va con `loading="lazy"`: sin bajarlo a mano la captura sale con la
// caja vacía (toda blanca en claro y toda oscura en oscuro).
await evaluar(`
    const img = document.querySelector('.ubicacion__plano img');

    img.loading = 'eager';
    img.scrollIntoView({ block: 'center' });

    return img.decode().then(() => ({ completo: img.complete, natural: img.naturalWidth }));
`).then((estado) => console.log(`plano descargado: ${JSON.stringify(estado)}`));

await esperar(400);
await capturarPlano('plano-claro');

await evaluar(`document.documentElement.setAttribute('data-tema', 'oscuro')`);
await esperar(500);
await capturarPlano('plano-oscuro');

console.log('filtro en oscuro: ' + await evaluar(`
    return getComputedStyle(document.querySelector('.ubicacion__plano img')).filter;
`));

ws.close();
chrome.kill();
process.exit(0);
