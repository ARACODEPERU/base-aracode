import fs from 'node:fs/promises';
import { execFileSync } from 'node:child_process';

const headers = { 'User-Agent': 'AulaVirtualEducationalAssets/1.0 (aula virtual escolar)' };
const wait = (ms) => new Promise((r) => setTimeout(r, ms));
const ffmpeg = '.tmp-assets/node_modules/ffmpeg-static/ffmpeg.exe';

const PHOTOS = {
    perro: 'File:Labrador_on_Quantock_(2175262184).jpg',
    gato: 'File:Cat_November_2010-1a.jpg',
    gallina: 'File:Buff_Orpington_chicken,_UK.jpg',
    vaca: 'File:Cow_(Fleckvieh_breed)_Oeschinensee_Slaunger_2009-07-07.jpg',
    cerdo: 'File:Pig_farm_Vampula_1.jpg',
    oveja: 'File:Romneyram1.jpg',
    pato: 'File:Anas_platyrhynchos_male_female_quadrat.jpg',
    caballo: 'File:Halterstandingshotarabianone.jpg',
    gallo: 'File:Looking_my_camera!.jpg',
    leon: 'File:020_The_lion_king_Snyggve_in_the_Serengeti_National_Park_Photo_by_Giles_Laurent.jpg',
    elefante: 'File:178_Male_African_bush_elephant_in_Etosha_National_Park_Photo_by_Giles_Laurent.jpg',
    mono: 'File:Capuchin_Costa_Rica.jpg',
    rana: 'File:European_Common_Frog_Rana_temporaria.jpg',
    pajaro: 'File:Erithacus_rubecula_with_cocked_head.jpg',
    buho: 'File:Bubo_virginianus_06.jpg',
};

const SOUNDS = {
    perro: 'File:Barking of a dog.ogg',
    gato: 'File:Meow of a Siamese cat - freemaster2.wav',
    gallina: 'File:Chickens demanding food.ogg',
    vaca: 'File:Single Cow Moo.ogg',
    cerdo: 'File:Pig grunt - Erdie.ogg',
    oveja: 'File:Sheep bleating.ogg',
    pato: 'File:Anas platyrhynchos - Mallard - XC62258.ogg',
    caballo: 'File:Wiehern.ogg',
    gallo: 'File:Rooster crowing.ogg',
    leon: 'File:Lion raring-sound1TamilNadu178.ogg',
    elefante: 'File:Elephant voice - trumpeting.ogg',
    mono: 'File:Brown woolly monkey alarm call.wav',
    rana: 'File:Single Frog Croak.oga',
    pajaro: 'File:Birds singing in garden.ogg',
    buho: 'File:Strix aluco male.oga',
};

async function metadata(titles, width) {
    const url = new URL('https://commons.wikimedia.org/w/api.php');
    Object.entries({
        format: 'json', action: 'query', titles: titles.join('|'), prop: 'imageinfo',
        iiprop: 'url|size|mime|extmetadata|duration',
        ...(width ? { iiurlwidth: String(width) } : {}),
    }).forEach(([k, v]) => url.searchParams.set(k, v));

    for (let i = 0; i < 6; i += 1) {
        const res = await fetch(url, { headers });
        if (res.ok) {
            const json = await res.json();
            const byTitle = {};
            for (const page of Object.values(json.query?.pages ?? {})) {
                byTitle[page.title] = page.imageinfo?.[0] ?? null;
            }
            return byTitle;
        }
        console.log('...', res.status, 'espera');
        await wait(8000 * (i + 1));
    }
    throw new Error('No se pudo consultar la API');
}

function credit(id, ii, kind) {
    const meta = ii?.extmetadata ?? {};
    const strip = (html) => (html ?? '').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();

    return {
        id,
        kind,
        file: ii?.descriptionurl ? decodeURIComponent(ii.descriptionurl.split('/wiki/').pop()) : '',
        page: ii?.descriptionurl ?? '',
        author: strip(meta.Artist?.value) || 'Wikimedia Commons',
        license: strip(meta.LicenseShortName?.value) || 'Ver en la ficha del archivo',
        licenseUrl: meta.LicenseUrl?.value ?? '',
        source: 'Wikimedia Commons',
    };
}

const photoMeta = await metadata(Object.values(PHOTOS), 900);
await wait(4000);
const soundMeta = await metadata(Object.values(SOUNDS), null);

const credits = { photos: {}, sounds: {} };

await fs.mkdir('public/themes/colegio/img/juegos/animales', { recursive: true });
await fs.mkdir('public/themes/colegio/audio/animales', { recursive: true });

for (const [id, title] of Object.entries(PHOTOS)) {
    const ii = photoMeta[title];
    if (!ii) {
        console.log('SIN META', id, title);
        continue;
    }
    const res = await fetch(ii.thumburl, { headers });
    if (!res.ok) {
        console.log('SIN FOTO', id, res.status);
        continue;
    }
    const buf = Buffer.from(await res.arrayBuffer());
    await fs.writeFile(`public/themes/colegio/img/juegos/animales/${id}.jpg`, buf);
    credits.photos[id] = credit(id, ii, 'foto');
    console.log('foto', id, Math.round(buf.length / 1024) + 'KB', credits.photos[id].license);
    await wait(300);
}

for (const [id, title] of Object.entries(SOUNDS)) {
    const ii = soundMeta[title];
    if (!ii) {
        console.log('SIN META AUDIO', id, title);
        continue;
    }
    const res = await fetch(ii.url, { headers });
    if (!res.ok) {
        console.log('SIN AUDIO', id, res.status);
        continue;
    }
    const ext = (ii.url.split('.').pop() || 'ogg').split('?')[0];
    const input = `.tmp-assets/sonido-${id}.${ext}`;
    await fs.writeFile(input, Buffer.from(await res.arrayBuffer()));

    const out = `public/themes/colegio/audio/animales/${id}.mp3`;
    const loop = id === 'cerdo' ? ['-stream_loop', '2'] : [];
    const filter = [
        'silenceremove=start_periods=1:start_duration=0.1:start_threshold=-45dB',
        'areverse',
        'silenceremove=start_periods=1:start_duration=0.1:start_threshold=-45dB',
        'areverse',
        'loudnorm=I=-20:TP=-1.5:LRA=11',
        'afade=t=in:st=0:d=0.05',
        'afade=t=out:st=11.2:d=0.8',
    ].join(',');

    try {
        execFileSync(ffmpeg, [
            '-y', '-loglevel', 'error', ...loop, '-i', input,
            '-af', filter, '-t', '12',
            '-ac', '1', '-ar', '44100', '-codec:a', 'libmp3lame', '-b:a', '96k',
            out,
        ]);
        const size = (await fs.stat(out)).size;
        credits.sounds[id] = credit(id, ii, 'sonido');
        console.log('audio', id, Math.round(size / 1024) + 'KB', 'orig', ii.duration ?? '?', credits.sounds[id].license);
    } catch (error) {
        console.log('ERROR AUDIO', id, String(error.message).slice(0, 200));
    }

    await wait(250);
}

await fs.writeFile('.tmp-assets/creditos.json', JSON.stringify(credits, null, 2));
