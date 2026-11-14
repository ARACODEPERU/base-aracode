const puppeteer = require('puppeteer');
(async () => {
    const nav = await puppeteer.launch({ headless: 'new', channel: 'chrome', args: ['--no-sandbox', '--hide-scrollbars'] });

    for (const ancho of [360, 390, 768, 1280, 1440]) {
        const p = await nav.newPage();
        await p.setViewport({ width: ancho, height: 1000 });
        await p.emulateMediaFeatures([{ name: 'prefers-reduced-motion', value: 'no-preference' }]);
        await p.goto('http://base-aracode.test/empresa', { waitUntil: 'networkidle2' });
        await p.evaluate(() => document.fonts && document.fonts.ready);
        await p.evaluate(async () => {
            const sec = document.querySelector('.ara-mv');
            for (let y = sec.offsetTop - 400; y < sec.offsetTop + sec.offsetHeight; y += 260) {
                window.scrollTo(0, y);
                await new Promise((r) => setTimeout(r, 100));
            }
            window.scrollTo(0, sec.offsetTop - 30);
            await new Promise((r) => setTimeout(r, 500));
        });

        const m = await p.evaluate(() => {
            const rejilla = document.querySelector('.ara-mv__rejilla');
            const tarjetas = Array.from(document.querySelectorAll('.ara-mv__tarjeta'));
            const textos = Array.from(document.querySelectorAll('.ara-mv__texto'));
            const alto = (el) => Math.round(el.getBoundingClientRect().height);
            return {
                columnas: getComputedStyle(rejilla).gridTemplateColumns.split(' ').length,
                altoTarjetas: tarjetas.map(alto),
                diferenciaTarjetas: Math.abs(alto(tarjetas[0]) - alto(tarjetas[1])),
                altoTextos: textos.map(alto),
                anchoTarjeta: Math.round(tarjetas[0].getBoundingClientRect().width),
                desborde: document.documentElement.scrollWidth - window.innerWidth,
            };
        });
        console.log(ancho + 'px', JSON.stringify(m));

        const el = await p.$('#mision');
        await el.screenshot({ path: `.tmp/mv-${ancho}.png` });
        await p.close();
    }

    await nav.close();
})();
