const puppeteer = require('puppeteer');
(async () => {
    const nav = await puppeteer.launch({ headless: 'new', channel: 'chrome', args: ['--no-sandbox', '--hide-scrollbars'] });

    for (const [ancho, alto] of [[360, 900], [390, 900], [768, 780], [1280, 700]]) {
        const p = await nav.newPage();
        await p.setViewport({ width: ancho, height: alto });
        await p.emulateMediaFeatures([{ name: 'prefers-reduced-motion', value: 'no-preference' }]);
        await p.goto('http://base-aracode.test/empresa', { waitUntil: 'networkidle2' });
        await p.evaluate(() => document.fonts && document.fonts.ready);
        await p.evaluate(async () => {
            const sec = document.querySelector('.ara-mv');
            for (let y = sec.offsetTop - 400; y < sec.offsetTop + sec.offsetHeight; y += 240) {
                window.scrollTo(0, y);
                await new Promise((r) => setTimeout(r, 90));
            }
            window.scrollTo(0, sec.offsetTop - 110);
            await new Promise((r) => setTimeout(r, 500));
        });
        await p.screenshot({ path: `.tmp/mvf-${ancho}.png` });
        await p.close();
    }

    await nav.close();
})();
