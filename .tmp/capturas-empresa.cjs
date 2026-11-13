const puppeteer = require('puppeteer');
(async () => {
    const nav = await puppeteer.launch({ headless: 'new', channel: 'chrome', args: ['--no-sandbox', '--hide-scrollbars'] });

    for (const ancho of [360, 768, 1280]) {
        const p = await nav.newPage();
        await p.setViewport({ width: ancho, height: 1000 });
        await p.goto('http://base-aracode.test/empresa', { waitUntil: 'networkidle2', timeout: 60000 });
        await p.evaluate(() => document.fonts && document.fonts.ready);

        const m = await p.evaluate(() => {
            const sec = document.querySelector('.ara-about');
            const texto = document.querySelector('.ara-about__texto');
            const media = document.querySelector('.ara-about__media');
            const img = document.querySelector('.ara-about__img');
            const r = (el) => Math.round(el.getBoundingClientRect().height);
            return {
                altoTexto: r(texto),
                altoMedia: r(media),
                diferencia: Math.abs(r(texto) - r(media)),
                img: Math.round(img.getBoundingClientRect().width) + 'x' + Math.round(img.getBoundingClientRect().height),
                desborde: document.documentElement.scrollWidth - window.innerWidth,
            };
        });
        console.log(ancho + 'px', JSON.stringify(m));

        const el = await p.$('#nosotros');
        await el.evaluate((n) => n.scrollIntoView({ block: 'start', behavior: 'instant' }));
        await new Promise((r) => setTimeout(r, 500));
        await el.screenshot({ path: `.tmp/emp-${ancho}.png` });

        await p.close();
    }

    await nav.close();
})();
