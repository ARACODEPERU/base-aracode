const puppeteer = require('puppeteer');
(async () => {
    const nav = await puppeteer.launch({ headless: 'new', channel: 'chrome', args: ['--no-sandbox', '--hide-scrollbars'] });

    for (const ancho of [360, 768, 1024, 1280, 1440, 1920]) {
        const p = await nav.newPage();
        await p.setViewport({ width: ancho, height: 1000 });
        await p.emulateMediaFeatures([{ name: 'prefers-reduced-motion', value: 'no-preference' }]);
        await p.goto('http://base-aracode.test/empresa', { waitUntil: 'networkidle2' });
        await p.evaluate(() => document.fonts && document.fonts.ready);
        await p.evaluate(() => document.querySelector('.ara-about').scrollIntoView({ block: 'start' }));

        const m = await p.evaluate(() => {
            const texto = document.querySelector('.ara-about__texto').getBoundingClientRect();
            const flujo = document.querySelector('.ara-flujo').getBoundingClientRect();
            const media = document.querySelector('.ara-about__media').getBoundingClientRect();
            return {
                texto: Math.round(texto.height),
                lienzo: Math.round(flujo.height),
                columnaDerecha: Math.round(media.height),
                diferencia: Math.round(Math.abs(texto.height - media.height)),
                recortado: document.querySelector('.ara-flujo').scrollHeight > document.querySelector('.ara-flujo').clientHeight + 1,
                desborde: document.documentElement.scrollWidth - window.innerWidth,
            };
        });
        console.log(ancho + 'px', JSON.stringify(m));

        if (ancho === 1440 || ancho === 1280 || ancho === 768) {
            const el = await p.$('#nosotros');
            await el.screenshot({ path: `.tmp/alt-${ancho}.png` });
        }
        await p.close();
    }

    await nav.close();
})();
