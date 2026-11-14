const puppeteer = require('puppeteer');
const rutas = ['/blog', '/nosotros', '/v2', '/contacto-v2', '/trabaja-con-nosotros',
    '/libro-reclamaciones', '/carrito', '/kapta', '/kirafact', '/pichanguero', '/clinica', '/hombre'];
(async () => {
    const nav = await puppeteer.launch({ headless: 'new', channel: 'chrome', args: ['--no-sandbox', '--hide-scrollbars'] });
    for (const ruta of rutas) {
        const p = await nav.newPage();
        await p.setViewport({ width: 1280, height: 800 });
        try { await p.goto('http://base-aracode.test' + ruta, { waitUntil: 'networkidle2', timeout: 25000 }); }
        catch (e) { console.log(ruta, 'ERROR'); await p.close(); continue; }
        await new Promise((r) => setTimeout(r, 400));
        const datos = await p.evaluate(() => ({
            titulo: document.title.slice(0, 60),
            sinLazy: Array.from(document.querySelectorAll('img')).filter((i) => !i.getAttribute('loading')).map((i) => {
                const r = i.getBoundingClientRect();
                return { src: (i.getAttribute('src') || '').slice(0, 90), top: Math.round(r.top + window.scrollY), w: Math.round(r.width) };
            }),
            iframes: Array.from(document.querySelectorAll('iframe')).map((f) => ({
                src: (f.getAttribute('src') || '').slice(0, 70), loading: f.getAttribute('loading') || 'sin',
            })),
        }));
        console.log('==', ruta, '|', datos.titulo);
        datos.sinLazy.forEach((i) => console.log('   img', i.top, i.w, i.src));
        datos.iframes.forEach((f) => console.log('   iframe', f.loading, f.src));
        await p.close();
    }
    await nav.close();
})();
