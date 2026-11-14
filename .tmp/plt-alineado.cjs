const puppeteer = require('puppeteer');
(async () => {
    const nav = await puppeteer.launch({ headless: 'new', channel: 'chrome', args: ['--no-sandbox', '--hide-scrollbars'] });
    for (const ancho of [1024, 1280, 390]) {
        const p = await nav.newPage();
        await p.setViewport({ width: ancho, height: 900 });
        await p.goto('http://base-aracode.test/plantillas', { waitUntil: 'networkidle2' });
        await p.evaluate(() => document.fonts && document.fonts.ready);
        const m = await p.evaluate(() => {
            const nombres = Array.from(document.querySelectorAll('.plt-card__nombre')).slice(0, 3);
            const metas = Array.from(document.querySelectorAll('.plt-card__chips')).slice(0, 3);
            return {
                yNombres: nombres.map((n) => Math.round(n.getBoundingClientRect().top)),
                altoMeta: metas.map((n) => Math.round(n.getBoundingClientRect().height)),
            };
        });
        console.log(ancho + 'px', JSON.stringify(m));
        if (ancho === 1024) {
            await p.evaluate(async () => { window.scrollTo(0, 330); await new Promise((r) => setTimeout(r, 700)); });
            await p.screenshot({ path: '.tmp/plt-fin-1024b.png' });
        }
        await p.close();
    }
    await nav.close();
})();
