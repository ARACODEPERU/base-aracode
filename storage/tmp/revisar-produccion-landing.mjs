/*
 * Revisión de la landing con el build de producción y los datos reales
 * extraídos de avc.pichanguero.com (replicados en la base local).
 *
 * Uso: node storage/tmp/revisar-produccion-landing.mjs
 */

import puppeteer from 'puppeteer';
import { mkdir } from 'node:fs/promises';

const URL = 'http://127.0.0.1:8000/torneos/edicion-2026-2026';
const SALIDA = 'storage/tmp/revision-produccion';
const CHROME = 'C:/Program Files/Google/Chrome/Application/chrome.exe';

await mkdir(SALIDA, { recursive: true });

const browser = await puppeteer.launch({
    headless: 'new',
    executablePath: CHROME,
    args: ['--no-sandbox', '--hide-scrollbars'],
});

const page = await browser.newPage();
const errores = [];
const fallos = [];

page.on('console', (msg) => {
    if (msg.type() === 'error') {
        errores.push(`consola: ${msg.text()}`);
    }
});

page.on('requestfailed', (req) => {
    errores.push(`petición fallida: ${req.url()} (${req.failure()?.errorText})`);
});

page.on('response', (res) => {
    if (res.status() >= 400) {
        errores.push(`HTTP ${res.status()}: ${res.url()}`);
    }
});

const medidas = [];

for (const [ancho, alto] of [[1920, 1080], [1440, 900], [1280, 900], [1100, 900], [1024, 768], [820, 900], [390, 844]]) {
    await page.setViewport({ width: ancho, height: alto, deviceScaleFactor: ancho >= 1024 ? 0.55 : 0.8 });
    await page.goto(URL, { waitUntil: 'networkidle2', timeout: 60000 });
    await new Promise((resolve) => setTimeout(resolve, 6800));

    const datos = await page.evaluate(() => {
        const raiz = getComputedStyle(document.documentElement);
        const h1 = document.querySelector('.se-hero__title');
        const boton = document.querySelector('.se-hero__cta .se-btn--primary');
        const marca = document.querySelector('.se-header .se-brand__title');
        const nav = document.querySelector('.se-header .se-nav');
        const menu = document.querySelector('.se-header .se-menu-toggle');
        const visible = (el) => !!el && getComputedStyle(el).display !== 'none' && el.getBoundingClientRect().width > 0;
        const assets = [...document.querySelectorAll('link[rel="stylesheet"], script[src]')]
            .map((el) => el.getAttribute('href') || el.getAttribute('src'))
            .filter((url) => url && url.includes('/build/assets/'));
        const dev = [...document.querySelectorAll('script[src], link[href]')]
            .some((el) => (el.getAttribute('src') || el.getAttribute('href') || '').includes('5173'));

        return {
            ancho: window.innerWidth,
            desborde: document.documentElement.scrollWidth - document.documentElement.clientWidth,
            alto: document.documentElement.scrollHeight,
            fondo: raiz.getPropertyValue('--se-bg').trim(),
            lima: raiz.getPropertyValue('--se-accent').trim(),
            fuenteCuerpo: getComputedStyle(document.body).fontFamily,
            fuenteTitular: h1 ? getComputedStyle(h1).fontFamily : null,
            boton: boton
                ? {
                      familia: getComputedStyle(boton).fontFamily,
                      mayusculas: getComputedStyle(boton).textTransform,
                      fondo: getComputedStyle(boton).backgroundColor,
                  }
                : null,
            tituloMarca: marca
                ? {
                      ancho: Math.round(marca.getBoundingClientRect().width),
                      completo: marca.scrollWidth,
                      // Con dos líneas permitidas, el recorte real es vertical.
                      recortado: marca.scrollHeight > marca.clientHeight + 1,
                      alto: marca.getBoundingClientRect().height,
                  }
                : null,
            navVisible: visible(nav),
            menuVisible: visible(menu),
            contactoEnNav: [...document.querySelectorAll('.se-nav a')].some((a) => a.getAttribute('href') === '#contacto'),
            assetsBuild: assets.length,
            usaViteDev: dev,
            equipos: document.querySelectorAll('.se-team-card').length,
            premios: document.querySelectorAll('.se-prize-card').length,
            filasTabla: document.querySelectorAll('.se-table tbody tr').length,
            secciones: document.querySelectorAll('.se-section').length,
            enlacesNav: [...document.querySelectorAll('.se-nav a')].map((a) => a.textContent.trim()),
        };
    });

    medidas.push(datos);

    if (datos.desborde !== 0) {
        fallos.push(`desborde horizontal a ${ancho}px: ${datos.desborde}px`);
    }
    if (datos.usaViteDev) {
        fallos.push(`la página usa el servidor de Vite a ${ancho}px`);
    }
    if (datos.assetsBuild === 0) {
        fallos.push(`no se cargaron assets del build a ${ancho}px`);
    }
    // En móvil el nombre se limita a dos líneas de forma intencionada.
    if (ancho >= 820 && datos.tituloMarca?.recortado) {
        fallos.push(`el nombre del torneo se recorta en la cabecera a ${ancho}px`);
    }
    if (ancho >= 1280 && !datos.navVisible) {
        fallos.push(`el menú de escritorio no se ve a ${ancho}px`);
    }
    if (ancho < 1280 && !datos.menuVisible) {
        fallos.push(`no aparece el botón de menú a ${ancho}px`);
    }

    await page.screenshot({ path: `${SALIDA}/hero-${ancho}.png` });

    if (ancho === 1440) {
        for (const [nombre, selector] of [
            ['equipos', '#equipos'],
            ['premios', '#premios'],
            ['posiciones', '#posiciones'],
            ['contacto', '#contacto'],
        ]) {
            await page.evaluate((sel) => document.querySelector(sel)?.scrollIntoView({ block: 'start' }), selector);
            await new Promise((resolve) => setTimeout(resolve, 800));
            await page.screenshot({ path: `${SALIDA}/${nombre}.png` });
        }
    }
}

