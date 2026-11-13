const puppeteer = require('puppeteer');
(async () => {
    const nav = await puppeteer.launch({ headless: 'new', channel: 'chrome', args: ['--no-sandbox', '--hide-scrollbars'] });
    for (const [ancho, alto, scroll] of [[1600, 700, 300], [1024, 800, 330]]) {
        const p = await nav.newPage();
        await p.setViewport({ width: ancho, height: alto });
        await p.emulateMediaFeatures([{ name: 'prefers-reduced-motion', value: 'no-preference' }]);
        await p.goto('http://base-aracode.test/plantillas', { waitUntil: 'networkidle2' });
        await p.evaluate(() => document.fonts && document.fonts.ready);
        await p.evaluate(async (y) => {
            window.scrollTo(0, 0);
            await new Promise((r) => setTimeout(r, 250));
            window.scrollTo(0, y);
            await new Promise((r) => setTimeout(r, 700));
        }, scroll);
        await p.screenshot({ path: `.tmp/plt-fin-${ancho}.png` });
        await p.close();
    }
    await nav.close();
})();
