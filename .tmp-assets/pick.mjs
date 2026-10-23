import fs from 'node:fs/promises';
import { execFileSync } from 'node:child_process';

const headers = { 'User-Agent': 'AulaVirtualEducationalAssets/1.0 (aula virtual escolar)' };
const wait = (ms) => new Promise((r) => setTimeout(r, ms));
const ffmpeg = '.tmp-assets/node_modules/ffmpeg-static/ffmpeg.exe';

const titles = [
    'File:Buff Orpington chicken, UK.jpg',
    'File:Leghorn cockerel and hen.jpg',
    'File:Australorp Rooster.jpg',
];

const search = await (async () => {
    const url = new URL('https://commons.wikimedia.org/w/api.php');
    Object.entries({
        format: 'json', action: 'query', list: 'search', srnamespace: '6', srlimit: '12',
        srsearch: 'sheep standing grass portrait filetype:bitmap',
    }).forEach(([k, v]) => url.searchParams.set(k, v));
    const res = await fetch(url, { headers });
    return res.json();
})();

titles.push(...(search.query?.search ?? []).map((r) => r.title).filter((t) => !/\.svg$/i.test(t)).slice(0, 6));

const url = new URL('https://commons.wikimedia.org/w/api.php');
Object.entries({
    format: 'json', action: 'query', titles: titles.join('|'), prop: 'imageinfo',
    iiprop: 'url|size|mime|extmetadata', iiurlwidth: '760',
}).forEach(([k, v]) => url.searchParams.set(k, v));

const json = await (await fetch(url, { headers })).json();
const pages = Object.values(json.query?.pages ?? {});
const tiles = [];

await fs.mkdir('.tmp-assets/pick', { recursive: true });
let i = 0;

for (const page of pages) {
    const ii = page.imageinfo?.[0];
    if (!ii) continue;
    const res = await fetch(ii.thumburl, { headers });
    if (!res.ok) continue;
    const name = `pick-${i}.jpg`;
    const full = '.tmp-assets/pick/' + name;
    await fs.writeFile(full, Buffer.from(await res.arrayBuffer()));
    execFileSync(ffmpeg, ['-y', '-loglevel', 'error', '-i', full, '-vf', 'scale=360:-1', '-q:v', '6', '.tmp-assets/pick/mini-' + name]);
    const meta = ii.extmetadata ?? {};
    tiles.push({
        name,
        title: page.title,
        b64: (await fs.readFile('.tmp-assets/pick/mini-' + name)).toString('base64'),
        license: meta.LicenseShortName?.value ?? '',
        artist: (meta.Artist?.value ?? '').replace(/<[^>]+>/g, '').trim().slice(0, 40),
        url: ii.descriptionurl,
        artistRaw: meta.Artist?.value ?? '',
        licenseUrl: meta.LicenseUrl?.value ?? '',
        description: (meta.ImageDescription?.value ?? '').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim(),
    });
    i += 1;
}

await fs.writeFile('.tmp-assets/pick.json', JSON.stringify(tiles, null, 2));
tiles.forEach((t) => console.log(t.name, t.license.padEnd(14), t.title));

const html = `<!doctype html><html lang="es"><head><meta charset="utf-8"><style>
body{font-family:system-ui;background:#f4f1ea;margin:14px;display:grid;grid-template-columns:repeat(4,1fr);gap:10px}
figure{margin:0;background:#fff;border-radius:8px;overflow:hidden}
img{width:100%;height:200px;object-fit:cover;display:block}
figcaption{padding:5px 7px;font-size:13px;font-weight:700;color:#173F35}
</style></head><body>
${tiles.map((t) => `<figure><img src="data:image/jpeg;base64,${t.b64}"><figcaption>${t.name} · ${t.title.replace('File:', '').slice(0, 60)}</figcaption></figure>`).join('')}
</body></html>`;

await fs.writeFile('.tmp-assets/pick.html', html);
console.log('tiles', tiles.length);
