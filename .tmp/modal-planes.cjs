const puppeteer = require('puppeteer');

const estado = (p) => p.evaluate(() => {
    const modal = document.getElementById('asesoriaModal');
    const servicio = document.getElementById('asesoriaServicio');
    const mensaje = document.getElementById('asesoriaMensaje');
    const wa = document.getElementById('asesoriaWhatsapp');
    return {
        abierto: !modal.hidden,
        servicio: servicio.value,
        mensaje: mensaje.value,
        whatsapp: wa ? decodeURIComponent(wa.href) : '',
        waVisible: wa ? wa.offsetParent !== null : false,
        foco: document.activeElement ? document.activeElement.id : '',
    };
});

(async () => {
    const nav = await puppeteer.launch({ headless: 'new', channel: 'chrome', args: ['--no-sandbox', '--hide-scrollbars'] });

    // 1. Planes de /paginas-web
    for (const [ancho, alto] of [[1280, 900], [768, 1000], [360, 900]]) {
        const p = await nav.newPage();
        await p.setViewport({ width: ancho, height: alto });
        await p.emulateMediaFeatures([{ name: 'prefers-reduced-motion', value: 'no-preference' }]);
        await p.goto('http://base-aracode.test/paginas-web', { waitUntil: 'networkidle2' });
        await p.evaluate(() => document.fonts && document.fonts.ready);
        await p.evaluate(async () => {
            const boton = document.querySelectorAll('a[data-asesoria-servicio="web"]')[0];
            boton.scrollIntoView({ block: 'center' });
            await new Promise((r) => setTimeout(r, 700));
        });
        await p.evaluate(() => document.querySelectorAll('a[data-asesoria-servicio="web"]')[0].click());
        await new Promise((r) => setTimeout(r, 500));

        console.log('plan ' + ancho + 'px', JSON.stringify(await estado(p)));
        await p.screenshot({ path: `.tmp/plan-modal-${ancho}.png` });

        // Escribir en el mensaje: el enlace de WhatsApp lo sigue.
        await p.type('#asesoriaMensaje', ' Mi nombre es Ana.');
        await new Promise((r) => setTimeout(r, 250));
        const trasEscribir = await estado(p);
        console.log('  con texto añadido, whatsapp termina en:', trasEscribir.whatsapp.slice(-60));
        await p.close();
    }

    // 2. Ficha de plantilla: mismo modal con la plantilla cargada.
    const p = await nav.newPage();
    await p.setViewport({ width: 1280, height: 900 });
    await p.goto('http://base-aracode.test/plantillas/clinica-dental', { waitUntil: 'networkidle2' });
    await p.evaluate(async () => {
        const b = document.querySelector('a[data-asesoria-servicio="web"]');
        b.scrollIntoView({ block: 'center' });
        await new Promise((r) => setTimeout(r, 500));
        b.click();
    });
    await new Promise((r) => setTimeout(r, 500));
    console.log('ficha 1280px', JSON.stringify(await estado(p)));
    await p.close();

    // 3. Segundo clic en otro plan: el mensaje se reemplaza, no se duplica.
    const q = await nav.newPage();
    await q.setViewport({ width: 1280, height: 900 });
    await q.goto('http://base-aracode.test/paginas-web', { waitUntil: 'networkidle2' });
    const botones = await q.$$('a[data-asesoria-servicio="web"]');
    await q.evaluate(() => document.querySelectorAll('a[data-asesoria-servicio="web"]')[0].click());
    await new Promise((r) => setTimeout(r, 300));
    await q.click('[data-asesoria-cerrar]');
    await new Promise((r) => setTimeout(r, 300));
    await q.evaluate(() => document.querySelectorAll('a[data-asesoria-servicio="web"]')[1].click());
    await new Promise((r) => setTimeout(r, 300));
    console.log('segundo plan', JSON.stringify(await estado(q)));
    await q.close();

    await nav.close();
})();
