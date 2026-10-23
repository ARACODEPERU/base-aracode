import fs from 'node:fs/promises';

const headers = { 'User-Agent': 'AulaVirtualEducationalAssets/1.0 (aula virtual escolar)' };
const wait = (ms) => new Promise((r) => setTimeout(r, ms));

const titles = [
    'File:Cow (Fleckvieh breed) Oeschinensee Slaunger 2009-07-07.jpg',
    'File:Pig farm Vampula 1.jpg',
    'File:020 The lion king Snyggve in the Serengeti National Park Photo by Giles Laurent.jpg',
    'File:178 Male African bush elephant in Etosha National Park Photo by Giles Laurent.jpg',
    'File:Romneyram1.jpg',
    'File:Anas platyrhynchos - Mallard - XC62258.ogg',
];

const url = new URL('https://commons.wikimedia.org/w/api.php');
Object.entries({
    format: 'json', action: 'query', titles: titles.join('|'), prop: 'imageinfo',
    iiprop: 'url|size|mime|extmetadata|duration',
}).forEach(([k, v]) => url.searchParams.set(k, v));

for (let attempt = 0; attempt < 10; attempt += 1) {
    const res = await fetch(url, { headers });

    if (res.ok) {
        const json = await res.json();
        const out = {};

        for (const page of Object.values(json.query?.pages ?? {})) {
            const ii = page.imageinfo?.[0];
            if (!ii) continue;
            const meta = ii.extmetadata ?? {};
            out[page.title] = {
                page: ii.descriptionurl,
                author: (meta.Artist?.value ?? '').replace(/<[^>]+>/g, '').replace(/\s+/g, ' ').trim(),
                license: (meta.LicenseShortName?.value ?? '').trim(),
                licenseUrl: meta.LicenseUrl?.value ?? '',
                duration: ii.duration ?? null,
                url: ii.url,
            };
        }

        await fs.writeFile('.tmp-assets/creditos-extra.json', JSON.stringify(out, null, 2));
        console.log(JSON.stringify(out, null, 2));
        break;
    }

    const retry = Number(res.headers.get('retry-after') || 0) * 1000 || 20000 * (attempt + 1);
    console.log('espera', Math.round(retry / 1000) + 's', res.status);
    await wait(retry);
}
