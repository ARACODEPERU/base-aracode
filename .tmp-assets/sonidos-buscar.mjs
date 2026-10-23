import fs from 'node:fs/promises';

const QUERIES = {
    perro: 'dog barking',
    gato: 'cat meowing',
    gallina: 'chicken clucking',
    vaca: 'cow mooing',
    cerdo: 'pig grunting',
    oveja: 'sheep bleating',
    pato: 'duck quacking',
    caballo: 'horse neighing',
    gallo: 'rooster crowing',
    leon: 'lion roaring',
    elefante: 'elephant trumpeting',
    mono: 'monkey calls',
    rana: 'frog croaking',
    pajaro: 'bird singing',
    buho: 'owl hooting',
};

const headers = { 'User-Agent': 'AulaVirtualEducationalAssets/1.0 (aula virtual escolar)' };
const wait = (ms) => new Promise((r) => setTimeout(r, ms));

async function api(params) {
    const url = new URL('https://commons.wikimedia.org/w/api.php');
    Object.entries({ format: 'json', ...params }).forEach(([k, v]) => url.searchParams.set(k, v));
    for (let i = 0; i < 6; i += 1) {
        const res = await fetch(url, { headers });
        if (res.ok) return res.json();
        const retry = Number(res.headers.get('retry-after') || 0) * 1000 || 5000 * (i + 1);
        console.log('...', res.status, 'espera', retry);
        await wait(retry);
    }
    return null;
}

const report = {};

for (const [id, query] of Object.entries(QUERIES)) {
    const search = await api({
        action: 'query', list: 'search', srnamespace: '6', srlimit: '10',
        srsearch: `${query} filetype:audio`,
    });

    const titles = (search?.query?.search ?? []).map((r) => r.title).filter((t) => !/\.(webm|flac)$/i.test(t)).slice(0, 6);
    if (!titles.length) {
        console.log(id, 'sin resultados');
        report[id] = [];
        await wait(7000);
        continue;
    }

    const info = await api({
        action: 'query', titles: titles.join('|'), prop: 'imageinfo',
        iiprop: 'url|size|mime|extmetadata',
    });

    report[id] = Object.values(info?.query?.pages ?? {}).map((page) => {
        const ii = page.imageinfo?.[0] ?? {};
        const meta = ii.extmetadata ?? {};
        return {
            title: page.title,
            mime: ii.mime,
            duration: ii.duration,
            size: ii.size,
            url: ii.url,
            page: ii.descriptionurl,
            license: meta.LicenseShortName?.value ?? '',
            licenseUrl: meta.LicenseUrl?.value ?? '',
            artist: (meta.Artist?.value ?? '').replace(/<[^>]+>/g, '').trim(),
            description: (meta.ImageDescription?.value ?? '').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 150),
        };
    });

    console.log('\n== ' + id);
    report[id].forEach((c) => console.log(
        (c.mime || '').padEnd(14),
        String(c.duration ?? '?').padStart(6),
        (c.license || '?').padEnd(14),
        c.title,
        '|',
        c.description.slice(0, 60),
    ));

    await wait(7000);
}

await fs.writeFile('.tmp-assets/sonidos.json', JSON.stringify(report, null, 2));
