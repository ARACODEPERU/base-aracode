const puppeteer = require('puppeteer');
(async () => {
    const nav = await puppeteer.launch({ headless: 'new', channel: 'chrome', args: ['--no-sandbox', '--hide-scrollbars'] });
    const p = await nav.newPage();
    await p.setViewport({ width: 1280, height: 900 });

    let pestanas = 0;
    nav.on('targetcreated', () => pestanas++);

    for (const ruta of ['/', '/soluciones']) {
        await p.goto('http://base-aracode.test' + ruta, { waitUntil: 'networkidle2' });
        const href = await p.evaluate(() => {
            const card = Array.from(document.querySelectorAll('.ara-product-card')).find((c) => /PICHANGUERO/i.test(c.textContent));
            const a = card.querySelector('.ara-product-cta');
            return { href: a.getAttribute('href'), target: a.getAttribute('target') };
        });
        await Promise.all([
            p.waitForNavigation({ waitUntil: 'networkidle2' }),
            p.evaluate(() => {
                const card = Array.from(document.querySelectorAll('.ara-product-card')).find((c) => /PICHANGUERO/i.test(c.textContent));
                card.querySelector('.ara-product-cta').click();
            }),
        ]);
        console.log(ruta, '->', JSON.stringify(href), '|', p.url(), '|', await p.title());
    }
    console.log('pestañas nuevas:', pestanas);
    await nav.close();
})();
