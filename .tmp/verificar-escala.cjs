// Verificación: los tamaños de letra de cada sección deben coincidir con los
// de Servicios, y nada debe desbordar ni recortarse tras el cambio.
const puppeteer = require('puppeteer');

(async () => {
    const navegador = await puppeteer.launch({
        headless: 'new',
        channel: 'chrome',
        args: ['--no-sandbox', '--hide-scrollbars'],
    });

    for (const ancho of [360, 768, 1280]) {
        const pagina = await navegador.newPage();
        await pagina.setViewport({ width: ancho, height: 1000, deviceScaleFactor: 1 });
        await pagina.goto('http://base-aracode.test/', { waitUntil: 'networkidle2', timeout: 60000 });
        await pagina.evaluate(() => document.fonts && document.fonts.ready);

        const datos = await pagina.evaluate(() => {
            const tam = (selector) => {
                const el = document.querySelector(selector);
                if (!el) return null;
                const cs = getComputedStyle(el);
                return Math.round(parseFloat(cs.fontSize) * 10) / 10;
            };

            const desc = document.querySelector('.ara-valor__tab[aria-selected="true"] .ara-valor__desc');
            const pie = document.querySelector('.ara-product-card--sobrepuesta .ara-product-pie');

            return {
                titulos: {
                    servicios: tam('.ara-services h2'),
                    productos: tam('.ara-products h2'),
                    valor: tam('.ara-valor__titulo'),
                    proceso: tam('.ara-proceso__titulo'),
                    cta: tam('.ara-cta-auto__titulo'),
                },
                bajadas: {
                    servicios: tam('.ara-services p.text-lg'),
                    productos: tam('.ara-products p.text-lg'),
                    hero: tam('.ara-hero p'),
                    valor: tam('.ara-valor__bajada'),
                    proceso: tam('.ara-proceso__bajada'),
                    cta: tam('.ara-cta-auto__lead'),
                },
                tarjetas: {
                    servicio: tam('.ara-services h3'),
                    producto: tam('.ara-product-card--sobrepuesta .ara-product-title'),
                    valorTab: tam('.ara-valor__tab-titulo'),
                    pasoTitulo: tam('.ara-proceso__paso-titulo'),
                },
                textos: {
                    servicio: tam('.ara-services .ara-service-card p'),
                    producto: tam('.ara-product-card--sobrepuesta .ara-product-text'),
                    valorDesc: tam('.ara-valor__desc'),
                    pasoTexto: tam('.ara-proceso__paso-texto'),
                },
                etiquetas: {
                    badge: tam('.ara-badge'),
                    cejaValor: tam('.ara-valor__ceja'),
                    cejaProceso: tam('.ara-proceso__ceja'),
                    ctaEyebrow: tam('.ara-cta-auto__eyebrow'),
                    chipProducto: tam('.ara-product-card--sobrepuesta .ara-product-chip'),
                },
                recortes: {
                    descValorCortada: desc ? desc.scrollHeight > desc.clientHeight + 1 : null,
                    pieProductoDesborda: pie ? pie.scrollWidth > pie.clientWidth + 1 : null,
                },
                desborde: document.documentElement.scrollWidth - window.innerWidth,
            };
        });

        console.log('=== ' + ancho + 'px ===');
        console.log(JSON.stringify(datos));

        for (const [nombre, selector] of [['proceso', '.ara-proceso'], ['valor', '.ara-valor'], ['productos', '.ara-products']]) {
            const el = await pagina.$(selector);
            await el.evaluate((n) => n.scrollIntoView({ block: 'start', behavior: 'instant' }));
            await new Promise((r) => setTimeout(r, 400));
            await el.screenshot({ path: `.tmp/tip-${ancho}-${nombre}.png` });
        }

        await pagina.close();
    }

    await navegador.close();
})();
