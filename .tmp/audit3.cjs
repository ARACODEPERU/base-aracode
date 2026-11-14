const puppeteer = require('puppeteer');
(async () => {
    const nav = await puppeteer.launch({ headless: 'new', channel: 'chrome', args: ['--no-sandbox', '--hide-scrollbars'] });
    for (const ruta of ['/', '/casos-exito', '/portafolio', '/soluciones', '/paginas-web', '/eqipo']) {
        const p = await nav.newPage();
        await p.setViewport({ width: 1280, height: 800 });
        try { await p.goto('http://base-aracode.test' + ruta, { waitUntil: 'networkidle2', timeout: 25000 }); }
        catch (e) { console.log(ruta, 'ERROR'); await p.close(); continue; }
        await new Promise((r) => setTimeout(r, 400));
        const imgs = await p.evaluate(() => Array.from(document.images)
            .filter((i) => !i.getAttribute('loading'))
            .map((i) => ({ src: (i.getAttribute('src') || '').replace('http://base-aracode.test', '').slice(0, 80), cls: i.className.slice(0, 40) })));
        console.log('==', ruta, 'sin loading:', imgs.length);
        imgs.forEach((i) => console.log('   ', i.cls, '|', i.src));
        await p.close();
    }
    await nav.close();
})();
