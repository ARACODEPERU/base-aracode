const puppeteer = require('puppeteer');
(async () => {
    const nav = await puppeteer.launch({ headless: 'new', channel: 'chrome', args: ['--no-sandbox', '--hide-scrollbars'] });

    for (const ancho of [360, 768, 1280]) {
        const p = await nav.newPage();
        await p.setViewport({ width: ancho, height: 1000 });
        await p.goto('http://base-aracode.test/empresa', { waitUntil: 'networkidle2' });
        await p.evaluate(() => document.fonts && document.fonts.ready);

        // Recorre la sección para que las apariciones escalonadas se disparen.
        await p.evaluate(async () => {
            const sec = document.querySelector('.ara-about');
            const hasta = sec.offsetTop + sec.offsetHeight;
            for (let y = sec.offsetTop - 600; y < hasta; y += 300) {
                window.scrollTo(0, y);
                await new Promise((r) => setTimeout(r, 120));
            }
            window.scrollTo(0, sec.offsetTop - 40);
            await new Promise((r) => setTimeout(r, 500));
        });

        const m = await p.evaluate(() => {
            const caja = (s) => { const el = document.querySelector(s); const r = el.getBoundingClientRect(); return { w: Math.round(r.width), h: Math.round(r.height), izq: Math.round(r.left), der: Math.round(r.right) }; };
            const texto = document.querySelector('.ara-about__texto');
            const media = document.querySelector('.ara-about__media');
            return {
                texto: Math.round(texto.getBoundingClientRect().height),
                media: Math.round(media.getBoundingClientRect().height),
                img: caja('.ara-about__img'),
                sec: caja('.ara-about'),
                desborde: document.documentElement.scrollWidth - window.innerWidth,
            };
        });
        console.log(ancho + 'px', JSON.stringify(m));

        const el = await p.$('#nosotros');
        await el.screenshot({ path: `.tmp/emp2-${ancho}.png` });
        await p.close();
    }

    // Foco visible del enlace nuevo.
    const p = await nav.newPage();
    await p.setViewport({ width: 1280, height: 900 });
    await p.goto('http://base-aracode.test/empresa', { waitUntil: 'networkidle2' });
    const foco = await p.evaluate(() => {
        const a = document.querySelector('.ara-about__enlace');
        a.focus();
        const cs = getComputedStyle(a);
        return { foco: document.activeElement === a, outline: cs.outlineStyle + ' ' + cs.outlineWidth + ' ' + cs.outlineColor, color: cs.color };
    });
    console.log('foco', JSON.stringify(foco));
    await nav.close();
})();
