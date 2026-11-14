const puppeteer = require('puppeteer');
(async () => {
    const nav = await puppeteer.launch({ headless: 'new', channel: 'chrome', args: ['--no-sandbox', '--hide-scrollbars'] });
    const p = await nav.newPage();
    await p.setViewport({ width: 390, height: 900 });
    await p.goto('http://base-aracode.test/plantillas', { waitUntil: 'networkidle2' });
    const m = await p.evaluate(() => {
        const caja = document.querySelector('.plt-buscador__campo').getBoundingClientRect();
        const cont = document.querySelector('.plt-hero .max-w-7xl').getBoundingClientRect();
        const chips = document.querySelector('.plt-categorias').getBoundingClientRect();
        const tarjeta = document.querySelector('.plt-card').getBoundingClientRect();
        return {
            contenedor: [Math.round(cont.left), Math.round(cont.right)],
            buscador: [Math.round(caja.left), Math.round(caja.right)],
            chips: [Math.round(chips.left), Math.round(chips.right)],
            tarjeta: [Math.round(tarjeta.left), Math.round(tarjeta.right)],
            desborde: document.documentElement.scrollWidth - window.innerWidth,
        };
    });
    console.log(JSON.stringify(m));
    await nav.close();
})();
