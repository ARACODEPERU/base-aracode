const puppeteer = require('puppeteer');
(async () => {
    const nav = await puppeteer.launch({ headless: 'new', channel: 'chrome', args: ['--no-sandbox', '--hide-scrollbars'] });
    for (const ruta of ['/', '/plantillas', '/casos-exito', '/equipo']) {
        const p = await nav.newPage();
        const errores = [];
        p.on('console', (m) => { if (m.type() === 'error') errores.push(m.text().slice(0, 80)); });
        await p.setViewport({ width: 1280, height: 800 });
        await p.goto('http://base-aracode.test' + ruta, { waitUntil: 'networkidle2' });
        await p.evaluate(async () => {
            const alto = document.body.scrollHeight;
            for (let y = 0; y < alto; y += 500) {
                window.scrollTo(0, y);
                await new Promise((r) => setTimeout(r, 60));
            }
            await new Promise((r) => setTimeout(r, 1200));
            window.scrollTo(0, document.body.scrollHeight);
        });
        await new Promise((r) => setTimeout(r, 900));
        const r = await p.evaluate(() => {
            const imgs = Array.from(document.images);
            return {
                total: imgs.length,
                rotas: imgs.filter((i) => !i.complete || i.naturalWidth === 0).map((i) => (i.getAttribute('src') || '').slice(-45)),
                lazy: imgs.filter((i) => i.getAttribute('loading') === 'lazy').length,
            };
        });
        console.log(ruta, JSON.stringify(r), 'errores consola:', errores.length);
        await p.close();
    }
    await nav.close();
})();
