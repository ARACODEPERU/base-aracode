/*
 * Revisión de la demostración «Portafolio Creativo» contra Chrome real.
 *
 * El panel de vista previa del editor no deja fijar el ancho de la ventana (las
 * media queries se evalúan contra la ventana, no contra un viewport emulado), así
 * que la comprobación a 1440 y a 390 se hace aquí: se abre la demo en Chrome sin
 * interfaz y se habla con él por el protocolo de DevTools, que sí permite fijar
 * las medidas del dispositivo, mover el ratón y capturar por sección.
 *
 * No usa ninguna librería: el WebSocket es el de Node y el protocolo se habla a
 * mano. (Puppeteer figura en package.json, pero no está instalado en este
 * repositorio; instalar dependencias para una revisión no vale la pena.)
 *
 * Uso: node storage/tmp/revisar-demo-portafolio.mjs [url]
 * Salida: storage/tmp/repaso/pup-*.png y storage/tmp/repaso/informe-pup.txt
 */

import { spawn } from 'node:child_process';
import { mkdir, writeFile } from 'node:fs/promises';

const URL_DEMO = process.argv[2] ?? 'http://127.0.0.1:8123/demos/portafolio-creativo/';
const SALIDA = new URL('./repaso/', import.meta.url).pathname.replace(/^\//, '');
const CHROME = 'C:/Program Files/Google/Chrome/Application/chrome.exe';
const PUERTO = 9333;

await mkdir(SALIDA, { recursive: true });

const chrome = spawn(CHROME, [
    '--headless=new',
    `--remote-debugging-port=${PUERTO}`,
    '--user-data-dir=' + SALIDA.replace(/\//g, '\\') + 'perfil-cdp',
    '--hide-scrollbars',
    '--no-first-run',
    '--force-device-scale-factor=1',
    'about:blank',
], { stdio: 'ignore' });

const esperar = (ms) => new Promise((r) => setTimeout(r, ms));

/** URL del WebSocket del navegador, en cuanto Chrome lo publique. */
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

/** Envía un comando del protocolo y espera su respuesta. */
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

/* Una pestaña por medida, con el ancho fijado de verdad por el protocolo. */
async function abrir(ancho, alto) {
    const { targetId } = await cdp('Target.createTarget', { url: 'about:blank' });
    const { sessionId } = await cdp('Target.attachToTarget', { targetId, flatten: true });

    await cdp('Page.enable', {}, sessionId);
    await cdp('Runtime.enable', {}, sessionId);
    await cdp('Emulation.setDeviceMetricsOverride', {
        width: ancho, height: alto, deviceScaleFactor: 1, mobile: ancho < 600,
    }, sessionId);

    await cdp('Page.navigate', { url: URL_DEMO }, sessionId);
    await esperar(2200);

    // Cada pestaña arranca en claro: la elección de tema se guarda en
    // localStorage y si no se limpia, la segunda medida heredaría el tema que
    // dejó la primera y las capturas saldrían cambiadas.
    await evaluar(sessionId, `
        try { localStorage.removeItem('portafolio-creativo-tema'); } catch (error) {}
        document.documentElement.setAttribute('data-tema', 'claro');
        document.querySelectorAll('[data-tema-switch]').forEach((b) => {
            b.setAttribute('aria-pressed', 'false');
            b.setAttribute('aria-label', 'Cambiar a modo oscuro');
        });
    `);

    // La aparición al hacer scroll es una transición: se muestra todo antes de medir.
    await evaluar(sessionId, `document.querySelectorAll('.revelar').forEach((b) => b.classList.add('visible'))`);
    await esperar(600);

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

async function capturar(sessionId, selector, archivo) {
    const caja = await evaluar(sessionId, `
        const e = document.querySelector(${JSON.stringify(selector)});

        if (!e) { return null; }

        const r = e.getBoundingClientRect();

        return { x: r.x + scrollX, y: r.y + scrollY, width: r.width, height: r.height };
    `);

    if (!caja) {
        anota(`  (no se encontró ${selector})`);

        return;
    }

    const { data } = await cdp('Page.captureScreenshot', {
        format: 'png',
        captureBeyondViewport: true,
        clip: { ...caja, scale: 1 },
    }, sessionId);

    await writeFile(`${SALIDA}${archivo}.png`, Buffer.from(data, 'base64'));
}

async function medir(sessionId, ancho) {
    const datos = await evaluar(sessionId, `
        const raiz = document.documentElement;
        const salidos = [...document.querySelectorAll('body *')].filter((e) => {
            const r = e.getBoundingClientRect();
            const estilo = getComputedStyle(e);

            return estilo.position !== 'fixed'
                && estilo.overflowX !== 'auto'
                && r.width > 0
                && Math.round(r.right) > ${ancho} + 1;
        }).slice(0, 8).map((e) => e.tagName.toLowerCase() + '.' + String(e.className).split(' ')[0]);

        const punto = document.querySelector('.carrusel__punto');
        const interruptor = document.querySelector('.tema-switch');
        const tabla = document.querySelector('.tabla-scroll');
        const mapa = document.querySelector('.ubicacion__plano img');
        const h1 = document.querySelector('h1');

        return {
            desborde: raiz.scrollWidth - raiz.clientWidth,
            salidos,
            punto: punto ? Math.round(punto.getBoundingClientRect().width) : null,
            interruptor: interruptor ? Math.round(interruptor.getBoundingClientRect().width) : null,
            tablaDesplaza: tabla ? tabla.scrollWidth > tabla.clientWidth + 1 : null,
            /* Un SVG sin medidas propias —el plano las lleva el propio img, no el
               archivo— informa naturalWidth 0 aunque se vea: se mide su caja. */
            mapaCargado: mapa ? mapa.getBoundingClientRect().height > 40 : false,
            alto: document.body.scrollHeight,
            h1Alto: h1 ? Math.round(h1.getBoundingClientRect().height) : null,
            portadaAlto: Math.round(document.querySelector('.portada').getBoundingClientRect().height),
        };
    `);

    anota(`  desborde lateral: ${datos.desborde} px · elementos fuera de la ventana: ${datos.salidos.length ? datos.salidos.join(', ') : 'ninguno'}`);
    anota(`  área táctil: punto del slider ${datos.punto} px · interruptor de tema ${datos.interruptor} px`);
    anota(`  tabla con desplazamiento propio: ${datos.tablaDesplaza ? 'sí' : 'no'} · plano cargado: ${datos.mapaCargado ? 'sí' : 'no'}`);
    anota(`  alto: portada ${datos.portadaAlto} px · página ${datos.alto} px (h1 en ${datos.h1Alto} px de alto)`);
}

/* ---------------------------------------------------------- Escritorio */

for (const ancho of [1440, 1024]) {
    const sesion = await abrir(ancho, 900);

    anota(`\n== ${ancho} px (tema claro) ==`);
    await medir(sesion, ancho);

    await capturar(sesion, '.portada', `pup-${ancho}-portada`);
    await capturar(sesion, '#proyectos', `pup-${ancho}-proyectos`);
    await capturar(sesion, '#planes', `pup-${ancho}-planes`);
    await capturar(sesion, '#visitanos', `pup-${ancho}-visitanos`);

    if (ancho >= 1024) {
        // El previo del índice: la pieza aparece al pasar el ratón por la fila.
        const centro = await evaluar(sesion, `
            const fila = document.querySelector('.indice__fila');
            fila.scrollIntoView({ block: 'center' });

            const r = fila.getBoundingClientRect();

            return { x: Math.round(r.x + r.width * 0.25), y: Math.round(r.y + 30) };
        `);

        await cdp('Input.dispatchMouseEvent', { type: 'mouseMoved', x: centro.x, y: centro.y }, sesion);
        await esperar(700);

        const pieza = await evaluar(sesion, `
            const p = document.querySelector('.indice__fila .indice__pieza');
            const r = p.getBoundingClientRect();

            return { opacidad: getComputedStyle(p).opacity, ancho: Math.round(r.width), alto: Math.round(r.height) };
        `);

        anota(`  previo del índice al pasar el ratón: pieza ${pieza.ancho}×${pieza.alto} px, opacidad ${pieza.opacidad}`);

        // Dos capturas: la fila sola y la ventana entera. La de la fila recorta con
        // `clip`, que vuelve a componer la página y a veces pierde el estado del
        // ratón; la de la ventana es la que menos intermediarios tiene.
        await capturar(sesion, '.indice__fila', `pup-${ancho}-indice-hover`);

        const { data: ventana } = await cdp('Page.captureScreenshot', { format: 'png' }, sesion);

        await writeFile(`${SALIDA}pup-${ancho}-indice-hover-ventana.png`, Buffer.from(ventana, 'base64'));

        const cargada = await evaluar(sesion, `
            const img = document.querySelector('.indice__fila .indice__pieza img');
            const r = img.getBoundingClientRect();

            return { completa: img.complete, anchoNatural: img.naturalWidth, caja: [Math.round(r.x), Math.round(r.y), Math.round(r.width), Math.round(r.height)] };
        `);

        anota(`  la foto del previo: cargada ${cargada.completa} (natural ${cargada.anchoNatural} px) en la caja ${cargada.caja.join(', ')}`);
    }

    // Interruptor de tema.
    await evaluar(sesion, `document.querySelector('[data-tema-switch]').click()`);
    await esperar(500);

    const tema = await evaluar(sesion, `
        return {
            tema: document.documentElement.dataset.tema,
            guardado: localStorage.getItem('portafolio-creativo-tema'),
            etiqueta: document.querySelector('[data-tema-switch]').getAttribute('aria-label'),
            papel: getComputedStyle(document.body).backgroundColor,
            titulo: getComputedStyle(document.querySelector('h2')).color,
            cuerpo: getComputedStyle(document.querySelector('.seccion__intro')).color,
            filtroMapa: getComputedStyle(document.querySelector('.ubicacion__plano img')).filter,
        };
    `);

    anota(`\n== ${ancho} px (tema oscuro) ==`);
    anota(`  data-tema: ${tema.tema} · guardado: ${tema.guardado} · botón: «${tema.etiqueta}»`);
    anota(`  papel ${tema.papel} · titulares ${tema.titulo} · cuerpo ${tema.cuerpo}`);
    anota(`  filtro del plano: ${tema.filtroMapa}`);

    await capturar(sesion, '.portada', `pup-${ancho}-portada-oscuro`);
    await capturar(sesion, '#visitanos', `pup-${ancho}-visitanos-oscuro`);
    await capturar(sesion, '#servicios', `pup-${ancho}-servicios-oscuro`);
}

/* -------------------------------------------------------------- Móvil */

const movil = await abrir(390, 844);

anota('\n== 390 px (tema claro) ==');
await medir(movil, 390);

anota('  menú de escritorio: ' + (await evaluar(movil, `return getComputedStyle(document.querySelector('.menu')).display === 'none'` ) ? 'oculto' : 'visible')
    + ' · botón de menú: ' + (await evaluar(movil, `return getComputedStyle(document.querySelector('.menu-boton')).display !== 'none'`) ? 'visible' : 'oculto'));

await capturar(movil, '.portada', 'pup-390-portada');
await capturar(movil, '#proyectos', 'pup-390-proyectos');
await capturar(movil, '#servicios', 'pup-390-servicios');
await capturar(movil, '#planes', 'pup-390-planes');
await capturar(movil, '#visitanos', 'pup-390-visitanos');
await capturar(movil, '#contacto', 'pup-390-contacto');

// El menú se abre.
await evaluar(movil, `document.querySelector('#menu-boton').click()`);
await esperar(300);
anota('  menú móvil abierto: ' + (await evaluar(movil, `return document.querySelector('.menu').classList.contains('menu--abierto')`) ? 'sí' : 'no')
    + ' · interruptor de tema visible dentro de la cabecera: '
    + (await evaluar(movil, `return getComputedStyle(document.querySelector('.tema-switch')).display !== 'none'`) ? 'sí' : 'no'));

// El formulario: primero vacío, después completo.
await evaluar(movil, `document.querySelector('#contacto').scrollIntoView(); document.querySelector('#formulario-encargo button[type="submit"]').click();`);
await esperar(400);
anota(`  formulario vacío: campos marcados = ${await evaluar(movil, `return document.querySelectorAll('.campo--error, .casilla--error').length`)}`
    + ` · aviso: «${await evaluar(movil, `return document.querySelector('#aviso-formulario').textContent.trim()`)}»`);

await evaluar(movil, `
    const formulario = document.querySelector('#formulario-encargo');

    formulario.nombre.value = 'Ana Quispe';
    formulario.correo.value = 'ana@ejemplo.com';
    formulario.tipo.value = 'Identidad y logotipo';
    formulario.presupuesto.value = 'S/ 1 000 a S/ 3 000';
    formulario.acepto.checked = true;
    formulario.querySelector('button[type="submit"]').click();
`);
await esperar(400);
anota(`  formulario completo: aviso: «${await evaluar(movil, `return document.querySelector('#aviso-formulario').textContent.trim()`)}»`);
await capturar(movil, '#contacto', 'pup-390-contacto-aviso');

// El filtro del índice.
await evaluar(movil, `document.querySelector('[data-filtro="mural"]').click()`);
await esperar(400);
anota(`  filtro «mural»: filas visibles = ${await evaluar(movil, `return [...document.querySelectorAll('.indice__fila')].filter((f) => !f.hidden).length`)}`);
await capturar(movil, '#proyectos', 'pup-390-proyectos-filtrado');

await writeFile(`${SALIDA}informe-pup.txt`, informe.join('\n') + '\n');

ws.close();
chrome.kill();

console.log(`\nInforme en ${SALIDA}informe-pup.txt`);
process.exit(0);
