import fs from 'node:fs/promises';
import { execFileSync } from 'node:child_process';

const headers = { 'User-Agent': 'AulaVirtualEducationalAssets/1.0 (aula virtual escolar)' };
const wait = (ms) => new Promise((r) => setTimeout(r, ms));
const ffmpeg = '.tmp-assets/node_modules/ffmpeg-static/ffmpeg.exe';
const data = JSON.parse(await fs.readFile('.tmp-assets/sonidos.json', 'utf8'));

const wanted = {
    cerdo: 'File:Pig grunt - Erdie.ogg',
    pato: 'File:Anas platyrhynchos - Mallard - XC62258.ogg',
    caballo: 'File:Wiehern.ogg',
    gallo: 'File:Rooster crowing.ogg',
    leon: 'File:Lion raring-sound1TamilNadu178.ogg',
    mono: 'File:Brown woolly monkey alarm call.wav',
    rana: 'File:Single Frog Croak.oga',
    pajaro: 'File:Birds singing in garden.ogg',
    buho: 'File:Strix aluco male.oga',
};

const urls = {};
for (const [id, title] of Object.entries(wanted)) {
    const found = (data[id] ?? []).find((c) => c.title === title);
    urls[id] = found?.url ?? null;
}

if (!urls.pato) {
    const url = new URL('https://commons.wikimedia.org/w/api.php');
    Object.entries({
        format: 'json', action: 'query', titles: wanted.pato, prop: 'imageinfo', iiprop: 'url|size|mime|extmetadata|duration',
    }).forEach(([k, v]) => url.searchParams.set(k, v));

    for (let i = 0; i < 5 && !urls.pato; i += 1) {
        const res = await fetch(url, { headers });
        if (res.ok) {
            const json = await res.json();
            const page = Object.values(json.query?.pages ?? {})[0];
            urls.pato = page?.imageinfo?.[0]?.url ?? null;
        } else {
            console.log('meta pato', res.status);
            await wait(10000 * (i + 1));
        }
    }
}

for (const [id, url] of Object.entries(urls)) {
    if (!url) {
        console.log('SIN URL', id);
        continue;
    }

    let buffer = null;
    for (let attempt = 0; attempt < 6; attempt += 1) {
        const res = await fetch(url, { headers });
        if (res.ok) {
            buffer = Buffer.from(await res.arrayBuffer());
            break;
        }
        const delay = Number(res.headers.get('retry-after') || 0) * 1000 || 12000 * (attempt + 1);
        console.log(id, res.status, 'espera', Math.round(delay / 1000) + 's');
        await wait(delay);
    }

    if (!buffer) {
        console.log('FALLO', id);
        continue;
    }

    const ext = (url.split('.').pop() || 'ogg').split('?')[0];
    const input = `.tmp-assets/dl-${id}.${ext}`;
    await fs.writeFile(input, buffer);

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
            `public/themes/colegio/audio/animales/${id}.mp3`,
        ]);
        const size = (await fs.stat(`public/themes/colegio/audio/animales/${id}.mp3`)).size;
        console.log('OK', id, Math.round(size / 1024) + 'KB');
    } catch (error) {
        console.log('ERROR', id, String(error.message).slice(0, 160));
    }

    await wait(4000);
}
