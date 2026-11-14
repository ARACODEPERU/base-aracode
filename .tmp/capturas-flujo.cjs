const puppeteer = require('puppeteer');
(async () => {
    const nav = await puppeteer.launch({ headless: 'new', channel: 'chrome', args: ['--no-sandbox', '--hide-scrollbars'] });

    for (const ancho of [360, 768, 1280]) {
        const p = await nav.newPage();
        await p.setViewport({ width: ancho, height: 1000 });
        await p.goto('http://base-aracode.test/empresa', { waitUntil: 'networkidle2' });
        await p.evaluate(() => document.fonts && document.fonts.ready);

        await p.evaluate(async () => {
            const sec = document.querySelector('.ara-about');
            for (let y = sec.offsetTop - 500; y < sec.offsetTop + sec.offsetHeight; y += 300) {
                window.scrollTo(0, y);
                await new Promise((r) => setTimeout(r, 100));
            }
            window.scrollTo(0, sec.offsetTop - 40);
            await new Promise((r) => setTimeout(r, 400));
        });

        const m = await p.evaluate(() => {
            const flujo = document.querySelector('.ara-flujo');
            const texto = document.querySelector('.ara-about__texto');
            const r = flujo.getBoundingClientRect();
            return {
                flujo: Math.round(r.width) + 'x' + Math.round(r.height),
                altoTexto: Math.round(texto.getBoundingClientRect().height),
                desborde: document.documentElement.scrollWidth - window.innerWidth,
                pasos: document.querySelectorAll('[data-flujo-paso]').length,
                activo: document.querySelectorAll('.ara-flujo__paso.is-activo').length,
                recortado: flujo.scrollHeight > flujo.clientHeight + 1,
            };
        });
        console.log(ancho + 'px', JSON.stringify(m));

        const el = await p.$('#nosotros');
        await el.screenshot({ path: `.tmp/flujo-${ancho}.png` });
        await p.close();
    }

    // El recorrido se mueve solo: anota qué etapa está activa cada segundo.
    const p = await nav.newPage();
    await p.setViewport({ width: 1280, height: 900 });
    await p.goto('http://base-aracode.test/empresa', { waitUntil: 'networkidle2' });
    await p.evaluate(() => document.querySelector('.ara-flujo').scrollIntoView({ block: 'center' }));

    const secuencia = [];
    for (let i = 0; i < 8; i++) {
        secuencia.push(await p.evaluate(() => Array.prototype.indexOf.call(
            document.querySelectorAll('[data-flujo-paso]'),
            document.querySelector('.ara-flujo__paso.is-activo')
        )));
        await new Promise((r) => setTimeout(r, 800));
    }
    console.log('secuencia', JSON.stringify(secuencia));

    await nav.close();
})();
