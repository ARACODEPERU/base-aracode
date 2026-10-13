/*
 * Revisión de la página del servicio de páginas web (/paginas-web).
 *
 * Comprueba contra Chrome real, a 1440 y a 390 px, que la página se pinta sin
 * errores de consola ni peticiones fallidas, que cada plan publica su importe
 * mensual y anual, que el acordeón de preguntas abre y cierra, que ningún bloque
 * se desborda a lo ancho y que el bloque de planes del catálogo aparece debajo de
 * la rejilla. Deja capturas para mirarlas a ojo.
 *
 * No usa ninguna librería: el WebSocket es el de Node y el protocolo de DevTools
 * se habla a mano, igual que en storage/tmp/revisar-catalogo-etiquetas.mjs.
 *
 * Uso: node storage/tmp/revisar-paginas-web.mjs [url]
 */

import { spawn } from 'node:child_process';
import { mkdir, rm, writeFile } from 'node:fs/promises';

const BASE = (process.argv[2] ?? 'http://base-aracode.test/paginas-web').replace(/\/$/, '');
const CATALOGO = BASE.replace(/\/paginas-web$/, '/plantillas');
const SOLUCIONES = BASE.replace(/\/paginas-web$/, '/soluciones');
const PORTADA = BASE.replace(/\/paginas-web$/, '/');
const SALIDA = new URL('./repaso-paginas-web/', import.meta.url).pathname.replace(/^\//, '');
const CHROME = 'C:/Program Files/Google/Chrome/Application/chrome.exe';
const PUERTO = 9352;

await mkdir(SALIDA, { recursive: true });

// Se borra el perfil de la pasada anterior: Chrome guarda ahí la caché y, con la
// hoja de estilos cacheada, la revisión mediría el CSS viejo y daría por fallado
// un cambio que sí está publicado.
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
    await esperar(2500);

    // Las secciones se revelan al hacer scroll: sin esto quedarían invisibles en
    // la captura. El sitio marca las suyas agregando la clase `revealed`.
    await evaluar(sessionId, `
        document.querySelectorAll('.reveal, .reveal-left, .reveal-right, .reveal-scale')
            .forEach((b) => b.classList.add('revealed'));

        return true;
    `);
    await esperar(300);

    return sessionId;
}

async function evaluar(sessionId, expresion) {
    const { result, exceptionDetails } = await cdp('Runtime.evaluate', {
        // La envoltura es async a propósito: varias comprobaciones bajan a la
        // sección y esperan (imágenes perezosas, revelados) antes de medir.
        expression: `(async () => { ${expresion} })()`,
        returnByValue: true,
        awaitPromise: true,
    }, sessionId);

    if (exceptionDetails) {
        return { error: exceptionDetails.text };
    }

    return result.value;
}

async function captura(sessionId, archivo) {
    const { data } = await cdp('Page.captureScreenshot', {
        format: 'png', captureBeyondViewport: true,
    }, sessionId);

    await writeFile(SALIDA + archivo, Buffer.from(data, 'base64'));
}

async function medir(sessionId) {
    return evaluar(sessionId, `
        const raiz = document.documentElement;

        // El menú móvil vive fuera de cuadro a propósito (se desliza al
        // abrirlo), así que no cuenta como desborde: lo que importa es que el
        // documento no gane ancho de scroll.
        const menu = document.querySelector('.ara-mobile-menu');
        const salidos = [...document.querySelectorAll('body *')].filter((e) => {
            if (menu && menu.contains(e)) return false;

            const r = e.getBoundingClientRect();
            const estilo = getComputedStyle(e);

            return estilo.position !== 'fixed'
                && estilo.overflowX !== 'auto'
                && r.width > 0
                && (r.right > raiz.clientWidth + 1 || r.left < -1);
        }).map((e) => e.tagName.toLowerCase() + '.' + (e.className || '').toString().slice(0, 40));

        // Cada tarjeta de plan publica su importe mensual y su importe anual.
        // El bloque común («Todos los planes incluyen») también es una tarjeta:
        // se descarta por su clase para quedarse con las tres de plan.
        const tarjetas = [...document.querySelectorAll('section#planes .ara-card')]
            .filter((c) => !c.classList.contains('mt-12'))
            .map((c) => ({
                texto: c.innerText.replace(/\\s+/g, ' ').trim().slice(0, 60),
                mensual: /S\\/ [\\d,]+\\s*\\/mes/.test(c.innerText),
                anual: /S\\/ [\\d,]+ el año completo/.test(c.innerText),
            }));

        // El acordeón: se abre la primera pregunta y se comprueba que el texto
        // quede visible de verdad, no solo sin la clase hidden.
        const boton = document.querySelector('.faq-toggle');
        boton.click();
        const respuesta = boton.nextElementSibling;
        const abierta = !respuesta.classList.contains('hidden')
            && respuesta.getBoundingClientRect().height > 0;
        const textoVisible = respuesta.innerText.trim().slice(0, 40);

        boton.click();
        const cerrada = respuesta.classList.contains('hidden');

        return {
            desborde: salidos,
            anchoDocumento: raiz.scrollWidth,
            anchoVentana: raiz.clientWidth,
            tarjetas,
            preguntas: document.querySelectorAll('.faq-toggle').length,
            acordeon: { abierta, textoVisible, cerrada },
            formatosEnlazados: [...document.querySelectorAll('a[href*="/plantillas?q="]')].length,
            // Dónde empieza cada bloque: sirve para recortar las capturas largas
            // sin adivinar la altura.
            bloques: Object.fromEntries([...document.querySelectorAll('section')].map((s, i) => [
                (s.querySelector('h2')?.innerText ?? 'sección ' + (i + 1)).slice(0, 28),
                Math.round(s.getBoundingClientRect().top + window.scrollY),
            ])),
        };
    `);
}

