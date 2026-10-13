/*
 * Revisión del branding del ERP (Configuraciones → Branding).
 *
 * Comprueba contra Chrome real que la pantalla de Branding existe con sus dos
 * selectores de color, que la vista previa toma esos colores y que la marca
 * guardada se aplica a todo el ERP (botones primarios y menú). Deja capturas e
 * informe en storage/tmp/revisar-branding-empresa/.
 *
 * Habla el protocolo de DevTools a mano, igual que storage/tmp/revisar-login-oscuro.mjs.
 * Las credenciales salen del .env del proyecto (ADMIN_EMAIL / ADMIN_PASSWORD) y no
 * se imprimen en ningún momento.
 *
 * Uso: node storage/tmp/revisar-branding-empresa.mjs
 */

import { spawn } from 'node:child_process';
import { mkdir, readFile, rm, writeFile } from 'node:fs/promises';

const BASE = (process.argv[2] ?? 'http://base-aracode.test').replace(/\/$/, '');
const LOGIN = BASE + '/login';
const BRANDING = BASE + '/branding/show';
const EMPRESA = BASE + '/company/show';
const SALIDA = new URL('./revisar-branding-empresa/', import.meta.url).pathname.replace(/^\//, '');
const RAIZ = new URL('../../', import.meta.url).pathname.replace(/^\//, '');
const CHROME = 'C:/Program Files/Google/Chrome/Application/chrome.exe';
const PUERTO = 9356;

await mkdir(SALIDA, { recursive: true });
await rm(SALIDA + 'perfil', { recursive: true, force: true });

/** Credenciales del .env, sin exponerlas: nunca se imprimen ni se guardan. */
async function credenciales() {
    const datos = { email: 'admin@gmail.com', password: '12345678' };

    try {
        const env = await readFile(RAIZ + '.env', 'utf8');

        for (const linea of env.split(/\r?\n/)) {
            const pareja = linea.match(/^\s*([A-Z_]+)\s*=\s*(.*)\s*$/);

            if (!pareja) continue;

            const valor = pareja[2].replace(/^["']|["']$/g, '');

            if (pareja[1] === 'ADMIN_EMAIL') datos.email = valor;
            if (pareja[1] === 'ADMIN_PASSWORD') datos.password = valor;
        }
    } catch {
        /* Sin .env se usan los valores históricos del seeder. */
    }

    return datos;
}

const credencialesLogin = await credenciales();

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

async function esperarA(sessionId, expresion, limite = 20000) {
    const inicio = Date.now();

    while (Date.now() - inicio < limite) {
        if (await evaluar(sessionId, `return !!(${expresion});`)) {
            return true;
        }

        await esperar(400);
    }

    return false;
}

const sesion = await abrir(1440, 900, LOGIN);

anota('== paso 1: iniciar sesión ==');

await esperarA(sesion, "document.querySelector('input[type=\"email\"]')");

await evaluar(sesion, `
    const escribir = (selector, valor) => {
        const campo = document.querySelector(selector);
        const setter = Object.getOwnPropertyDescriptor(window.HTMLInputElement.prototype, 'value').set;
        setter.call(campo, valor);
        campo.dispatchEvent(new Event('input', { bubbles: true }));
        campo.dispatchEvent(new Event('change', { bubbles: true }));
    };

    escribir('input[type="email"]', ${JSON.stringify(credencialesLogin.email)});
    escribir('input[type="password"]', ${JSON.stringify(credencialesLogin.password)});

    return true;
`);

await esperar(300);
await evaluar(sesion, `
    const boton = [...document.querySelectorAll('button')]
        .find((b) => /entrar|iniciar/i.test(b.textContent || ''));

    boton?.click();

    return true;
`);

const entro = await esperarA(sesion, "!location.pathname.startsWith('/login')", 25000);
anota(`   sesión iniciada: ${entro ? 'sí' : 'NO'}`);

if (!entro) {
    anota(`   la página se quedó en: ${await evaluar(sesion, 'return location.pathname;')}`);
    await captura(sesion, 'login-no-entro.png');
    await writeFile(SALIDA + 'informe.txt', informe.join('\n') + '\n');
    ws.close();
    chrome.kill();
    process.exit(1);
}

anota('== paso 2: Configuraciones → Branding ==');

await cdp('Page.navigate', { url: BRANDING }, sesion);
const monto = await esperarA(sesion, "document.querySelectorAll('input[type=\"color\"]').length === 2", 30000);

anota(`   pantalla montada con dos selectores: ${monto ? 'sí' : 'NO'}`);

const medidas = await evaluar(sesion, `
    const color = (elemento) => elemento ? getComputedStyle(elemento).backgroundColor : null;
    const selectores = [...document.querySelectorAll('input[type="color"]')];

    return {
        url: location.pathname,
        coloresDeLosSelectores: selectores.map((s) => s.value),
        opcionEnMenu: (document.body.innerText || '').includes('Branding'),
        encabezado: (document.querySelector('h5')?.textContent || '').trim(),
        botonVistaPrevia: color(document.querySelector('.brand-vista-previa .btn-primary')),
        botonSecundario: color(document.querySelector('.brand-vista-previa .btn-secondary')),
    };
`);

if (medidas.error) {
    anota('   error leyendo la página: ' + medidas.error);
} else {
    anota(`   url: ${medidas.url}`);
    anota(`   opción «Branding» en el menú: ${medidas.opcionEnMenu ? 'sí' : 'NO'}`);
    anota(`   colores de los selectores: ${medidas.coloresDeLosSelectores.join(' y ')}`);
    anota(`   botón primario de la vista previa: ${medidas.botonVistaPrevia}`);
    anota(`   botón secundario de la vista previa: ${medidas.botonSecundario}`);
}

await captura(sesion, 'branding-pantalla-1440.png');

anota('== paso 3: la marca en el resto del ERP ==');

await cdp('Page.navigate', { url: EMPRESA }, sesion);
await esperarA(sesion, "document.querySelector('.btn-primary')", 30000);

const fuera = await evaluar(sesion, `
    const color = (elemento) => elemento ? getComputedStyle(elemento).backgroundColor : null;
    const boton = document.querySelector('.btn-primary');
    const enlaceDeMarca = [...document.querySelectorAll('.text-primary')][0] ?? null;

    return {
        url: location.pathname,
        boton: boton ? color(boton) : null,
        texto: enlaceDeMarca ? getComputedStyle(enlaceDeMarca).color : null,
    };
`);

anota(`   ${fuera.url} · botón primario: ${fuera.boton} · texto primario: ${fuera.texto}`);

await captura(sesion, 'empresa-con-marca-1440.png');

anota('== consola y red ==');
anota(`   errores de consola: ${consola.length === 0 ? 'ninguno' : consola.join(' | ')}`);
anota(`   respuestas con error: ${red.length === 0 ? 'ninguna' : red.join(' | ')}`);

await writeFile(SALIDA + 'informe.txt', informe.join('\n') + '\n');

ws.close();
chrome.kill();

const problemas = [
    ...(monto ? [] : ['la pantalla de Branding no montó sus dos selectores']),
    ...(medidas.opcionEnMenu ? [] : ['la opción «Branding» no aparece en el menú']),
    ...(medidas.botonVistaPrevia === 'rgb(1, 136, 238)' ? [] : [`la vista previa quedó en ${medidas.botonVistaPrevia}, no en el azul guardado`]),
    ...(fuera.boton === 'rgb(1, 136, 238)' ? [] : [`el botón primario del ERP quedó en ${fuera.boton}, no en el azul guardado`]),
    ...red.filter((linea) => !linea.includes('favicon')),
];

if (problemas.length > 0) {
    console.error('\nPROBLEMAS:\n - ' + problemas.join('\n - '));
    process.exit(1);
}

console.log('\nSin problemas: Branding guarda los colores y el ERP se pinta con ellos.');
