/*
 * Revisión del ecosistema en /soluciones y en la portada.
 *
 * Comprueba contra Chrome real que /soluciones presenta el mismo ecosistema que
 * el home: las siete tarjetas (cuatro productos y tres servicios) con la tarjeta
 * compacta, tres por fila a 1440 px, dos a 800 y una a 390, sin listas de
 * prestaciones, sin errores de consola, sin peticiones fallidas y sin desbordes.
 * Además comprueba que la franja de imagen no recorte el diseño: los del
 * ecosistema son cuadrados (1:1) o panorámicos (2:1) y la franja es 4/3, así que
 * lo único que puede dejarlos enteros es `object-fit: contain`. Se mide el
 * ajuste real que aplica Chrome y la proporción que ocupa el diseño dentro de la
 * franja, para poder reportarlo con números.
 *
 * No usa ninguna librería: el WebSocket es el de Node y el protocolo de
 * DevTools se habla a mano, igual que en storage/tmp/revisar-paginas-web.mjs.
 *
 * Uso: node storage/tmp/revisar-tarjetas-ecosistema.mjs
 */

import { spawn } from 'node:child_process';
import { mkdir, rm, writeFile } from 'node:fs/promises';

const BASE = (process.argv[2] ?? 'http://base-aracode.test').replace(/\/$/, '');
const SOLUCIONES = BASE + '/soluciones';
const PORTADA = BASE + '/';
const SALIDA = new URL('./revisar-tarjetas/', import.meta.url).pathname.replace(/^\//, '');
const CHROME = 'C:/Program Files/Google/Chrome/Application/chrome.exe';
const PUERTO = 9353;

await mkdir(SALIDA, { recursive: true });

// Se borra el perfil de la pasada anterior: Chrome guarda ahí la caché y, con la
// hoja de estilos cacheada, la revisión mediría el CSS viejo.
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
    // la captura. El sitio marca las suyas agregando la clase «revealed».
    await evaluar(sessionId, `
        document.querySelectorAll('.reveal, .reveal-left, .reveal-right, .reveal-scale')
            .forEach((b) => b.classList.add('revealed'));

        return true;
    `);
    await esperar(300);

    return sessionId;
}

async function captura(sessionId, archivo, clip = null) {
    const { data } = await cdp('Page.captureScreenshot', {
        format: 'png', captureBeyondViewport: true, ...(clip ? { clip: { ...clip, scale: 1 } } : {}),
    }, sessionId);

    await writeFile(SALIDA + archivo, Buffer.from(data, 'base64'));
}

