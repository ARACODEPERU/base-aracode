const puppeteer = require('puppeteer');
(async () => {
    const nav = await puppeteer.launch({ headless: 'new', channel: 'chrome', args: ['--no-sandbox', '--hide-scrollbars'] });

    for (const [ancho, alto] of [[1280, 1000], [768, 1000], [360, 1000]]) {
        const p = await nav.newPage();
        await p.setViewport({ width: ancho, height: alto });
        await p.emulateMediaFeatures([{ name: 'prefers-reduced-motion', value: 'no-preference' }]);
        await p.goto('http://base-aracode.test/plantillas', { waitUntil: 'networkidle2' });
        await p.evaluate(() => document.fonts && document.fonts.ready);
        await p.evaluate(async () => {
            for (let y = 0; y < 1200; y += 200) { window.scrollTo(0, y); await new Promise((r) => setTimeout(r, 80)); }
            window.scrollTo(0, 300);
            await new Promise((r) => setTimeout(r, 600));
        });

        const m = await p.evaluate(() => {
            const cards = Array.from(document.querySelectorAll('.plt-card'));
            const primera = cards[0];
            const media = primera.querySelector('.plt-card__media').getBoundingClientRect();
            const nombre = primera.querySelector('.plt-card__nombre').getBoundingClientRect();
            const etiquetas = primera.querySelectorAll('.plt-card__etiquetas li');
            return {
                tituloDesdeCaptura: Math.round(nombre.top - media.bottom),
                etiquetas: Array.from(etiquetas).map((li) => li.textContent.trim()),
                alturasEtiquetas: Array.from(etiquetas).map((li) => Math.round(li.getBoundingClientRect().height)),
                filasEtiquetas: Math.round(primera.querySelector('.plt-card__etiquetas').getBoundingClientRect().height),
                yNombres: cards.slice(0, 3).map((c) => Math.round(c.querySelector('.plt-card__nombre').getBoundingClientRect().top)),
                desborde: document.documentElement.scrollWidth - window.innerWidth,
            };
        });
        console.log(ancho + 'px', JSON.stringify(m));

        if (ancho === 768) {
            const card = await p.$('.plt-card');
            await card.screenshot({ path: '.tmp/card-768.png' });
        }
        if (ancho === 1280) {
            const card = await p.$('.plt-card');
            await card.screenshot({ path: '.tmp/card-1280.png' });
            await p.screenshot({ path: `.tmp/plt-card-${ancho}.png` });
        }
        if (ancho === 360) await p.screenshot({ path: `.tmp/plt-card-${ancho}.png` });
        await p.close();
    }

    await nav.close();
})();
