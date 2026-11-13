// Medición y capturas de las secciones nuevas con Puppeteer.
// Comprueba que los containers coincidan con los de servicios y productos
// y deja una captura por sección a 360, 768 y 1280 px.
const puppeteer = require('puppeteer');

(async () => {
    const navegador = await puppeteer.launch({
        headless: 'new',
        channel: 'chrome',
        args: ['--no-sandbox', '--hide-scrollbars'],
    });

    const salida = [];

    for (const ancho of [360, 768, 1280]) {
        const pagina = await navegador.newPage();
        await pagina.setViewport({ width: ancho, height: 1000, deviceScaleFactor: 1 });
        await pagina.goto('http://base-aracode.test/', { waitUntil: 'networkidle2', timeout: 60000 });
        await pagina.evaluate(() => document.fonts && document.fonts.ready);

        const medidas = await pagina.evaluate(() => {
            const caja = (selector) => {
                const el = document.querySelector(selector);
                if (!el) return null;
                const r = el.getBoundingClientRect();
                return { izq: Math.round(r.left), der: Math.round(r.right), ancho: Math.round(r.width) };
            };

            return {
                servicios: caja('.ara-services > div'),
                productos: caja('.ara-products > div'),
                valor: caja('.ara-valor__envoltura'),
                proceso: caja('.ara-proceso__envoltura'),
                desborde: document.documentElement.scrollWidth - window.innerWidth,
            };
        });

        for (const [nombre, selector] of [['proceso', '.ara-proceso'], ['valor', '.ara-valor']]) {
            const el = await pagina.$(selector);
            await el.evaluate((nodo) => nodo.scrollIntoView({ block: 'start', behavior: 'instant' }));
            await new Promise((r) => setTimeout(r, 400));
            await el.screenshot({ path: `.tmp/cap-${ancho}-${nombre}.png` });
        }

        salida.push({ ancho, medidas });
        await pagina.close();
    }

    console.log(JSON.stringify(salida, null, 1));
    await navegador.close();
})();