/** Mide las tarjetas del ecosistema de una página, en el ancho que sea. */
async function medirEcosistema(sessionId) {
    return evaluar(sessionId, `
        const tarjetas = [...document.querySelectorAll('section article.ara-product-card')];

        if (tarjetas.length === 0) {
            return { total: 0 };
        }

        // Las imágenes son perezosas: se baja a la rejilla y se espera para que
        // se pidan de verdad, que es como las verá quien navegue.
        tarjetas[0].scrollIntoView({ block: 'center' });
        await new Promise((r) => setTimeout(r, 900));

        const conImagen = tarjetas.filter((t) => t.querySelector('img'));
        const alturas = tarjetas.map((t) => Math.round(t.getBoundingClientRect().top));
        const primera = Math.min(...alturas);
        const primeraTarjeta = tarjetas[0].getBoundingClientRect();
        const imagen = tarjetas[0].querySelector('img');
        const caja = imagen ? imagen.getBoundingClientRect() : null;
        const cta = tarjetas[0].querySelector('.ara-product-cta');
        const rTarjeta = tarjetas[0].getBoundingClientRect();

        return {
            total: tarjetas.length,
            compactas: tarjetas.filter((t) => t.classList.contains('ara-product-card--compacta')).length,
            conFrase: tarjetas.filter((t) => t.querySelector('.ara-product-text')).length,
            conLista: tarjetas.filter((t) => t.querySelector('.ara-product-features')).length,
            plano: tarjetas.filter((t) => t.classList.contains('ara-product-card--plano')).length,
            conImagen: conImagen.length,
            imagenesCargadas: conImagen.filter((t) => t.querySelector('img').naturalWidth > 0).length,
            // Tres por fila = las tarjetas de la primera fila empiezan a la
            // misma altura; «filas» cuenta las alturas distintas en total.
            primeraFila: alturas.filter((a) => a === primera).length,
            filas: new Set(alturas).size,
            ancho: Math.round(primeraTarjeta.width),
            alto: Math.round(primeraTarjeta.height),
            // Franja frente al diseño real: con «contain» el diseño entra entero
            // y estos dos números solo describen cuánto relleno queda.
            ratioCaja: caja ? +(caja.width / caja.height).toFixed(3) : null,
            ratioNatural: imagen?.naturalWidth ? +(imagen.naturalWidth / imagen.naturalHeight).toFixed(3) : null,
            // El ajuste que aplica Chrome: tiene que ser «contain» en las cuatro
            // imágenes para que ningún diseño se recorte.
            ajustes: conImagen.map((t) => getComputedStyle(t.querySelector('img')).objectFit),
            // Píxeles que sobran entre la franja y el diseño ya ajustado dentro.
            // La franja se mide desde el archivo, así que tiene que ser 0: es el
            // «el espacio de la imagen está pegado a su alto».
            sobrantes: conImagen.map((t) => {
                const caja = t.querySelector('.ara-product-media').getBoundingClientRect();
                const img = t.querySelector('img');
                const proporcion = img.naturalWidth / img.naturalHeight;

                if (!proporcion) return -1;

                return Math.round(caja.height - Math.min(caja.height, caja.width / proporcion));
            }),
            botonDentro: cta ? cta.getBoundingClientRect().bottom <= rTarjeta.bottom + 1 : null,
            // Dónde empieza la sección del ecosistema: sirve para recortar su
            // captura sin adivinar la altura del hero.
            seccion: (() => {
                const s = document.querySelector('section article.ara-product-card')?.closest('section');

                if (!s) return null;

                const r = s.getBoundingClientRect();

                return { y: Math.round(r.top + window.scrollY), alto: Math.round(r.height) };
            })(),
            desbordeLateral: document.documentElement.scrollWidth > document.documentElement.clientWidth + 1,
            tuboDesborde: [...document.querySelectorAll('body *')].filter((e) => {
                const r = e.getBoundingClientRect();
                const estilo = getComputedStyle(e);
                const menu = document.querySelector('.ara-mobile-menu');

                return !(menu && menu.contains(e))
                    && estilo.position !== 'fixed'
                    && estilo.overflowX !== 'auto'
                    && r.width > 0
                    && (r.right > document.documentElement.clientWidth + 1 || r.left < -1);
            }).length,
        };
    `);
}

// --------------------------------------------------------------- /soluciones
const soluciones = await abrir(1440, 1000, SOLUCIONES);
const datos = await medirEcosistema(soluciones);

anota('== /soluciones · tarjetas del ecosistema, 1440 px ==');
anota(`   tarjetas: ${datos.total} (${datos.plano} servicios con franja sin imagen) · compactas: ${datos.compactas} · con frase: ${datos.conFrase} · con lista: ${datos.conLista}`);
anota(`   filas: ${datos.filas} · en la primera: ${datos.primeraFila} · medida de la tarjeta: ${datos.ancho} × ${datos.alto} px`);
anota(`   imágenes cargadas: ${datos.imagenesCargadas} de ${datos.conImagen} · franja ${datos.ratioCaja} frente al diseño ${datos.ratioNatural} · ajuste: ${[...new Set(datos.ajustes)].join(', ')}`);
anota(`   alto que sobra sobre el diseño en cada tarjeta: ${datos.sobrantes.join(', ')} px`);
anota(`   botón dentro de la tarjeta: ${datos.botonDentro ? 'sí' : 'NO'} · elementos fuera del ancho: ${datos.tuboDesborde}`);

await captura(soluciones, 'soluciones-1440.png');
await captura(soluciones, 'soluciones-rejilla-1440.png', {
    x: 0, y: datos.seccion.y, width: 1440, height: datos.seccion.alto,
});

const tablet = await abrir(800, 900, SOLUCIONES);
const datosTablet = await medirEcosistema(tablet);

anota('== /soluciones, 800 px ==');
anota(`   filas: ${datosTablet.filas} · en la primera: ${datosTablet.primeraFila} · medida: ${datosTablet.ancho} × ${datosTablet.alto} px`);

const movil = await abrir(390, 844, SOLUCIONES);
const datosMovil = await medirEcosistema(movil);

