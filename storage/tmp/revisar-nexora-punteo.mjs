/*
 * Punteo corto de la demo «NÉXORA MARKET», complementario a
 * storage/tmp/revisar-demo-nexora.mjs: ahí se revisa cada página por separado,
 * aquí se comprueba lo que solo se ve pasando de una a otra —que la elección de
 * tema viaje entre las cinco páginas— y que las anclas de la revista existan
 * (los enlaces de la portada apuntan a #guia-*, y un id mal escrito no lo
 * detecta el recorrido de enlaces, porque la página abre igual).
 *
 * Uso: node storage/tmp/revisar-nexora-punteo.mjs [url-base]
 */

import { spawn } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const BASE = (process.argv[2] ?? 'http://127.0.0.1:8132/demos/nexora-market/').replace(/\/$/, '');
const ORIGEN = new URL(BASE).origin;
const CHROME = 'C:/Program Files/Google/Chrome/Application/chrome.exe';
const PUERTO = 9356;
const PERFIL = fileURLToPath(new URL('./repaso-nexora/perfil-punteo', import.meta.url));

const chrome = spawn(CHROME, [
    '--headless=new',
    `--remote-debugging-port=${PUERTO}`,
    '--user-data-dir=' + PERFIL,
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
const problemas = [];

ws.onmessage = (mensaje) => {
    const datos = JSON.parse(mensaje.data);

    if (datos.method === 'Runtime.exceptionThrown') {
        problemas.push('EXCEPCIÓN: ' + (datos.params.exceptionDetails.exception?.description ?? datos.params.exceptionDetails.text));
    }

    if (datos.method === 'Runtime.consoleAPICalled' && datos.params.type === 'error') {
        problemas.push('console.error: ' + datos.params.args.map((a) => a.value ?? a.description).join(' '));
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

const fallos = [];
const ok = (condicion, texto, detalle = '') => {
    console.log(`  ${condicion ? 'ok  ' : 'FALLA'} ${texto}${detalle ? ' — ' + detalle : ''}`);

    if (! condicion) {
        fallos.push(texto);
    }
};

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
await cdp('Emulation.setDeviceMetricsOverride', { width: 1280, height: 900, deviceScaleFactor: 1, mobile: false }, sessionId);
await cdp('Storage.enable', {}, sessionId).catch(() => {});
await cdp('Storage.clearDataForOrigin', { origin: ORIGEN, storageTypes: 'local_storage' }, sessionId).catch(() => {});

console.log('== El tema viaja entre las cinco páginas ==');

await cdp('Page.navigate', { url: `${BASE}/index.html` }, sessionId);
await esperar(1400);

const encendido = await evaluar(sessionId, `
    const interruptor = document.querySelector('[data-tema-switch]');

    interruptor.click();

    return {
        tema: document.documentElement.getAttribute('data-tema'),
        aria: interruptor.getAttribute('aria-pressed'),
        guardado: localStorage.getItem('nexora-market-tema'),
    };
`);

ok(encendido.tema === 'oscuro' && encendido.aria === 'true' && encendido.guardado === 'oscuro',
    'index.html: el interruptor enciende el oscuro, lo anuncia y lo guarda', JSON.stringify(encendido));

for (const pagina of ['catalogo.html', 'producto.html', 'carrito.html', 'revista.html', 'index.html']) {
    await cdp('Page.navigate', { url: `${BASE}/${pagina}` }, sessionId);
    await esperar(1400);

    const estado = await evaluar(sessionId, `
        const interruptor = document.querySelector('[data-tema-switch]');

        return {
            tema: document.documentElement.getAttribute('data-tema'),
            aria: interruptor ? interruptor.getAttribute('aria-pressed') : null,
            guardado: localStorage.getItem('nexora-market-tema'),
        };
    `);

    ok(estado.tema === 'oscuro' && estado.aria === 'true', `${pagina}: abre en oscuro con el interruptor en consonancia`, JSON.stringify(estado));
}

await cdp('Page.navigate', { url: `${BASE}/revista.html` }, sessionId);
await esperar(1400);

const vuelta = await evaluar(sessionId, `
    document.querySelector('[data-tema-switch]').click();

    return {
        tema: document.documentElement.getAttribute('data-tema'),
        guardado: localStorage.getItem('nexora-market-tema'),
    };
`);

ok(vuelta.tema === 'claro' && vuelta.guardado === 'claro', 'volver a claro desde otra página también se guarda', JSON.stringify(vuelta));

console.log('== Anclas de la revista ==');

const anclas = await evaluar(sessionId, `
    return ['guia-tecnologia', 'guia-hogar', 'guia-estilo'].map((id) => ({
        id: id,
        existe: !! document.getElementById(id),
    }));
`);

anclas.forEach((ancla) => ok(ancla.existe, `revista.html: existe la ancla #${ancla.id}`));

console.log('== La canasta se ve en las cinco páginas ==');

for (const pagina of ['index.html', 'catalogo.html', 'producto.html', 'carrito.html', 'revista.html']) {
    await cdp('Page.navigate', { url: `${BASE}/${pagina}` }, sessionId);
    await esperar(1200);

    const puedeAbrir = await evaluar(sessionId, `
        const boton = document.querySelector('[data-canasta-abrir]');
        const panel = document.querySelector('[data-canasta-panel]');

        if (! boton || ! panel) {
            return { boton: !! boton, panel: !! panel };
        }

        boton.click();

        return { boton: true, panel: true };
    `);

    // Tres lecturas separadas: el panel se abre en el fotograma siguiente y el
    // cierre retira el `hidden` cuando termina la transición (300 ms).
    await esperar(500);

    const abierto = await evaluar(sessionId, `
        const panel = document.querySelector('[data-canasta-panel]');
        const fondo = document.querySelector('[data-panel-fondo]');

        return {
            panel: panel.classList.contains('esta-abierto') && ! panel.hidden,
            fondo: fondo ? ! fondo.hidden : null,
            foco: document.activeElement === document.querySelector('[data-canasta-cerrar]'),
        };
    `);

    await evaluar(sessionId, `document.querySelector('[data-canasta-cerrar]').click();`);

    await esperar(700);

    const cerrado = await evaluar(sessionId, `
        const panel = document.querySelector('[data-canasta-panel]');
        const fondo = document.querySelector('[data-panel-fondo]');

        return {
            panel: ! panel.classList.contains('esta-abierto') && panel.hidden,
            fondo: fondo ? fondo.hidden : null,
        };
    `);

    ok(puedeAbrir.boton && abierto.panel && abierto.fondo === true && abierto.foco && cerrado.panel && cerrado.fondo === true,
        `${pagina}: la canasta abre con su fondo, lleva el foco y cierra`,
        JSON.stringify({ ...abierto, cerrado: cerrado.panel }));
}

console.log('== La ficha de producto se arma desde la URL ==');

for (const [clave, relacionados] of [['auriculares-nexora-studio', 4], ['zapatillas-nexora-runner', 2]]) {
    await cdp('Page.navigate', { url: `${BASE}/producto.html?p=${clave}` }, sessionId);
    await esperar(1400);

    const ficha = await evaluar(sessionId, `
        const nombre = document.querySelector('[data-ficha-nombre]');
        const relacionados = document.querySelectorAll('[data-ficha-relacionados] .producto');
        const filas = document.querySelectorAll('[data-ficha-detalles] tr');
        const precio = document.querySelector('[data-ficha-precio]');

        return {
            nombre: nombre ? nombre.textContent.trim() : null,
            relacionados: relacionados.length,
            filas: filas.length,
            precio: precio ? precio.textContent.trim() : null,
            repetido: Array.prototype.some.call(relacionados, (tarjeta) => tarjeta.getAttribute('data-producto') === '${clave}'),
        };
    `);

    ok(ficha.nombre && /^[^\u0000]+$/.test(ficha.nombre) && ficha.relacionados === relacionados && ficha.filas >= 4 && ! ficha.repetido,
        `${clave}: ficha aparte, ${relacionados} relacionados sin repetirse y ${ficha.filas} filas de especificaciones`,
        JSON.stringify(ficha));
}

console.log('== Portátil y tableta: 1024 y 820 px ==');

for (const [ancho, alto] of [[1024, 800], [820, 1180]]) {
    await cdp('Emulation.setDeviceMetricsOverride', { width: ancho, height: alto, deviceScaleFactor: 1, mobile: false }, sessionId);

    for (const pagina of ['index.html', 'catalogo.html', 'producto.html', 'carrito.html', 'revista.html']) {
        await cdp('Page.navigate', { url: `${BASE}/${pagina}` }, sessionId);
        await esperar(1300);

        const medida = await evaluar(sessionId, `
            const raiz = document.documentElement;
            const rejilla = document.querySelector('[data-catalogo-rejilla], .rejilla-productos');
            const columnas = rejilla
                ? getComputedStyle(rejilla).gridTemplateColumns.split(' ').filter((p) => p !== '').length
                : null;
            // Solo se mira si algo es más ancho que la pantalla: un elemento
            // recortado por su contenedor en scroll miente sobre el desborde.
            let anchos = 0;

            Array.prototype.forEach.call(document.querySelectorAll('body *'), (nodo) => {
                if (nodo.getBoundingClientRect().width > raiz.clientWidth + 1) {
                    anchos++;
                }
            });

            return {
                desborde: raiz.scrollWidth - raiz.clientWidth,
                anchos: anchos,
                columnas: columnas,
            };
        `);

        ok(medida.desborde <= 1 && medida.anchos === 0,
            `${pagina} a ${ancho} px: sin desborde horizontal ni nada más ancho que la pantalla`,
            JSON.stringify(medida));
    }
}

await cdp('Emulation.setDeviceMetricsOverride', { width: 820, height: 1180, deviceScaleFactor: 1, mobile: false }, sessionId);
await cdp('Page.navigate', { url: `${BASE}/catalogo.html` }, sessionId);
await esperar(1300);

const rejilla = await evaluar(sessionId, `
    const rejilla = document.querySelector('[data-catalogo-rejilla]');

    return { columnas: getComputedStyle(rejilla).gridTemplateColumns.split(' ').filter((p) => p !== '').length };
`);

ok(rejilla.columnas >= 2 && rejilla.columnas <= 3, `el catálogo reparte sus columnas a 820 px`, JSON.stringify(rejilla));

console.log(`\n${fallos.length === 0 ? 'Sin fallos' : fallos.length + ' fallos'} · problemas de consola: ${problemas.length}`);

if (problemas.length) {
    console.log(problemas.join('\n'));
}

chrome.kill();

process.exit(fallos.length === 0 && problemas.length === 0 ? 0 : 1);
