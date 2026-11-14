const puppeteer = require('puppeteer');
(async () => {
    const nav = await puppeteer.launch({ headless: 'new', channel: 'chrome', args: ['--no-sandbox', '--hide-scrollbars'] });

    for (const [ancho, alto] of [[1280, 1000], [390, 900]]) {
        const p = await nav.newPage();
        await p.setViewport({ width: ancho, height: alto });
        await p.emulateMediaFeatures([{ name: 'prefers-reduced-motion', value: 'no-preference' }]);
        await p.goto('http://base-aracode.test/plantillas', { waitUntil: 'networkidle2' });
        await p.evaluate(() => document.fonts && document.fonts.ready);
        await new Promise((r) => setTimeout(r, 800));
        await p.screenshot({ path: `.tmp/plt-antes-${ancho}.png` });

        const m = await p.evaluate(() => {
            const cajas = Array.from(document.querySelectorAll('.plt-categorias .plt-chip'));
            const hero = document.querySelector('.plt-hero').getBoundingClientRect();
            const h2 = document.querySelector('.plt-rejilla').previousElementSibling;
            const rej = document.querySelector('.plt-rejilla').getBoundingClientRect();
            return {
                hero: Math.round(hero.height),
                chips: cajas.length,
                altoChips: Math.round(document.querySelector('.plt-categorias').getBoundingClientRect().height),
                altoHeader: Math.round(h2.getBoundingClientRect().height),
                rejilla: Math.round(rej.width),
                tarjetas: document.querySelectorAll('.plt-card').length,
                altoTarjeta: Math.round(document.querySelector('.plt-card').getBoundingClientRect().height),
                desborde: document.documentElement.scrollWidth - window.innerWidth,
                scrollChips: document.querySelector('.plt-categorias').scrollWidth - document.querySelector('.plt-categorias').clientWidth,
            };
        });
        console.log(ancho + 'px', JSON.stringify(m));
        await p.close();
    }

    await nav.close();
})();
