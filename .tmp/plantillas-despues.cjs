const puppeteer = require('puppeteer');
(async () => {
    const nav = await puppeteer.launch({ headless: 'new', channel: 'chrome', args: ['--no-sandbox', '--hide-scrollbars'] });

    for (const [ancho, alto] of [[1280, 1000], [768, 900], [390, 900]]) {
        const p = await nav.newPage();
        await p.setViewport({ width: ancho, height: alto });
        await p.emulateMediaFeatures([{ name: 'prefers-reduced-motion', value: 'no-preference' }]);
        await p.goto('http://base-aracode.test/plantillas', { waitUntil: 'networkidle2' });
        await p.evaluate(() => document.fonts && document.fonts.ready);
        await p.evaluate(async () => {
            window.scrollTo(0, 0);
            await new Promise((r) => setTimeout(r, 300));
            window.scrollTo(0, 360);
            await new Promise((r) => setTimeout(r, 700));
        });
        await p.screenshot({ path: `.tmp/plt-desp-${ancho}.png` });

        const m = await p.evaluate(() => {
            const cabs = document.querySelector('.plt-catalogo__cabecera').getBoundingClientRect();
            const chips = document.querySelector('.plt-categorias');
            const rc = chips.getBoundingClientRect();
            const rej = document.querySelector('.plt-rejilla').getBoundingClientRect();
            return {
                cabecera: Math.round(cabs.height),
                chipsAlto: Math.round(rc.height),
                chipsCorto: chips.scrollWidth > chips.clientWidth + 1,
                huecoCabeceraChips: Math.round(rc.top - cabs.bottom),
                huecoChipsRejilla: Math.round(rej.top - rc.bottom),
                anchoTarjeta: Math.round(document.querySelector('.plt-card').getBoundingClientRect().width),
                altoTarjeta: Math.round(document.querySelector('.plt-card').getBoundingClientRect().height),
                filaMeta: Math.round(document.querySelector('.plt-card__chips').getBoundingClientRect().height),
                desborde: document.documentElement.scrollWidth - window.innerWidth,
            };
        });
        console.log(ancho + 'px', JSON.stringify(m));
        await p.close();
    }

    await nav.close();
})();
