const puppeteer = require('puppeteer');
(async () => {
    const nav = await puppeteer.launch({ headless: 'new', channel: 'chrome', args: ['--no-sandbox', '--hide-scrollbars'] });
    for (const ancho of [360, 390, 421, 768, 1280]) {
        const p = await nav.newPage();
        await p.setViewport({ width: ancho, height: 900 });
        await p.goto('http://base-aracode.test/empresa', { waitUntil: 'networkidle2' });
        const d = await p.evaluate(() => getComputedStyle(document.querySelector('.ara-flujo__marca')).display);
        console.log(ancho + 'px marca:', d);
        await p.close();
    }
    await nav.close();
})();
