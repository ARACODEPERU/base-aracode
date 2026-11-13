const puppeteer = require('puppeteer');

const rutas = ['/', '/empresa', '/soluciones', '/soluciones/desarrollo', '/soluciones/kapta',
    '/soluciones/facturacion', '/paginas-web', '/plantillas', '/plantillas/clinica-dental',
    '/casos-exito', '/blog', '/equipo', '/faq', '/contacto', '/lms', '/sitios-webs',
    '/tienda-online', '/e-learning', '/portafolio', '/precios'];

(async () => {
    const nav = await puppeteer.launch({ headless: 'new', channel: 'chrome', args: ['--no-sandbox', '--hide-scrollbars'] });
    const filas = [];

    for (const ruta of rutas) {
        const p = await nav.newPage();
        await p.setViewport({ width: 1280, height: 800 });
        try {
            await p.goto('http://base-aracode.test' + ruta, { waitUntil: 'networkidle2', timeout: 30000 });
        } catch (e) {
            filas.push({ ruta, error: 'no carga' });
            await p.close();
            continue;
        }
        await new Promise((r) => setTimeout(r, 600));
        const imgs = await p.evaluate(() => Array.from(document.images).map((i) => ({
            src: i.getAttribute('src') || i.currentSrc,
            loading: i.getAttribute('loading') || '',
            top: Math.round(i.getBoundingClientRect().top + window.scrollY),
            alta: i.complete && i.naturalWidth > 0,
            ancho: Math.round(i.getBoundingClientRect().width),
            alto: Math.round(i.getBoundingClientRect().height),
        })).filter((i) => i.ancho > 0 && i.alto > 0));

        filas.push({ ruta, imgs });
        await p.close();
    }

    let sinLazy = 0, total = 0;
    for (const f of filas) {
        if (f.error) { console.log('ERROR', f.ruta); continue; }
        const problema = f.imgs.filter((i) => i.loading !== 'lazy' && i.top > 900);
        total += f.imgs.length;
        sinLazy += problema.length;
        console.log('==', f.ruta, '| imgs:', f.imgs.length, '| sin lazy bajo el pliegue:', problema.length);
        problema.forEach((i) => console.log('   ', i.top, i.src.replace('http://base-aracode.test', '')));
        f.imgs.filter((i) => i.top <= 900 && i.loading !== 'lazy').forEach((i) => console.log('   (arriba)', i.top, i.src.replace('http://base-aracode.test', '')));
    }
    console.log('TOTAL imgs:', total, 'sin lazy bajo el pliegue:', sinLazy);
    await nav.close();
})();
