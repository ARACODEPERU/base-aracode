const puppeteer = require('puppeteer');
(async () => {
    const nav = await puppeteer.launch({ headless: 'new', channel: 'chrome', args: ['--no-sandbox', '--hide-scrollbars'] });
    const p = await nav.newPage();
    await p.setViewport({ width: 360, height: 640 });
    await p.goto('http://base-aracode.test/paginas-web', { waitUntil: 'networkidle2' });
    await p.evaluate(() => document.querySelectorAll('a[data-asesoria-servicio="web"]')[0].click());
    await new Promise((r) => setTimeout(r, 500));

    const antes = await p.evaluate(() => {
        const v = document.querySelector('.ara-modal-ventana');
        return { scroll: v.scrollHeight, visible: v.clientHeight };
    });

    await p.evaluate(async () => {
        const v = document.querySelector('.ara-modal-ventana');
        v.scrollTo(0, v.scrollHeight);
        await new Promise((r) => setTimeout(r, 400));
    });

    const despues = await p.evaluate(() => {
        const v = document.querySelector('.ara-modal-ventana').getBoundingClientRect();
        const boton = document.querySelector('.ara-modal-enviar').getBoundingClientRect();
        const wa = document.getElementById('asesoriaWhatsapp').getBoundingClientRect();
        const dentro = (r) => r.top >= v.top - 1 && r.bottom <= v.bottom + 1;
        return { envioVisible: dentro(boton), waVisible: dentro(wa), waAlto: Math.round(wa.height) };
    });

    console.log('360x640', JSON.stringify({ ...antes, ...despues }));
    await p.screenshot({ path: '.tmp/plan-modal-360-abajo.png' });
    await nav.close();
})();
