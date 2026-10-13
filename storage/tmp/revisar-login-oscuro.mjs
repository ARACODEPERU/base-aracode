/*
 * Revisión del logotipo del /login en modo oscuro.
 *
 * Comprueba contra Chrome real que, con el tema oscuro activo, el panel muestra
 * el LOGO NEGATIVO y deja captura. El archivo que sube la empresa
 * (storage/uploads/company/...png) no está en la copia local —las subidas viven
 * en el servidor—, así que la imagen se apunta al negativo de public/img, que es
 * byte a byte el mismo archivo (md5 a196ee7f…): lo que se ve en la captura es lo
 * que se verá en producción.
 *
 * No usa ninguna librería: el WebSocket es el de Node y el protocolo de
 * DevTools se habla a mano, igual que en storage/tmp/revisar-paginas-web.mjs.
 *
 * Uso: node storage/tmp/revisar-login-oscuro.mjs
 */

import { spawn } from 'node:child_process';
import { mkdir, rm, writeFile } from 'node:fs/promises';

const BASE = (process.argv[2] ?? 'http://base-aracode.test').replace(/\/$/, '');
const LOGIN = BASE + '/login';
const SALIDA = new URL('./revisar-login-oscuro/', import.meta.url).pathname.replace(/^\//, '');
const CHROME = 'C:/Program Files/Google/Chrome/Application/chrome.exe';
const PUERTO = 9354;
const NEGATIVO_LOCAL = '/img/logo176x32_negativo.png';

await mkdir(SALIDA, { recursive: true });
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

const informe = [];
const anota = (linea) => {
    informe.push(linea);
    console.log(linea);
};

async function abrir(ancho, alto, url) {
    const { targetId } = await cdp('Target.createTarget', { url: 'about:blank' });
    const { sessionId } = await cdp('Target.attachToTarget', { targetId, flatten: true });

    await cdp('Page.enable', {}, sessionId);
    await cdp('Runtime.enable', {}, sessionId);
    await cdp('Network.enable', {}, sessionId);
    await cdp('Emulation.setDeviceMetricsOverride', {
        width: ancho, height: alto, deviceScaleFactor: 1, mobile: ancho < 600,
    }, sessionId);

    await cdp('Page.navigate', { url }, sessionId);
    await esperar(3000);

    return sessionId;
}

async function captura(sessionId, archivo) {
    const { data } = await cdp('Page.captureScreenshot', { format: 'png' }, sessionId);

    await writeFile(SALIDA + archivo, Buffer.from(data, 'base64'));
}

/** Estado del logotipo y el tema, con el negativo ya cargado si hace falta. */
async function mirarLogin(sessionId, srcForzado) {
    return evaluar(sessionId, `
        const img = document.querySelector('.aracode-login__logo');

        if (!img) {
            return { error: 'no hay página de login' };
        }

        const oscuro = !!document.querySelector('.aracode-login--dark');

        if (${JSON.stringify(srcForzado ?? null)}) {
            img.src = ${JSON.stringify(srcForzado)};
            await new Promise((r) => setTimeout(r, 1200));
        }

        const caja = img.getBoundingClientRect();
        const fondo = getComputedStyle(img.closest('.aracode-login__logo-wrap')).backgroundColor;

        return {
            titulo: document.title,
            oscuro,
            src: img.src,
            cargada: img.complete && img.naturalWidth > 0,
            natural: [img.naturalWidth, img.naturalHeight],
            caja: [Math.round(caja.width), Math.round(caja.height)],
            fondoDelMarco: fondo,
            // El logo va sobre el panel: si el marco tuviera fondo claro, el
            // negativo (blanco) se perdería igual que con el JPG.
            filtro: getComputedStyle(img).filter,
        };
    `);
}

/** Enciende el tema oscuro con el propio interruptor de la página, que es como
    lo hará quien navega, y confirma que quedó encendido. */
async function encenderOscuro(sesion) {
    const yaOscuro = await evaluar(sesion, "return !!document.querySelector('.aracode-login--dark');");

    if (!yaOscuro) {
        await evaluar(sesion, `
            document.querySelector('.aracode-login__switch input')?.click();
            await new Promise((r) => setTimeout(r, 900));

            return true;
        `);
    }

    return evaluar(sesion, "return !!document.querySelector('.aracode-login--dark');");
}

const sesion = await abrir(1440, 900, LOGIN);

// Primero, el dato que importa: sin tocar nada, qué logo resuelve el tema con
// el que entra el visitante (el tema se recuerda entre visitas).
const inicial = await mirarLogin(sesion, null);
anota('== /login, tema con el que entra el visitante ==');
anota(`   oscuro: ${inicial.oscuro ? 'sí' : 'no'} · logo resuelto: ${inicial.src}`);

const encendido = await encenderOscuro(sesion);

if (!encendido) {
    console.error('El interruptor no encendió el tema oscuro.');
    process.exit(1);
}

// La captura del oscuro apunta la imagen al negativo local (idéntico al de la
// empresa): las subidas no existen en esta copia, así que el archivo real
// devuelve 404 aunque la URL resuelta sea la correcta.
const oscuro = await mirarLogin(sesion, NEGATIVO_LOCAL);

anota('== /login en modo oscuro ==');
anota(`   oscuro: ${oscuro.oscuro ? 'sí' : 'no'}`);
anota(`   logo resuelto: ${oscuro.src}`);
anota(`   logo negativo cargado: ${oscuro.cargada ? `sí (${oscuro.natural[0]}×${oscuro.natural[1]})` : 'NO'}`);
anota(`   tamaño en pantalla: ${oscuro.caja[0]}×${oscuro.caja[1]} px · filtro: ${oscuro.filtro}`);
anota(`   fondo del marco del logo: ${oscuro.fondoDelMarco}`);

await captura(sesion, 'login-oscuro-1440.png');

// Y el otro lado, para que quede claro que el cambio es solo del oscuro: en
// claro sigue el logo de siempre sobre su recuadro blanco.
await evaluar(sesion, `
    document.querySelector('.aracode-login__switch input')?.click();
    await new Promise((r) => setTimeout(r, 900));

    return true;
`);

const claro = await mirarLogin(sesion, '/img/logo176x32.png');

anota('== /login en modo claro ==');
anota(`   oscuro: ${claro.oscuro ? 'sí' : 'no'} · logo resuelto: ${claro.src || claro.error}`);
anota(`   fondo del marco del logo: ${claro.fondoDelMarco}`);

await captura(sesion, 'login-claro-1440.png');

const movil = await abrir(390, 844, LOGIN);
await encenderOscuro(movil);
const oscuroMovil = await mirarLogin(movil, NEGATIVO_LOCAL);

anota('== /login en modo oscuro, 390 px ==');
anota(`   oscuro: ${oscuroMovil.oscuro ? 'sí' : 'no'} · logo cargado: ${oscuroMovil.cargada ? 'sí' : 'NO'} · tamaño: ${oscuroMovil.caja[0]}×${oscuroMovil.caja[1]} px`);

await captura(movil, 'login-oscuro-390.png');

anota('== consola y red ==');
anota(`   errores de consola: ${consola.length === 0 ? 'ninguno' : consola.join(' | ')}`);
anota(`   respuestas con error: ${red.length === 0 ? 'ninguna' : red.join(' | ')}`);

await writeFile(SALIDA + 'informe.txt', informe.join('\n') + '\n');

ws.close();
chrome.kill();

const problemas = [
    ...(oscuro.oscuro ? [] : ['el tema oscuro no quedó encendido']),
    ...(oscuro.cargada ? [] : ['el logo negativo no carga en el login oscuro']),
    ...(oscuro.caja[0] > 0 && oscuro.caja[0] <= 320 ? [] : [`el logo mide ${oscuro.caja[0]} px: se sale del panel`]),
    ...(oscuroMovil.cargada ? [] : ['el logo negativo no carga en móvil']),
    ...consola,
];

if (problemas.length > 0) {
    console.error('\nPROBLEMAS:\n - ' + problemas.join('\n - '));
    process.exit(1);
}

console.log('\nSin problemas: el login oscuro muestra el logo negativo entero.');
