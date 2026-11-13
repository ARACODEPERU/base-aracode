const puppeteer = require('puppeteer');
(async () => {
    const nav = await puppeteer.launch({ headless: 'new', channel: 'chrome', args: ['--no-sandbox', '--hide-scrollbars'] });
    const p = await nav.newPage();
    await p.setViewport({ width: 360, height: 900 });
    await p.goto('http://base-aracode.test/empresa', { waitUntil: 'networkidle2' });
    await p.evaluate(() => document.fonts && document.fonts.ready);

    const img = await p.$('.ara-about__media');
    await img.evaluate((n) => n.scrollIntoView({ block: 'center', behavior: 'instant' }));
    await new Promise((r) => setTimeout(r, 900));

    const estado = await p.evaluate(() => {
        const media = document.querySelector('.ara-about__media');
        const img = document.querySelector('.ara-about__img');
        const link = document.querySelector('.ara-about__enlace');
        return {
            opacidadMedia: getComputedStyle(media).opacity,
            opacidadEnlace: getComputedStyle(link).opacity,
            imagenCargada: img.complete && img.naturalWidth > 0,
            altoImagen: Math.round(img.getBoundingClientRect().height),
            altoEnlace: Math.round(link.getBoundingClientRect().height),
        };
    });
    console.log(JSON.stringify(estado));

    await p.screenshot({ path: '.tmp/emp-360-imagen.png' });
    await nav.close();
})();
