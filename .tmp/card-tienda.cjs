const puppeteer = require('puppeteer');
(async () => {
    const nav = await puppeteer.launch({ headless: 'new', channel: 'chrome', args: ['--no-sandbox', '--hide-scrollbars'] });
    for (const [ancho, alto] of [[1280, 1000], [360, 1000]]) {
        const p = await nav.newPage();
        await p.setViewport({ width: ancho, height: alto });
        await p.emulateMediaFeatures([{ name: 'prefers-reduced-motion', value: 'no-preference' }]);
        await p.goto('http://base-aracode.test/plantillas', { waitUntil: 'networkidle2' });
        await p.evaluate(() => document.fonts && document.fonts.ready);
        await p.evaluate(async () => {
            for (let y = 0; y < 1600; y += 200) { window.scrollTo(0, y); await new Promise((r) => setTimeout(r, 80)); }
            window.scrollTo(0, 900);
            await new Promise((r) => setTimeout(r, 900));
        });
        const datos = await p.evaluate(() => {
            const card = Array.from(document.querySelectorAll('.plt-card')).find((c) => c.textContent.includes('Tienda'));
            const fila = card.querySelector('.plt-card__etiquetas');
            return {
                etiqueta: card.querySelector('.plt-tipo').textContent.trim(),
                altoFila: Math.round(fila.getBoundingClientRect().height),
                nombre: card.querySelector('.plt-card__nombre').textContent.trim(),
            };
        });
        console.log(ancho + 'px', JSON.stringify(datos));
        const card = await p.evaluateHandle(() => Array.from(document.querySelectorAll('.plt-card')).find((c) => c.textContent.includes('Tienda')));
        await card.asElement().screenshot({ path: `.tmp/card-tienda-${ancho}.png` });
        await p.close();
    }
    await nav.close();
})();
