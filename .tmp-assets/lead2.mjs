import fs from 'node:fs/promises';
import { execFileSync } from 'node:child_process';

const TARGETS = {
    perro: ['Dog', 'Labrador Retriever', 'Golden Retriever'],
    gato: ['Cat', 'Tabby cat'],
    gallina: ['Chicken', 'Hen'],
    vaca: ['Holstein Friesian cattle', 'Cattle'],
    cerdo: ['Domestic pig', 'Berkshire pig'],
    oveja: ['Sheep', 'Domestic sheep'],
    pato: ['Mallard', 'Domestic duck'],
    caballo: ['Horse', 'Arabian horse', 'Thoroughbred'],
    gallo: ['Rooster'],
    leon: ['Lion'],
    elefante: ['African bush elephant'],
    mono: ['Rhesus macaque', 'Capuchin monkey'],
    rana: ['Common frog', 'Frog'],
    pajaro: ['European robin', 'House sparrow'],
    buho: ['Great horned owl', 'Barn owl'],
};

const headers = { 'User-Agent': 'AulaVirtualEducationalAssets/1.0 (aula virtual escolar)' };
const wait = (ms) => new Promise((r) => setTimeout(r, ms));
const ffmpeg = '.tmp-assets/node_modules/ffmpeg-static/ffmpeg.exe';

async function summary(title) {
    const res = await fetch('https://en.wikipedia.org/api/rest_v1/page/summary/' + encodeURIComponent(title), { headers });
    if (!res.ok) return null;
    return res.json();
}

async function commonsInfo(fileTitle) {
    const url = new URL('https://commons.wikimedia.org/w/api.php');
    Object.entries({
        format: 'json',
        action: 'query',
        titles: fileTitle,
        prop: 'imageinfo',
        iiprop: 'url|size|mime|extmetadata',
        iiurlwidth: '760',
    }).forEach(([k, v]) => url.searchParams.set(k, v));

    for (let i = 0; i < 4; i += 1) {
        const res = await fetch(url, { headers });
        if (res.ok) {
            const json = await res.json();
            return Object.values(json.query?.pages ?? {})[0]?.imageinfo?.[0] ?? null;
        }
        await wait(3000 * (i + 1));
    }
    return null;
}

await fs.mkdir('.tmp-assets/lead', { recursive: true });
await fs.mkdir('.tmp-assets/leadmini', { recursive: true });
const out = {};

for (const [id, titles] of Object.entries(TARGETS)) {
    out[id] = [];
    for (const title of titles) {
        const sum = await summary(title);
        if (!sum?.originalimage?.source) continue;
        const clean = decodeURIComponent(sum.originalimage.source.split('?')[0].split('/').pop());
        const info = await commonsInfo('File:' + clean);
        const meta = info?.extmetadata ?? {};
        out[id].push({
            wiki: title,
            file: 'File:' + clean,
            thumb: info?.thumburl ?? sum.originalimage.source.split('?')[0],
            width: sum.originalimage.width,
            height: sum.originalimage.height,
            artist: (meta.Artist?.value ?? '').replace(/<[^>]+>/g, '').trim(),
            license: meta.LicenseShortName?.value ?? '',
            licenseUrl: meta.LicenseUrl?.value ?? '',
            pageUrl: info?.descriptionurl ?? '',
            description: (meta.ImageDescription?.value ?? '').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 140),
        });
        await wait(700);
    }
    console.log(id, out[id].length);
}

await fs.writeFile('.tmp-assets/lead.json', JSON.stringify(out, null, 2));

const tiles = [];
for (const [id, list] of Object.entries(out)) {
    for (const [i, c] of list.entries()) {
        const res = await fetch(c.thumb, { headers });
        if (!res.ok) {
            console.log('fallo', id, i, res.status);
            continue;
        }
        const name = `${id}-${i}.jpg`;
        const full = '.tmp-assets/lead/' + name;
        await fs.writeFile(full, Buffer.from(await res.arrayBuffer()));
        const mini = '.tmp-assets/leadmini/' + name;
        execFileSync(ffmpeg, ['-y', '-loglevel', 'error', '-i', full, '-vf', 'scale=340:-1', '-q:v', '6', mini]);
        tiles.push({ name, b64: (await fs.readFile(mini)).toString('base64'), wiki: c.wiki, license: c.license, artist: c.artist.slice(0, 40) });
        await wait(250);
    }
}

const html = `<!doctype html><html lang="es"><head><meta charset="utf-8"><style>
body{font-family:system-ui;background:#f4f1ea;margin:14px;display:grid;grid-template-columns:repeat(5,1fr);gap:10px}
figure{margin:0;background:#fff;border-radius:8px;overflow:hidden}
img{width:100%;height:190px;object-fit:cover;display:block}
figcaption{padding:5px 7px;font-size:13px;font-weight:700;color:#173F35}
</style></head><body>
${tiles.map((t) => `<figure><img src="data:image/jpeg;base64,${t.b64}"><figcaption>${t.name.replace('.jpg', '')} · ${t.wiki}<br><span style="font-weight:400">${t.license} · ${t.artist}</span></figcaption></figure>`).join('')}
</body></html>`;

await fs.writeFile('.tmp-assets/lead.html', html);
console.log('tiles', tiles.length, 'bytes', html.length);
