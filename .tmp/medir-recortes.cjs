const puppeteer = require('puppeteer');
(async () => {
    const nav = await puppeteer.launch({ headless: 'new', channel: 'chrome', args: ['--no-sandbox', '--hide-scrollbars'] });
    for (const ancho of [360, 768, 1280]) {
        const p = await nav.newPage();
        await p.setViewport({ width: ancho, height: 1000 });
        await p.goto('http://base-aracode.test/', { waitUntil: 'networkidle2' });
        await p.evaluate(() => document.fonts && document.fonts.ready);
        const r = await p.evaluate(() => {
            const d = document.querySelector('.ara-valor__tab[aria-selected="true"] .ara-valor__desc');
            const cards = Array.from(document.querySelectorAll('.ara-product-card--sobrepuesta'));
            return {
                desc: d ? { alto: d.clientHeight, contenido: d.scrollHeight, cortada: d.scrollHeight > d.clientHeight + 1 } : null,
                tarjetas: cards.map((c) => {
                    const pie = c.querySelector('.ara-product-pie');
                    const contenido = c.querySelector('.ara-product-content');
                    return {
                        sobraX: pie.scrollWidth - pie.clientWidth,
                        altoTarjeta: Math.round(c.getBoundingClientRect().height),
                        altoContenido: Math.round(contenido.getBoundingClientRect().height + parseFloat(getComputedStyle(contenido).paddingTop) + parseFloat(getComputedStyle(contenido).paddingBottom)),
                        seSaleAbajo: Math.round(contenido.getBoundingClientRect().bottom - c.getBoundingClientRect().bottom),
                    };
                }),
            };
        });
        console.log(ancho + 'px', JSON.stringify(r));
        await p.close();
    }
    await nav.close();
})();
