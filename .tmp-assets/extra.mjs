import fs from 'node:fs/promises';
import { execFileSync } from 'node:child_process';

const QUERIES = {
    gallina: 'hen head closeup',
    gallo: 'rooster head closeup',
    oveja: 'sheep face closeup',
};
const headers = { 'User-Agent': 'AulaVirtualEducationalAssets/1.0 (aula virtual escolar)' };
const wait = (ms) => new Promise((r) => setTimeout(r, ms));
const ffmpeg = '.tmp-assets/node_modules/ffmpeg-static/ffmpeg.exe';

async function api(params) {
    const url = new URL('https://commons.wikimedia.org/w/api.php');
    Object.entries({ format: 'json', ...params }).forEach(([k, v]) => url.searchParams.set(k, v));
    for (let i = 0; i < 5; i += 1) {
        const res = await fetch(url, { headers });
        if (res.ok) return res.json();
        await wait(3000 * (i + 1));
    }
    throw new Error('sin API');
}

await fs.mkdir('.tmp-assets/extra', { recursive: true });
const out = {};
const tiles = [];

for (const [id, query] of Object.entries(QUERIES)) {
    const search = await api({ action: 'query', list: 'search', srnamespace: '6', srlimit: '14', srsearch: `${query} filetype:bitmap` });
    const titles = (search.query?.search ?? []).map((r) => r.title);
    const info = await api({ action: 'query', titles: titles.join('|'), prop: 'imageinfo', iiprop: 'url|size|mime|extmetadata', iiurlwidth: '700' });
    const pages = Object.values(info.query?.pages ?? {});
    const list = [];

    for (const page of pages) {
        const ii = page.imageinfo?.[0];
        if (!ii || !/^image\/(jpeg|png)$/.test(ii.mime) || ii.width < 800 || ii.height < 600) continue;
        const meta = ii.extmetadata ?? {};
        list.push({
            title: page.title,
            thumb: ii.thumburl,
            width: ii.width,
            height: ii.height,
            artist: (meta.Artist?.value ?? '').replace(/<[^>]+>/g, '').trim(),
            license: meta.LicenseShortName?.value ?? '',
            pageUrl: ii.descriptionurl,
            description: (meta.ImageDescription?.value ?? '').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 120),
        });
        if (list.length >= 4) break;
    }

    out[id] = list;
    console.log(id, list.length);

    for (const [i, c] of list.entries()) {
        const res = await fetch(c.thumb, { headers });
        if (!res.ok) continue;
        const name = `${id}-${i}.jpg`;
        const full = '.tmp-assets/extra/' + name;
        await fs.writeFile(full, Buffer.from(await res.arrayBuffer()));
        execFileSync(ffmpeg, ['-y', '-loglevel', 'error', '-i', full, '-vf', 'scale=340:-1', '-q:v', '6', '.tmp-assets/extra/mini-' + name]);
        tiles.push({ name, b64: (await fs.readFile('.tmp-assets/extra/mini-' + name)).toString('base64'), title: c.title.replace('File:', '').slice(0, 55) });
    }

    await wait(2000);
}

await fs.writeFile('.tmp-assets/extra.json', JSON.stringify(out, null, 2));

const html = `<!doctype html><html lang="es"><head><meta charset="utf-8"><style>
body{font-family:system-ui;background:#f4f1ea;margin:14px;display:grid;grid-template-columns:repeat(4,1fr);gap:10px}
figure{margin:0;background:#fff;border-radius:8px;overflow:hidden}
img{width:100%;height:200px;object-fit:cover;display:block}
figcaption{padding:5px 7px;font-size:13px;font-weight:700;color:#173F35}
</style></head><body>
${tiles.map((t) => `<figure><img src="data:image/jpeg;base64,${t.b64}"><figcaption>${t.name.replace('.jpg', '')} · ${t.title}</figcaption></figure>`).join('')}
</body></html>`;

await fs.writeFile('.tmp-assets/extra.html', html);
console.log('tiles', tiles.length);