anota('== /soluciones, 390 px ==');
anota(`   filas: ${datosMovil.filas} · en la primera: ${datosMovil.primeraFila} · medida: ${datosMovil.ancho} × ${datosMovil.alto} px`);

await captura(movil, 'soluciones-390.png');
await captura(movil, 'soluciones-rejilla-390.png', {
    x: 0, y: datosMovil.seccion.y, width: 390, height: datosMovil.seccion.alto,
});

// -------------------------------------------------------------------- portada
const portada = await abrir(1440, 1000, PORTADA);
const datosPortada = await medirEcosistema(portada);

anota('== portada · tarjetas de producto, 1440 px ==');
anota(`   tarjetas: ${datosPortada.total} · compactas: ${datosPortada.compactas} · con frase: ${datosPortada.conFrase} · con lista: ${datosPortada.conLista}`);
anota(`   filas: ${datosPortada.filas} · en la primera: ${datosPortada.primeraFila} · medida de la tarjeta: ${datosPortada.ancho} × ${datosPortada.alto} px`);

await captura(portada, 'portada-1440.png');
await captura(portada, 'portada-rejilla-1440.png', {
    x: 0, y: datosPortada.seccion.y, width: 1440, height: datosPortada.seccion.alto,
});

anota('== consola y red ==');
anota(`   errores de consola: ${consola.length === 0 ? 'ninguno' : consola.join(' | ')}`);
anota(`   peticiones fallidas: ${red.length === 0 ? 'ninguna' : red.join(' | ')}`);

await writeFile(SALIDA + 'informe.txt', informe.join('\n') + '\n');

ws.close();
chrome.kill();

// Un problema real marca la salida, para que el guion sirva en una comprobación
// automática.
const problemas = [
    ...(datos.total === 7 ? [] : ['/soluciones no publica las siete tarjetas del ecosistema']),
    ...(datos.compactas === 7 ? [] : ['alguna tarjeta de /soluciones no es compacta']),
    ...(datos.conFrase === 7 ? [] : ['alguna tarjeta de /soluciones no resume con la frase']),
    ...(datos.conLista === 0 ? [] : ['alguna tarjeta de /soluciones sigue pintando la lista de prestaciones']),
    ...(datos.plano === 3 ? [] : ['los tres servicios no llevan su franja sin imagen']),
    ...(datos.imagenesCargadas === 4 && datos.conImagen === 4 ? [] : ['alguna imagen de producto no carga']),
    ...(datos.ajustes.length > 0 && datos.ajustes.every((a) => a === 'contain') ? [] : ['algún diseño de producto se está recortando en su franja']),
    ...(datos.sobrantes.every((s) => s >= 0 && s <= 1) ? [] : [`la franja no está pegada al alto de la imagen (sobra ${datos.sobrantes.join('/')} px)`]),
    ...(datos.filas === 3 && datos.primeraFila === 3 ? [] : ['/soluciones no pone tres tarjetas por fila a 1440 px']),
    ...(datosTablet.primeraFila === 2 ? [] : ['/soluciones no pone dos tarjetas por fila a 800 px']),
    ...(datosMovil.primeraFila === 1 ? [] : ['/soluciones no apila las tarjetas a 390 px']),
    ...(datosPortada.total === 3 && datosPortada.compactas === 3 && datosPortada.primeraFila === 3 ? [] : ['la portada dejó de publicar sus tres tarjetas compactas en una fila']),
    ...(Math.abs(datosPortada.ancho - datos.ancho) <= 2 ? [] : [`la tarjeta del catálogo (${datos.ancho} px) no mide como la de la portada (${datosPortada.ancho} px)`]),
    ...(datos.botonDentro && datosPortada.botonDentro ? [] : ['el botón se sale de su tarjeta']),
    ...(datos.desbordeLateral || datosTablet.desbordeLateral || datosMovil.desbordeLateral || datosPortada.desbordeLateral ? ['desborde lateral en alguna página'] : []),
    ...(datos.tuboDesborde > 0 || datosTablet.tuboDesborde > 0 || datosMovil.tuboDesborde > 0 ? ['hay elementos fuera del ancho'] : []),
    ...consola, ...red,
];

if (problemas.length > 0) {
    console.error('\nPROBLEMAS:\n - ' + problemas.join('\n - '));
    process.exit(1);
}

console.log('\nSin problemas: el catálogo presenta el mismo formato que la portada.');
