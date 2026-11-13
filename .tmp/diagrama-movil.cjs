const puppeteer = require('puppeteer');
(async () => {
    const nav = await puppeteer.launch({ headless: 'new', channel: 'chrome', args: ['--no-sandbox', '--hide-scrollbars'] });

    for (const ancho of [360, 390]) {
        const p = await nav.newPage();
        await p.setViewport({ width: ancho, height: 900, deviceScaleFactor: 2 });
        await p.emulateMediaFeatures([{ name: 'prefers-reduced-motion', value: 'reduce' }]);
        await p.goto('http://base-aracode.test/empresa', { waitUntil: 'networkidle2' });
        await p.evaluate(() => document.fonts && document.fonts.ready);
        await p.evaluate(() => document.querySelector('.ara-flujo').scrollIntoView({ block: 'center' }));
        await new Promise((r) => setTimeout(r, 700));

        const m = await p.evaluate(() => {
            const f = document.querySelector('.ara-flujo');
            const cs = getComputedStyle(f);
            const pasos = Array.from(document.querySelectorAll('.ara-flujo__paso'));
            const nota = document.querySelector('.ara-flujo__nota');
            const caja = (el) => el.getBoundingClientRect();

            return {
                lienzo: Math.round(caja(f).width) + 'x' + Math.round(caja(f).height),
                padding: cs.paddingTop + ' / ' + cs.paddingLeft,
                gapEntreEtapas: getComputedStyle(document.querySelector('.ara-flujo__pasos')).rowGap,
                altoEtapas: pasos.map((el) => Math.round(caja(el).height)),
                nombreLineas: Array.from(document.querySelectorAll('.ara-flujo__nombre')).map((el) => Math.round(caja(el).height / parseFloat(getComputedStyle(el).lineHeight))),
                distanciaUltimaNota: Math.round(caja(nota).top - caja(pasos[2]).bottom),
                distanciaNotaFondo: Math.round(caja(f).bottom - caja(nota).bottom),
                recortado: f.scrollHeight > f.clientHeight + 1,
                desbordeEtapa: pasos.map((el) => el.scrollWidth - el.clientWidth),
            };
        });
        console.log(ancho + 'px', JSON.stringify(m));

        const el = await p.$('.ara-flujo');
        await el.screenshot({ path: `.tmp/diag-${ancho}.png` });
        await p.close();
    }

    await nav.close();
})();
