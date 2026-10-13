/*
 * Sincroniza los bloques de producto de las páginas estáticas de la demo
 * «Tienda en línea» con los datos de js/app.js.
 *
 * El catálogo (tienda.html) y los destacados (index.html) están escritos en el
 * HTML —los filtros leen los atributos `data-` de cada tarjeta, no la memoria
 * del JavaScript— así que hay dos copias de la misma información: la de app.js y
 * la del marcado. Este guion reescribe el marcado desde los datos para que no se
 * separen. De paso rehace los atajos de categoría del catálogo y las colecciones
 * del escaparate, que llevan el mismo recuento.
 *
 * Uso: node storage/tmp/casa-tarjetas.mjs
 */

import { readFileSync, writeFileSync } from 'node:fs';

const RAIZ = 'public/demos/tienda-online/';
const DESTACADOS = ['sarten-hierro', 'sabanas-lino', 'taza-gres', 'maceta-ceramica'];

const app = readFileSync(RAIZ + 'js/app.js', 'utf8');

function extraer(nombre) {
    const inicio = app.indexOf(`var ${nombre} = `);
    const fin = app.indexOf('\n    ];', inicio);

    if (inicio < 0 || fin < 0) {
        throw new Error(`No encontré ${nombre} en js/app.js`);
    }

    return eval('(' + app.slice(app.indexOf('[', inicio), fin + 6).trim().replace(/;$/, '') + ')');
}

const CATEGORIAS = extraer('CATEGORIAS');
const PRODUCTOS = extraer('PRODUCTOS');

const escapar = (texto) => String(texto)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');

const normalizar = (texto) => String(texto)
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '');

const nombreCategoria = (clave) => (CATEGORIAS.find((c) => c.clave === clave) || {}).nombre || clave;

const precioTexto = (valor) => 'S/ ' + valor.toLocaleString('es-PE');

const ESTRELLA = '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="m12 3 2.6 5.6 6.1.8-4.5 4.2 1.2 6-5.4-3-5.4 3 1.2-6L3.3 9.4l6.1-.8Z"/></svg>';

const BOLSA = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 8h14l-1.2 12H6.2L5 8Z"/><path d="M9.5 8V6.5a2.5 2.5 0 0 1 5 0V8"/></svg>';

/** Tarjeta de producto: la misma pieza sirve para el catálogo y los destacados. */
function tarjeta(pieza) {
    const insignia = pieza.etiqueta
        ? `\n                    <span class="insignia pieza__insignia">${escapar(pieza.etiqueta)}</span>`
        : '';

    // Solo el descuento va sobre la foto: el precio se lee abajo, con el texto.
    const descuento = pieza.antes
        ? `\n                    <span class="pieza__oferta">−${Math.round((1 - pieza.precio / pieza.antes) * 100)} %</span>`
        : '';

    const precio = `<p class="pieza__precio">`
        + `<strong>${precioTexto(pieza.precio)}</strong>`
        + (pieza.antes ? `<s>${precioTexto(pieza.antes)}</s>` : '')
        + '</p>';

    const buscar = normalizar(
        pieza.nombre + ' ' + pieza.resumen + ' ' + nombreCategoria(pieza.categoria) + ' ' + pieza.slug
    );

    return `            <article class="pieza" data-pieza data-producto="${pieza.slug}" data-categoria="${pieza.categoria}" data-precio="${pieza.precio}" data-nombre="${escapar(pieza.nombre)}" data-buscar="${escapar(buscar)}">
                <a class="pieza__marco" href="producto.html?p=${pieza.slug}">
                    <img src="${pieza.foto}" alt="${escapar(pieza.alt)}" width="900" height="675" loading="lazy" decoding="async">
                    ${descuento + insignia}
                </a>
                <div class="pieza__cuerpo">
                    <p class="pieza__categoria">${escapar(nombreCategoria(pieza.categoria))}</p>
                    <h3 class="pieza__nombre"><a href="producto.html?p=${pieza.slug}">${escapar(pieza.nombre)}</a></h3>
                    <p class="pieza__detalle">${escapar(pieza.resumen)}</p>
                    <div class="pieza__pie">
                        ${precio}
                        <div class="pieza__acciones">
                            <span class="estrellas" role="img" aria-label="${pieza.valoracion} de 5 estrellas">
                                ${ESTRELLA}
                                ${ESTRELLA}
                                ${ESTRELLA}
                                ${ESTRELLA}
                                ${ESTRELLA}
                            </span>
                            <button type="button" class="pieza__agregar" data-agregar="${pieza.slug}" aria-label="Añadir ${escapar(pieza.nombre)} a la canasta">
                                ${BOLSA}
                            </button>
                        </div>
                    </div>
                </div>
            </article>`;
}

