/*
 * Comprueba en el navegador que la cuenta local inicia sesión y llega al
 * panel, y que la edición del torneo es visible.
 *
 * Uso: node storage/tmp/probar-acceso-local.mjs
 */

import puppeteer from 'puppeteer';

const BASE = 'http://127.0.0.1:8000';
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
await page.goto(`${BASE}/login`, { waitUntil: 'networkidle2', timeout: 60000 });
await page.type('input[type="email"]', 'revisor@aracode.test');
await page.type('input[type="password"]', 'Revisor2026!');

await page.evaluate(() => document.querySelector('form')?.requestSubmit());
await page.waitForFunction(() => !location.pathname.startsWith('/login'), { timeout: 30000 });
await new Promise((resolve) => setTimeout(resolve, 2500));

const panel = {
    url: page.url(),
    titulo: await page.title(),
    texto: (await page.evaluate(() => document.body.innerText)).slice(0, 300).replace(/\s+/g, ' '),
};

// Intentar la ruta pública de la landing con sesión iniciada
await page.goto(`${BASE}/torneos/edicion-2026-2026`, { waitUntil: 'networkidle2', timeout: 60000 });
await new Promise((resolve) => setTimeout(resolve, 5500));
const landing = await page.evaluate(() => ({
    status: document.querySelectorAll('.se-team-card').length,
    titulo: document.querySelector('.se-hero__title')?.textContent.trim(),
    esLanding: document.body.classList.contains('se-landing'),
}));

// Listado de ediciones del módulo (Inertia)
await page.goto(`${BASE}/socialevents/editions`, { waitUntil: 'networkidle2', timeout: 60000 });
await new Promise((resolve) => setTimeout(resolve, 2500));
const modulo = await page.evaluate(() => ({
    url: location.href,
    tieneEdicion: document.body.innerText.includes('CAMPEONATO DE EX ALUMNOS ANDRÉS A. CÁCERES'),
    texto: document.body.innerText.slice(0, 600).replace(/\s+/g, ' '),
}));
await page.screenshot({ path: 'storage/tmp/revision-produccion/panel-ediciones.png' });

console.log(JSON.stringify({ panel, landing, modulo, errores }, null, 2));

await browser.close();
