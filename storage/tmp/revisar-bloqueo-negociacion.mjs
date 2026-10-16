/*
 * Formulario publico de la negociacion: los inputs que llenan la consulta a RENIEC y
 * SUNAT deben quedar de solo lectura, y solo editables cuando la consulta no devuelve
 * datos.
 *
 * Las respuestas de las APIs se simulan parcheando window.axios.post: no se gasta cuota
 * real de apis.net.pe ni se depende de que el servicio este arriba. Tampoco se envia el
 * formulario (el envio registraria al cliente y confirmaria la negociacion): solo se
 * revisa el estado de los inputs antes del resumen.
 *
 * Uso: node storage/tmp/revisar-bloqueo-negociacion.mjs [token]
 */

import { spawn } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const TOKEN = process.argv[2] ?? '911cb469-d21a-4bc5-b157-9b2e9ebbd57f';
const BASE = 'http://base_aracode.test';
const CHROME = 'C:/Program Files/Google/Chrome/Application/chrome.exe';
const PUERTO = 9357;
const PERFIL = fileURLToPath(new URL('./repaso-nexora/perfil-negociacion', import.meta.url));

spawn(CHROME, [
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

    throw new Error('Chrome no publico el puerto de depuracion');
}

const ws = new WebSocket(await urlDepuracion());
await new Promise((r) => (ws.onopen = r));

let siguienteId = 0;
const pendientes = new Map();
const excepciones = [];

ws.onmessage = (mensaje) => {
    const datos = JSON.parse(mensaje.data);

    if (datos.method === 'Runtime.exceptionThrown') {
        excepciones.push(datos.params.exceptionDetails.exception?.description ?? datos.params.exceptionDetails.text);
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

/** Espera a que una condicion de la pagina se cumpla (el dev server compila a demanda). */
async function esperarA(sessionId, condicion) {
    for (let intento = 0; intento < 120; intento++) {
        if (await evaluar(sessionId, `return Boolean(${condicion});`) === true) {
            return true;
        }

        await esperar(1000);
    }

    return false;
}

const { targetId } = await cdp('Target.createTarget', { url: 'about:blank' });
const { sessionId } = await cdp('Target.attachToTarget', { targetId, flatten: true });

await cdp('Page.enable', {}, sessionId);
await cdp('Runtime.enable', {}, sessionId);
await cdp('Emulation.setDeviceMetricsOverride', { width: 1280, height: 1200, deviceScaleFactor: 1, mobile: false }, sessionId);

await cdp('Page.navigate', { url: `${BASE}/negotiations/registro-de-inscripcion/${TOKEN}` }, sessionId);

// Utilidades dentro de la pagina (despues de navegar: la navegacion limpia el contexto).
await evaluar(sessionId, `
    window.__cerrarAlertas = () => {
        const boton = document.querySelector('.swal2-confirm');
        if (boton) boton.click();
    };
    window.__escribir = (id, valor) => {
        const el = document.getElementById(id);
        if (! el) return false;
        el.value = valor;
        el.dispatchEvent(new Event('input', { bubbles: true }));
        return true;
    };
    window.__campos = (ids) => ids.map((id) => {
        const el = document.getElementById(id);
        return el ? { id, disabled: el.disabled, value: el.value } : { id, ausente: true };
    });
    // Boton de busqueda que acompana a un campo (hay varios "Buscar en sunat" en la pagina).
    window.__botonCerca = (idCampo, texto) => {
        const contenedor = document.getElementById(idCampo)?.parentElement;
        if (! contenedor) return false;
        const boton = [...contenedor.querySelectorAll('button')]
            .find((item) => item.textContent.toLowerCase().includes(texto.toLowerCase()));
        if (! boton) return false;
        boton.click();
        return true;
    };
    window.__respuestas = { dni: { success: false, error: 'sin datos' }, ruc: { success: false, error: 'sin datos' } };
    return true;
`);

// El formulario solo aparece despues de aceptar las condiciones de la negociacion: se
// pulsa el aviso y se confirma el dialogo hasta que el formulario este en pantalla.
ok(await esperarA(sessionId, `typeof window.axios === "function"`), 'la aplicacion arranca');

let formularioListo = false;

for (let intento = 0; intento < 30 && ! formularioListo; intento++) {
    // El aviso "Entendido, comenzar" abre el dialogo de confirmacion.
    await evaluar(sessionId, `
        const aviso = [...document.querySelectorAll('button')].find((item) => /entendido/i.test(item.textContent));
        if (aviso && ! document.getElementById('number')) aviso.click();
        return true;
    `);

    formularioListo = await evaluar(sessionId, `
        if (! document.getElementById('number')) window.__cerrarAlertas();
        window.__cerrarAlertas();
        return Boolean(document.getElementById('number'));
    `) === true;

    if (! formularioListo) await esperar(1000);
}

ok(formularioListo, 'el formulario aparece tras aceptar las condiciones');

// A partir de aqui las consultas a RENIEC y SUNAT se responden desde el test.
await evaluar(sessionId, `
    const original = window.axios.post.bind(window.axios);
    window.axios.post = (url, payload) => {
        const ruta = String(url);
        if (ruta.includes('validate-dni')) return Promise.resolve({ data: window.__respuestas.dni });
        if (ruta.includes('validate-ruc')) return Promise.resolve({ data: window.__respuestas.ruc });
        return original(url, payload);
    };
    return true;
`);

console.log('== DNI del cliente con datos de RENIEC ==');

await evaluar(sessionId, `
    window.__respuestas.dni = { success: true, source: 'reniec', person: {
        names: 'JUAN', father_lastname: 'PEREZ', mother_lastname: 'GOMEZ', full_name: 'PEREZ GOMEZ JUAN',
    } };
    return window.__escribir('number', '12345678');
`);
await esperar(1200);
await evaluar(sessionId, 'window.__cerrarAlertas(); return true;');

let campos = await evaluar(sessionId, 'return window.__campos(["names", "father_lastname", "mother_lastname"]);');
console.log('  ', JSON.stringify(campos));

ok(campos.every((campo) => campo.disabled === true), 'RENIEC devolvio los nombres: los tres inputs quedan bloqueados');
ok(campos[0]?.value === 'JUAN' && campos[1]?.value === 'PEREZ' && campos[2]?.value === 'GOMEZ', 'los inputs muestran los datos de RENIEC');

console.log('== DNI del cliente sin datos de RENIEC ==');

await evaluar(sessionId, `
    window.__respuestas.dni = { success: false, error: 'No se encontro el documento.' };
    return window.__escribir('number', '87654321');
`);
await esperar(1200);
await evaluar(sessionId, 'window.__cerrarAlertas(); return true;');

campos = await evaluar(sessionId, 'return window.__campos(["names", "father_lastname", "mother_lastname"]);');
console.log('  ', JSON.stringify(campos));

ok(campos.every((campo) => campo.disabled === false), 'sin datos de RENIEC los inputs vuelven a ser editables');
ok(campos.every((campo) => ! campo.value), 'los nombres del documento anterior no se arrastran');

console.log('== Documento RUC del cliente con datos de SUNAT ==');

const abrirTipoDocumento = await evaluar(sessionId, `
    const selector = document.querySelector('.ant-select-selector');
    if (! selector) return false;
    selector.dispatchEvent(new MouseEvent('mousedown', { bubbles: true }));
    return true;
`);
await esperar(700);

const elegirRuc = await evaluar(sessionId, `
    const opcion = [...document.querySelectorAll('.ant-select-item-option')].find((item) => /^ruc/i.test(item.textContent.trim()));
    if (! opcion) return [...document.querySelectorAll('.ant-select-item-option')].map((item) => item.textContent.trim());
    opcion.click();
    return true;
`);
await esperar(900);

ok(elegirRuc === true, 'el tipo de documento se cambia a RUC', elegirRuc === true ? '' : JSON.stringify(elegirRuc) + ' / selector=' + abrirTipoDocumento);

await evaluar(sessionId, `
    window.__respuestas.ruc = { success: true, person: {
        razon_social: 'EMPRESA SAC', direccion: 'AV. SIEMPRE VIVA 123', estado: 'ACTIVO', condicion: 'HABIDO',
    } };
    return window.__escribir('number', '20123456789');
`);
await evaluar(sessionId, 'return window.__botonCerca("number", "Buscar en SUNAT");');
await esperar(1200);
await evaluar(sessionId, 'window.__cerrarAlertas(); return true;');

let camposRuc = await evaluar(sessionId, 'return window.__campos(["full_name", "address"]);');
console.log('  ', JSON.stringify(camposRuc));

ok(camposRuc[0]?.value === 'EMPRESA SAC' && camposRuc[0]?.disabled === true, 'la razon social de SUNAT queda bloqueada');
ok(camposRuc[1]?.value === 'AV. SIEMPRE VIVA 123' && camposRuc[1]?.disabled === true, 'la direccion de SUNAT queda bloqueada');

console.log('== Documento RUC del cliente sin datos de SUNAT ==');

await evaluar(sessionId, `
    window.__respuestas.ruc = { success: false, error: 'No se pudo validar el RUC.' };
    return window.__escribir('number', '20999999999');
`);
await evaluar(sessionId, 'return window.__botonCerca("number", "Buscar en SUNAT");');
await esperar(1200);
await evaluar(sessionId, 'window.__cerrarAlertas(); return true;');

camposRuc = await evaluar(sessionId, 'return window.__campos(["full_name", "address"]);');
console.log('  ', JSON.stringify(camposRuc));

ok(camposRuc.every((campo) => campo.disabled === false), 'sin datos de SUNAT los campos se pueden escribir a mano');

console.log('== RUC de la factura con datos de SUNAT ==');

await evaluar(sessionId, `
    const radio = document.querySelector('input[value="factura"]');
    if (radio) radio.click();
    return true;
`);
await esperar(700);

await evaluar(sessionId, `
    window.__respuestas.ruc = { success: true, person: {
        razon_social: 'EMPRESA FACTURA SAC', direccion: 'JR. FISCAL 456', estado: 'ACTIVO', condicion: 'HABIDO',
        ubigeo: '150101', distrito: 'LIMA', provincia: 'LIMA', departamento: 'LIMA',
    } };
    return window.__escribir('ruc', '20556677889');
`);
await evaluar(sessionId, 'return window.__botonCerca("ruc", "Buscar en sunat");');
await esperar(1200);
await evaluar(sessionId, 'window.__cerrarAlertas(); return true;');

let camposFactura = await evaluar(sessionId, 'return window.__campos(["invoice_razon_social", "invoice_direccion"]);');
console.log('  ', JSON.stringify(camposFactura));

ok(camposFactura[0]?.value === 'EMPRESA FACTURA SAC' && camposFactura[0]?.disabled === true, 'la razon social de la factura queda bloqueada');
ok(camposFactura[1]?.value === 'JR. FISCAL 456' && camposFactura[1]?.disabled === true, 'la direccion fiscal queda bloqueada');

console.log('== RUC de la factura al cambiar de numero ==');

await evaluar(sessionId, `
    window.__respuestas.ruc = { success: false, error: 'No se pudo validar el RUC.' };
    return window.__escribir('ruc', '20999888777');
`);
await esperar(500);
await evaluar(sessionId, 'return window.__botonCerca("ruc", "Buscar en sunat");');
await esperar(1200);
await evaluar(sessionId, 'window.__cerrarAlertas(); return true;');

camposFactura = await evaluar(sessionId, 'return window.__campos(["invoice_razon_social", "invoice_direccion"]);');
console.log('  ', JSON.stringify(camposFactura));

ok(camposFactura.every((campo) => campo.disabled === false), 'con otro RUC sin datos los campos fiscales vuelven a ser editables');

console.log('== DNI de la boleta a nombre de otra persona ==');

await evaluar(sessionId, `
    const radio = document.querySelector('input[value="boleta"]');
    if (radio) radio.click();
    return true;
`);
await esperar(700);

const activarTercero = await evaluar(sessionId, `
    const etiqueta = [...document.querySelectorAll('label')].find((item) => /otra persona/i.test(item.textContent));
    const casilla = etiqueta ? etiqueta.querySelector('input[type="checkbox"]') : null;
    if (! casilla) return false;
    casilla.click();
    return true;
`);
await esperar(700);

if (activarTercero === true) {
    await evaluar(sessionId, `
        window.__respuestas.dni = { success: true, person: { names: 'ANA', father_lastname: 'TORRES', mother_lastname: 'DIAZ', full_name: 'TORRES DIAZ ANA' } };
        return window.__escribir('boleta_numero', '44556677');
    `);
    await evaluar(sessionId, 'return window.__botonCerca("boleta_numero", "Buscar en reniec");');
    await esperar(1200);
    await evaluar(sessionId, 'window.__cerrarAlertas(); return true;');

    let campoBoleta = await evaluar(sessionId, 'return window.__campos(["boleta_numero", "boleta_nombre"]);');
    console.log('  ', JSON.stringify(campoBoleta));

    ok(campoBoleta[1]?.value === 'TORRES DIAZ ANA' && campoBoleta[1]?.disabled === true, 'el nombre de RENIEC de la boleta queda bloqueado');

    await evaluar(sessionId, 'return window.__escribir("boleta_numero", "11223344");');
    await esperar(700);

    campoBoleta = await evaluar(sessionId, 'return window.__campos(["boleta_nombre"]);');
    console.log('  ', JSON.stringify(campoBoleta));

    ok(campoBoleta[0]?.disabled === false && ! campoBoleta[0]?.value, 'con otro DNI el nombre vuelve a ser editable y se vacia');
} else {
    ok(false, 'no se pudo activar la boleta a nombre de otra persona');
}

console.log('== Envio con el RUC consultado sin datos ==');

// La boleta a terceros quedaria incompleta: se desactiva para que el gate que se revisa
// sea el del RUC del cliente (que se consulto y no devolvio datos).
await evaluar(sessionId, `
    const etiqueta = [...document.querySelectorAll('label')].find((item) => /otra persona/i.test(item.textContent));
    const casilla = etiqueta ? etiqueta.querySelector('input[type="checkbox"]') : null;
    if (casilla && casilla.checked) casilla.click();
    return true;
`);

// La razon social la escribe el cliente y acepta los tres documentos legales.
await evaluar(sessionId, `
    window.__escribir('full_name', 'EMPRESA MANUAL SAC');
    ['accepted_terminos', 'accepted_devoluciones', 'accepted_privacidad'].forEach((id) => {
        const casilla = document.getElementById(id);
        if (casilla && ! casilla.checked) casilla.click();
    });
    return true;
`);
await esperar(400);

const botonEnviar = await evaluar(sessionId, `
    const boton = document.querySelector('button[type="submit"]');
    if (! boton) return false;
    boton.click();
    return true;
`);
await esperar(1200);

// No se confirma el resumen: el POST del formulario queda sin disparar.
const titulo = await evaluar(sessionId, `
    const dialogo = document.querySelector('.swal2-title');
    return dialogo ? dialogo.textContent.trim() : null;
`);

ok(botonEnviar === true, 'el boton de enviar esta disponible', String(botonEnviar));
ok(titulo === '¿Estas seguro y de acuerdo?', 'consultado el RUC sin datos, el cliente llega al resumen y puede enviar', 'dialogo: ' + titulo);

ok(excepciones.length === 0, 'la pagina no lanzo excepciones de JavaScript', excepciones.join(' | '));

console.log(fallos.length === 0 ? '\nTodo en orden.' : `\nFallos: ${fallos.length}`);

ws.close();
process.exit(fallos.length === 0 ? 0 : 1);
