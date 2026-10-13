/*
 * Revisión de la demostración «Supermercado Online — VERDIA Market» contra
 * Chrome real.
 *
 * El panel de vista previa del editor no deja fijar el ancho de la ventana, así
 * que la comprobación se hace aquí: Chrome sin interfaz y el protocolo de
 * DevTools, que sí permite fijar el dispositivo, hacer clic, capturar por sección
 * y emular preferencias. No usa ninguna librería.
 *
 * Recorre las cuatro páginas (escaparate, catálogo, ficha y canasta), mide el
 * desborde, prueba el carrito, los filtros, las pestañas, el buscador, la
 * simulación del pedido y el interruptor de tema, y deja capturas en
 * storage/tmp/repaso-verdia/.
 *
 * Uso: node storage/tmp/revisar-demo-verdia.mjs [url-base]
 */

import { spawn } from 'node:child_process';
import { mkdir, writeFile } from 'node:fs/promises';

const BASE = (process.argv[2] ?? 'http://127.0.0.1:8125/demos/supermercado-online-verdia/').replace(/\/$/, '');
const SALIDA = new URL('./repaso-verdia/', import.meta.url).pathname.replace(/^\//, '');
const ORIGEN = new URL(BASE).origin;
const CHROME = 'C:/Program Files/Google/Chrome/Application/chrome.exe';
const PUERTO = 9351;

await mkdir(SALIDA, { recursive: true });

const chrome = spawn(CHROME, [
    '--headless=new',
    `--remote-debugging-port=${PUERTO}`,
    '--user-data-dir=' + SALIDA.replace(/\//g, '\\') + 'perfil',
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
const problemas = [];

ws.onmessage = (mensaje) => {
    const datos = JSON.parse(mensaje.data);

    if (datos.method === 'Runtime.exceptionThrown') {
        problemas.push('EXCEPCIÓN: ' + (datos.params.exceptionDetails.exception?.description
            ?? datos.params.exceptionDetails.text));
    }

    if (datos.method === 'Runtime.consoleAPICalled' && datos.params.type === 'error') {
        problemas.push('console.error: ' + datos.params.args.map((a) => a.value ?? a.description).join(' '));
    }

    if (datos.method === 'Log.entryAdded' && datos.params.entry.level === 'error') {
        problemas.push('log: ' + datos.params.entry.text);
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

const formato = (lado) => (lado ? `${lado[0]}×${lado[1]} px` : 'oculto');

async function evaluar(sessionId, expresion) {
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

async function capturar(sessionId, nombre, selector = null) {
    let params = { format: 'png', captureBeyondViewport: true };

    if (selector) {
        const caja = await evaluar(sessionId, `
            const e = document.querySelector('${selector}');

            if (! e) { return null; }

            const r = e.getBoundingClientRect();

            return {
                x: Math.round(r.left + scrollX),
                y: Math.round(r.top + scrollY),
                width: Math.round(r.width),
                height: Math.round(r.height),
            };
        `);

        if (! caja) {
            anota(`  (sin captura: no existe ${selector})`);

            return;
        }

        params = { ...params, clip: { ...caja, scale: 1 } };
    }

    const { data } = await cdp('Page.captureScreenshot', params, sessionId);
    await writeFile(`${SALIDA}${nombre}.png`, Buffer.from(data, 'base64'));
}

/**
 * Abre una pestaña con las medidas pedidas y limpia el almacén de la tienda.
 * `movimiento` fija la preferencia: esta máquina la trae reducida y sin fijarla
 * se mediría la variante de movimiento reducido creyendo que es la normal.
 */
async function abrir(ancho, alto, ruta = 'index.html', movimiento = 'no-preference') {
    const { targetId } = await cdp('Target.createTarget', { url: 'about:blank' });
    const { sessionId } = await cdp('Target.attachToTarget', { targetId, flatten: true });

    await cdp('Page.enable', {}, sessionId);
    await cdp('Runtime.enable', {}, sessionId);
    await cdp('Log.enable', {}, sessionId);
    await cdp('Emulation.setDeviceMetricsOverride', {
        width: ancho, height: alto, deviceScaleFactor: 1, mobile: ancho < 600,
    }, sessionId);
    await cdp('Emulation.setEmulatedMedia', {
        features: [
            { name: 'prefers-reduced-motion', value: movimiento },
            { name: 'prefers-color-scheme', value: 'light' },
        ],
    }, sessionId);

    await cdp('Storage.enable', {}, sessionId).catch(() => {});
    await cdp('Storage.clearDataForOrigin', {
        origin: ORIGEN, storageTypes: 'local_storage',
    }, sessionId).catch(() => {});

    await cdp('Page.navigate', { url: BASE + '/' + ruta }, sessionId);
    await esperar(2200);

    // El observador de aparición necesita que las secciones estén dentro de la
    // ventana o marcadas: se marcan a mano para que las capturas salgan enteras.
    await evaluar(sessionId, `
        document.querySelectorAll('[data-aparece]').forEach((b) => b.classList.add('esta-visible'));

        return true;
    `);

    // Y las fotos se cargan todas: las capturas se toman enteras
    // (`captureBeyondViewport`), pero eso no dispara la carga diferida, así que
    // sin esto las secciones de más abajo salen con las imágenes en blanco.
    await evaluar(sessionId, `
        document.querySelectorAll('img[loading="lazy"]').forEach((i) => { i.loading = 'eager'; });

        return Promise.all([...document.images].filter((i) => ! i.complete).map((i) => new Promise((r) => {
            i.addEventListener('load', r, { once: true });
            i.addEventListener('error', r, { once: true });
        }))).then(() => document.images.length);
    `);
    await esperar(400);

    return sessionId;
}

async function clic(sessionId, selector) {
    const caja = await evaluar(sessionId, `
        const e = document.querySelector('${selector}');

        if (! e) { return null; }

        e.scrollIntoView({ block: 'center' });

        const r = e.getBoundingClientRect();

        return { x: Math.round(r.x + r.width / 2), y: Math.round(r.y + r.height / 2) };
    `);

    if (! caja) {
        anota(`  (no se pudo pulsar ${selector}: no existe)`);

        return false;
    }

    await cdp('Input.dispatchMouseEvent', { type: 'mousePressed', x: caja.x, y: caja.y, button: 'left', clickCount: 1 }, sessionId);
    await cdp('Input.dispatchMouseEvent', { type: 'mouseReleased', x: caja.x, y: caja.y, button: 'left', clickCount: 1 }, sessionId);

    return true;
}

async function desborde(sessionId, ancho) {
    const datos = await evaluar(sessionId, `
        const raiz = document.documentElement;
        const salidos = [...document.querySelectorAll('body *')].filter((e) => {
            const r = e.getBoundingClientRect();
            const estilo = getComputedStyle(e);

            return estilo.position !== 'fixed'
                && estilo.overflowX !== 'auto'
                && r.width > 0
                && Math.round(r.right) > ${ancho} + 1;
        }).slice(0, 6).map((e) => e.tagName.toLowerCase() + '.' + String(e.className).split(' ')[0]);

        return {
            desborde: raiz.scrollWidth - raiz.clientWidth,
            salidos,
            altoPagina: document.body.scrollHeight,
            altoVentana: window.innerHeight,
        };
    `);

    anota(`  desborde lateral: ${datos.desborde} px · fuera de la ventana: ${datos.salidos.length ? datos.salidos.join(', ') : 'ninguno'}`);

    return datos;
}

/* ---------------------------------------------- Escaparate (index.html) */

const escritorio = await abrir(1440, 900, 'index.html');

anota('== Escaparate · 1440 px (claro) ==');
await desborde(escritorio, 1440);

anota('  estructura: ' + JSON.stringify(await evaluar(escritorio, `
    return {
        pasillos: document.querySelectorAll('[data-rubros] .rubro').length,
        populares: document.querySelectorAll('[data-populares] .pieza').length,
        ofertas: document.querySelectorAll('[data-ofertas] .pieza').length,
        pestanas: document.querySelectorAll('[data-grupos] button').length,
        primeraPestana: document.querySelectorAll('[data-grupos-panel] .pieza').length,
        necesidades: document.querySelectorAll('[data-necesidades] .necesidad').length,
        bandas: document.querySelectorAll('.banda').length,
        diapositivas: document.querySelectorAll('[data-diapositiva]').length,
        puntos: document.querySelectorAll('[data-carrusel-punto]').length,
        titulosPiezas: [...document.querySelectorAll('[data-populares] .pieza__nombre')].map((e) => e.textContent.trim()),
    };
`)));

await capturar(escritorio, '01-portada', '.portada');
await capturar(escritorio, '02-pasillos', '#pasillos');
await capturar(escritorio, '03-populares', '#populares');
await capturar(escritorio, '04-ofertas', '#ofertas');

// El slider avanza con un clic real en la flecha.
await clic(escritorio, '[data-carrusel-siguiente]');
await esperar(700);

anota('  slider tras «siguiente»: ' + JSON.stringify(await evaluar(escritorio, `
    const diapositivas = [...document.querySelectorAll('[data-diapositiva]')];

    return {
        activa: diapositivas.findIndex((d) => d.hasAttribute('data-activa')),
        puntoActivo: [...document.querySelectorAll('[data-carrusel-punto]')].findIndex((p) => p.getAttribute('aria-current') === 'true'),
    };
`)));

// Pestañas de «lo más vendido»: cambian los productos de verdad.
const antesDePestana = await evaluar(escritorio, `return document.querySelector('[data-grupos-panel] .pieza__nombre').textContent.trim();`);
await clic(escritorio, '[data-grupo="lacteos"]');
await esperar(300);
const despuesDePestana = await evaluar(escritorio, `return [...document.querySelectorAll('[data-grupos-panel] .pieza__nombre')].map((e) => e.textContent.trim()).join(' | ');`);

anota(`  pestañas: «${antesDePestana}» → «${despuesDePestana}»`);

// Buscador con sugerencias.
await evaluar(escritorio, `
    const campo = document.querySelector('#buscar-portada');
    campo.value = 'pan';
    campo.dispatchEvent(new Event('input', { bubbles: true }));

    return true;
`);
await esperar(300);

anota('  sugerencias del buscador: ' + JSON.stringify(await evaluar(escritorio, `
    return [...document.querySelectorAll('[data-sugerencias] a strong')].map((e) => e.textContent.trim());
`)));

// Carrito desde la portada: se añade y se cuenta.
await clic(escritorio, '[data-populares] [data-agregar]');
await esperar(400);

anota('  carrito tras añadir uno: ' + JSON.stringify(await evaluar(escritorio, `
    return {
        cuenta: document.querySelector('[data-canasta-cuenta]').textContent.trim(),
        panel: document.querySelector('[data-canasta-panel]').hasAttribute('hidden') ? 'cerrado' : 'abierto',
    };
`)));

await clic(escritorio, '[data-canasta-abrir]');
await esperar(400);
// El panel vive fijo sobre la ventana: se captura el viewport entero, no un
// recorte, porque el recorte de un elemento fijo sale con el alto del documento.
await capturar(escritorio, '05-panel-canasta');

anota('  panel de canasta: ' + JSON.stringify(await evaluar(escritorio, `
    return {
        abierto: ! document.querySelector('[data-canasta-panel]').hasAttribute('hidden'),
        lineas: document.querySelectorAll('[data-canasta-lista] .linea').length,
        total: document.querySelector('[data-canasta-total]').textContent.trim(),
    };
`)));

// Tema oscuro.
await clic(escritorio, '[data-canasta-cerrar]');
await evaluar(escritorio, `document.querySelector('[data-tema-switch]').click(); return true;`);
await esperar(400);

anota('  tema oscuro: ' + JSON.stringify(await evaluar(escritorio, `
    return {
        tema: document.documentElement.dataset.tema,
        guardado: localStorage.getItem('supermercado-online-verdia-tema'),
        pulsado: document.querySelector('[data-tema-switch]').getAttribute('aria-pressed'),
        etiqueta: document.querySelector('[data-tema-switch]').getAttribute('aria-label'),
    };
`)));

await capturar(escritorio, '06-oscuro', '.portada');

await evaluar(escritorio, `document.querySelector('[data-tema-switch]').click(); return true;`);
await esperar(300);

/* ------------------------------------------------ Catálogo (tienda.html) */

const catalogo = await abrir(1440, 900, 'tienda.html');

anota('== Catálogo · 1440 px ==');
await desborde(catalogo, 1440);

anota('  sin filtros: ' + JSON.stringify(await evaluar(catalogo, `
    return {
        productos: document.querySelectorAll('[data-catalogo] .pieza').length,
        filtros: document.querySelectorAll('[data-catalogo-filtros] button').length,
        resumen: document.querySelector('[data-catalogo-resumen]').textContent.trim(),
    };
`)));

await clic(catalogo, '[data-catalogo-filtros] [data-rubro="panaderia"]');
await esperar(400);
anota('  filtro panadería: ' + JSON.stringify(await evaluar(catalogo, `
    return {
        productos: [...document.querySelectorAll('[data-catalogo] .pieza__nombre')].map((e) => e.textContent.trim()),
        url: location.search,
    };
`)));

await clic(catalogo, '[data-catalogo-filtros] [data-necesidad="ahorro"]');
await esperar(400);
anota('  necesidad ahorro: ' + JSON.stringify(await evaluar(catalogo, `
    return {
        productos: [...document.querySelectorAll('[data-catalogo] .pieza__nombre')].map((e) => e.textContent.trim()),
        url: location.search,
    };
`)));

// Orden por precio.
await evaluar(catalogo, `
    document.querySelector('[data-catalogo-filtros] [data-rubro=""]').click();

    const orden = document.querySelector('[data-catalogo-orden]');
    orden.value = 'precio-asc';
    orden.dispatchEvent(new Event('change', { bubbles: true }));

    return true;
`);
await esperar(300);
anota('  orden por precio: ' + JSON.stringify(await evaluar(catalogo, `
    return {
        montos: [...document.querySelectorAll('[data-catalogo] .pieza__monto')].slice(0, 4).map((e) => e.textContent.trim()),
        url: location.search,
    };
`)));

// Estado vacío.
await evaluar(catalogo, `
    const campo = document.querySelector('[data-catalogo-buscador]');
    campo.value = 'zzzz';
    campo.dispatchEvent(new Event('input', { bubbles: true }));

    return true;
`);
await esperar(300);
anota('  estado vacío: ' + JSON.stringify(await evaluar(catalogo, `
    return {
        piezas: document.querySelectorAll('[data-catalogo] .pieza').length,
        mensaje: (document.querySelector('[data-catalogo] .vacio') || {}).textContent,
    };
`)));
await capturar(catalogo, '07-catalogo-vacio', 'main');

// La URL con filtro abre ya filtrada (enlace compartible).
await cdp('Page.navigate', { url: BASE + '/tienda.html?rubro=lacteos' }, catalogo);
await esperar(1800);
anota('  enlace con filtro: ' + JSON.stringify(await evaluar(catalogo, `
    return {
        productos: [...document.querySelectorAll('[data-catalogo] .pieza__nombre')].map((e) => e.textContent.trim()),
        activo: document.querySelector('[data-catalogo-filtros] .esta-activo').textContent.trim(),
    };
`)));
await capturar(catalogo, '08-catalogo-lacteos', 'main');

/* --------------------------------------------------- Ficha (producto.html) */

const ficha = await abrir(1440, 1000, 'producto.html?p=cafe');

anota('== Ficha de producto · 1440 px ==');
anota('  ficha de «cafe»: ' + JSON.stringify(await evaluar(ficha, `
    return {
        titulo: document.title,
        nombre: document.querySelector('[data-ficha-nombre]').textContent.trim(),
        rubro: document.querySelector('[data-ficha-rubro]').textContent.trim(),
        precio: document.querySelector('[data-ficha-precio]').textContent.trim(),
        presentacion: document.querySelector('[data-ficha-presentacion]').textContent.trim(),
        foto: document.querySelector('[data-ficha-imagen]').getAttribute('src'),
        relacionados: document.querySelectorAll('[data-ficha-relacionados] .pieza').length,
    };
`)));

await capturar(ficha, '09-ficha-cafe', 'main');

// Un producto con precio anterior muestra el ahorro.
await cdp('Page.navigate', { url: BASE + '/producto.html?p=filete' }, ficha);
await esperar(1800);
anota('  ficha de «filete» (oferta): ' + JSON.stringify(await evaluar(ficha, `
    return {
        nombre: document.querySelector('[data-ficha-nombre]').textContent.trim(),
        antes: document.querySelector('[data-ficha-precio-antes]').textContent.trim(),
        insignia: document.querySelector('[data-ficha-insignia]').textContent.trim(),
        boton: document.querySelector('[data-ficha-agregar]').getAttribute('data-agregar'),
    };
`)));

/* -------------------------------------------------- Canasta (canasta.html) */

const canasta = await abrir(1440, 1000, 'canasta.html');

anota('== Canasta · 1440 px ==');
anota('  canasta vacía: ' + JSON.stringify(await evaluar(canasta, `
    return {
        vacia: ! document.querySelector('[data-canasta-vacia]').hasAttribute('hidden'),
        pie: document.querySelector('[data-canasta-pie]').hasAttribute('hidden') ? 'oculto' : 'visible',
    };
`)));
await capturar(canasta, '10-canasta-vacia', 'main');

// Se llena la canasta como lo haría el visitante: desde el catálogo.
await evaluar(canasta, `
    localStorage.setItem('supermercado-online-verdia-canasta', JSON.stringify([
        { slug: 'huevos', cantidad: 2 },
        { slug: 'pan', cantidad: 1 },
        { slug: 'filete', cantidad: 1 },
    ]));

    return true;
`);
await cdp('Page.navigate', { url: BASE + '/canasta.html' }, canasta);
await esperar(1800);

anota('  canasta con tres productos: ' + JSON.stringify(await evaluar(canasta, `
    return {
        lineas: document.querySelectorAll('[data-canasta-lineas] tr').length,
        unidades: document.querySelector('[data-total-unidades]').textContent.trim(),
        subtotal: document.querySelector('[data-total-subtotal]').textContent.trim(),
        total: document.querySelector('[data-total-total]').textContent.trim(),
        cuenta: document.querySelector('[data-canasta-cuenta]').textContent.trim(),
    };
`)));

// Se cambia una cantidad con el botón «+».
await clic(canasta, '[data-cantidad="mas"][data-slug="pan"]');
await esperar(400);
anota('  tras subir el pan a 2: ' + JSON.stringify(await evaluar(canasta, `
    return {
        unidades: document.querySelector('[data-total-unidades]').textContent.trim(),
        subtotal: document.querySelector('[data-total-subtotal]').textContent.trim(),
    };
`)));

await capturar(canasta, '11-canasta-llena', 'main');

// Pedido de prueba: sin datos, avisa; con datos, arma el recibo.
await clic(canasta, '[data-pedido] button[type="submit"]');
await esperar(300);
anota('  pedido sin datos: ' + JSON.stringify(await evaluar(canasta, `
    return {
        error: document.querySelector('[data-pedido-error]').hasAttribute('hidden') ? null : document.querySelector('[data-pedido-error]').textContent.trim().slice(0, 80),
        recibo: document.querySelector('[data-pedido-recibo]').hasAttribute('hidden') ? 'oculto' : 'visible',
    };
`)));

await evaluar(canasta, `
    document.querySelector('#pedido-nombre').value = 'Cliente de prueba';
    document.querySelector('#pedido-telefono').value = '999000000';
    document.querySelector('#pedido-entrega').value = 'Recojo en tienda';

    return true;
`);
await clic(canasta, '[data-pedido] button[type="submit"]');
await esperar(700);

anota('  pedido de prueba: ' + JSON.stringify(await evaluar(canasta, `
    const recibo = document.querySelector('[data-pedido-recibo]');

    return {
        visible: ! recibo.hasAttribute('hidden'),
        aviso: /no se envió ningún pedido/i.test(recibo.textContent),
        total: (recibo.querySelector('.recibo__total') || {}).textContent,
    };
`)));

await capturar(canasta, '12-recibo', '[data-pedido-recibo]');

/* --------------------------------------------------------------- Móvil */

const movil = await abrir(390, 844, 'index.html');

anota('== Escaparate · 390 px (móvil) ==');
await desborde(movil, 390);

anota('  áreas táctiles: ' + JSON.stringify(await evaluar(movil, `
    const lado = (selector) => {
        const e = document.querySelector(selector);

        return e && getComputedStyle(e).display !== 'none'
            ? [Math.round(e.getBoundingClientRect().width), Math.round(e.getBoundingClientRect().height)]
            : null;
    };

    return {
        punto: lado('.carrusel__punto'),
        interruptor: lado('.tema-switch'),
        menu: lado('.menu-movil'),
        canasta: lado('[data-canasta-abrir]'),
        pasillosVisibles: getComputedStyle(document.querySelector('.pasillos')).display,
    };
`)));

await capturar(movil, '13-movil-portada', '.portada');
await capturar(movil, '14-movil-pasillos', '#pasillos');

// El menú de pasillos se despliega en móvil.
await clic(movil, '[data-menu-boton]');
await esperar(400);
anota('  menú móvil: ' + JSON.stringify(await evaluar(movil, `
    return {
        clase: document.querySelector('.pasillos').className,
        visible: getComputedStyle(document.querySelector('.pasillos')).display,
        expandido: document.querySelector('[data-menu-boton]').getAttribute('aria-expanded'),
    };
`)));

await capturar(movil, '15-movil-menu', '.cabecera');

/* ------------------------------------------- Enlaces internos de la demo */

const rutas = ['index.html', 'tienda.html', 'producto.html?p=naranjas', 'canasta.html'];
const enlaces = new Set();

for (const ruta of rutas) {
    const respuesta = await fetch(BASE + '/' + ruta);
    const html = await respuesta.text();

    for (const coincidencia of html.matchAll(/href="([^"#:]+\.html(?:\?[^"]*)?)"/g)) {
        enlaces.add(coincidencia[1]);
    }
}

const rotos = [];

for (const enlace of enlaces) {
    const respuesta = await fetch(BASE + '/' + enlace, { method: 'GET' });

    if (! respuesta.ok) {
        rotos.push(`${enlace} → ${respuesta.status}`);
    }
}

anota(`== Enlaces internos ==\n  ${enlaces.size} enlaces revisados · rotos: ${rotos.length ? rotos.join(', ') : 'ninguno'}`);

/* ------------------------------------------------------------- Resultado */

if (problemas.length) {
    anota(`\nErrores de consola: ${problemas.length}`);
    problemas.slice(0, 10).forEach((p) => anota('  - ' + p.slice(0, 200)));
} else {
    anota('\nSin errores de consola ni peticiones fallidas.');
}

await writeFile(`${SALIDA}informe.txt`, informe.join('\n'));

chrome.kill();
ws.close();

console.log(`\nCapturas e informe en ${SALIDA}`);
process.exit(problemas.length ? 1 : 0);
