// Inventario de tamaños de letra por sección del home, a 1280 y 360 px.
// Agrupa por etiqueta + clase para ver qué se sale de la escala de Servicios.
const puppeteer = require('puppeteer');

const secciones = {
    hero: '.ara-hero',
    clientes: '.ara-clients',
    servicios: '.ara-services',
    productos: '.ara-products',
    valor: '.ara-valor',
    proceso: '.ara-proceso',
    cta: '.ara-cta-auto',
    footer: 'footer',
};

(async () => {
    const navegador = await puppeteer.launch({
        headless: 'new',
        channel: 'chrome',
        args: ['--no-sandbox', '--hide-scrollbars'],
    });

    for (const ancho of [1280, 360]) {
        const pagina = await navegador.newPage();
        await pagina.setViewport({ width: ancho, height: 900, deviceScaleFactor: 1 });
        await pagina.goto('http://base-aracode.test/', { waitUntil: 'networkidle2', timeout: 60000 });
        await pagina.evaluate(() => document.fonts && document.fonts.ready);

        const datos = await pagina.evaluate((mapa) => {
            const salida = {};

            for (const [nombre, selector] of Object.entries(mapa)) {
                const raiz = document.querySelector(selector);
                if (!raiz) { salida[nombre] = 'no está'; continue; }

                const filas = {};
                raiz.querySelectorAll('h1, h2, h3, h4, p, a, span, li').forEach((el) => {
                    // Solo elementos con texto propio visible.
                    const texto = Array.from(el.childNodes)
                        .filter((n) => n.nodeType === 3)
                        .map((n) => n.textContent.trim())
                        .join(' ');
                    if (!texto) return;

                    const cs = getComputedStyle(el);
                    if (cs.display === 'none' || cs.visibility === 'hidden') return;

                    const clave = el.tagName.toLowerCase() + '.' + (el.className || '').toString().split(' ')[0];
                    const valor = Math.round(parseFloat(cs.fontSize) * 10) / 10 + 'px/' + cs.fontWeight;

                    filas[clave] = filas[clave] || { valor, ejemplos: [] };
                    if (filas[clave].ejemplos.length < 2) filas[clave].ejemplos.push(texto.slice(0, 40));
                });

                salida[nombre] = filas;
            }

            return salida;
        }, secciones);

        console.log('=== ' + ancho + 'px ===');
        console.log(JSON.stringify(datos, null, 1));
        await pagina.close();
    }

    await navegador.close();
})();