// ---------------------------------------------------------------- escritorio
let sessionId = await abrir(1440, 1000, BASE);
let datos = await medir(sessionId);

anota('== /paginas-web a 1440 px ==');
anota(`   ancho del documento: ${datos.anchoDocumento} px (ventana ${datos.anchoVentana})`);
anota(`   planes publicados: ${datos.tarjetas.length}`);
datos.tarjetas.forEach((t, i) => anota(`   · plan ${i + 1}: mensual ${t.mensual ? 'sí' : 'NO'} · anual ${t.anual ? 'sí' : 'NO'} — ${t.texto}`));
anota(`   preguntas frecuentes: ${datos.preguntas} · acordeón abre ${datos.acordeon.abierta ? 'sí' : 'NO'} («${datos.acordeon.textoVisible}») y cierra ${datos.acordeon.cerrada ? 'sí' : 'NO'}`);
anota(`   enlaces a plantillas por formato: ${datos.formatosEnlazados}`);
anota(`   fuera del ancho: ${datos.desborde.length === 0 ? 'ninguno' : datos.desborde.join(', ')}`);
Object.entries(datos.bloques).forEach(([titulo, y]) => anota(`   bloque «${titulo}» empieza en ${y} px`));

await captura(sessionId, 'paginas-web-1440.png');

// ------------------------------------------------------------------- móvil
const movil = await abrir(390, 844, BASE);
const datosMovil = await medir(movil);

anota('== /paginas-web a 390 px ==');
anota(`   ancho del documento: ${datosMovil.anchoDocumento} px (ventana ${datosMovil.anchoVentana})`);
anota(`   planes publicados: ${datosMovil.tarjetas.length}`);
anota(`   fuera del ancho: ${datosMovil.desborde.length === 0 ? 'ninguno' : datosMovil.desborde.join(', ')}`);
Object.entries(datosMovil.bloques).forEach(([titulo, y]) => anota(`   bloque «${titulo}» empieza en ${y} px`));

await captura(movil, 'paginas-web-390.png');

// --------------------------------------------------- bloque dentro del catálogo
const catalogo = await abrir(1440, 1000, CATALOGO);
const datosCatalogo = await evaluar(catalogo, `
    const rejilla = document.querySelector('.plt-rejilla');
    const bloque = [...document.querySelectorAll('section')].find((s) => s.innerText.includes('¿Y después de elegir la plantilla?'));

    if (!bloque) {
        return { bloque: false };
    }

    const r = bloque.getBoundingClientRect();
    const tarjetas = bloque.querySelectorAll('.ara-card').length;
    const enlace = bloque.querySelector('a[href*="/paginas-web"]');

    return {
        bloque: true,
        debajoDeLaRejilla: rejilla ? bloque.getBoundingClientRect().top > rejilla.getBoundingClientRect().top : null,
        tarjetas,
        enlace: enlace ? enlace.href : null,
        salidos: [...bloque.querySelectorAll('*')].filter((e) => {
            const c = e.getBoundingClientRect();
            return c.right > document.documentElement.clientWidth + 1;
        }).length,
    };
`);

