const puppeteer = require('puppeteer');
(async () => {
    const nav = await puppeteer.launch({ headless: 'new', channel: 'chrome', args: ['--no-sandbox', '--hide-scrollbars'] });

    // 1) Movimiento normal: la secuencia debe avanzar 0 → 1 → 2.
    let p = await nav.newPage();
    await p.setViewport({ width: 1280, height: 900 });
    await p.emulateMediaFeatures([{ name: 'prefers-reduced-motion', value: 'no-preference' }]);
    await p.goto('http://base-aracode.test/empresa', { waitUntil: 'networkidle2' });
    await p.evaluate(() => document.querySelector('.ara-flujo').scrollIntoView({ block: 'center' }));

    const reducePorDefecto = await p.evaluate(() => window.matchMedia('(prefers-reduced-motion: reduce)').matches);
    const secuencia = [];
    for (let i = 0; i < 9; i++) {
        secuencia.push(await p.evaluate(() => Array.prototype.indexOf.call(
            document.querySelectorAll('[data-flujo-paso]'),
            document.querySelector('.ara-flujo__paso.is-activo')
        )));
        await new Promise((r) => setTimeout(r, 700));
    }
    console.log('reduce por defecto:', reducePorDefecto, '| secuencia:', JSON.stringify(secuencia));
    await p.close();

    // 2) Movimiento reducido: debe quedarse en la primera etapa.
    p = await nav.newPage();
    await p.setViewport({ width: 1280, height: 900 });
    await p.emulateMediaFeatures([{ name: 'prefers-reduced-motion', value: 'reduce' }]);
    await p.goto('http://base-aracode.test/empresa', { waitUntil: 'networkidle2' });
    await p.evaluate(() => document.querySelector('.ara-flujo').scrollIntoView({ block: 'center' }));
    const quieto = [];
    for (let i = 0; i < 4; i++) {
        quieto.push(await p.evaluate(() => Array.prototype.indexOf.call(
            document.querySelectorAll('[data-flujo-paso]'),
            document.querySelector('.ara-flujo__paso.is-activo')
        )));
        await new Promise((r) => setTimeout(r, 900));
    }
    console.log('con reduce:', JSON.stringify(quieto));
    await p.close();

    // 3) Medidas y capturas con movimiento normal.
    for (const ancho of [360, 768, 1280]) {
        p = await nav.newPage();
        await p.setViewport({ width: ancho, height: 1000 });
        await p.emulateMediaFeatures([{ name: 'prefers-reduced-motion', value: 'no-preference' }]);
        await p.goto('http://base-aracode.test/empresa', { waitUntil: 'networkidle2' });
        await p.evaluate(() => document.fonts && document.fonts.ready);
        await p.evaluate(async () => {
            const sec = document.querySelector('.ara-about');
            for (let y = sec.offsetTop - 500; y < sec.offsetTop + sec.offsetHeight; y += 280) {
                window.scrollTo(0, y);
                await new Promise((r) => setTimeout(r, 110));
            }
            window.scrollTo(0, sec.offsetTop - 40);
            await new Promise((r) => setTimeout(r, 500));
        });

        const m = await p.evaluate(() => {
            const f = document.querySelector('.ara-flujo');
            const t = document.querySelector('.ara-about__texto');
            const r = f.getBoundingClientRect();
            return {
                flujo: Math.round(r.width) + 'x' + Math.round(r.height),
                texto: Math.round(t.getBoundingClientRect().height),
                recortado: f.scrollHeight > f.clientHeight + 1,
                desborde: document.documentElement.scrollWidth - window.innerWidth,
            };
        });
        console.log(ancho + 'px', JSON.stringify(m));

        const el = await p.$('#nosotros');
        await el.screenshot({ path: `.tmp/flujo-${ancho}.png` });
        await p.close();
    }

    await nav.close();
})();
