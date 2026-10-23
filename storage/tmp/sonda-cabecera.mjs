/*
 * Mide la cabecera de la landing con el build actual para decidir cómo
 * evitar que el título del torneo se recorte.
 *
 * Uso: node storage/tmp/sonda-cabecera.mjs
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
const salida = [];

for (const ancho of [1920, 1600, 1440, 1280, 1100, 1024, 820, 390]) {
    await page.setViewport({ width: ancho, height: 900, deviceScaleFactor: 1 });
    await page.goto(URL, { waitUntil: 'domcontentloaded', timeout: 60000 });
    await new Promise((resolve) => setTimeout(resolve, 600));

    const datos = await page.evaluate(() => {
        const marca = document.querySelector('.se-header .se-brand');
        const titulo = document.querySelector('.se-header .se-brand__title');
        const nav = document.querySelector('.se-header .se-nav');
        const acciones = document.querySelector('.se-header__actions');
        const cta = document.querySelector('.se-header .se-btn--contact-desktop');
        const info = (el) => (el ? { w: Math.round(el.getBoundingClientRect().width), x: Math.round(el.getBoundingClientRect().x) } : null);
        const estilo = titulo ? getComputedStyle(titulo) : null;

        return {
            contenedor: info(document.querySelector('.se-header__inner')),
            marca: info(marca),
            titulo: info(titulo),
            tituloCompleto: titulo ? titulo.scrollWidth : null,
            fuente: estilo ? estilo.fontSize : null,
            nav: info(nav),
            navGap: nav ? getComputedStyle(nav).columnGap : null,
            acciones: info(acciones),
            ctaVisible: cta ? getComputedStyle(cta).display !== 'none' : false,
        };
    });

    salida.push({ ancho, ...datos });
    console.log(ancho, JSON.stringify(datos));
}

await browser.close();
console.log(JSON.stringify(salida, null, 2));