anota('== bloque del servicio dentro de /plantillas ==');
anota(`   presente: ${datosCatalogo.bloque ? 'sí' : 'NO'} · debajo de la rejilla: ${datosCatalogo.debajoDeLaRejilla ? 'sí' : 'NO'}`);
anota(`   tarjetas de plan: ${datosCatalogo.tarjetas} · enlace: ${datosCatalogo.enlace ?? 'NO'} · elementos desbordados: ${datosCatalogo.salidos}`);

await captura(catalogo, 'catalogo-bloque-1440.png');

// ------------------------------------------- la tarjeta dentro del ecosistema
const ecosistema = await abrir(1440, 1000, SOLUCIONES);
const datosEcosistema = await evaluar(ecosistema, `
    const tarjeta = [...document.querySelectorAll('article.ara-product-card')]
        .find((t) => t.innerText.includes('Páginas Web'));

    if (!tarjeta) {
        return { tarjeta: false };
    }

    const cta = tarjeta.querySelector('.ara-product-cta');
    const media = tarjeta.querySelector('.ara-product-media');
    const r = tarjeta.getBoundingClientRect();

    return {
        tarjeta: true,
        titulo: tarjeta.querySelector('.ara-product-title').innerText.trim(),
        insignia: tarjeta.querySelector('.ara-product-badge').innerText.trim(),
        cta: cta ? cta.getAttribute('href') : null,
        precio: tarjeta.innerText.includes('al mes'),
        prestaciones: tarjeta.querySelectorAll('.ara-product-features li').length,
        degradado: getComputedStyle(media).backgroundImage.includes('124, 58, 237'),
        // La tarjeta entera es la franja que se puede pulsar: el botón va dentro.
        ctaDentro: cta ? cta.getBoundingClientRect().bottom <= r.bottom + 1 : false,
        recorte: Math.round(r.width) + ' × ' + Math.round(r.height),
        desborde: [...tarjeta.querySelectorAll('*')].filter((e) => {
            const c = e.getBoundingClientRect();
            return c.right > document.documentElement.clientWidth + 1;
        }).length,
    };
`);

anota('== el servicio dentro de /soluciones ==');
anota(`   tarjeta: ${datosEcosistema.tarjeta ? 'sí' : 'NO'} · medalla: ${datosEcosistema.insignia ?? '—'} · título: ${datosEcosistema.titulo ?? '—'}`);
anota(`   precio de entrada visible: ${datosEcosistema.precio ? 'sí' : 'NO'} · prestaciones: ${datosEcosistema.prestaciones}`);
anota(`   botón: ${datosEcosistema.cta ?? 'NO'} (dentro de la tarjeta: ${datosEcosistema.ctaDentro ? 'sí' : 'NO'})`);
anota(`   degradado morado: ${datosEcosistema.degradado ? 'sí' : 'NO'} · medida: ${datosEcosistema.recorte} · elementos desbordados: ${datosEcosistema.desborde}`);

await captura(ecosistema, 'soluciones-servicio-1440.png');

const ecosistemaMovil = await abrir(390, 844, SOLUCIONES);

await captura(ecosistemaMovil, 'soluciones-servicio-390.png');

// ---------------------------------------- las tarjetas de producto del home
const portada = await abrir(1440, 1000, PORTADA);

/** Mide las tres tarjetas de producto de la portada, en el ancho que sea. */
async function medirPortada(sessionId) {
    return evaluar(sessionId, `
        const tarjetas = [...document.querySelectorAll('section article.ara-product-card')]
            .filter((t) => t.querySelector('img'));

        // Las imágenes son perezosas: se baja a la sección y se espera para que
        // se pidan de verdad, que es como las verá quien navegue.
        tarjetas[0].scrollIntoView({ block: 'center' });
        await new Promise((r) => setTimeout(r, 800));

        return {
            total: tarjetas.length,
            compactas: tarjetas.filter((t) => t.classList.contains('ara-product-card--compacta')).length,
            conFrase: tarjetas.filter((t) => t.querySelector('.ara-product-text')).length,
            conLista: tarjetas.filter((t) => t.querySelector('.ara-product-features')).length,
            // Tres por fila = las tres tarjetas empiezan a la misma altura.
            // Cuántas tarjetas comparten la primera fila (las que empiezan a la
            // altura más alta) y cuántas filas hay en total.
            primeraFila: (() => {
                const alturas = tarjetas.map((t) => Math.round(t.getBoundingClientRect().top));
                const primera = Math.min(...alturas);
                return alturas.filter((a) => a === primera).length;
            })(),
            filas: new Set(tarjetas.map((t) => Math.round(t.getBoundingClientRect().top))).size,
            mismaFila: new Set(tarjetas.map((t) => Math.round(t.getBoundingClientRect().top))).size === 1,
            imagenesCargadas: tarjetas.filter((t) => t.querySelector('img').naturalWidth > 0).length,
            alto: Math.round(tarjetas[0].getBoundingClientRect().height),
            ancho: Math.round(tarjetas[0].getBoundingClientRect().width),
            desborde: document.documentElement.scrollWidth > document.documentElement.clientWidth + 1,
        };
    `);
}