// Splash y degradado del patrocinador con el build real
await page.setViewport({ width: 1440, height: 900, deviceScaleFactor: 0.5 });
await page.goto(URL, { waitUntil: 'domcontentloaded', timeout: 60000 });
await new Promise((resolve) => setTimeout(resolve, 1200));
await page.screenshot({ path: `${SALIDA}/splash.png` });
const splash = await page.evaluate(() => {
    const strong = document.querySelector('.se-splash__message strong');
    const logo = document.querySelector('.se-splash__logo');
    if (!strong) {
        return null;
    }
    const estilo = getComputedStyle(strong);
    return {
        texto: strong.textContent.trim(),
        recorteTexto: estilo.webkitBackgroundClip || estilo.backgroundClip,
        color: estilo.color,
        logoCargado: logo ? logo.naturalWidth > 0 : false,
        logoUrl: logo ? logo.getAttribute('src') : null,
    };
});

if (!splash || splash.recorteTexto !== 'text') {
    fallos.push('el degradado del patrocinador del splash no recorta sobre el texto');
}
if (!splash?.logoCargado) {
    fallos.push('el logo del splash no carga');
}

// Menú móvil a 820 y 390
const menus = {};
for (const ancho of [820, 390]) {
    await page.setViewport({ width: ancho, height: 844, deviceScaleFactor: 0.8 });
    await page.goto(URL, { waitUntil: 'networkidle2', timeout: 60000 });
    await new Promise((resolve) => setTimeout(resolve, 6800));
    await page.click('.se-menu-toggle');
    await new Promise((resolve) => setTimeout(resolve, 600));
    await page.screenshot({ path: `${SALIDA}/menu-${ancho}.png` });
    menus[ancho] = await page.evaluate(() => {
        const overlay = document.querySelector('.se-mobile-nav');
        return {
            abierto: overlay?.classList.contains('is-open'),
            visible: overlay ? getComputedStyle(overlay).visibility !== 'hidden' : false,
            enlaces: [...document.querySelectorAll('.se-mobile-nav a')].map((a) => a.textContent.trim()),
        };
    });
    if (!menus[ancho].abierto) {
        fallos.push(`el menú móvil no se abre a ${ancho}px`);
    }
}

console.log(JSON.stringify({ medidas, splash, menus, fallos, errores }, null, 2));

await browser.close();
