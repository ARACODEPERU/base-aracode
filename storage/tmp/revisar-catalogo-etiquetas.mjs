/*
 * Revisión de las etiquetas de la tarjeta del catálogo (/plantillas).
 *
 * Comprueba contra Chrome real que las etiquetas de la tarjeta —el formato
 * (#Landing / #SitioWeb), el rubro y «Destacada»— están en la fila del cuerpo,
 * debajo de la captura y encima del nombre, y que dentro de la captura no queda
 * ninguna etiqueta superpuesta.
 *
 * No usa ninguna librería: el WebSocket es el de Node y el protocolo de DevTools
 * se habla a mano, igual que en storage/tmp/revisar-demo-tienda.mjs.
 *
 * Uso: node storage/tmp/revisar-catalogo-etiquetas.mjs [url]
 */

import { spawn } from 'node:child_process';
import { mkdir, writeFile } from 'node:fs/promises';

const URL_CATALOGO = process.argv[2] ?? 'http://base-aracode.test/plantillas';
const SALIDA = new URL('./repaso-catalogo/', import.meta.url).pathname.replace(/^\//, '');
const CHROME = 'C:/Program Files/Google/Chrome/Application/chrome.exe';
const PUERTO = 9341;

await mkdir(SALIDA, { recursive: true });

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

ws.onmessage = (mensaje) => {
    const datos = JSON.parse(mensaje.data);

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
const informe = [];
const anota = (linea) => {
    informe.push(linea);
    console.log(linea);
};

async function evaluar(sessionId, expresion) {
    const { result, exceptionDetails } = await cdp('Runtime.evaluate', {
        expression: `(() => { ${expresion} })()`,
        returnByValue: true,
        awaitPromise: true,
    }, sessionId);

    if (exceptionDetails) {
        return { error: exceptionDetails.exception?.description ?? exceptionDetails.text };
    }

    return result.value;
}

async function abrir(ancho, alto) {
    const { targetId } = await cdp('Target.createTarget', { url: 'about:blank' });
    const { sessionId } = await cdp('Target.attachToTarget', { targetId, flatten: true });

    await cdp('Page.enable', {}, sessionId);
    await cdp('Runtime.enable', {}, sessionId);
    await cdp('Emulation.setDeviceMetricsOverride', {
        width: ancho, height: alto, deviceScaleFactor: 1, mobile: ancho < 600,
    }, sessionId);

    await cdp('Page.navigate', { url: URL_CATALOGO }, sessionId);
    await esperar(2500);

    // Las tarjetas aparecen con un revelado al entrar en pantalla: se recorren
    // para que todas estén reveladas y medidas en su sitio final.
    await evaluar(sessionId, `
        for (let y = 0; y < document.documentElement.scrollHeight; y += 400) {
            window.scrollTo(0, y);
        }
        window.scrollTo(0, 0);

        return true;
    `);
    await esperar(900);

    return sessionId;
}

/**
 * Mide cada tarjeta: la captura, la fila de etiquetas, cada etiqueta y el
 * nombre, y devuelve solo los problemas.
 */
async function medir(sessionId, ancho) {
    return evaluar(sessionId, `
        const problemas = [];
        const tarjetas = [...document.querySelectorAll('.plt-card')];

        if (tarjetas.length === 0) {
            return { total: 0, problemas: ['no hay ninguna tarjeta en la página'] };
        }

        const caja = (nodo) => {
            const r = nodo.getBoundingClientRect();

            return { arriba: Math.round(r.top), abajo: Math.round(r.bottom), izq: Math.round(r.left), der: Math.round(r.right), ancho: Math.round(r.width), alto: Math.round(r.height) };
        };

        const filas = [];

        tarjetas.forEach((tarjeta, i) => {
            const media = tarjeta.querySelector('.plt-card__media');
            const cuerpo = tarjeta.querySelector('.plt-card__cuerpo');
            const fila = tarjeta.querySelector('.plt-card__chips');
            const nombre = tarjeta.querySelector('.plt-card__nombre');
            const chips = [...(fila?.children ?? [])];

            if (! media || ! cuerpo || ! fila || ! nombre) {
                problemas.push('tarjeta ' + i + ': falta la captura, el cuerpo, la fila de etiquetas o el nombre');

                return;
            }

            const cMedia = caja(media);
            const cFila = caja(fila);
            const cNombre = caja(nombre);

            // 1. La fila vive dentro del cuerpo y debajo de la captura.
            if (! cuerpo.contains(fila)) {
                problemas.push('tarjeta ' + i + ': la fila de etiquetas no está en .plt-card__cuerpo');
            }

            if (cFila.arriba < cMedia.abajo - 1) {
                problemas.push('tarjeta ' + i + ': la fila de etiquetas pisa la captura (' + cFila.arriba + ' < ' + cMedia.abajo + ')');
            }

            // 2. La fila va encima del nombre.
            if (cFila.abajo > cNombre.arriba + 1) {
                problemas.push('tarjeta ' + i + ': la fila de etiquetas se solapa con el nombre (' + cFila.abajo + ' > ' + cNombre.arriba + ')');
            }

            // 3. Dentro de la captura solo puede quedar la imagen o el mosaico.
            [...media.children].forEach((hijo) => {
                if (! hijo.matches('img, .plt-card__sin-captura')) {
                    problemas.push('tarjeta ' + i + ': sigue habiendo algo dentro de la captura (' + hijo.className + ')');
                }
            });

            // 4. Todo lo que había encima de la captura está ahora en la fila: el
            //    formato, el rubro y, cuando toca, «Destacada».
            const enFila = chips.map((c) => c.className);

            if (! enFila.some((c) => c.includes('plt-tipo'))) {
                problemas.push('tarjeta ' + i + ': la fila no trae el chip de formato');
            }

            if (! enFila.some((c) => c.includes('plt-card__etiqueta'))) {
                problemas.push('tarjeta ' + i + ': la fila no trae el chip del rubro');
            }

            // 5. Ninguna etiqueta se sale de la tarjeta ni de la fila visible.
            const cTarjeta = caja(tarjeta);

            chips.forEach((chip) => {
                const cChip = caja(chip);

                if (cChip.izq < cTarjeta.izq || cChip.der > cTarjeta.der) {
                    problemas.push('tarjeta ' + i + ': el chip ' + chip.className + ' se sale de la tarjeta');
                }

                if (cChip.alto < 16) {
                    problemas.push('tarjeta ' + i + ': el chip ' + chip.className + ' mide ' + cChip.alto + ' px de alto');
                }
            });

            // 6. La fila es una sola línea de chips, o varias apiladas, pero
            //    siempre con la misma altura de texto: nada de solaparse entre sí.
            chips.forEach((chip, j) => {
                chips.slice(j + 1).forEach((otro) => {
                    const a = caja(chip);
                    const b = caja(otro);

                    if (a.izq < b.der && b.izq < a.der && a.arriba < b.abajo && b.arriba < a.abajo) {
                        problemas.push('tarjeta ' + i + ': dos chips se solapan (' + chip.className + ' / ' + otro.className + ')');
                    }
                });
            });

            filas.push({
                captura: cMedia.alto,
                fila: cFila.alto,
                alto: cTarjeta.alto,
                chips: chips.map((c) => (c.textContent.trim() || c.className) + ' (' + caja(c).ancho + '×' + caja(c).alto + ')'),
            });
        });

        // 7. La página no se desborda a lo ancho.
        if (document.documentElement.scrollWidth > window.innerWidth) {
            problemas.push('la página se desborda a lo ancho: ' + document.documentElement.scrollWidth + ' > ' + window.innerWidth);
        }

        return { total: tarjetas.length, filas, problemas };
    `);
}

for (const [ancho, alto] of [[1440, 900], [390, 844]]) {
    const sessionId = await abrir(ancho, alto);
    const resultado = await medir(sessionId, ancho);

    if (resultado.error) {
        fallos.push(`${ancho} px: ${resultado.error}`);
        anota(`${ancho} px  Error al medir: ${resultado.error}`);

        continue;
    }

    anota(`${ancho} px  ${resultado.total} tarjetas · ${resultado.problemas.length} problemas`);

    resultado.filas.slice(0, 3).forEach((fila, i) => {
        anota(`   tarjeta ${i}: captura ${fila.captura} px · fila ${fila.fila} px · chips: ${fila.chips.join(' | ')}`);
    });

    const primera = resultado.filas[resultado.filas.length - 1];

    if (primera) {
        anota(`   última tarjeta: chips: ${primera.chips.join(' | ')}`);
    }

    resultado.problemas.forEach((p) => fallos.push(`${ancho} px: ${p}`));

    // Captura de la primera fila de tarjetas para revisarla a ojo (el recorte
    // va en coordenadas de la página, así que no hace falta desplazarse).
    // La captura se toma de la ventana, con la primera tarjeta arriba: recortar
    // fuera de la ventana (captureBeyondViewport) sale en blanco en el móvil.
    const listo = await evaluar(sessionId, `
        const primera = document.querySelector('.plt-card');

        if (! primera) {
            return false;
        }

        window.scrollTo(0, primera.getBoundingClientRect().top + window.scrollY - 24);
        document.querySelectorAll('.plt-card').forEach((t) => t.classList.add('is-visible', 'reveal-visible'));

        return true;
    `);

    if (listo) {
        await esperar(700);

        const { data } = await cdp('Page.captureScreenshot', { format: 'png' }, sessionId);
        await writeFile(SALIDA + `tarjetas-${ancho}.png`, Buffer.from(data, 'base64'));
    } else {
        fallos.push(`${ancho} px: no se pudo situar la primera tarjeta para capturarla`);
    }

    await cdp('Target.closeTarget', { targetId: (await cdp('Target.getTargets')).targetInfos.at(-1).targetId }).catch(() => {});
}

chrome.kill();

anota('');

if (fallos.length > 0) {
    anota(`${fallos.length} fallos:`);
    fallos.forEach((f) => anota(' - ' + f));

    process.exit(1);
}

anota('Todo correcto: las etiquetas van en la fila del cuerpo, debajo de la captura.');
