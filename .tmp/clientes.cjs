const puppeteer = require('puppeteer');
(async () => {
    const nav = await puppeteer.launch({ headless: 'new', channel: 'chrome', args: ['--no-sandbox', '--hide-scrollbars'] });
    const p = await nav.newPage();
    await p.setViewport({ width: 1280, height: 800 });
    await p.goto('http://base-aracode.test/', { waitUntil: 'networkidle2' });
    await p.evaluate(async () => {
        for (let y = 0; y < document.body.scrollHeight; y += 500) { window.scrollTo(0, y); await new Promise((r) => setTimeout(r, 60)); }
        await new Promise((r) => setTimeout(r, 1500));
    });
    const datos = await p.evaluate(() => Array.from(document.images)
        .filter((i) => (i.getAttribute('src') || '').includes('customers/'))
        .slice(0, 6)
        .map((i) => {
            const r = i.getBoundingClientRect();
            const caja = i.closest('[class]');
            return {
                src: (i.getAttribute('src') || '').split('/').pop(),
                completo: i.complete,
                w: i.naturalWidth,
                x: Math.round(r.x), y: Math.round(r.y + window.scrollY),
                contenedor: caja ? caja.className.slice(0, 45) : '',
                lazy: i.getAttribute('loading'),
            };
        }));
    console.log(JSON.stringify(datos, null, 1));
    await nav.close();
})();
