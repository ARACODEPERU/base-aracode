const puppeteer = require('puppeteer');
const rutas = ['/', '/empresa', '/soluciones', '/paginas-web', '/plantillas', '/plantillas/clinica-dental',
    '/casos-exito', '/blog', '/equipo', '/faq', '/contacto', '/precios', '/carrito', '/pichanguero', '/kirafact'];
(async () => {
    const nav = await puppeteer.launch({ headless: 'new', channel: 'chrome', args: ['--no-sandbox', '--hide-scrollbars'] });
    let pendientes = 0;

    for (const ruta of rutas) {
        const p = await nav.newPage();
        await p.setViewport({ width: 1280, height: 800 });
        try { await p.goto('http://base-aracode.test' + ruta, { waitUntil: 'networkidle2', timeout: 25000 }); }
        catch (e) { console.log(ruta, 'ERROR'); await p.close(); continue; }
        await new Promise((r) => setTimeout(r, 400));
        const imgs = await p.evaluate(() => Array.from(document.images)
            .filter((i) => !i.getAttribute('loading'))
            .map((i) => ({ src: (i.getAttribute('src') || '').replace('http://base-aracode.test', '').slice(0, 70), cls: i.className.slice(0, 30), top: Math.round(i.getBoundingClientRect().top + window.scrollY) })));
        console.log('==', ruta, '| sin loading:', imgs.length, imgs.map((i) => `${i.cls}@${i.top}`).join(', '));
        pendientes += imgs.filter((i) => i.top > 800).length;
        await p.close();
    }

    // Un artículo del blog: la imagen del cuerpo debe salir con lazy.
    const p = await nav.newPage();
    await p.goto('http://base-aracode.test/blog', { waitUntil: 'networkidle2' });
    const enlace = await p.evaluate(() => {
        const a = Array.from(document.querySelectorAll('a[href*="/blog/"]')).map((x) => x.href).filter(Boolean);
        return a[0] || '';
    });
    if (enlace) {
        await p.goto(enlace, { waitUntil: 'networkidle2' });
        const cuerpo = await p.evaluate(() => {
            const c = document.querySelector('.prose');
            return c ? Array.from(c.querySelectorAll('img')).map((i) => ({
                lazy: i.getAttribute('loading'), dec: i.getAttribute('decoding'), src: (i.getAttribute('src') || '').slice(-40),
            })) : 'sin cuerpo';
        });
        console.log('articulo', enlace.replace('http://base-aracode.test', ''), JSON.stringify(cuerpo));
    } else {
        console.log('articulo: no hay enlaces en /blog');
    }
    await p.close();

    console.log('imgs sin lazy por debajo de 800px:', pendientes);
    await nav.close();
})();
