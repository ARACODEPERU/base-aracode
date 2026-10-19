/*
 * Diagnóstico: a 1024 px las cinco páginas de NÉXORA MARKET desbordan en
 * horizontal (scrollWidth - clientWidth = 99). Este guion abre cada página con
 * Chrome sin interfaz y lista los elementos que se salen del ancho visible, con
 * su etiqueta, sus clases, su caja y el motivo probable, para localizar el
 * culpable antes de tocar la hoja de estilos.
 *
 * Uso: node storage/tmp/diagnosticar-desborde-nexora.mjs [url-base]
 */

import { spawn } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const BASE = (process.argv[2] ?? 'http://127.0.0.1:8132/demos/nexora-market/').replace(/\/$/, '');
const CHROME = 'C:/Program Files/Google/Chrome/Application/chrome.exe';
const PUERTO = 9357;
const PERFIL = fileURLToPath(new URL('./repaso-nexora/perfil-desborde', import.meta.url));

const chrome = spawn(CHROME, [
    '--headless=new',
    `--remote-debugging-port=${PUERTO}`,
    '--user-data-dir=' + PERFIL,
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

async function evaluar(sessionId, expresion) {
    const { result, exceptionDetails } = await cdp('Runtime.evaluate', {
        expression: `(() => { ${expresion} })()`,
        returnByValue: true,
        awaitPromise: true,
    }, sessionId);

    return exceptionDetails ? { error: exceptionDetails.text } : result.value;
}

const { targetId } = await cdp('Target.createTarget', { url: 'about:blank' });
const { sessionId } = await cdp('Target.attachToTarget', { targetId, flatten: true });

await cdp('Page.enable', {}, sessionId);
await cdp('Runtime.enable', {}, sessionId);

for (const ancho of [1280, 1180, 1120, 1100, 1024, 1000, 950, 901, 900, 820, 390]) {
    await cdp('Emulation.setDeviceMetricsOverride', { width: ancho, height: 800, deviceScaleFactor: 1, mobile: false }, sessionId);

    await cdp('Page.navigate', { url: `${BASE}/index.html` }, sessionId);
    await esperar(1200);

    const informe = await evaluar(sessionId, `
        const raiz = document.documentElement;
        const ancho = raiz.clientWidth;
        const culpables = [];

        Array.prototype.forEach.call(document.querySelectorAll('body *'), (nodo) => {
            const caja = nodo.getBoundingClientRect();
            const estilo = getComputedStyle(nodo);

            if (caja.width === 0 || estilo.position === 'fixed') {
                return;
            }

            // Los hijos de un panel fijo (la canasta) no cuentan para el ancho
            // desplazable del documento.
            let fijo = false;

            for (let padre = nodo.parentElement; padre; padre = padre.parentElement) {
                if (getComputedStyle(padre).position === 'fixed') {
                    fijo = true;
                    break;
                }
            }

            if (fijo) {
                return;
            }

            if (caja.right > ancho + 1) {
                culpables.push({
                    etiqueta: nodo.tagName.toLowerCase(),
                    id: nodo.id || '',
                    clases: nodo.className && typeof nodo.className === 'string' ? nodo.className : '',
                    ancho: Math.round(caja.width),
                    derecha: Math.round(caja.right),
                    izquierda: Math.round(caja.left),
                    posicion: estilo.position,
                    desbordeDelPadre: getComputedStyle(nodo.parentElement || nodo).overflowX,
                });
            }
        });

        // Los más anchos primero: ahí está la causa del desborde.
        culpables.sort((a, b) => b.derecha - a.derecha);

        return {
            desborde: raiz.scrollWidth - raiz.clientWidth,
            anchoVisible: ancho,
            culpables: culpables.slice(0, 8),
        };
    `);

    const nav = await cdp('Runtime.evaluate', {
        expression: `(() => {
            const lista = document.querySelector('[data-catalogo-rejilla]') ? null : document.querySelector('.nav__lista');
            const nav = document.querySelector('.nav');

            if (! nav) { return null; }

            return {
                visible: getComputedStyle(nav).display !== 'none',
                alto: Math.round(nav.getBoundingClientRect().height),
                filas: lista ? Math.round(lista.getBoundingClientRect().height / 48) : null,
            };
        })()`,
        returnByValue: true,
    }, sessionId);

    console.log(`\n== index.html a ${ancho} px ==`);
    console.log(`desborde: ${informe.desborde} px (ventana ${informe.anchoVisible}) · nav ${JSON.stringify(nav.result.value)}`);

    informe.culpables.forEach((c) => {
        console.log(`  <${c.etiqueta}${c.id ? ' id=' + c.id : ''} class="${c.clases}"> ancho ${c.ancho} · derecha ${c.derecha} · pos ${c.posicion} · overflow del padre: ${c.desbordeDelPadre}`);
    });
}

chrome.kill();
