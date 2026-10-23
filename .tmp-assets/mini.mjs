import fs from 'node:fs/promises';
import { execFileSync } from 'node:child_process';

const ffmpeg = '.tmp-assets/node_modules/ffmpeg-static/ffmpeg.exe';
const dir = '.tmp-assets/candidatos';
const files = (await fs.readdir(dir)).filter((f) => f.endsWith('.jpg')).sort((a, b) => {
    const [an, ai] = a.replace('.jpg', '').split('-');
    const [bn, bi] = b.replace('.jpg', '').split('-');
    return an.localeCompare(bn) || Number(ai) - Number(bi);
});

await fs.mkdir('.tmp-assets/mini', { recursive: true });
const tiles = [];
for (const file of files) {
    const out = '.tmp-assets/mini/' + file;
    try {
        execFileSync(ffmpeg, ['-y', '-loglevel', 'error', '-i', dir + '/' + file, '-vf', 'scale=280:-1', '-q:v', '7', out]);
    } catch (e) {
        continue;
    }
    tiles.push({ file, b64: (await fs.readFile(out)).toString('base64') });
}

const html = `<!doctype html><html lang="es"><head><meta charset="utf-8"><style>
body{font-family:system-ui;background:#f4f1ea;margin:14px;display:grid;grid-template-columns:repeat(6,1fr);gap:8px}
figure{margin:0;background:#fff;border-radius:8px;overflow:hidden}
img{width:100%;height:150px;object-fit:cover;display:block}
figcaption{padding:4px 6px;font-size:15px;font-weight:700;color:#173F35}
</style></head><body>
${tiles.map((t) => `<figure><img src="data:image/jpeg;base64,${t.b64}"><figcaption>${t.file.replace('.jpg', '')}</figcaption></figure>`).join('')}
</body></html>`;

await fs.writeFile('.tmp-assets/contacto-mini.html', html);
console.log('tiles', tiles.length, 'bytes', html.length);
