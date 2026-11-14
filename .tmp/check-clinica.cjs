const puppeteer = require('puppeteer');
(async () => {
    const nav = await puppeteer.launch({ headless: 'new', channel: 'chrome', args: ['--no-sandbox'] });
    const p = await nav.newPage();
    await p.setViewport({ width: 1280, height: 900 });
    await p.goto('http://base-aracode.test/clinica', { waitUntil: 'networkidle2' });
    console.log(JSON.stringify(await p.evaluate(() => {
        const caja = document.querySelector('.ara-card.bg-ara-navy');
        if (!caja) return { existe: false };
        const h = caja.querySelector('h3, h2');
        return { existe: true, fondo: getComputedStyle(caja).backgroundColor, colorTitulo: h ? getComputedStyle(h).color : null, texto: h ? h.textContent.trim() : null };
    })));
    await nav.close();
})();
