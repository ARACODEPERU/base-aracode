/*
 * Mide cómo queda el nav horizontal entre 901 y 1180 px después de apretarlo:
 * cuántos enlaces caen en cada fila y si la última fila queda con un huérfano.
 * Es la única forma de juzgar el salto de línea sin ver la pantalla, porque el
 * panel de vista previa de este entorno no compone fotogramas.
 *
 * Uso: node storage/tmp/medir-nav-nexora.mjs
 */

import { spawn } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const BASE = 'http://127.0.0.1:8132/demos/nexora-market';
const CHROME = 'C:/Program Files/Google/Chrome/Application/chrome.exe';
const PUERTO = 9358;

const chrome = spawn(CHROME, [
    '--headless=new',
    `--remote-debugging-port=${PUERTO}`,
    '--user-data-dir=' + fileURLToPath(new URL('./repaso-nexora/perfil-nav', import.meta.url)),
    '--hide-scrollbars',
    '--no-first-run',
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
            /* Se reintenta. */
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

for (const ancho of [1180, 1120, 1100, 1024, 1000, 960, 901]) {
    await cdp('Emulation.setDeviceMetricsOverride', { width: ancho, height: 900, deviceScaleFactor: 1, mobile: false }, sessionId);
    await cdp('Page.navigate', { url: `${BASE}/index.html` }, sessionId);
    await esperar(1200);

    const { result } = await cdp('Runtime.evaluate', {
        expression: `(() => {
            const interiores = document.querySelector('.nav__interior');
            const boton = document.querySelector('.nav__categorias');
            const enlaces = Array.from(document.querySelectorAll('.nav__lista a'));

            // Agrupa por la coordenada vertical de cada pieza.
            const porFila = new Map();
            const suma = (nodo) => {
                const fila = Math.round(nodo.getBoundingClientRect().top);

                if (! porFila.has(fila)) { porFila.set(fila, []); }

                porFila.get(fila).push(nodo.textContent.trim());
            };

            suma(boton);
            enlaces.forEach(suma);

            const filas = Array.from(porFila.entries()).sort((a, b) => a[0] - b[0])
                .map(([, piezas]) => piezas);

            return {
                alto: Math.round(interiores.getBoundingClientRect().height),
                filas: filas.map((piezas) => piezas.length + ' · ' + piezas.join(' / ')),
            };
        })()`,
        returnByValue: true,
    }, sessionId);

    console.log(`\n== ${ancho} px · nav ${result.value.alto} px ==`);
    result.value.filas.forEach((fila, indice) => console.log(`  fila ${indice + 1}: ${fila}`));
}

chrome.kill();