const datosPortada = await medirPortada(portada);
const datosPortadaTablet = await medirPortada(await abrir(800, 900, PORTADA));
const datosPortadaMovil = await medirPortada(await abrir(390, 844, PORTADA));

anota('== tarjetas de producto de la portada ==');
anota(`   1440 px: ${datosPortada.total} tarjetas · ${datosPortada.compactas} compactas · ${datosPortada.conFrase} con frase · ${datosPortada.conLista} con lista`);
anota(`   medida: ${datosPortada.ancho} × ${datosPortada.alto} px · filas: ${datosPortada.filas} · en la primera: ${datosPortada.primeraFila}`);
anota(`   imágenes cargadas: ${datosPortada.imagenesCargadas} de ${datosPortada.total}`);
anota(`   800 px: filas: ${datosPortadaTablet.filas} · en la primera: ${datosPortadaTablet.primeraFila}`);
anota(`   390 px: filas: ${datosPortadaMovil.filas} · en la primera: ${datosPortadaMovil.primeraFila}`);

await captura(portada, 'portada-productos-1440.png');

anota('== consola y red ==');
anota(`   errores de consola: ${consola.length === 0 ? 'ninguno' : consola.join(' | ')}`);
anota(`   peticiones fallidas: ${red.length === 0 ? 'ninguna' : red.join(' | ')}`);

await writeFile(SALIDA + 'informe.txt', informe.join('\n') + '\n');

ws.close();
chrome.kill();

// Un problema real (fuera del ancho, error de consola o petición fallida) marca
// la salida, para que el guion sirva en una comprobación automática.
const problemas = [
    ...datos.desborde, ...datosMovil.desborde,
    ...consola, ...red,
    ...(datos.anchoDocumento > datos.anchoVentana + 1 ? ['desborde lateral en escritorio'] : []),
    ...(datosMovil.anchoDocumento > datosMovil.anchoVentana + 1 ? ['desborde lateral en móvil'] : []),
    ...(datos.tarjetas.length !== 3 ? ['no se publicaron los tres planes'] : []),
    ...(datosCatalogo.bloque !== true ? ['el bloque del catálogo no aparece'] : []),
    ...(datosEcosistema.tarjeta !== true ? ['la tarjeta del servicio no aparece en /soluciones'] : []),
    ...(datosEcosistema.cta?.includes('/paginas-web') ? [] : ['la tarjeta de /soluciones no lleva a /paginas-web']),
    ...(datosEcosistema.degradado === true ? [] : ['la franja de la tarjeta no lleva el degradado morado']),
    ...(datosPortada.total === 3 ? [] : ['la portada no publica tres tarjetas de producto']),
    ...(datosPortada.compactas === 3 ? [] : ['alguna tarjeta de la portada no es compacta']),
    ...(datosPortada.conFrase === 3 && datosPortada.conLista === 0 ? [] : ['las tarjetas de la portada no resumen con la frase corta']),
    ...(datosPortada.filas === 1 && datosPortada.primeraFila === 3 ? [] : ['la portada no pone las tres tarjetas en una fila']),
    ...(datosPortadaTablet.primeraFila === 2 ? [] : ['en tableta la portada no pone dos tarjetas por fila']),
    ...(datosPortadaMovil.primeraFila === 1 ? [] : ['en móvil la portada no apila las tarjetas en una columna']),
    ...(datosPortada.desborde ? ['desborde lateral en la portada'] : []),
    ...(datosPortada.imagenesCargadas === datosPortada.total ? [] : ['alguna imagen de producto de la portada no carga']),
];

console.log(problemas.length === 0 ? '\nSin problemas.' : `\n${problemas.length} problema(s):\n - ` + problemas.join('\n - '));

process.exit(problemas.length === 0 ? 0 : 1);
