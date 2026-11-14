const puppeteer = require('puppeteer');
(async () => {
    const nav = await puppeteer.launch({ headless: 'new', channel: 'chrome', args: ['--no-sandbox', '--hide-scrollbars'] });
    for (const ancho of [768, 1280]) {
        const p = await nav.newPage();
        await p.setViewport({ width: ancho, height: 1000 });
        await p.goto('http://base-aracode.test/empresa', { waitUntil: 'networkidle2' });
        const d = await p.evaluate(() => {
            const sec = document.querySelector('.ara-mv');
            const cont = sec.querySelector('div');
            const rej = document.querySelector('.ara-mv__rejilla');
            const t = document.querySelector('.ara-mv__tarjeta');
            const info = (el) => {
                const cs = getComputedStyle(el);
                const r = el.getBoundingClientRect();
                return {
                    clase: el.className,
                    ancho: Math.round(r.width),
                    izq: Math.round(r.left),
                    max: cs.maxWidth,
                    pad: cs.paddingLeft,
                    cols: cs.gridTemplateColumns,
                };
            };
            return { ventana: window.innerWidth, sec: info(sec), cont: info(cont), rej: info(rej), tarjeta: info(t) };
        });
        console.log(ancho + 'px', JSON.stringify(d, null, 1));
        await p.close();
    }
    await nav.close();
})();
