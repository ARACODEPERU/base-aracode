const puppeteer = require('puppeteer');

(async () => {
    const nav = await puppeteer.launch({ headless: 'new', channel: 'chrome', args: ['--no-sandbox', '--hide-scrollbars'] });

    for (const ancho of [360, 768, 1280]) {
        const p = await nav.newPage();
        const errores = [];
        p.on('console', (m) => { if (m.type() === 'error') errores.push(m.text()); });
        p.on('pageerror', (e) => errores.push(String(e)));
        await p.setViewport({ width: ancho, height: 900 });

        // Llega desde la portada pulsando «Leer más» de la tarjeta, como el visitante.
        await p.goto('http://base-aracode.test/', { waitUntil: 'networkidle2' });
        const hrefTarjeta = await p.evaluate(() => {
            const card = Array.from(document.querySelectorAll('.ara-product-card')).find((c) => /PICHANGUERO/i.test(c.textContent));
            return card.querySelector('.ara-product-cta').getAttribute('href');
        });
        await Promise.all([
            p.waitForNavigation({ waitUntil: 'networkidle2' }),
            p.evaluate(() => {
                const card = Array.from(document.querySelectorAll('.ara-product-card')).find((c) => /PICHANGUERO/i.test(c.textContent));
                card.querySelector('.ara-product-cta').click();
            }),
        ]);

        // Dispara las animaciones de entrada antes de la captura.
        const alto = await p.evaluate(() => document.body.scrollHeight);
        for (let y = 0; y < alto; y += 500) { await p.evaluate((v) => window.scrollTo(0, v), y); await new Promise((r) => setTimeout(r, 60)); }
        await p.evaluate(() => window.scrollTo(0, 0));
        await new Promise((r) => setTimeout(r, 700));

        const datos = await p.evaluate(() => {
            const boton = Array.from(document.querySelectorAll('a')).find((a) => /Ir al sitio de/i.test(a.textContent));
            return {
                url: location.href,
                titulo: document.title,
                h1: document.querySelector('h1')?.textContent.trim().replace(/\s+/g, ' '),
                desborde: document.documentElement.scrollWidth - window.innerWidth,
                oficial: boton ? { href: boton.getAttribute('href'), target: boton.getAttribute('target') } : null,
            };
        });
        await p.screenshot({ path: `.tmp/ficha-pichanguero-${ancho}.png`, fullPage: true });
        console.log(ancho, JSON.stringify({ hrefTarjeta, ...datos, errores }));
        await p.close();
    }
    await nav.close();
})();
