/*
 * Comprueba la galería real (6 archivos en 3 fechas) y su lightbox con el
 * build de producción.
 *
 * Uso: node storage/tmp/comprobar-galeria-landing.mjs
 */

import puppeteer from 'puppeteer';

const URL = 'http://127.0.0.1:8000/torneos/edicion-2026-2026';
const CHROME = 'C:/Program Files/Google/Chrome/Application/chrome.exe';

const browser = await puppeteer.launch({
    headless: 'new',
    executablePath: CHROME,
    args: ['--no-sandbox', '--hide-scrollbars'],
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

await page.setViewport({ width: 1440, height: 900, deviceScaleFactor: 0.55 });
await page.goto(URL, { waitUntil: 'networkidle2', timeout: 60000 });
await new Promise((resolve) => setTimeout(resolve, 6800));

const datos = await page.evaluate(() => ({
    grupos: [...document.querySelectorAll('.se-gallery__day-title')].map((el) =>
        el.textContent.trim().replace(/\s+/g, ' ')
    ),
    items: document.querySelectorAll('.se-gallery__item').length,
    imagenesCargadas: [...document.querySelectorAll('.se-gallery__item img')].filter((img) => img.naturalWidth > 0)
        .length,
    etiquetas: [...document.querySelectorAll('.se-gallery__tag')].map((el) => el.textContent.trim()),
}));

await page.evaluate(() => document.querySelector('#galeria')?.scrollIntoView({ block: 'start' }));
await new Promise((resolve) => setTimeout(resolve, 700));
await page.screenshot({ path: 'storage/tmp/revision-produccion/galeria.png' });

await page.click('.se-gallery__item');
await new Promise((resolve) => setTimeout(resolve, 600));
const lightbox = await page.evaluate(() => {
    const caja = document.querySelector('.se-lightbox');
    const img = document.querySelector('.se-lightbox__media img');
    return {
        abierto: caja?.classList.contains('is-open'),
        visible: caja ? getComputedStyle(caja).visibility : null,
        imagenCargada: img ? img.naturalWidth > 0 : false,
        etiqueta: document.querySelector('.se-lightbox__label')?.textContent.trim() ?? null,
    };
});
await page.screenshot({ path: 'storage/tmp/revision-produccion/galeria-lightbox.png' });

console.log(JSON.stringify({ datos, lightbox, errores }, null, 2));

await browser.close();
