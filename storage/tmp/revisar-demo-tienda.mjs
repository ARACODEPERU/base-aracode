/*
 * Revisión de la demostración «Tienda en línea» contra Chrome real.
 *
 * El panel de vista previa del editor no deja fijar el ancho de la ventana (las
 * media queries se evalúan contra la ventana, no contra un viewport emulado), así
 * que la comprobación se hace aquí: Chrome sin interfaz y el protocolo de
 * DevTools, que sí permite fijar el dispositivo, mover el ratón, capturar por
 * sección y emular preferencias.
 *
 * No usa ninguna librería: el WebSocket es el de Node y el protocolo se habla a
 * mano. Recorre las cuatro páginas del sitio (escaparate, catálogo, ficha y
 * canasta) y deja capturas en storage/tmp/repaso-tienda/.
 *
 * Uso: node storage/tmp/revisar-demo-tienda.mjs [url-base]
 */

import { spawn } from 'node:child_process';
import { mkdir, writeFile } from 'node:fs/promises';

const BASE = (process.argv[2] ?? 'http://127.0.0.1:8123/demos/tienda-online/').replace(/\/$/, '');
const SALIDA = new URL('./repaso-tienda/', import.meta.url).pathname.replace(/^\//, '');
const ORIGEN = new URL(BASE).origin;
const CHROME = 'C:/Program Files/Google/Chrome/Application/chrome.exe';
const PUERTO = 9340;

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
const consola = [];

ws.onmessage = (mensaje) => {
    const datos = JSON.parse(mensaje.data);

    if (datos.method === 'Runtime.exceptionThrown') {
        consola.push('EXCEPCIÓN: ' + (datos.params.exceptionDetails.exception?.description
            ?? datos.params.exceptionDetails.text));
    }

    if (datos.method === 'Runtime.consoleAPICalled' && datos.params.type === 'error') {
        consola.push('console.error: ' + datos.params.args.map((a) => a.value ?? a.description).join(' '));
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

/**
 * Abre una pestaña con las medidas pedidas y limpia el estado de la tienda.
 *
 * `movimiento` fija la preferencia `prefers-reduced-motion`: el sistema de esta
 * máquina la trae reducida, así que sin fijarla se mediría la variante de
 * movimiento reducido creyendo que es la normal.
 */
async function abrir(ancho, alto, ruta = 'index.html', movimiento = 'no-preference') {
    const { targetId } = await cdp('Target.createTarget', { url: 'about:blank' });
    const { sessionId } = await cdp('Target.attachToTarget', { targetId, flatten: true });

    await cdp('Page.enable', {}, sessionId);
    await cdp('Runtime.enable', {}, sessionId);
    await cdp('Network.enable', {}, sessionId);
    await cdp('Emulation.setDeviceMetricsOverride', {
        width: ancho, height: alto, deviceScaleFactor: 1, mobile: ancho < 600,
    }, sessionId);
    await cdp('Emulation.setEmulatedMedia', {
        features: [
            { name: 'prefers-reduced-motion', value: movimiento },
            { name: 'prefers-color-scheme', value: 'light' },
        ],
    }, sessionId);

    // El almacén se vacía ANTES de navegar: la tienda lo lee al cargar, así que
    // borrarlo después deja la canasta de la pasada anterior dentro de la página.
    await cdp('Storage.enable', {}, sessionId).catch(() => {});
    await cdp('Storage.clearDataForOrigin', {
        origin: ORIGEN, storageTypes: 'local_storage',
    }, sessionId).catch(() => {});

    await cdp('Page.navigate', { url: BASE + '/' + ruta }, sessionId);
    await esperar(2200);

    // Cada pestaña arranca en claro y con las secciones ya reveladas.
    await evaluar(sessionId, `
        document.documentElement.setAttribute('data-tema', 'claro');
        document.querySelectorAll('.revelar').forEach((b) => b.classList.add('revelar--visible'));

        return true;
    `);
    await esperar(300);

    return sessionId;
}

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

async function medir(sessionId, ancho) {
    const datos = await evaluar(sessionId, `
        const raiz = document.documentElement;
        const ancho = ${ancho};
        const salidos = [...document.querySelectorAll('body *')].filter((e) => {
            const r = e.getBoundingClientRect();
            const estilo = getComputedStyle(e);

            return estilo.position !== 'fixed'
                && estilo.overflowX !== 'auto'
                // Los cajones fuera de lienzo (canasta, menú) viven a un lado
                // de la ventana a propósito: no cuentan como desborde.
                && ! e.closest('[aria-hidden="true"]')
                && r.width > 0
                && Math.round(r.right) > ancho + 1;
        }).slice(0, 8).map((e) => e.tagName.toLowerCase() + '.' + String(e.className).split(' ')[0]);

        const lado = (selector) => {
            const e = document.querySelector(selector);

            return e && getComputedStyle(e).display !== 'none'
                ? [Math.round(e.getBoundingClientRect().width), Math.round(e.getBoundingClientRect().height)]
                : null;
        };

        return {
            desborde: raiz.scrollWidth - raiz.clientWidth,
            salidos,
            punto: lado('.carrusel__punto'),
            interruptor: lado('.tema-switch'),
            canasta: lado('.canasta-boton'),
            menuBoton: lado('.menu-boton'),
            whatsapp: lado('.whatsapp'),
            portada: document.querySelector('.portada')
                ? Math.round(document.querySelector('.portada').getBoundingClientRect().height)
                : null,
            controles: (() => {
                const e = document.querySelector('.portada__controles');

                if (! e || getComputedStyle(e).display === 'none') { return null; }

                const r = e.getBoundingClientRect();

                return { abajo: Math.round(r.bottom), dentroDeLaVentana: r.bottom <= innerHeight + 1 };
            })(),
            altoPagina: document.body.scrollHeight,
            altoVentana: window.innerHeight,
        };
    `);

    anota(`  desborde lateral: ${datos.desborde} px · fuera de la ventana: ${datos.salidos.length ? datos.salidos.join(', ') : 'ninguno'}`);
    anota(`  áreas táctiles: punto ${formato(datos.punto)} · interruptor ${formato(datos.interruptor)} · canasta ${formato(datos.canasta)} · menú ${formato(datos.menuBoton)} · WhatsApp ${formato(datos.whatsapp)}`);
    anota(`  portada: ${datos.portada} px de ${datos.altoVentana} de ventana · página ${datos.altoPagina} px`);
    anota(`  controles del slider: ${datos.controles
        ? `terminan en ${datos.controles.abajo} px (${datos.controles.dentroDeLaVentana ? 'se ven' : 'FUERA de la ventana'})`
        : 'ocultos'}`);

    return datos;
}

const formato = (lado) => (lado ? `${lado[0]}×${lado[1]} px` : 'oculto');

/* ---------------------------------------------- Escaparate (index.html) */

const escritorio = await abrir(1440, 900, 'index.html');

anota('== Escaparate · 1440 px (claro) ==');
await medir(escritorio, 1440);

anota('  portada: ' + JSON.stringify(await evaluar(escritorio, `
    const diapositivas = [...document.querySelectorAll('[data-diapositiva]')];

    return {
        diapositivas: diapositivas.length,
        puntos: document.querySelectorAll('[data-carrusel-punto]').length,
        activa: diapositivas.findIndex((d) => d.getAttribute('aria-hidden') === 'false'),
        fotos: diapositivas.map((d) => d.querySelector('img').getAttribute('src')),
        cinta: getComputedStyle(document.querySelector('.cinta__pista')).animationName,
    };
`)));

// El slider se mueve a mano con un clic real.
const flecha = await evaluar(escritorio, `
    const b = document.querySelector('[data-carrusel-siguiente]');
    const r = b.getBoundingClientRect();

    return { x: Math.round(r.x + r.width / 2), y: Math.round(r.y + r.height / 2) };
`);

await cdp('Input.dispatchMouseEvent', { type: 'mouseMoved', x: flecha.x, y: flecha.y }, escritorio);
await cdp('Input.dispatchMouseEvent', { type: 'mousePressed', x: flecha.x, y: flecha.y, button: 'left', clickCount: 1 }, escritorio);
await cdp('Input.dispatchMouseEvent', { type: 'mouseReleased', x: flecha.x, y: flecha.y, button: 'left', clickCount: 1 }, escritorio);
await esperar(900);

anota('  tras el clic en «siguiente»: ' + JSON.stringify(await evaluar(escritorio, `
    const diapositivas = [...document.querySelectorAll('[data-diapositiva]')];

    return {
        activa: diapositivas.findIndex((d) => d.getAttribute('aria-hidden') === 'false'),
        transform: document.querySelector('.portada__pista').style.transform,
    };
`)));

// Tema oscuro: el interruptor y el plano invertido.
await evaluar(escritorio, `document.querySelector('[data-tema-switch]').click(); return true;`);
await esperar(500);

anota('  tema oscuro: ' + JSON.stringify(await evaluar(escritorio, `
    return {
        tema: document.documentElement.dataset.tema,
        guardado: localStorage.getItem('tienda-online-tema'),
        etiqueta: document.querySelector('[data-tema-switch]').getAttribute('aria-label'),
        pulsado: document.querySelector('[data-tema-switch]').getAttribute('aria-pressed'),
        papel: getComputedStyle(document.body).backgroundColor,
        titulo: getComputedStyle(document.querySelector('h2')).color,
        filtroPlano: getComputedStyle(document.querySelector('.recojo__plano img')).filter,
    };
`)));

// La canasta se agrega desde una tarjeta y la cuenta de la cabecera sube.
await evaluar(escritorio, `
    document.querySelector('[data-agregar]').click();
    document.querySelector('[data-agregar]').click();

    return true;
`);
await esperar(400);

anota('  canasta desde una tarjeta: ' + JSON.stringify(await evaluar(escritorio, `
    return {
        cuenta: document.querySelector('[data-canasta-cuenta]').textContent,
        oculta: document.querySelector('[data-canasta-cuenta]').hidden,
        guardado: JSON.parse(localStorage.getItem('tienda-online-canasta') || '[]'),
    };
`)));

// El panel lateral: abrir, ver el total y cerrar.
await evaluar(escritorio, `document.querySelector('[data-canasta-abrir]').click(); return true;`);
await esperar(500);

anota('  panel de la canasta: ' + JSON.stringify(await evaluar(escritorio, `
    const panel = document.querySelector('#panel-canasta');

    return {
        abierto: panel.classList.contains('canasta-panel--abierto'),
        ariaOculto: panel.getAttribute('aria-hidden'),
        lineas: panel.querySelectorAll('.canasta-linea').length,
        resumen: panel.querySelector('.canasta-totales__total span:last-child')?.textContent,
    };
`)));

await capturar(escritorio, '.portada', 'portada-1440');
await capturar(escritorio, '#estudio', 'estudio-1440');
await capturar(escritorio, '#recojo', 'recojo-oscuro-1440');
await capturar(escritorio, '#panel-canasta', 'panel-canasta');

await cdp('Page.captureScreenshot', { format: 'png' }, escritorio).then(async ({ data }) => {
    await writeFile(`${SALIDA}escaparate-1440-oscuro.png`, Buffer.from(data, 'base64'));
});

/* ------------------------------------------------ Catálogo (tienda.html) */

for (const [ruta, titulo] of [
    ['tienda.html', 'catálogo completo'],
    ['tienda.html?categoria=cocina', 'filtro por cocina'],
    ['tienda.html?q=tabla', 'búsqueda «tabla»'],
    ['tienda.html?orden=precio', 'orden por precio'],
    ['tienda.html?hasta=100&categoria=deco', 'precio máximo + deco'],
]) {
    const sesion = await abrir(1440, 900, ruta);

    anota(`\n== Catálogo · ${titulo} (${ruta}) ==`);
    anota('  ' + JSON.stringify(await evaluar(sesion, `
        const visibles = [...document.querySelectorAll('[data-pieza]')].filter((p) => ! p.hidden);
        const activo = document.querySelector('.chip--activo');

        return {
            visibles: visibles.length,
            nombres: visibles.map((p) => p.getAttribute('data-nombre')),
            precioPrimero: visibles[0] ? Number(visibles[0].getAttribute('data-precio')) : null,
            resumen: document.querySelector('[data-resumen]').textContent.replace(/\\s+/g, ' ').trim(),
            chip: activo ? activo.textContent.trim() : null,
            vacio: document.querySelector('[data-vacio]').classList.contains('catalogo-vacio--visible'),
            orden: document.querySelector('[name="orden"]').value,
            categoria: document.querySelector('[name="categoria"]').value,
        };
    `)));

    await capturar(sesion, '[data-rejilla]', 'catalogo-' + titulo.replace(/[^a-z0-9]+/gi, '-').toLowerCase());
}

const catalogo = await abrir(1440, 900, 'tienda.html');

anota('\n== Catálogo · filtros en vivo ==');
anota('  antes de filtrar: ' + (await evaluar(catalogo, `return document.querySelectorAll('[data-pieza]:not([hidden])').length;`)) + ' piezas visibles');

await evaluar(catalogo, `
    const f = document.querySelector('[data-filtros]');
    f.categoria.value = 'textil';
    f.orden.value = 'precio';

    return true;
`);
await evaluar(catalogo, `document.querySelector('[data-filtros] button[type="submit"]').click(); return true;`);
await esperar(500);

anota('  tras filtrar por textil: ' + JSON.stringify(await evaluar(catalogo, `
    const visibles = [...document.querySelectorAll('[data-pieza]')].filter((p) => ! p.hidden);

    return {
        visibles: visibles.length,
        url: window.location.search,
        chipActivo: document.querySelector('.chip--activo')?.textContent.trim(),
    };
`)));

await capturar(catalogo, '[data-rejilla]', 'catalogo-1440');

/* --------------------------------------------------- Ficha (producto.html) */

const ficha = await abrir(1440, 900, 'producto.html?p=sabanas-lino');

anota('\n== Ficha · producto.html?p=sabanas-lino ==');
anota('  ' + JSON.stringify(await evaluar(ficha, `
    return {
        titulo: document.title,
        nombre: document.querySelector('[data-ficha-nombre]').textContent,
        categoria: document.querySelector('[data-ficha-categoria]').textContent,
        precio: document.querySelector('[data-ficha-precio]').textContent.replace(/\\s+/g, ' ').trim(),
        variantes: document.querySelectorAll('[data-variante-clave]').length,
        colores: [...document.querySelectorAll('[data-variante-clave]')].map((b) => b.textContent),
        medidas: document.querySelector('[data-ficha-medidas]').textContent,
        foto: document.querySelector('[data-galeria-principal]').getAttribute('src'),
        relacionados: document.querySelectorAll('[data-relacionadas] .pieza').length,
        migaja: document.querySelector('[data-ficha-migaja]').textContent,
    };
`)));

// Variante, cantidad y agregar: el nombre de la variante debe viajar a la canasta.
await evaluar(ficha, `
    document.querySelectorAll('[data-variante-clave]')[1].click();
    document.querySelector('[data-pieza-mas]').click();

    return true;
`);
await esperar(300);

anota('  tras elegir variante y subir la cantidad: ' + JSON.stringify(await evaluar(ficha, `
    return {
        elegida: document.querySelector('[data-variante-clave][aria-pressed="true"]').textContent,
        cantidad: document.querySelector('[data-pieza-cantidad]').value,
        dataCantidad: document.querySelector('[data-agregar]').getAttribute('data-cantidad'),
    };
`)));

await evaluar(ficha, `document.querySelector('[data-agregar]').click(); return true;`);
await esperar(600);

anota('  tras agregar: ' + JSON.stringify(await evaluar(ficha, `
    const linea = JSON.parse(localStorage.getItem('tienda-online-canasta') || '[]')[0];

    return {
        panelAbierto: document.querySelector('#panel-canasta').classList.contains('canasta-panel--abierto'),
        linea: linea,
        aviso: document.querySelector('#aviso-pieza')?.textContent,
        cuenta: document.querySelector('[data-canasta-cuenta]').textContent,
    };
`)));

await capturar(ficha, '.ficha__rejilla', 'ficha-1440');

/* ------------------------------------------------- Canasta (canasta.html) */

const canasta = await abrir(1440, 900, 'tienda.html');

// La canasta se llena desde la tienda, como lo haría una clienta: una taza con
// el botón de su tarjeta y recién ahí se abre la página del pedido. El almacén
// es el mismo porque la pestaña no cambia.
await evaluar(canasta, `document.querySelector('[data-agregar=\"taza-gres"]').click(); return true;`);
await esperar(500);
await cdp('Page.navigate', { url: BASE + '/canasta.html' }, canasta);
await esperar(2200);

anota('\n== Canasta · con una taza y entrega en Lima ==');
anota('  ' + JSON.stringify(await evaluar(canasta, `
    const lista = document.querySelector('[data-canasta-lista]');
    const totales = [...document.querySelectorAll('[data-canasta-totales] div')].map((d) => d.textContent.replace(/\\s+/g, ' ').trim());

    return {
        lineas: lista.querySelectorAll('.canasta-linea').length,
        vacia: document.querySelector('[data-canasta-vacia]').hidden,
        totales,
        recibo: document.querySelector('[data-recibo]').textContent.split('\\n')[0],
        distritoOculto: document.querySelector('#p-distrito').closest('.campo').hidden,
    };
`)));

// Entrega en Lima: aparece el envío y el umbral de envío gratis.
await evaluar(canasta, `
    const radio = document.querySelector('[name="entrega"][value="lima"]');
    radio.checked = true;
    radio.dispatchEvent(new Event('change', { bubbles: true }));

    return true;
`);
await esperar(400);

anota('  con delivery en Lima: ' + JSON.stringify(await evaluar(canasta, `
    const totales = [...document.querySelectorAll('[data-canasta-totales] div')].map((d) => d.textContent.replace(/\\s+/g, ' ').trim());

    return {
        totales,
        distritoVisible: ! document.querySelector('#p-distrito').closest('.campo').hidden,
        nota: document.querySelector('.canasta-totales .nota-precios')?.textContent.replace(/\\s+/g, ' ').trim() ?? null,
    };
`)));

// Subir la cantidad hasta pasar el umbral: el envío debe desaparecer.
await evaluar(canasta, `
    for (let i = 0; i < 3; i++) {
        document.querySelector('[data-canasta-mas]').click();
    }

    return true;
`);
await esperar(400);

anota('  pasando el umbral de envío gratis: ' + JSON.stringify(await evaluar(canasta, `
    const totales = [...document.querySelectorAll('[data-canasta-totales] div')].map((d) => d.textContent.replace(/\\s+/g, ' ').trim());

    return { totales, linea: JSON.parse(localStorage.getItem('tienda-online-canasta'))[0] };
`)));

// Formulario vacío y formulario completo.
await evaluar(canasta, `document.querySelector('[data-pedido] button[type="submit"]').click(); return true;`);
await esperar(400);

anota('  pedido sin datos: ' + JSON.stringify(await evaluar(canasta, `
    return {
        camposMarcados: document.querySelectorAll('.campo--error').length,
        casillaMarcada: document.querySelectorAll('.casilla--error').length,
        aviso: document.querySelector('#aviso-pedido').textContent.trim(),
        foco: document.activeElement.id,
    };
`)));

// Con recojo en el showroom no hay viaje: el distrito y la dirección no se piden.
await evaluar(canasta, `
    const radio = document.querySelector('[name="entrega"][value="recojo"]');

    radio.checked = true;
    radio.dispatchEvent(new Event('change', { bubbles: true }));
    document.querySelector('#p-distrito').value = '';
    document.querySelector('#p-direccion').value = '';
    document.querySelector('[data-pedido] button[type="submit"]').click();

    return true;
`);
await esperar(400);

anota('  pedido sin datos y con recojo: ' + JSON.stringify(await evaluar(canasta, `
    return {
        camposMarcados: document.querySelectorAll('.campo--error').length,
        distritoMarcado: document.querySelector('#p-distrito').closest('.campo').classList.contains('campo--error'),
        direccionMarcada: document.querySelector('#p-direccion').closest('.campo').classList.contains('campo--error'),
        aviso: document.querySelector('#aviso-pedido').textContent.replace(/\\s+/g, ' ').trim(),
    };
`)));

await evaluar(canasta, `
    const entrega = document.querySelector('[name="entrega"][value="lima"]');
    const f = document.querySelector('[data-pedido]');

    entrega.checked = true;
    entrega.dispatchEvent(new Event('change', { bubbles: true }));
    f.nombre.value = 'Ana Quispe';
    f.celular.value = '987654321';
    f.correo.value = 'ana@ejemplo.com';
    f.distrito.value = 'Barranco';
    f.direccion.value = 'Av. Grau 320, dpto. 4';
    f.notas.value = 'Es un regalo: si pueden envolverlo con papel, mejor.';
    f.acepto.checked = true;
    document.querySelector('[name="pago"][value="Yape"]').checked = true;
    f.querySelector('button[type="submit"]').click();

    return true;
`);
await esperar(500);

anota('  pedido completo: ' + JSON.stringify(await evaluar(canasta, `
    const recibo = document.querySelector('[data-recibo]').textContent;

    return {
        camposMarcados: document.querySelectorAll('.campo--error').length,
        aviso: document.querySelector('#aviso-pedido').textContent.replace(/\\s+/g, ' ').trim(),
        reciboPrimeras: recibo.split('\\n').slice(0, 2),
        reciboUltimas: recibo.split('\\n').slice(-3),
    };
`)));

await capturar(canasta, '.canasta-pagina', 'canasta-1440');

/* --------------------------------------------------------- Móvil 390 px */

const movilPortada = await abrir(390, 844, 'index.html');

anota('\n== Escaparate · 390 px ==');
await medir(movilPortada, 390);

anota('  bloques de la portada (dónde terminan): ' + JSON.stringify(await evaluar(movilPortada, `
    const puntos = {
        h1: 'h1', buscador: '.buscador', acciones: '.portada__acciones',
        garantias: '.portada__garantias', oferta: '.oferta', controles: '.portada__controles',
    };
    const salida = {};

    Object.keys(puntos).forEach((clave) => {
        const e = document.querySelector(puntos[clave]);

        salida[clave] = e && getComputedStyle(e).display !== 'none'
            ? Math.round(e.getBoundingClientRect().bottom)
            : null;
    });

    salida.ventana = innerHeight;

    return salida;
`)));

anota('  caja de la portada: ' + JSON.stringify(await evaluar(movilPortada, `
    const portada = document.querySelector('.portada');
    const caja = portada.getBoundingClientRect();
    const contenido = document.querySelector('.portada__contenido').getBoundingClientRect();
    const controles = document.querySelector('.portada__controles').getBoundingClientRect();

    return {
        portada: [Math.round(caja.top), Math.round(caja.bottom)],
        contenido: [Math.round(contenido.top), Math.round(contenido.bottom)],
        controles: [Math.round(controles.top), Math.round(controles.bottom)],
        recorta: getComputedStyle(portada).overflow,
        desbordado: portada.scrollHeight > portada.clientHeight + 1,
    };
`)));

// La pastilla tiene que poder usarse en el celular: se baja hasta ella y se
// avanza la foto, que es lo que pide la comprobación final del banco.
await evaluar(movilPortada, `document.querySelector('.portada__controles').scrollIntoView({ block: 'center' }); return true;`);
await esperar(300);
await evaluar(movilPortada, `document.querySelector('[data-carrusel-siguiente]').click(); return true;`);
await esperar(600);

anota('  foto tras avanzar a mano en el celular: ' + JSON.stringify(await evaluar(movilPortada, `
    const diapositivas = [...document.querySelectorAll('[data-diapositiva]')];

    return {
        activa: diapositivas.findIndex((d) => d.getAttribute('aria-hidden') === 'false'),
        transform: document.querySelector('.portada__pista').style.transform,
    };
`)));

const movil = await abrir(390, 844, 'tienda.html');

anota('\n== Catálogo · 390 px ==');
await medir(movil, 390);

anota('  menú: ' + JSON.stringify(await evaluar(movil, `
    return {
        menuOculto: getComputedStyle(document.querySelector('.menu')).display === 'none',
        botonVisible: getComputedStyle(document.querySelector('.menu-boton')).display !== 'none',
        aria: document.querySelector('.menu-boton').getAttribute('aria-expanded'),
    };
`)));

await evaluar(movil, `document.querySelector('#menu-boton').click(); return true;`);
await esperar(400);

anota('  menú abierto: ' + JSON.stringify(await evaluar(movil, `
    const menu = document.querySelector('.menu');

    return {
        abierto: menu.classList.contains('menu--abierto'),
        aria: document.querySelector('.menu-boton').getAttribute('aria-expanded'),
        interruptorDentro: getComputedStyle(document.querySelector('.tema-switch')).display !== 'none',
        alto: Math.round(menu.getBoundingClientRect().height),
    };
`)));

await capturar(movilPortada, '.portada', 'portada-390');
await capturar(movilPortada, '#recojo', 'recojo-390');
await capturar(movil, '[data-rejilla]', 'catalogo-390');

/* ------------------------------------------- Movimiento reducido */

// La misma página con `prefers-reduced-motion: reduce`: la cinta y el paso
// automático del slider se apagan, pero el sitio sigue entero.
const quieto = await abrir(1440, 900, 'index.html', 'reduce');

anota('\n== Escaparate · 1440 px con movimiento reducido ==');
anota('  ' + JSON.stringify(await evaluar(quieto, `
    const diapositivas = [...document.querySelectorAll('[data-diapositiva]')];

    return {
        reducido: matchMedia('(prefers-reduced-motion: reduce)').matches,
        cinta: getComputedStyle(document.querySelector('.cinta__pista')).animationName,
        pista: getComputedStyle(document.querySelector('.portada__pista')).transitionDuration,
        puntos: document.querySelectorAll('[data-carrusel-punto]').length,
        activa: diapositivas.findIndex((d) => d.getAttribute('aria-hidden') === 'false'),
    };
`)));

// Aun sin animación, el paso a mano tiene que funcionar.
await evaluar(quieto, `document.querySelector('[data-carrusel-siguiente]').click(); return true;`);
await esperar(400);

anota('  tras avanzar a mano: ' + JSON.stringify(await evaluar(quieto, `
    const diapositivas = [...document.querySelectorAll('[data-diapositiva]')];

    return { activa: diapositivas.findIndex((d) => d.getAttribute('aria-hidden') === 'false') };
`)));

/* ----------------------------------------------------- Peticiones y red */

const origenes = await evaluar(movil, `
    return [...new Set(performance.getEntriesByType('resource').map((r) => new URL(r.name).origin))];
`);

anota('\n== Peticiones de la página ==');
anota('  orígenes cargados: ' + JSON.stringify(origenes));
anota('  consola: ' + (consola.length ? consola.join(' | ') : '(sin mensajes ni excepciones)'));

await writeFile(`${SALIDA}informe.txt`, informe.join('\n') + '\n');

ws.close();
chrome.kill();

console.log(`\nInforme en ${SALIDA}informe.txt`);
process.exit(0);

/** Captura el recorte de un elemento (o la ventana si no se encuentra). */
async function capturar(sessionId, selector, archivo) {
    const caja = await evaluar(sessionId, `
        const e = document.querySelector(${JSON.stringify(selector)});

        if (! e) { return null; }

        const r = e.getBoundingClientRect();

        return { x: r.x + scrollX, y: r.y + scrollY, width: r.width, height: r.height };
    `);

    if (! caja || caja.error) {
        anota(`  (no se pudo capturar ${selector})`);

        return;
    }

    const { data } = await cdp('Page.captureScreenshot', {
        format: 'png',
        captureBeyondViewport: true,
        clip: { ...caja, scale: 1 },
    }, sessionId);

    await writeFile(`${SALIDA}${archivo}.png`, Buffer.from(data, 'base64'));
}
