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
import { tmpdir } from 'node:os';
import path from 'node:path';

const BASE = (process.argv[2] ?? 'http://base-aracode.test').replace(/\/$/, '');
const LOGIN = BASE + '/login';
const BRANDING = BASE + '/branding/show';
const EMPRESA = BASE + '/company/show';
const SALIDA = new URL('./revisar-branding-empresa/', import.meta.url).pathname.replace(/^\//, '');
const RAIZ = new URL('../../', import.meta.url).pathname.replace(/^\//, '');
const CHROME = 'C:/Program Files/Google/Chrome/Application/chrome.exe';
const PUERTO = 9356;

await mkdir(SALIDA, { recursive: true });

/*
 * El perfil de Chrome vive en la carpeta temporal del sistema, no en el
 * proyecto: dentro del repositorio dejaba miles de archivos del navegador en
 * `git status`. Se borra el de la corrida anterior para arrancar limpio y, si
 * Windows lo tiene bloqueado (Defender, un Chrome zombi), se usa uno nuevo.
 */
let PERFIL = path.join(tmpdir(), 'revisar-branding-empresa-perfil');

try {
    await rm(PERFIL, { recursive: true, force: true });
} catch (error) {
    PERFIL = path.join(tmpdir(), 'revisar-branding-empresa-perfil-' + Date.now());
    console.warn(`aviso: no se pudo limpiar el perfil anterior (${error.code ?? error.message}); se usa uno nuevo.`);
}

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
    '--user-data-dir=' + PERFIL.replace(/\//g, '\\'),
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

/*
 * El informe puede estar abierto en un visor y Windows bloquea la escritura: se
 * reintenta y, si no se puede guardar, queda igualmente en la salida de consola.
 */
async function guardarInforme() {
    for (let intento = 1; intento <= 3; intento++) {
        try {
            await writeFile(SALIDA + 'informe.txt', informe.join('\n') + '\n');

            return true;
        } catch (error) {
            console.warn(`aviso: no se pudo guardar informe.txt (intento ${intento}, ${error.code ?? error.message})`);
            await esperar(400 * intento);
        }
    }

    return false;
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
    await esperar(3000);

    return sessionId;
}

/*
 * Guarda una captura. En Windows el archivo puede estar abierto por un visor o
 * por el indexador y la escritura falla con un error genérico: se reintenta y,
 * si sigue bloqueado, la revisión continúa sin esa imagen en lugar de abortar.
 */
async function captura(sessionId, archivo) {
    const { data } = await cdp('Page.captureScreenshot', { format: 'png' }, sessionId);
    const bytes = Buffer.from(data, 'base64');

    for (let intento = 1; intento <= 3; intento++) {
        try {
            await writeFile(SALIDA + archivo, bytes);

            return true;
        } catch (error) {
            anota(`   aviso: no se pudo guardar ${archivo} (intento ${intento}, ${error.code ?? error.message})`);
            await esperar(400 * intento);
        }
    }

    return false;
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
    await guardarInforme();
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
        encabezado: (document.querySelector('h5')?.textContent || '').trim(),
        botonVistaPrevia: color(document.querySelector('.brand-vista-previa .btn-primary')),
        botonSecundario: color(document.querySelector('.brand-vista-previa .btn-secondary')),
    };
`);

if (medidas.error) {
    anota('   error leyendo la página: ' + medidas.error);
} else {
    anota(`   url: ${medidas.url}`);
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

anota('== paso 4: el menú lateral (sidebar) con el color primario ==');

// El menú se abre (estado expandido) y la app en tema claro, como la ve el
// usuario: el color del ítem activo se mide en los dos temas.
await evaluar(sesion, `
    localStorage.setItem('sidebar', 'true');
    localStorage.setItem('theme', 'light');

    return true;
`);
await cdp('Page.navigate', { url: BASE + '/dashboard' }, sesion);
const menuMontado = await esperarA(sesion, "document.querySelector('.sidebar-option-item')", 30000);

anota(`   menú montado con sus opciones: ${menuMontado ? 'sí' : 'NO'}`);

/*
 * El ítem activo del menú ya no se pinta con un azul fijo: lleva la clase
 * `bg-primary`, que lee `--brand-primary`. Antes de medir se vuelca todo lo que
 * tiene fondo pintado dentro del menú, para saber exactamente qué se está
 * mirando y detectar cualquier constante que haya quedado.
 *
 * Las clases del menú llevan transición (`transition-all`), así que cada espera
 * es de reloj real y no de un par de frames: medir a mitad de la animación da
 * un color intermedio y haría pasar por bueno (o por malo) lo que no es.
 */
const ESPERA_TRANSICION = 900;

const pintaFondos = `
    [...document.querySelectorAll('.sidebar *')]
        .filter((el) => {
            const fondo = getComputedStyle(el).backgroundColor;

            return fondo && fondo !== 'rgba(0, 0, 0, 0)' && fondo !== 'transparent';
        })
        .slice(0, 12)
        .map((el) => ({
            tag: el.tagName.toLowerCase(),
            fondo: getComputedStyle(el).backgroundColor,
            clase: String(el.className || '').replace(/\\s+/g, ' ').slice(0, 64),
        }))
`;

const menu = await evaluar(sesion, `
    // El módulo activo de la barra (el cuadrado del icono) y la opción activa
    // del panel: las dos filas que el menú resalta.
    const activo = document.querySelector('.sidebar .bg-primary');
    const opcion = document.querySelector(
        '.sidebar-option-item a[class*="bg-primary-light"], .sidebar-option-item button[class*="bg-primary-light"],' +
        '.sidebar-option-item a[class*="from-primary"], .sidebar-option-item button[class*="from-primary"]'
    );
    // La variable efectiva es la que resuelve el propio ítem: con marca guardada
    // el layout la inyecta en el contenedor de la página, no en :root.
    const canales = (getComputedStyle(activo).getPropertyValue('--brand-primary') || '').trim();
    const fondo = (el) => el ? getComputedStyle(el).backgroundColor : null;

    return {
        url: location.pathname,
        variable: canales,
        esperado: canales ? 'rgb(' + canales.split(/\\s+/).join(', ') + ')' : null,
        // Tinte suave del primario (el primario mezclado con blanco al 90 %), que
        // es lo que usan las filas activas del panel de opciones.
        tinte: canales
            ? 'rgb(' + canales.split(/\\s+/).map((c) => Math.round(Number(c) + (255 - Number(c)) * 0.9)).join(', ') + ')'
            : null,
        hayActivo: !!activo,
        activo: activo ? activo.tagName.toLowerCase() : null,
        fondoDelActivo: fondo(activo),
        textoDelActivo: activo ? getComputedStyle(activo).color : null,
        hayOpcion: !!opcion,
        claseDeLaOpcion: opcion ? String(opcion.className || '').replace(/\\s+/g, ' ').slice(0, 70) : null,
        fondoDeLaOpcion: fondo(opcion),
        textoDeLaOpcion: opcion ? getComputedStyle(opcion).color : null,
        gradienteDeLaOpcion: opcion && getComputedStyle(opcion).backgroundImage !== 'none'
            ? getComputedStyle(opcion).backgroundImage.slice(0, 80)
            : null,
        hayAzulFijo: !!document.querySelector('.sidebar .bg-blue-600, .sidebar .bg-blue-500, .sidebar .bg-sky-600, .sidebar .bg-sky-500'),
        fondos: ${pintaFondos},
    };
`);

if (menu.error) {
    anota('   error leyendo el menú: ' + menu.error);
} else {
    anota(`   ${menu.url} · --brand-primary: ${menu.variable} → ${menu.esperado} · tinte: ${menu.tinte}`);
    anota(`   módulo activo (${menu.activo ?? 'ninguno'}): fondo ${menu.fondoDelActivo} · texto ${menu.textoDelActivo}`);
    anota(`   opción activa: fondo ${menu.fondoDeLaOpcion} · texto ${menu.textoDeLaOpcion} · color de fondo ${menu.gradienteDeLaOpcion ?? 'liso'}`);
    anota(`      ${menu.claseDeLaOpcion ?? 'sin clase'}`);
    anota(`   ¿queda algún azul fijo en el menú? ${menu.hayAzulFijo ? 'sí' : 'no'}`);
    anota('   fondos pintados dentro del menú:');

    for (const fondo of menu.fondos ?? []) {
        anota(`      ${fondo.tag} · ${fondo.fondo} · ${fondo.clase}`);
    }
}

await captura(sesion, 'sidebar-marca-1440.png');

// Prueba de fuego: se cambia la variable a mano; si el fondo del activo la
// sigue, el menú está leyendo el color primario de la marca y no una constante.
anota('   con --brand-primary forzado a 23 99 61 (#17633D):');

const forzado = await evaluar(sesion, `
    const activo = document.querySelector('.sidebar .bg-primary');
    let destino = null;


    // La variable vive en :root o donde el layout inyecte la marca: se sube por
    // los ancestros hasta el que la declare para cambiarla exactamente ahí, que
    // es lo mismo que hace la pantalla de Branding al guardar.
    for (let el = activo; el; el = el.parentElement) {
        if (el.style && el.style.getPropertyValue('--brand-primary').trim()) {
            destino = el;
            break;
        }
    }

    if (!destino) {
        destino = document.documentElement;
    }

    const antes = getComputedStyle(activo).backgroundColor;
    // El valor que ya tenía el contenedor: hay que devolverlo tal cual, no borrar
    // la propiedad, porque es la marca que el layout inyectó desde el servidor.
    const original = destino.style.getPropertyValue('--brand-primary');

    destino.style.setProperty('--brand-primary', '23 99 61');

    await new Promise((r) => setTimeout(r, ${ESPERA_TRANSICION}));

    const durante = getComputedStyle(activo).backgroundColor;

    destino.style.setProperty('--brand-primary', original);

    await new Promise((r) => setTimeout(r, ${ESPERA_TRANSICION}));

    return {
        origen: destino.className ? '.' + String(destino.className).trim().split(/\\s+/)[0] : destino.tagName.toLowerCase(),
        antes,
        durante,
        despues: getComputedStyle(activo).backgroundColor,
    };
`);

anota(`   la variable se declara en: ${forzado.origen}`);
anota(`   antes: ${forzado.antes} · forzado: ${forzado.durante} · devuelto: ${forzado.despues}`);

// Modo oscuro de verdad (el que usa el ERP: `body.dark`): el activo conserva el
// primario y su texto queda blanco.
const oscuro = await evaluar(sesion, `
    document.body.classList.add('dark');

    await new Promise((r) => setTimeout(r, ${ESPERA_TRANSICION}));

    const activo = document.querySelector('.sidebar .bg-primary');
    const datos = {
        fondo: activo ? getComputedStyle(activo).backgroundColor : null,
        texto: activo ? getComputedStyle(activo).color : null,
        fondos: ${pintaFondos},
    };

    return datos;
`);

anota(`   en modo oscuro · fondo: ${oscuro.fondo} · texto: ${oscuro.texto}`);

await captura(sesion, 'sidebar-marca-oscuro-1440.png');

const deVuelta = await evaluar(sesion, `
    document.body.classList.remove('dark');

    await new Promise((r) => setTimeout(r, ${ESPERA_TRANSICION}));

    const activo = document.querySelector('.sidebar .bg-primary');

    return {
        fondo: activo ? getComputedStyle(activo).backgroundColor : null,
        texto: activo ? getComputedStyle(activo).color : null,
    };
`);

anota(`   de vuelta en claro · fondo: ${deVuelta.fondo} · texto: ${deVuelta.texto}`);

// Y en la propia pantalla de Branding: el módulo Configuraciones queda resaltado
// con la marca y la opción «Branding» está montada en el menú real.
await cdp('Page.navigate', { url: BRANDING }, sesion);
await esperarA(sesion, "document.querySelector('.sidebar-option-item a[href*=\"branding\"]')", 30000);

const opcionBranding = await evaluar(sesion, `
    const enlace = document.querySelector('.sidebar-option-item a[href*="branding"]');
    const modulo = document.querySelector('.sidebar .bg-primary');

    if (!enlace) {
        return { hay: false, modulo: !!modulo };
    }

    const estilo = getComputedStyle(enlace);
    const canales = (getComputedStyle(document.documentElement).getPropertyValue('--brand-primary') || '').trim();

    return {
        hay: true,
        modulo: !!modulo,
        clase: String(enlace.className || '').replace(/\\s+/g, ' ').slice(0, 70),
        fondo: estilo.backgroundColor,
        gradiente: estilo.backgroundImage === 'none' ? null : estilo.backgroundImage.slice(0, 80),
        texto: estilo.color,
        borde: estilo.borderColor,
        bordeEsperado: canales ? 'rgba(' + canales.split(/\\s+/).join(', ') + ', 0.4)' : null,
    };
`);

anota(`   módulo Configuraciones resaltado: ${opcionBranding.modulo ? 'sí' : 'NO'}`);

if (opcionBranding.hay) {
    anota(`   opción «Branding»: fondo ${opcionBranding.fondo} · texto ${opcionBranding.texto} · borde ${opcionBranding.borde}`);
    anota(`      ${opcionBranding.clase}`);
} else {
    anota('   opción «Branding»: NO aparece en el menú');
}

await captura(sesion, 'sidebar-branding-activo-1440.png');

anota('== paso 5: el tooltip de los módulos con el color secundario ==');

// El globito solo se abre con el menú recogido (`isCollapsed`).
await evaluar(sesion, `
    localStorage.setItem('menu', 'collapsible-vertical');
    localStorage.setItem('sidebar', 'false');

    return true;
`);
await cdp('Page.navigate', { url: BASE + '/dashboard' }, sesion);
await esperarA(sesion, "document.querySelector('.sidebar button')", 30000);

// Se pasa el mouse por los módulos hasta que el tooltip se abre, como haría una
// persona: es el único camino para que el globito exista en el DOM.
const tooltip = await evaluar(sesion, `
    const botones = [...document.querySelectorAll('.sidebar button')];

    for (const boton of botones) {
        boton.dispatchEvent(new MouseEvent('mouseenter', { bubbles: true }));
        boton.dispatchEvent(new MouseEvent('mouseover', { bubbles: true }));

        await new Promise((r) => setTimeout(r, 350));

        if (document.querySelector('.ant-tooltip-inner')) {
            break;
        }
    }

    const globito = document.querySelector('.ant-tooltip-inner');

    if (!globito) {
        return { hay: false };
    }

    const canales = (getComputedStyle(document.documentElement).getPropertyValue('--brand-secondary') || '').trim();

    return {
        hay: true,
        texto: (globito.textContent || '').trim(),
        fondo: getComputedStyle(globito).backgroundColor,
        secundario: canales,
        esperado: canales ? 'rgb(' + canales.split(/\\s+/).join(', ') + ')' : null,
    };
`);

if (tooltip.hay) {
    anota(`   tooltip «${tooltip.texto}» · fondo ${tooltip.fondo}`);
    anota(`   --brand-secondary del documento: ${tooltip.secundario} → ${tooltip.esperado}`);
} else {
    anota('   no se abrió ningún tooltip del menú');
}

await captura(sesion, 'sidebar-tooltip-1440.png');

// Prueba de fuego: con otro secundario, el globito lo sigue.
const tooltipForzado = await evaluar(sesion, `
    const raiz = document.documentElement;
    const original = raiz.style.getPropertyValue('--brand-secondary');
    const globito = document.querySelector('.ant-tooltip-inner');

    raiz.style.setProperty('--brand-secondary', '200 20 120');

    await new Promise((r) => setTimeout(r, 400));

    const fondo = globito ? getComputedStyle(globito).backgroundColor : null;

    raiz.style.setProperty('--brand-secondary', original);

    await new Promise((r) => setTimeout(r, 400));

    return { fondo, devuelto: globito ? getComputedStyle(globito).backgroundColor : null };
`);

anota(`   con --brand-secondary forzado a 200 20 120 → ${tooltipForzado.fondo} · devuelto: ${tooltipForzado.devuelto}`);

anota('== paso 6: el hover de las filas del menú con el color secundario ==');

await evaluar(sesion, `localStorage.setItem('sidebar', 'true'); return true;`);
await cdp('Page.navigate', { url: BRANDING }, sesion);
await esperarA(sesion, "document.querySelector('.sidebar-option-item')", 20000);

// Las filas del panel solo existen cuando el módulo activo tiene opciones: se
// comprueba en qué estado quedó el menú antes de elegir la fila de prueba.
const estadoDelPanel = await evaluar(sesion, `
    const sidebar = document.querySelector('.sidebar');

    return {
        url: location.pathname,
        titulo: document.title,
        ancho: sidebar ? getComputedStyle(sidebar).width : null,
        filas: document.querySelectorAll('.sidebar-option-item a, .sidebar-option-item button').length,
        vacio: !!document.querySelector('.sidebar .border-dashed'),
        modulo: localStorage.getItem('activeModule'),
        menu: localStorage.getItem('menu'),
        recogido: localStorage.getItem('sidebar'),
        texto: (document.body.innerText || '').replace(/\s+/g, ' ').slice(0, 160),
    };
`);

anota(`   panel · ${estadoDelPanel.url} «${estadoDelPanel.titulo}» · ancho ${estadoDelPanel.ancho} · filas ${estadoDelPanel.filas} · estado vacío ${estadoDelPanel.vacio ? 'sí' : 'no'}`);

if (!estadoDelPanel.ancho) {
    anota(`   la página no montó el menú: ${estadoDelPanel.texto}`);
}

// Plan B cuando el módulo en curso no tiene opciones: se abren los módulos del
// rail (solo los botones: los enlaces cambiarían de página) hasta que aparezcan.
if (estadoDelPanel.filas === 0) {
    const abrioModulo = await evaluar(sesion, `
        const botones = [...document.querySelectorAll('.sidebar button')];

        for (const boton of botones) {
            boton.click();

            await new Promise((r) => setTimeout(r, 700));

            if (document.querySelector('.sidebar-option-item a, .sidebar-option-item button')) {
                return true;
            }
        }

        return false;
    `);

    anota(`   se abrió un módulo con opciones: ${abrioModulo ? 'sí' : 'NO'}`);
}

const filaElegida = await evaluar(sesion, `
    const filas = [...document.querySelectorAll('.sidebar-option-item a, .sidebar-option-item button')];
    const retrato = (el) => ({
        fondo: getComputedStyle(el).backgroundColor,
        clase: String(el.className || '').replace(/\\s+/g, ' ').slice(0, 70),
    });
    // Fila en reposo: la que se queda con el fondo base del panel; las que llevan
    // la marca encima (activa, abierta o con el mouse) no sirven de referencia.
    const fila = filas.find((el) => ['rgba(255, 255, 255, 0.7)', 'rgba(30, 41, 59, 0.8)'].includes(getComputedStyle(el).backgroundColor));

    if (!fila) {
        return { sinFila: true, retratos: filas.map(retrato) };
    }

    // La fila tiene que estar a la vista para que el puntero del navegador caiga
    // de verdad sobre ella.
    fila.scrollIntoView({ block: 'center' });

    await new Promise((r) => setTimeout(r, 300));

    const caja = fila.getBoundingClientRect();
    const icono = fila.querySelector('.rounded-lg');

    return {
        sinFila: false,
        texto: (fila.textContent || '').trim().slice(0, 40),
        filas: filas.length,
        x: Math.round(caja.left + caja.width / 2),
        y: Math.round(caja.top + caja.height / 2),
        fondoAntes: getComputedStyle(fila).backgroundColor,
        fondoDelIconoAntes: icono ? getComputedStyle(icono).backgroundColor : null,
    };
`);

anota(`   filas de opción en el panel: ${filaElegida?.filas ?? 0}`);

if (filaElegida?.sinFila) {
    anota('   ninguna fila quedó en reposo; así está el panel:');

    for (const retrato of filaElegida.retratos) {
        anota(`      ${retrato.fondo} · ${retrato.clase}`);
    }
}

if (filaElegida && !filaElegida.sinFila) {
    anota(`   se probará con «${filaElegida.texto}» · fondo en reposo ${filaElegida.fondoAntes}`);

    // El estado `:hover` de CSS solo lo activa el ratón del navegador, así que el
    // puntero se mueve por el protocolo de DevTools y no con eventos armados
    // dentro de la página. El movimiento se repite un poco desplazado porque el
    // primer aviso puede caer antes de que el panel termine de colocarse.
    for (let intento = 1; intento <= 3; intento++) {
        await cdp(
            'Input.dispatchMouseEvent',
            { type: 'mouseMoved', x: filaElegida.x + intento - 1, y: filaElegida.y + intento - 1 },
            sesion
        );
        await esperar(500 + intento * 200);

        const hayHover = await evaluar(sesion, "return !!document.querySelector('.sidebar-option-item a:hover, .sidebar-option-item button:hover');");

        if (hayHover) {
            break;
        }
    }
}

const hover = await evaluar(sesion, `
    const fila = document.querySelector('.sidebar-option-item a:hover, .sidebar-option-item button:hover');
    const icono = fila ? fila.querySelector('.rounded-lg') : null;
    const canales = (getComputedStyle(document.documentElement).getPropertyValue('--brand-secondary') || '').trim();
    const conPeso = (peso) => canales
        ? 'rgb(' + canales.split(/\\s+/).map((c) => Math.round(Number(c) + (255 - Number(c)) * peso)).join(', ') + ')'
        : null;

    return {
        texto: fila ? (fila.textContent || '').trim().slice(0, 40) : null,
        fondo: fila ? getComputedStyle(fila).backgroundColor : null,
        borde: fila ? getComputedStyle(fila).borderColor : null,
        fondoDelIcono: icono ? getComputedStyle(icono).backgroundColor : null,
        colorDelIcono: icono ? getComputedStyle(icono).color : null,
        secundario: conPeso(0),
        tinte: conPeso(0.9),
    };
`);

if (hover.texto) {
    anota(`   con el mouse encima de «${hover.texto}»:`);
    anota(`      fondo ${hover.fondo} (tinte del secundario: ${hover.tinte})`);
    anota(`      borde ${hover.borde} · icono ${hover.colorDelIcono} sobre ${hover.fondoDelIcono}`);
} else {
    anota('   ninguna fila quedó con el mouse encima');
}

await captura(sesion, 'sidebar-hover-1440.png');

// La opción abierta (con sus subopciones desplegadas) es el otro estado
// destacado del panel: antes era naranja y ahora comparte el color secundario.
/*
 * La otra fila destacada del panel es la que el usuario acaba de pulsar: una
 * opción con subopciones queda seleccionada y desplegada a la vez. Se comprueba
 * que toma el color primario con su borde, que es lo que antes se quedaba sin
 * pintar porque el borde base ganaba la partida.
 */
const seleccionada = await evaluar(sesion, `
    const desplegables = () => [...document.querySelectorAll('.sidebar-option-item button')];

    // Si el módulo en curso no tiene opciones desplegables, se abren los del rail
    // (solo los botones: los enlaces cambiarían de página) hasta encontrar uno.
    if (desplegables().length === 0) {
        const modulos = [...document.querySelectorAll('.sidebar button')].filter((el) => !el.closest('.sidebar-option-item'));

        for (const modulo of modulos) {
            modulo.click();

            await new Promise((r) => setTimeout(r, 800));

            if (desplegables().length > 0) {
                break;
            }
        }
    }

    const fila = desplegables()[0];

    if (!fila) {
        return { hay: false };
    }

    fila.click();

    await new Promise((r) => setTimeout(r, 900));

    return {
        hay: true,
        texto: (fila.textContent || '').trim().replace(/\\s+/g, ' ').slice(0, 40),
        fondo: getComputedStyle(fila).backgroundColor,
        borde: getComputedStyle(fila).borderColor,
        color: getComputedStyle(fila).color,
    };
`);

const bordePrimario = menu.variable ? 'rgba(' + menu.variable.split(/\s+/).join(', ') + ', 0.4)' : null;

if (seleccionada.hay) {
    anota(`   opción pulsada «${seleccionada.texto}» · fondo ${seleccionada.fondo} (tinte del primario: ${menu.tinte}) · borde ${seleccionada.borde} · texto ${seleccionada.color}`);
    await captura(sesion, 'sidebar-opcion-seleccionada-1440.png');
} else {
    anota('   este panel no tiene opciones desplegables que pulsar');
}

anota('== paso 7: las franjas de las tarjetas del panel con el color primario ==');

await cdp('Page.navigate', { url: BASE + '/dashboard' }, sesion);
const panelListo = await esperarA(sesion, "document.querySelector('.border-t-4')", 30000);

anota(`   panel montado con tarjetas: ${panelListo ? 'sí' : 'NO'}`);

// El borde superior de las tarjetas era un azul fijo de Tailwind. Se compara el
// color computado con la variable de marca que resuelve el propio elemento: con
// el azul de Tailwind (37, 99, 235) la comparación falla, con la marca no.
const franjas = await evaluar(sesion, `
    const tarjetas = [...document.querySelectorAll('.border-t-4')];
    const canales = (getComputedStyle(tarjetas[0] ?? document.documentElement).getPropertyValue('--brand-primary') || '').trim();

    return {
        cuantas: tarjetas.length,
        variable: canales,
        esperado: canales ? 'rgb(' + canales.split(/\\s+/).join(', ') + ')' : null,
        lista: tarjetas.slice(0, 8).map((el) => ({
            borde: getComputedStyle(el).borderTopColor,
            ancho: getComputedStyle(el).borderTopWidth,
            texto: (el.textContent || '').replace(/\\s+/g, ' ').trim().slice(0, 28),
        })),
    };
`);

anota(`   tarjetas con franja: ${franjas.cuantas} · --brand-primary: ${franjas.variable} → ${franjas.esperado}`);

for (const franja of franjas.lista ?? []) {
    anota(`      ${franja.borde} (${franja.ancho}) · ${franja.texto}`);
}

await captura(sesion, 'dashboard-franjas-1440.png');

anota('== consola y red ==');
anota(`   errores de consola: ${consola.length === 0 ? 'ninguno' : consola.join(' | ')}`);

// Respuestas con error que no vienen de la marca (logo apuntando a un archivo
// que no existe, POSTs del panel con el token de la página): se informan aparte
// para no mezclarlas con el resultado de la revisión.
const avisos = red.filter((linea) => !linea.includes('favicon'));

anota(`   respuestas con error: ${avisos.length === 0 ? 'ninguna' : avisos.join(' | ')}`);

await guardarInforme();

ws.close();
chrome.kill();

const problemas = [
    ...(monto ? [] : ['la pantalla de Branding no montó sus dos selectores']),
    ...(menu.error ? [`no se pudo leer el menú lateral: ${menu.error}`] : []),
    ...(!menu.error && !menu.hayActivo ? ['no se encontró el ítem activo del menú lateral'] : []),
    ...(menu.hayAzulFijo ? ['el menú lateral todavía tiene azules fijos (bg-blue-600 / bg-sky-600)'] : []),
    ...(menu.fondoDelActivo === menu.esperado
        ? []
        : [`el ítem activo del menú quedó en ${menu.fondoDelActivo}, no en el primario ${menu.esperado}`]),
    ...(forzado.durante === 'rgb(23, 99, 61)' && forzado.despues === forzado.antes
        ? []
        : [`al forzar --brand-primary el ítem activo pasó por ${forzado.durante} y volvió a ${forzado.despues}: el menú no lee la variable`]),
    ...(opcionBranding.hay && opcionBranding.fondo === menu.tinte
        ? []
        : [`la fila activa del menú quedó con fondo ${opcionBranding?.fondo}, no en el tinte de la marca ${menu.tinte}`]),
    ...(opcionBranding.hay && opcionBranding.borde !== opcionBranding.bordeEsperado
        ? [`la fila activa del menú quedó con borde ${opcionBranding.borde}, no con el primario ${opcionBranding.bordeEsperado}`]
        : []),
    ...(oscuro.fondo === menu.esperado && oscuro.texto === 'rgb(255, 255, 255)'
        ? []
        : [`en modo oscuro el ítem activo quedó en fondo ${oscuro.fondo} con texto ${oscuro.texto}, no en el primario ${menu.esperado}`]),
    ...(deVuelta.fondo === menu.esperado
        ? []
        : [`al volver al tema claro el ítem activo quedó en ${deVuelta.fondo}, no en el primario ${menu.esperado}`]),
    ...(opcionBranding.hay ? [] : ['el menú no muestra la opción «Branding» de Configuraciones']),
    ...(opcionBranding.modulo ? [] : ['en la pantalla de Branding el módulo del menú no quedó resaltado con la marca']),
    ...(tooltip.hay ? [] : ['no se abrió el tooltip de los módulos: no se pudo revisar su color']),
    ...(tooltip.hay && tooltip.fondo === tooltip.esperado
        ? []
        : [`el tooltip de los módulos quedó en ${tooltip.fondo}, no en el secundario ${tooltip.esperado}`]),
    ...(tooltip.hay && (tooltipForzado.fondo !== 'rgb(200, 20, 120)' || tooltipForzado.devuelto !== tooltip.fondo)
        ? [`al forzar --brand-secondary el tooltip pasó por ${tooltipForzado.fondo} y volvió a ${tooltipForzado.devuelto}`]
        : []),
    ...(filaElegida && !filaElegida.sinFila ? [] : ['no se encontró una fila tranquila del menú para probar el hover']),
    ...(filaElegida && !filaElegida.sinFila && hover.fondo === hover.tinte
        ? []
        : [`la fila con el mouse encima quedó en ${hover.fondo}, no en el tinte del secundario ${hover.tinte}`]),
    ...(filaElegida && !filaElegida.sinFila && hover.colorDelIcono === hover.secundario
        ? []
        : [`el icono de la fila con el mouse encima quedó en ${hover.colorDelIcono}, no en el secundario ${hover.secundario}`]),
    ...(seleccionada.hay && seleccionada.fondo !== menu.tinte
        ? [`la opción pulsada quedó con fondo ${seleccionada.fondo}, no en el tinte del primario ${menu.tinte}`]
        : []),
    ...(seleccionada.hay && seleccionada.borde !== bordePrimario
        ? [`el borde de la opción pulsada quedó en ${seleccionada.borde}, no en el primario ${bordePrimario}`]
        : []),
    ...(franjas.cuantas === 0
        ? ['no se encontró ninguna tarjeta con franja superior en el panel del dashboard']
        : (franjas.lista ?? []).every((franja) => franja.borde === franjas.esperado)
            ? []
            : [`las franjas del panel quedaron en ${(franjas.lista ?? []).map((f) => f.borde).join(', ')}, no en el primario ${franjas.esperado}`]),
    ...(medidas.botonVistaPrevia === 'rgb(1, 136, 238)' ? [] : [`la vista previa quedó en ${medidas.botonVistaPrevia}, no en el azul guardado`]),
    ...(fuera.boton === 'rgb(1, 136, 238)' ? [] : [`el botón primario del ERP quedó en ${fuera.boton}, no en el azul guardado`]),
];

// Los avisos de red se imprimen siempre, pero no son fallo de la marca.
if (avisos.length > 0) {
    console.warn('\nAVISOS (preexistentes, ajenos a la marca):\n - ' + avisos.join('\n - '));
}

if (problemas.length > 0) {
    console.error('\nPROBLEMAS DE MARCA:\n - ' + problemas.join('\n - '));
    process.exit(1);
}

console.log('\nSin problemas de marca: los colores se guardan y el ERP se pinta con ellos.');
