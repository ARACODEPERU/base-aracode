/*
 * Revisión de la imagen del artículo del blog.
 *
 * Comprueba contra Chrome real que la imagen destacada se ve ENTERA: que su
 * proporción en pantalla es la misma que la del archivo. Si no coinciden, la
 * caja la está recortando (era lo que pasaba con una caja 16/9 y `object-cover`
 * sobre imágenes 3/2: se perdía cerca del 15 % de la altura).
 *
 * Mide en 1440 y en 390 px, deja capturas y falla si hay recorte, desborde,
 * error de consola o petición fallida.
 *
 * Uso: node storage/tmp/revisar-blog-articulo.mjs [url]
 */

import { spawn } from 'node:child_process';
import { mkdir, rm, writeFile } from 'node:fs/promises';

const URL_ARTICULO = process.argv[2]
    ?? 'http://base-aracode.test/blog/gestion-empresarial-como-tomar-mejores-decisiones-con-informacion-y-datos';
const SALIDA = new URL('./repaso-blog/', import.meta.url).pathname.replace(/^\//, '');
const CHROME = 'C:/Program Files/Google/Chrome/Application/chrome.exe';
const PUERTO = 9361;

await mkdir(SALIDA, { recursive: true });

// Sin caché de la pasada anterior: se mide la hoja de estilos de ahora.
await rm(SALIDA + 'perfil', { recursive: true, force: true });

const chrome = spawn(CHROME, [
    '--headless=new',
    `--remote-debugging-port=${PUERTO}`,
    '--user-data-dir=' + SALIDA.replace(/\//g, '\\') + 'perfil',
    '--host-resolver-rules=MAP base-aracode.test 127.0.0.1',
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
const red = [];

ws.onmessage = (mensaje) => {
    const datos = JSON.parse(mensaje.data);

    if (datos.method === 'Runtime.exceptionThrown') {
        consola.push('EXCEPCIÓN: ' + (datos.params.exceptionDetails.exception?.description
            ?? datos.params.exceptionDetails.text));
    }

    if (datos.method === 'Runtime.consoleAPICalled' && datos.params.type === 'error') {
        consola.push('console.error: ' + datos.params.args.map((a) => a.value ?? a.description).join(' '));
    }

    if (datos.method === 'Network.loadingFailed') {
        red.push('falló: ' + datos.params.errorText);
    }

    if (datos.method === 'Network.responseReceived' && datos.params.response.status >= 400) {
        red.push(datos.params.response.status + ' ' + datos.params.response.url);
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

const informe = [];
const anota = (linea) => {
    informe.push(linea);
    console.log(linea);
};

async function evaluar(sessionId, expresion) {
    const { result, exceptionDetails } = await cdp('Runtime.evaluate', {
        expression: `(async () => { ${expresion} })()`,
        returnByValue: true,
        awaitPromise: true,
    }, sessionId);

    if (exceptionDetails) {
        return { error: exceptionDetails.text };
    }

    return result.value;
}

async function abrir(ancho, alto) {
    const { targetId } = await cdp('Target.createTarget', { url: 'about:blank' });
    const { sessionId } = await cdp('Target.attachToTarget', { targetId, flatten: true });

    await cdp('Page.enable', {}, sessionId);
    await cdp('Runtime.enable', {}, sessionId);
    await cdp('Network.enable', {}, sessionId);
    await cdp('Emulation.setDeviceMetricsOverride', {
        width: ancho, height: alto, deviceScaleFactor: 1, mobile: ancho < 600,
    }, sessionId);

    await cdp('Page.navigate', { url: URL_ARTICULO }, sessionId);
    await esperar(2500);

    await evaluar(sessionId, `
        document.querySelectorAll('.reveal, .reveal-left, .reveal-right, .reveal-scale')
            .forEach((b) => b.classList.add('revealed'));

        return true;
    `);
    await esperar(400);

    return sessionId;
}

async function captura(sessionId, archivo) {
    const { data } = await cdp('Page.captureScreenshot', {
        format: 'png', captureBeyondViewport: true,
    }, sessionId);

    await writeFile(SALIDA + archivo, Buffer.from(data, 'base64'));
}

/** Compara la proporción del archivo con la que ocupa en pantalla. */
async function medirImagen(sessionId) {
    return evaluar(sessionId, `
        const imagen = document.querySelector('article img');

        if (!imagen) {
            return { hay: false };
        }

        // La imagen es perezosa: se baja a ella para que se pida de verdad.
        imagen.scrollIntoView({ block: 'center' });
        await new Promise((r) => setTimeout(r, 700));

        const r = imagen.getBoundingClientRect();
        const delArchivo = imagen.naturalWidth / imagen.naturalHeight;
        const enPantalla = r.width / r.height;

        return {
            hay: true,
            archivo: imagen.naturalWidth + ' × ' + imagen.naturalHeight + ' · ' + delArchivo.toFixed(3),
            enPantalla: Math.round(r.width) + ' × ' + Math.round(r.height) + ' · ' + enPantalla.toFixed(3),
            // Una diferencia de más del 1 % ya es recorte visible.
            recorte: Math.abs(enPantalla - delArchivo) / delArchivo > 0.01,
            cargada: imagen.naturalWidth > 0,
            desborde: document.documentElement.scrollWidth > document.documentElement.clientWidth + 1,
        };
    `);
}

const escritorio = await abrir(1440, 1000);
const enEscritorio = await medirImagen(escritorio);

anota('== imagen destacada del artículo ==');
anota(`   1440 px · archivo: ${enEscritorio.archivo ?? '—'} · en pantalla: ${enEscritorio.enPantalla ?? '—'}`);
anota(`   se recorta: ${enEscritorio.recorte ? 'SÍ' : 'no'} · cargada: ${enEscritorio.cargada ? 'sí' : 'NO'}`);

await captura(escritorio, 'articulo-1440.png');

const movil = await abrir(390, 844);
const enMovil = await medirImagen(movil);

anota(`   390 px · en pantalla: ${enMovil.enPantalla ?? '—'} · se recorta: ${enMovil.recorte ? 'SÍ' : 'no'}`);

await captura(movil, 'articulo-390.png');

anota('== consola y red ==');
anota(`   errores de consola: ${consola.length === 0 ? 'ninguno' : consola.join(' | ')}`);
anota(`   peticiones fallidas: ${red.length === 0 ? 'ninguna' : red.join(' | ')}`);

await writeFile(SALIDA + 'informe.txt', informe.join('\n') + '\n');

ws.close();
chrome.kill();

const problemas = [
    ...(enEscritorio.hay === false ? ['no hay imagen destacada en el artículo'] : []),
    ...(enEscritorio.cargada === false ? ['la imagen destacada no carga'] : []),
    ...(enEscritorio.recorte ? ['la imagen se recorta en escritorio'] : []),
    ...(enMovil.recorte ? ['la imagen se recorta en móvil'] : []),
    ...(enEscritorio.desborde || enMovil.desborde ? ['desborde lateral'] : []),
    ...consola, ...red,
];

console.log(problemas.length === 0 ? '\nSin problemas.' : `\n${problemas.length} problema(s):\n - ` + problemas.join('\n - '));

process.exit(problemas.length === 0 ? 0 : 1);
