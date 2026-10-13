/*
 * Comprueba en Chrome real el comportamiento del slider de la demo con y sin
 * `prefers-reduced-motion`, que es el único punto de la lista obligatoria que las
 * entregas anteriores dieron por no verificable: el protocolo de DevTools sí deja
 * emular la preferencia (`Emulation.setEmulatedMedia`).
 *
 * Espera un poco más que el intervalo del slider (6000 ms) para saber si avanza
 * solo, y después lo mueve a mano: con movimiento reducido el paso automático
 * debe estar apagado y el control manual seguir funcionando.
 *
 * Uso: node storage/tmp/repaso-movimiento.mjs
 */

import { spawn } from 'node:child_process';
import { mkdir } from 'node:fs/promises';

const URL_DEMO = process.argv[2] ?? 'http://127.0.0.1:8123/demos/portafolio-creativo/';
const SALIDA = new URL('./repaso/', import.meta.url).pathname.replace(/^\//, '');
const CHROME = 'C:/Program Files/Google/Chrome/Application/chrome.exe';
const PUERTO = 9335;
const ESPERA = 4000;

await mkdir(SALIDA, { recursive: true });

const chrome = spawn(CHROME, [
    '--headless=new',
    `--remote-debugging-port=${PUERTO}`,
    '--user-data-dir=' + SALIDA.replace(/\//g, '\\') + 'perfil-movimiento',
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

const informe = [];
const anota = (linea) => {
    informe.push(linea);
    console.log(linea);
};

/** Abre una pestaña con la preferencia de movimiento pedida. */
async function sesion(valor) {
    const { targetId } = await cdp('Target.createTarget', { url: 'about:blank' });
    const { sessionId } = await cdp('Target.attachToTarget', { targetId, flatten: true });

    await cdp('Page.enable', {}, sessionId);
    await cdp('Runtime.enable', {}, sessionId);
    await cdp('Emulation.setDeviceMetricsOverride', {
        width: 1440, height: 900, deviceScaleFactor: 1, mobile: false,
    }, sessionId);
    await cdp('Emulation.setEmulatedMedia', {
        features: [{ name: 'prefers-reduced-motion', value: valor }],
    }, sessionId);

    await cdp('Page.navigate', { url: URL_DEMO }, sessionId);
    await esperar(2200);

    return sessionId;
}

async function evaluar(sessionId, expresion) {
    const { result } = await cdp('Runtime.evaluate', {
        expression: `(() => { ${expresion} })()`,
        returnByValue: true,
        awaitPromise: true,
    }, sessionId);

    return result.value;
}

/** Estado de la portada: diapositiva activa y animaciones que dependen del movimiento. */
const estado = (sessionId) => evaluar(sessionId, `
    const activo = [...document.querySelectorAll('[data-carrusel-punto]')]
        .findIndex((p) => p.getAttribute('aria-current') === 'true');
    // La diapositiva activa es la única que queda con aria-hidden="false".
    const visible = [...document.querySelectorAll('[data-diapositiva]')]
        .findIndex((d) => d.getAttribute('aria-hidden') === 'false');
    const cinta = document.querySelector('.cinta__pista');
    const anillo = document.querySelector('.whatsapp__anillo');

    return {
        puntoActivo: activo,
        diapositivaVisible: visible,
        cinta: getComputedStyle(cinta).animationName,
        anillo: anillo ? getComputedStyle(anillo).animationName : null,
        preferencia: matchMedia('(prefers-reduced-motion: reduce)').matches,
    };
`);

async function revisar(valor) {
    const sessionId = await sesion(valor);

    const inicial = await estado(sessionId);

    anota(`\n== prefers-reduced-motion: ${valor} ==`);
    anota(`  el navegador declara la preferencia: ${inicial.preferencia ? 'sí' : 'no'}`);
    anota(`  diapositiva ${inicial.diapositivaVisible + 1} activa (punto ${inicial.puntoActivo + 1}) · animación de la cinta: ${inicial.cinta} · latido del WhatsApp: ${inicial.anillo}`);

    // Más que el intervalo del slider (6000 ms): si avanza solo, aquí se ve.
    await esperar(8000);

    const despues = await estado(sessionId);
    const soloAvanzo = despues.diapositivaVisible !== inicial.diapositivaVisible;

    anota(`  a los 10 s, diapositiva ${despues.diapositivaVisible + 1} (punto ${despues.puntoActivo + 1}) · paso automático: ${soloAvanzo ? 'sí avanzó' : 'no avanzó'}`);

    // El control manual tiene que funcionar en los dos casos.
    await evaluar(sessionId, `document.querySelector('[data-carrusel-siguiente]').click()`);
    await esperar(900);

    const manual = await estado(sessionId);

    anota(`  tras pulsar «siguiente», diapositiva ${manual.diapositivaVisible + 1} (punto ${manual.puntoActivo + 1}): el control manual ${manual.diapositivaVisible !== despues.diapositivaVisible ? 'funciona' : 'NO funciona'}`);

    return { autoavanza: soloAvanzo, manual: manual.diapositivaVisible !== despues.diapositivaVisible, cinta: inicial.cinta, anillo: inicial.anillo };
}

const reducido = await revisar('reduce');
const normal = await revisar('no-preference');

anota('\n== veredicto ==');
anota(`  con movimiento reducido: paso automático ${reducido.autoavanza ? 'ENCENDIDO (falla)' : 'apagado, como debe'}, control manual ${reducido.manual ? 'funciona' : 'roto'}`);
anota(`  sin la preferencia: paso automático ${normal.autoavanza ? 'funciona' : 'APAGADO (falla)'}, control manual ${normal.manual ? 'funciona' : 'roto'}`);
anota(`  animaciones apagadas por la preferencia: cinta ${reducido.cinta} (normal: ${normal.cinta}) · latido ${reducido.anillo} (normal: ${normal.anillo})`);

ws.close();
chrome.kill();
process.exit(0);
