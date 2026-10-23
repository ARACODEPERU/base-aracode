import fs from 'node:fs/promises';

const ANIMALS = {
    perro: 'dog portrait',
    gato: 'cat portrait',
    gallina: 'hen chicken portrait',
    vaca: 'cow portrait',
    cerdo: 'pig portrait',
    oveja: 'sheep portrait',
    pato: 'duck portrait',
    caballo: 'horse portrait',
    gallo: 'rooster portrait',
    leon: 'lion portrait',
    elefante: 'elephant portrait',
    mono: 'monkey portrait',
    rana: 'frog portrait',
    pajaro: 'songbird portrait',
    buho: 'owl portrait',
};

const API = 'https://commons.wikimedia.org/w/api.php';
const headers = { 'User-Agent': 'AulaVirtualEducationalAssets/1.0 (colegio aula virtual; asset sourcing)' };
const wait = (ms) => new Promise((r) => setTimeout(r, ms));

async function api(params) {
    const url = new URL(API);
    Object.entries({ format: 'json', ...params }).forEach(([k, v]) => url.searchParams.set(k, v));

    for (let attempt = 0; attempt < 6; attempt += 1) {
        const res = await fetch(url, { headers });
        if (res.ok) return res.json();
        if (res.status !== 429 && res.status < 500) throw new Error('API ' + res.status);
        const retry = Number(res.headers.get('retry-after') || 0) * 1000 || 4000 * (attempt + 1);
        console.log('... espera', retry, 'ms (' + res.status + ')');
        await wait(retry);
    }

    throw new Error('API sin respuesta tras reintentos');
}

const out = await fs.readFile('.tmp-assets/fotos.json', 'utf8').then(JSON.parse).catch(() => ({}));
let first = true;

for (const [id, query] of Object.entries(ANIMALS)) {
    if (out[id]?.length) {
        console.log(id, 'ya estaba');
        continue;
    }

    if (!first) await wait(2500);
    first = false;
    const search = await api({
        action: 'query',
        list: 'search',
        srnamespace: '6',
        srlimit: '12',
        srsearch: `${query} filetype:bitmap`,
    });

    const titles = (search.query?.search ?? []).map((r) => r.title);
    const info = await api({
        action: 'query',
        titles: titles.join('|'),
        prop: 'imageinfo',
        iiprop: 'url|size|mime|extmetadata',
        iiurlwidth: '520',
    });

    const pages = Object.values(info.query?.pages ?? {});
    const candidates = [];

    for (const page of pages) {
        const ii = page.imageinfo?.[0];
        if (!ii || !/^image\/(jpeg|png)$/.test(ii.mime)) continue;
        if (ii.width < 900 || ii.height < 600) continue;
        const meta = ii.extmetadata ?? {};
        const artist = (meta.Artist?.value ?? '').replace(/<[^>]+>/g, '').trim();
        candidates.push({
            title: page.title,
            page: ii.descriptionurl,
            file: ii.url,
            thumb: ii.thumburl,
            width: ii.width,
            height: ii.height,
            artist,
            license: meta.LicenseShortName?.value ?? '',
            licenseUrl: meta.LicenseUrl?.value ?? '',
            description: (meta.ImageDescription?.value ?? '').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 180),
        });
    }

    candidates.sort((a, b) => (b.width / b.height > 1.4 ? -1 : 0) - (a.width / a.height > 1.4 ? -1 : 0));
    out[id] = candidates.slice(0, 4);
    console.log(id, out[id].length);
}

await fs.mkdir('.tmp-assets/candidatos', { recursive: true });

let index = 0;
const tiles = [];
for (const [id, list] of Object.entries(out)) {
    for (const [i, c] of list.entries()) {
        const res = await fetch(c.thumb, { headers });
        if (!res.ok) continue;
        const buf = Buffer.from(await res.arrayBuffer());
        const name = `${id}-${i}.jpg`;
        await fs.writeFile(`.tmp-assets/candidatos/${name}`, buf);
        tiles.push({ id, i, name, title: c.title, license: c.license, artist: c.artist.slice(0, 50), description: c.description.slice(0, 90), base64: buf.toString('base64') });
        index += 1;
    }
}

await fs.writeFile('.tmp-assets/fotos.json', JSON.stringify(out, null, 2));

const html = `<!doctype html><html lang="es"><head><meta charset="utf-8"><style>
body{font-family:system-ui;background:#f4f1ea;margin:16px;display:grid;grid-template-columns:repeat(7,1fr);gap:10px}
figure{margin:0;background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 1px 4px #0002}
img{width:100%;height:150px;object-fit:cover;display:block}
figcaption{padding:5px 7px;font-size:10px;line-height:1.25}
b{font-size:12px;text-transform:uppercase}
</style></head><body>
${tiles.map((t) => `<figure><img src="data:image/jpeg;base64,${t.base64}"><figcaption><b>${t.id} ${t.i}</b><br>${t.description || t.title}<br>${t.license} · ${t.artist}</figcaption></figure>`).join('')}
</body></html>`;

await fs.writeFile('.tmp-assets/contacto.html', html);
console.log('tiles', tiles.length, 'html bytes', html.length);
