const puppeteer = require('puppeteer');
(async () => {
    const nav = await puppeteer.launch({ headless: 'new', channel: 'chrome', args: ['--no-sandbox'] });
    const p = await nav.newPage();
    await p.setViewport({ width: 1280, height: 900 });

    for (const url of ['http://base-aracode.test/pichanguero', 'http://base-aracode.test/clinica']) {
        await p.goto(url, { waitUntil: 'networkidle2' });
        const info = await p.evaluate(() => {
            const h = Array.from(document.querySelectorAll('h3')).find((x) => /Lleva el torneo|Lleva tu consultorio|en el bolsillo|tu clínica/i.test(x.textContent));
            if (!h) return { url: location.pathname, bloque: null };
            const caja = h.parentElement;
            return {
                url: location.pathname,
                clases: caja.className,
                fondo: getComputedStyle(caja).backgroundColor,
                colorH3: getComputedStyle(h).color,
            };
        });
        console.log(JSON.stringify(info));
    }
    await nav.close();
})();