/**
 * Reemplaza el interior de un bloque: de la marca de apertura al `</div>` de
 * cierre, que va con ocho espacios de sangría. El cierre se busca por su
 * sangría, así que da igual cómo quedó la vez anterior: el bloque se reescribe
 * entero, con sus saltos de línea en su sitio.
 */
function reemplazarBloque(ruta, marca, contenido) {
    const texto = readFileSync(RAIZ + ruta, 'utf8');
    const inicio = texto.indexOf(marca);

    if (inicio < 0) {
        throw new Error(`No encontré «${marca}» en ${ruta}`);
    }

    // El cierre se busca contando <div> abiertos y cerrados: es la única forma
    // de acertar cuando el bloque lleva tarjetas con divs dentro.
    const abre = texto.indexOf('>', inicio) + 1;
    const etiquetas = /<\/?div\b/g;
    let profundidad = 1;
    let cierre = -1;
    let encontrado;

    etiquetas.lastIndex = abre;

    while ((encontrado = etiquetas.exec(texto)) !== null) {
        profundidad += encontrado[0] === '</div' ? -1 : 1;

        if (profundidad === 0) {
            cierre = encontrado.index;

            break;
        }
    }

    if (cierre < 0) {
        throw new Error(`No encontré el cierre del bloque «${marca}» en ${ruta}`);
    }

    // El cierre se repone con su sangría: se conserva desde el salto de línea.
    const inicioLinea = texto.lastIndexOf('\n', cierre);
    const salida = texto.slice(0, abre) + '\n' + contenido + texto.slice(inicioLinea);
    writeFileSync(RAIZ + ruta, salida);

    return salida.split('\n').length;
}

const catalogo = PRODUCTOS.map(tarjeta).join('\n\n');
const destacados = DESTACADOS
    .map((slug) => PRODUCTOS.find((p) => p.slug === slug))
    .filter(Boolean)
    .map(tarjeta)
    .join('\n\n');

const chips = ['            <a class="chip chip--activo" href="tienda.html" data-chip-categoria="">Todo el catálogo</a>']
    .concat(CATEGORIAS.map((c) => `            <a class="chip" href="tienda.html?categoria=${c.clave}" data-chip-categoria="${c.clave}">${escapar(c.nombre)}</a>`))
    .join('\n');

const colecciones = CATEGORIAS.map((c) => {
    const cuantas = PRODUCTOS.filter((p) => p.categoria === c.clave).length;

    return `            <a class="categoria" href="tienda.html?categoria=${c.clave}">
                <strong>${escapar(c.nombre)}</strong>
                <span>${cuantas} ${cuantas === 1 ? 'producto' : 'productos'} del catálogo</span>
                <span class="categoria__linea" aria-hidden="true"></span>
            </a>`;
}).join('\n');

reemplazarBloque('tienda.html', '<div class="productos" data-rejilla>', catalogo);
reemplazarBloque('tienda.html', '<div class="chips">', chips);
reemplazarBloque('index.html', '<div class="productos productos--destacados revelar">', destacados);
reemplazarBloque('index.html', '<div class="categorias revelar">', colecciones);

console.log(`${PRODUCTOS.length} piezas en el catálogo · ${DESTACADOS.length} destacadas · ${CATEGORIAS.length} categorías`);
