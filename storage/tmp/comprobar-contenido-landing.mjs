/*
 * Comprueba el contenido real de las secciones de la landing con el build
 * de producción: equipos, fixture desplegable, posiciones, premios,
 * galería, app, bases y contacto.
 *
 * Uso: node storage/tmp/comprobar-contenido-landing.mjs
 */

import puppeteer from 'puppeteer';

const URL = 'http://127.0.0.1:8000/torneos/edicion-2026-2026';
const CHROME = 'C:/Program Files/Google/Chrome/Application/chrome.exe';

const browser = await puppeteer.launch({
    headless: 'new',
    executablePath: CHROME,
    args: ['--no-sandbox'],
});

const page = await browser.newPage();
const errores = [];

page.on('console', (msg) => {
    if (msg.type() === 'error') {
        errores.push(msg.text());
    }
});
page.on('response', (res) => {
    if (res.status() >= 400) {
        errores.push(`HTTP ${res.status()}: ${res.url()}`);
    }
});

await page.setViewport({ width: 1440, height: 900, deviceScaleFactor: 0.6 });
await page.goto(URL, { waitUntil: 'networkidle2', timeout: 60000 });
await new Promise((resolve) => setTimeout(resolve, 6800));

const antes = await page.evaluate(() => {
    const texto = (sel) => [...document.querySelectorAll(sel)].map((el) => el.textContent.trim().replace(/\s+/g, ' '));

    return {
        equipos: texto('.se-team-card__name'),
        fechas: texto('.se-round summary'),
        partidosVisibles: document.querySelectorAll('.se-match').length,
        filasPosiciones: texto('.se-table tbody tr'),
        premios: texto('.se-prize-card'),
        contacto: texto('.se-contact-card'),
        galeria: document.querySelectorAll('.se-gallery__item').length,
        seccionApp: !!document.querySelector('#app'),
        enlacesBases: [...document.querySelectorAll('a')]
            .filter((a) => a.textContent.trim() === 'Bases')
            .map((a) => a.getAttribute('href')),
    };
});

// Desplegar la primera fecha del fixture
await page.evaluate(() => {
    const primera = document.querySelector('.se-round');
    if (primera) {
        primera.open = true;
    }
});
await new Promise((resolve) => setTimeout(resolve, 700));

const despues = await page.evaluate(() => ({
    partidosPrimeraFecha: [...document.querySelectorAll('.se-round[open] .se-match')].map((el) =>
        el.textContent.trim().replace(/\s+/g, ' ')
    ),
}));

await page.evaluate(() => document.querySelector('#fixture')?.scrollIntoView({ block: 'start' }));
await new Promise((resolve) => setTimeout(resolve, 600));
await page.screenshot({ path: 'storage/tmp/revision-produccion/fixture-abierto.png' });

console.log(JSON.stringify({ antes, despues, errores }, null, 2));

await browser.close();
