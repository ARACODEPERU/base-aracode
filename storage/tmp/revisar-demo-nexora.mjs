/*
 * Revisión de la demostración «NÉXORA MARKET» contra Chrome real.
 *
 * El panel de vista previa de este entorno no compone fotogramas, así que la
 * comprobación se hace como en el resto del banco: Chrome sin interfaz y el
 * protocolo de DevTools, que sí permite fijar el dispositivo, hacer clic y
 * emular preferencias. No usa ninguna librería.
 *
 * Comprueba, en las cinco páginas: consola limpia, sin desborde horizontal a
 * 1440 y a 390 px, el carrusel, el interruptor de tema, el paso automático con
 * movimiento reducido, el buscador con sugerencias, los filtros y el orden del
 * catálogo, el estado vacío, los favoritos, la canasta (sumas incluidas), la
 * ficha de producto, la validación del formulario de pedido, el estado sin
 * JavaScript y que todos los enlaces internos lleven a algo.
 *
 * Uso: node storage/tmp/revisar-demo-nexora.mjs [url-base]
 */

import { spawn } from 'node:child_process';
import { mkdir, writeFile } from 'node:fs/promises';

const BASE = (process.argv[2] ?? 'http://127.0.0.1:8132/demos/nexora-market/').replace(/\/$/, '');
const SALIDA = new URL('./repaso-nexora/', import.meta.url).pathname.replace(/^\//, '');
const ORIGEN = new URL(BASE).origin;
const CHROME = 'C:/Program Files/Google/Chrome/Application/chrome.exe';
const PUERTO = 9355;

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
let capturando = true;

ws.onmessage = (mensaje) => {
    const datos = JSON.parse(mensaje.data);

    if (! capturando) {
        return;
    }

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
const fallos = [];

const anota = (linea) => {
    informe.push(linea);
    console.log(linea);
};

const comprobar = (condicion, descripcion, detalle = '') => {
    if (condicion) {
        anota(`  ok   ${descripcion}`);

        return true;
    }

    fallos.push(`${descripcion}${detalle ? ' — ' + detalle : ''}`);
    anota(`  FALLA ${descripcion}${detalle ? ' — ' + detalle : ''}`);

    return false;
};

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

async function capturar(sessionId, nombre) {
    const { data } = await cdp('Page.captureScreenshot', { format: 'png', captureBeyondViewport: false }, sessionId);
    await writeFile(`${SALIDA}${nombre}.png`, Buffer.from(data, 'base64'));
}

async function abrir(ancho, alto, ruta = 'index.html', movimiento = 'no-preference', limpiar = true) {
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

    if (limpiar) {
        await cdp('Storage.enable', {}, sessionId).catch(() => {});
        await cdp('Storage.clearDataForOrigin', { origin: ORIGEN, storageTypes: 'local_storage' }, sessionId).catch(() => {});
    }

    await cdp('Page.navigate', { url: BASE + '/' + ruta }, sessionId);
    await esperar(1800);

    return sessionId;
}

async function cerrar(sessionId) {
    const { targetInfo } = await cdp('Target.getTargetInfo', {}, sessionId).catch(() => ({ targetInfo: null }));

    if (targetInfo) {
        await cdp('Target.closeTarget', { targetId: targetInfo.targetId }).catch(() => {});
    }
}

/** Mide desborde horizontal y devuelve los elementos culpables. */
const medirDesborde = `
    const ancho = document.documentElement.clientWidth;
    const culpables = [];

    document.querySelectorAll('body *').forEach((nodo) => {
        const caja = nodo.getBoundingClientRect();

        if (caja.width > 0 && caja.right > ancho + 1.5) {
            culpables.push(nodo.tagName.toLowerCase() + (nodo.className && typeof nodo.className === 'string' ? '.' + nodo.className.split(' ').slice(0, 2).join('.') : ''));
        }
    });

    return {
        scroll: document.documentElement.scrollWidth,
        ancho: ancho,
        culpables: [...new Set(culpables)].slice(0, 6),
    };
`;

const paginas = ['index.html', 'catalogo.html', 'producto.html', 'carrito.html', 'revista.html'];

let sesion = await abrir(1440, 900, 'index.html');

anota('== 1. Las cinco páginas: consola y desborde ==');

for (const pagina of paginas) {
    await cdp('Page.navigate', { url: BASE + '/' + pagina }, sesion);
    await esperar(1500);

    const titulo = await evaluar(sesion, 'return document.title;');
    const desborde = await evaluar(sesion, medirDesborde);

    comprobar(typeof titulo === 'string' && titulo.includes('NÉXORA'), `${pagina}: carga con su título`, String(titulo));
    comprobar(desborde.scroll <= desborde.ancho + 1, `${pagina}: sin desborde horizontal a 1440 px`, JSON.stringify(desborde));
}

anota('== 2. Portada: carrusel, tema y aparición ==');

await cdp('Page.navigate', { url: BASE + '/index.html' }, sesion);
await esperar(1800);

const portada = await evaluar(sesion, `
    return {
        diapositivas: document.querySelectorAll('[data-diapositiva]').length,
        activa: document.querySelectorAll('.portada__diapositiva.esta-activa').length,
        productoDestacado: !! document.querySelector('[data-escaparate] .producto'),
        rubros: document.querySelectorAll('[data-rubros] .rubro').length,
        marcas: document.querySelectorAll('[data-marcas] .marca-aliada').length,
        ofertas: document.querySelectorAll('[data-ofertas] .producto').length,
        controlesVisibles: getComputedStyle(document.querySelector('.portada__controles')).display,
        imagenesRotas: [...document.images].filter((i) => i.complete && i.naturalWidth === 0).map((i) => i.currentSrc || i.src),
    };
`);

comprobar(portada.diapositivas === 3, 'la portada tiene tres fotos', String(portada.diapositivas));
comprobar(portada.activa === 1, 'solo una diapositiva está activa', String(portada.activa));
comprobar(portada.controlesVisibles === 'flex', 'los controles del carrusel se ven con JavaScript', portada.controlesVisibles);
comprobar(portada.rubros === 6, 'se pintan las seis categorías', String(portada.rubros));
comprobar(portada.marcas === 6, 'se pintan las seis marcas ficticias', String(portada.marcas));
comprobar(portada.productoDestacado, 'el escaparate pinta productos');
comprobar(portada.ofertas === 4, 'las ofertas del día pintan cuatro productos', String(portada.ofertas));
comprobar(portada.imagenesRotas.length === 0, 'ninguna imagen rota en la portada', portada.imagenesRotas.join(', '));

// Carrusel: la flecha cambia de diapositiva.
await evaluar(sesion, `document.querySelector('[data-carrusel-siguiente]').click(); return true;`);
await esperar(600);

const trasClic = await evaluar(sesion, `
    return {
        indice: [...document.querySelectorAll('[data-carrusel-punto]')].findIndex((p) => p.getAttribute('aria-current') === 'true'),
        activas: document.querySelectorAll('.portada__diapositiva.esta-activa').length,
    };
`);

comprobar(trasClic.indice === 1 && trasClic.activas === 1, 'la flecha avanza a la segunda foto', JSON.stringify(trasClic));

// Tema: interruptor y persistencia.
await evaluar(sesion, `document.querySelector('[data-tema-switch]').click(); return true;`);
await esperar(300);

const enOscuro = await evaluar(sesion, `
    const fondo = getComputedStyle(document.querySelector('.pie')).backgroundColor;

    return {
        tema: document.documentElement.getAttribute('data-tema'),
        guardado: localStorage.getItem('nexora-market-tema'),
        aria: document.querySelector('[data-tema-switch]').getAttribute('aria-pressed'),
        sol: getComputedStyle(document.querySelector('.tema-switch__icono--sol')).opacity,
        luna: getComputedStyle(document.querySelector('.tema-switch__icono--luna')).opacity,
        fondo: fondo,
    };
`);

comprobar(enOscuro.tema === 'oscuro' && enOscuro.guardado === 'oscuro', 'el interruptor pasa a oscuro y lo guarda', JSON.stringify(enOscuro));
comprobar(enOscuro.aria === 'true' && enOscuro.sol === '1' && enOscuro.luna === '0', 'el icono cambia al sol con aria-pressed', JSON.stringify(enOscuro));

await capturar(sesion, 'portada-oscuro-1440');

await cdp('Page.navigate', { url: BASE + '/index.html' }, sesion);
await esperar(1400);

const recarga = await evaluar(sesion, `
    return {
        tema: document.documentElement.getAttribute('data-tema'),
        colorScheme: getComputedStyle(document.documentElement).colorScheme,
    };
`);

comprobar(recarga.tema === 'oscuro', 'al recargar se mantiene el modo oscuro', JSON.stringify(recarga));
comprobar(String(recarga.colorScheme).includes('dark'), 'color-scheme acompaña al modo oscuro', recarga.colorScheme);

await evaluar(sesion, `document.querySelector('[data-tema-switch]').click(); return true;`);
await esperar(200);
await capturar(sesion, 'portada-claro-1440');

anota('== 3. Buscador de la cabecera ==');

const sugerencias = await evaluar(sesion, `
    const campo = document.querySelector('#buscador-cabecera');
    campo.value = 'audio';
    campo.dispatchEvent(new Event('input', { bubbles: true }));

    return {
        visibles: ! document.querySelector('[data-sugerencias]').hidden,
        cuantas: document.querySelectorAll('[data-sugerencias] .sugerencia').length,
    };
`);

comprobar(sugerencias.visibles && sugerencias.cuantas > 0, 'el buscador sugiere productos al escribir', JSON.stringify(sugerencias));

const sinCoincidencias = await evaluar(sesion, `
    const campo = document.querySelector('#buscador-cabecera');
    campo.value = 'zzzz';
    campo.dispatchEvent(new Event('input', { bubbles: true }));

    return document.querySelector('[data-sugerencias]').textContent;
`);

comprobar(String(sinCoincidencias).includes('Sin coincidencias'), 'el buscador avisa cuando no hay coincidencias', String(sinCoincidencias).slice(0, 60));

anota('== 4. Catálogo: filtros, orden y estado vacío ==');

await cdp('Page.navigate', { url: BASE + '/catalogo.html' }, sesion);
await esperar(1600);

const total = await evaluar(sesion, `return document.querySelectorAll('[data-catalogo-rejilla] .producto').length;`);

comprobar(total === 27, 'el catálogo pinta los 27 productos', String(total));

await evaluar(sesion, `document.querySelector('[data-filtro-categoria="tecnologia"]').click(); return true;`);
await esperar(1500);

const tecnologia = await evaluar(sesion, `
    return {
        cuantos: document.querySelectorAll('[data-catalogo-rejilla] .producto').length,
        titulo: document.querySelector('[data-catalogo-titulo]').textContent,
        categoria: new URLSearchParams(location.search).get('categoria'),
        chip: document.querySelector('[data-filtro-categoria="tecnologia"]').classList.contains('esta-activo'),
    };
`);

comprobar(tecnologia.cuantos === 9 && tecnologia.categoria === 'tecnologia', 'filtrar por Tecnología deja 9 productos', JSON.stringify(tecnologia));
comprobar(tecnologia.chip && tecnologia.titulo === 'Tecnología', 'el chip y el título acompañan al filtro', JSON.stringify(tecnologia));

await cdp('Page.navigate', { url: BASE + '/catalogo.html?oferta=1' }, sesion);
await esperar(1600);

const ofertas = await evaluar(sesion, `return document.querySelectorAll('[data-catalogo-rejilla] .producto').length;`);

comprobar(ofertas === 9, 'el filtro de ofertas deja los 9 productos rebajados', String(ofertas));

await cdp('Page.navigate', { url: BASE + '/catalogo.html?categoria=tecnologia&coleccion=Audio' }, sesion);
await esperar(1600);

const coleccion = await evaluar(sesion, `
    return {
        cuantos: document.querySelectorAll('[data-catalogo-rejilla] .producto').length,
        titulo: document.querySelector('[data-catalogo-titulo]').textContent,
    };
`);

comprobar(coleccion.cuantos === 4 && coleccion.titulo === 'Audio', 'la colección Audio deja los 4 productos de audio', JSON.stringify(coleccion));

await cdp('Page.navigate', { url: BASE + '/catalogo.html?orden=precio-asc' }, sesion);
await esperar(1600);

const ordenado = await evaluar(sesion, `
    const precios = [...document.querySelectorAll('[data-catalogo-rejilla] .producto__precio strong')]
        .map((n) => parseFloat(n.textContent.replace(/[^0-9.]/g, '')));

    return { primero: precios[0], ultimo: precios[precios.length - 1], cuantos: precios.length };
`);

comprobar(ordenado.primero === 69.9 && ordenado.ultimo === 2890, 'el orden por precio ascendente es correcto', JSON.stringify(ordenado));

// Favoritos: marcar uno y filtrar por ellos.
await cdp('Page.navigate', { url: BASE + '/catalogo.html' }, sesion);
await esperar(1600);

await evaluar(sesion, `document.querySelector('[data-favorito]').click(); return true;`);
await esperar(300);

const favoritos = await evaluar(sesion, `
    return {
        cuenta: document.querySelector('[data-favoritos-cuenta]').textContent,
        activo: document.querySelector('[data-favorito]').classList.contains('esta-activo'),
        guardado: JSON.parse(localStorage.getItem('nexora-market-favoritos') || '[]'),
    };
`);

comprobar(favoritos.cuenta === '1' && favoritos.activo && favoritos.guardado.length === 1, 'marcar un favorito lo guarda y actualiza el contador', JSON.stringify(favoritos));

await cdp('Page.navigate', { url: BASE + '/catalogo.html?favoritos=1' }, sesion);
await esperar(1600);

const soloFavoritos = await evaluar(sesion, `
    return {
        cuantos: document.querySelectorAll('[data-catalogo-rejilla] .producto').length,
        titulo: document.querySelector('[data-catalogo-titulo]').textContent,
    };
`);

comprobar(soloFavoritos.cuantos === 1 && soloFavoritos.titulo === 'Mis favoritos', 'el filtro de favoritos deja solo lo marcado', JSON.stringify(soloFavoritos));

await evaluar(sesion, `document.querySelector('[data-favorito]').click(); return true;`);
await esperar(300);

const sinFavoritos = await evaluar(sesion, `
    return {
        cuenta: document.querySelector('[data-favoritos-cuenta]').textContent,
        aviso: ! document.querySelector('[data-catalogo-vacio]').hidden,
    };
`);

comprobar(sinFavoritos.cuenta === '0' && sinFavoritos.aviso, 'desmarcar el favorito deja el estado vacío', JSON.stringify(sinFavoritos));

await cdp('Page.navigate', { url: BASE + '/catalogo.html?q=zzzz' }, sesion);
await esperar(1600);

const vacio = await evaluar(sesion, `
    return {
        productos: document.querySelectorAll('[data-catalogo-rejilla] .producto').length,
        aviso: ! document.querySelector('[data-catalogo-vacio]').hidden,
    };
`);

comprobar(vacio.productos === 0 && vacio.aviso, 'la búsqueda sin coincidencias muestra el estado vacío', JSON.stringify(vacio));

const limpiarVisible = await evaluar(sesion, `return ! document.querySelector('[data-filtros-limpiar]').hidden;`);

comprobar(limpiarVisible, 'el botón de quitar filtros aparece cuando hay filtro');

anota('== 5. Ficha de producto ==');

const sesionFicha = await abrir(1440, 900, 'producto.html?p=reloj-nexora-fit-2');

const ficha = await evaluar(sesionFicha, `
    return {
        nombre: document.querySelector('[data-ficha-nombre]').textContent,
        precio: document.querySelector('[data-ficha-precio]').textContent,
        antes: ! document.querySelector('[data-ficha-antes]').hidden,
        especificaciones: document.querySelectorAll('[data-ficha-detalles] tr').length,
        relacionados: document.querySelectorAll('[data-ficha-relacionados] .producto').length,
        foto: document.querySelector('[data-ficha-foto]').getAttribute('src'),
        whatsapp: document.querySelector('[data-ficha-whatsapp]').href.includes('wa.me'),
        migaja: document.querySelector('[data-ficha-migaja]').textContent.replace(/\\s+/g, ' ').trim(),
    };
`);

comprobar(ficha.nombre.includes('NEXORA Fit 2'), 'la ficha trae el producto pedido', ficha.nombre);
comprobar(ficha.precio === 'S/ 429.00' && ficha.antes, 'el precio y el precio anterior salen de los datos', JSON.stringify(ficha));
comprobar(ficha.especificaciones === 4, 'la tabla de especificaciones tiene cuatro filas', String(ficha.especificaciones));
comprobar(ficha.relacionados === 4, 'la ficha ofrece cuatro productos relacionados', String(ficha.relacionados));
comprobar(ficha.foto === 'img/p-reloj.webp', 'la ficha usa la foto del producto', ficha.foto);
comprobar(ficha.whatsapp, 'el botón de WhatsApp tiene destino');
comprobar(ficha.migaja.includes('Tecnología'), 'la migaja muestra la categoría', ficha.migaja);

await capturar(sesionFicha, 'ficha-1440');

// Cantidad y añadido a la canasta.
await evaluar(sesionFicha, `
    document.querySelector('[data-ficha-mas]').click();
    document.querySelector('[data-ficha-anadir]').click();

    return true;
`);
await esperar(500);

const canastaTrasFicha = await evaluar(sesionFicha, `
    return {
        cuenta: document.querySelector('[data-canasta-cuenta]').textContent,
        guardado: JSON.parse(localStorage.getItem('nexora-market-canasta') || '[]'),
        aviso: document.querySelector('[data-aviso-canasta]').textContent,
    };
`);

comprobar(canastaTrasFicha.cuenta === '2', 'añadir dos unidades desde la ficha actualiza el contador', JSON.stringify(canastaTrasFicha));
comprobar(canastaTrasFicha.aviso.includes('NEXORA Fit 2'), 'el aviso de añadido nombra el producto', canastaTrasFicha.aviso);

await cerrar(sesionFicha);

anota('== 6. Canasta: sumas, cantidades y pedido simulado ==');

await cdp('Page.navigate', { url: BASE + '/carrito.html' }, sesion);
await esperar(1600);

const carrito = await evaluar(sesion, `
    return {
        lineas: document.querySelectorAll('[data-carrito-lineas] .carrito-linea').length,
        subtotal: document.querySelector('[data-resumen-subtotal]').textContent,
        total: document.querySelector('[data-resumen-total]').textContent,
        envio: document.querySelector('[data-resumen-envio]').textContent,
        unidades: document.querySelector('[data-resumen-unidades]').textContent,
        vacioOculto: document.querySelector('[data-carrito-vacio]').hidden,
    };
`);

comprobar(carrito.lineas === 1 && carrito.unidades === '2 artículos', 'la canasta conserva lo elegido en la ficha', JSON.stringify(carrito));
comprobar(carrito.subtotal === 'S/ 858.00' && carrito.total === 'S/ 858.00', 'el subtotal suma precio × cantidad', JSON.stringify(carrito));
comprobar(carrito.envio === 'Recojo en tienda' && carrito.vacioOculto, 'la entrega por defecto es el recojo', JSON.stringify(carrito));

// Una unidad más: 3 × 429 = 1287.
await evaluar(sesion, `document.querySelector('[data-cantidad-mas]').click(); return true;`);
await esperar(400);

const tres = await evaluar(sesion, `
    return {
        subtotal: document.querySelector('[data-resumen-subtotal]').textContent,
        total: document.querySelector('[data-resumen-total]').textContent,
        unidades: document.querySelector('[data-resumen-unidades]').textContent,
    };
`);

comprobar(tres.subtotal === 'S/ 1,287.00' || tres.subtotal === 'S/ 1287.00', 'sumar una unidad recalcula el subtotal', JSON.stringify(tres));

// Envío a domicilio: 1287 > 199, así que sigue sin costo.
await evaluar(sesion, `
    const radio = document.querySelector('[data-envio][value="envio"]');
    radio.checked = true;
    radio.dispatchEvent(new Event('change', { bubbles: true }));

    return true;
`);
await esperar(300);

const conEnvio = await evaluar(sesion, `return document.querySelector('[data-resumen-envio]').textContent;`);

comprobar(conEnvio === 'Gratis', 'el envío de ejemplo es gratis desde S/ 199', conEnvio);

// Formulario: vacío da errores; completo muestra el comprobante.
await evaluar(sesion, `document.querySelector('[data-pedido] button[type="submit"]').click(); return true;`);
await esperar(300);

const errores = await evaluar(sesion, `
    return {
        nombre: ! document.querySelector('[data-error-nombre]').hidden,
        correo: ! document.querySelector('[data-error-correo]').hidden,
        telefono: ! document.querySelector('[data-error-telefono]').hidden,
        comprobante: ! document.querySelector('[data-comprobante]').hidden,
        aria: document.querySelector('[data-campo-nombre]').getAttribute('aria-invalid'),
    };
`);

comprobar(errores.nombre && errores.correo && errores.telefono && ! errores.comprobante, 'el formulario vacío muestra los tres errores', JSON.stringify(errores));
comprobar(errores.aria === 'true', 'el campo en error se marca con aria-invalid');

await evaluar(sesion, `
    const poner = (selector, valor) => {
        const campo = document.querySelector(selector);
        campo.value = valor;
        campo.dispatchEvent(new Event('change', { bubbles: true }));
    };

    poner('[data-campo-nombre]', 'Ana Pérez');
    poner('[data-campo-correo]', 'ana@ejemplo.com');
    poner('[data-campo-telefono]', '999 111 222');
    document.querySelector('[data-pedido] button[type="submit"]').click();

    return true;
`);
await esperar(600);

const comprobante = await evaluar(sesion, `
    const nodo = document.querySelector('[data-comprobante]');

    return {
        visible: ! nodo.hidden,
        detalle: document.querySelector('[data-comprobante-detalle]').textContent,
        whatsapp: document.querySelector('[data-comprobante-whatsapp]').href.startsWith('https://wa.me/'),
        errores: [...document.querySelectorAll('.campo__error')].filter((e) => ! e.hidden).length,
    };
`);

comprobar(comprobante.visible && comprobante.errores === 0, 'el formulario completo muestra el comprobante', JSON.stringify({ visible: comprobante.visible, errores: comprobante.errores }));
comprobar(comprobante.detalle.includes('Total de demostración: S/ 1,287.00') || comprobante.detalle.includes('Total de demostración: S/ 1287.00'), 'el comprobante lleva el total', comprobante.detalle.split('\n').slice(-1)[0]);
comprobar(comprobante.detalle.includes('Ana Pérez') && comprobante.detalle.includes('3 × '), 'el comprobante lleva el cliente y las cantidades');
comprobar(comprobante.whatsapp, 'el comprobante arma el enlace de WhatsApp');

await capturar(sesion, 'carrito-1440');

// Vaciar: vuelve el estado vacío.
await evaluar(sesion, `document.querySelector('[data-canasta-vaciar]').click(); return true;`);
await esperar(500);

const vaciada = await evaluar(sesion, `
    return {
        aviso: ! document.querySelector('[data-carrito-vacio]').hidden,
        lineas: document.querySelectorAll('[data-carrito-lineas] .carrito-linea').length,
        cuenta: document.querySelector('[data-canasta-cuenta]').textContent,
        lleno: document.querySelector('[data-canasta-lleno]').hidden,
    };
`);

comprobar(vaciada.aviso && vaciada.lineas === 0 && vaciada.cuenta === '0' && vaciada.lleno, 'vaciar la canasta deja el estado vacío', JSON.stringify(vaciada));

anota('== 7. Movimiento reducido y sin JavaScript ==');

const sesionReducido = await abrir(1440, 900, 'index.html', 'reduce');
const reducido = await evaluar(sesionReducido, `
    return {
        inicio: [...document.querySelectorAll('[data-carrusel-punto]')].findIndex((p) => p.getAttribute('aria-current') === 'true'),
        aparece: [...document.querySelectorAll('[data-aparece]')].every((n) => n.classList.contains('esta-visible')),
    };
`);

await esperar(8500);

const reducidoDespues = await evaluar(sesionReducido, `
    const trasEspera = [...document.querySelectorAll('[data-carrusel-punto]')].findIndex((p) => p.getAttribute('aria-current') === 'true');

    document.querySelector('[data-carrusel-siguiente]').click();

    return {
        trasEspera: trasEspera,
        trasClic: [...document.querySelectorAll('[data-carrusel-punto]')].findIndex((p) => p.getAttribute('aria-current') === 'true'),
    };
`);

comprobar(reducido.aparece, 'con movimiento reducido el contenido aparece igual (sin animación)');
comprobar(reducidoDespues.trasEspera === reducido.inicio, 'con movimiento reducido el paso automático está apagado', JSON.stringify({ antes: reducido.inicio, despues: reducidoDespues.trasEspera }));
comprobar(reducidoDespues.trasClic !== reducidoDespues.trasEspera, 'con movimiento reducido el carrusel sigue funcionando a mano', JSON.stringify(reducidoDespues));

await cerrar(sesionReducido);

// Sin JavaScript: se devuelve la marca del documento a `no-js`, que es lo que
// encuentra el navegador que no ejecuta scripts, y se mide el CSS. La medición
// necesita la portada delante.
await cdp('Page.navigate', { url: BASE + '/index.html' }, sesion);
await esperar(1600);

const sinJs = await evaluar(sesion, `
    document.documentElement.classList.remove('js');
    document.documentElement.classList.add('no-js');

    const primeraFoto = document.querySelector('.portada__diapositiva');

    return {
        interruptor: getComputedStyle(document.querySelector('.tema-switch')).display,
        controles: getComputedStyle(document.querySelector('.portada__controles')).display,
        primeraFoto: getComputedStyle(primeraFoto).opacity,
        titular: document.querySelector('h1').textContent,
        secciones: document.querySelectorAll('main section').length,
    };
`);

comprobar(sinJs.interruptor === 'none', 'sin JavaScript el interruptor de tema no se muestra', sinJs.interruptor);
comprobar(sinJs.controles === 'none', 'sin JavaScript los controles del carrusel no se muestran', sinJs.controles);
comprobar(sinJs.primeraFoto === '1', 'sin JavaScript se ve la primera foto de la portada', sinJs.primeraFoto);
comprobar(String(sinJs.titular).includes('Todo lo que buscas'), 'sin JavaScript el titular de la portada se lee', sinJs.titular);

anota('== 8. Enlaces internos: todos llevan a algo ==');

const sesionEnlaces = await abrir(1200, 900, 'index.html');
const destinos = new Set();

for (const pagina of paginas) {
    await cdp('Page.navigate', { url: BASE + '/' + pagina }, sesionEnlaces);
    await esperar(1000);

    const enlaces = await evaluar(sesionEnlaces, `
        return [...new Set([...document.querySelectorAll('a[href]')]
            .map((a) => a.getAttribute('href'))
            .filter((h) => h && ! h.startsWith('http') && ! h.startsWith('#') && ! h.startsWith('mailto')))];
    `);

    enlaces.forEach((h) => destinos.add(h));
}

anota(`  ${destinos.size} destinos internos distintos`);

const sinResultado = [];
const sinProductos = [];
const fichasVacias = [];

for (const destino of destinos) {
    await cdp('Page.navigate', { url: BASE + '/' + destino.replace(/^\.\//, '') }, sesionEnlaces);
    await esperar(700);

    const estado = await evaluar(sesionEnlaces, `
        const cuerpo = document.body.textContent;
        const error = cuerpo.includes('Ese producto no está en la demostración');
        const productos = document.querySelectorAll('.producto').length;
        const vacio = !! document.querySelector('[data-catalogo-vacio]') && ! document.querySelector('[data-catalogo-vacio]').hidden;

        return {
            error: error,
            productos: productos,
            vacio: vacio,
            encabezado: !! document.querySelector('h1'),
            nombreFicha: document.querySelector('[data-ficha-nombre]') ? document.querySelector('[data-ficha-nombre]').textContent : null,
            pie: !! document.querySelector('.pie'),
            titulo: document.title,
        };
    `);

    // La página tiene que existir y estar montada: cabecera, encabezado, pie y
    // sin el mensaje de producto inexistente.
    if (estado.error || ! estado.encabezado || ! estado.pie) {
        sinResultado.push(`${destino} → ${JSON.stringify(estado)}`);
    }

    // Un enlace de catálogo (categoría o colección) tiene que traer productos:
    // el estado vacío ahí sería un filtro roto.
    if ((destino.includes('categoria=') || destino.includes('coleccion=')) && estado.productos === 0) {
        sinProductos.push(`${destino} → ${estado.productos} productos`);
    }

    // Y una ficha tiene que haber cargado su producto, no quedarse en el texto
    // de espera.
    if (estado.nombreFicha !== null && estado.nombreFicha.includes('Cargando')) {
        fichasVacias.push(`${destino} → ${estado.nombreFicha}`);
    }
}

comprobar(sinResultado.length === 0, 'todos los enlaces internos abren una página montada', sinResultado.join(' | '));
comprobar(sinProductos.length === 0, 'todos los enlaces de categoría o colección traen productos', sinProductos.join(' | '));
comprobar(fichasVacias.length === 0, 'todas las fichas enlazadas cargan su producto', fichasVacias.join(' | '));

await cerrar(sesionEnlaces);

anota('== 9. Revisión a 390 px ==');
await cerrar(sesion);

const angosto = await abrir(390, 844, 'index.html');

for (const pagina of paginas) {
    await cdp('Page.navigate', { url: BASE + '/' + pagina }, angosto);
    await esperar(1400);

    const medido = await evaluar(angosto, medirDesborde);

    comprobar(medido.scroll <= medido.ancho + 1, `${pagina}: sin desborde horizontal a 390 px`, JSON.stringify(medido));
}

await cdp('Page.navigate', { url: BASE + '/index.html' }, angosto);
await esperar(1400);

const menuMovil = await evaluar(angosto, `
    document.querySelector('[data-menu-boton]').click();

    return {
        boton: getComputedStyle(document.querySelector('[data-menu-boton]')).display,
        abierto: document.querySelector('[data-nav]').classList.contains('esta-abierto'),
        visible: getComputedStyle(document.querySelector('[data-nav]')).display,
    };
`);

// El botón se ve como `flex` y no `inline-flex` porque su contenedor ya es
// flexbox y el navegador ajusta el valor computado.
comprobar(menuMovil.boton !== 'none' && menuMovil.abierto && menuMovil.visible === 'block', 'el menú móvil se abre y se ve', JSON.stringify(menuMovil));

await capturar(angosto, 'portada-390');

await cdp('Page.navigate', { url: BASE + '/catalogo.html' }, angosto);
await esperar(1400);
await capturar(angosto, 'catalogo-390');

await cdp('Page.navigate', { url: BASE + '/index.html' }, angosto);
await esperar(1200);

const mega = await evaluar(angosto, `
    document.querySelector('[data-mega-boton]').click();

    return {
        abierto: document.querySelector('[data-mega]').classList.contains('esta-abierto'),
        enlaces: document.querySelectorAll('[data-mega] [data-filtro-coleccion]').length,
    };
`);

comprobar(mega.abierto && mega.enlaces === 18, 'el mega panel abre con todas las colecciones', JSON.stringify(mega));

await cerrar(angosto);

capturando = false;

await writeFile(`${SALIDA}informe.txt`, informe.join('\n') + '\n');

console.log(`\n${fallos.length === 0 ? 'Sin fallos' : fallos.length + ' fallos'}`);
console.log(`Capturas en ${SALIDA}`);
console.log(`Problemas de consola: ${problemas.length}`);

if (problemas.length) {
    console.log(problemas.join('\n'));
}

chrome.kill();

process.exit(fallos.length === 0 && problemas.length === 0 ? 0 : 1);
