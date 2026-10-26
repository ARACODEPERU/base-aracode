import Konva from 'konva';

// El QR y el PDF se cargan bajo demanda: el editor abre sin esperar por
// jsPDF (350 kB) y sólo se descarga cuando se exporta o se genera un QR.
let qrModule = null;
let pdfModule = null;

const qrLibrary = async () => {
    qrModule ??= await import('qrcode');

    return qrModule.default;
};

const pdfLibrary = async () => {
    pdfModule ??= await import('jspdf');

    return pdfModule.jsPDF;
};

/**
 * Dibujo del diploma con Konva.
 *
 * Los constructores de configuración se comparten entre el editor (vue-konva)
 * y la exportación (Konva imperativo), para que el PDF sea idéntico a lo que se
 * ve en pantalla.
 */

export const loadImage = (src) =>
    new Promise((resolve) => {
        // En el render del servidor (Inertia SSR) no existe Image.
        if (!src || typeof Image === 'undefined') {
            resolve(null);
            return;
        }

        const image = new Image();
        image.crossOrigin = 'anonymous';
        image.onload = () => resolve(image);
        image.onerror = () => resolve(null);
        image.src = src;
    });

export const textConfig = (element) => ({
    x: Number(element.x || 0),
    y: Number(element.y || 0),
    width: element.width ? Number(element.width) : undefined,
    text: element.text || '',
    fontSize: Number(element.fontSize || 32),
    fontFamily: element.fontFamily || 'Poppins Light',
    fill: element.color || '#111111',
    align: element.align || 'left',
    lineHeight: Number(element.lineHeight || 1.2),
    letterSpacing: Number(element.letterSpacing || 0),
    wrap: 'word',
    listening: element.locked !== true,
});

export const rectConfig = (element) => ({
    x: Number(element.x || 0),
    y: Number(element.y || 0),
    width: Number(element.width || 100),
    height: Number(element.height || 100),
    stroke: element.stroke || '#111111',
    strokeWidth: Number(element.strokeWidth ?? 2),
    fill: element.fill || undefined,
    cornerRadius: Number(element.radius || 0),
    listening: element.locked !== true,
});

export const lineConfig = (element) => ({
    points: [0, 0, Number(element.width || 100), 0],
    x: Number(element.x || 0),
    y: Number(element.y || 0),
    stroke: element.stroke || '#111111',
    strokeWidth: Number(element.strokeWidth ?? 2),
    listening: element.locked !== true,
});

export const imageConfig = (element, image) => ({
    x: Number(element.x || 0),
    y: Number(element.y || 0),
    width: Number(element.width || 120),
    height: Number(element.height || 120),
    image,
    listening: element.locked !== true,
});

export const qrConfig = (element, image) => ({
    x: Number(element.x || 0),
    y: Number(element.y || 0),
    width: Number(element.size || 120),
    height: Number(element.size || 120),
    image,
    listening: element.locked !== true,
});

/**
 * Divide el plantel en columnas y devuelve los textos ya armados.
 *
 * Se usa tanto en el editor como al exportar; el servidor ya entrega los
 * renglones armados (`items[].line`), aquí solo se reparten.
 */
export function squadColumnConfigs(element) {
    const items = element.items || [];
    const columns = Math.max(1, Math.min(4, Number(element.columns || 1)));
    const totalWidth = Number(element.width || 600);
    const gap = Number(element.gap || 0);
    const columnWidth = (totalWidth - gap * (columns - 1)) / columns;
    const perColumn = Math.max(1, Math.ceil(items.length / columns));
    const fontSize = Number(element.fontSize || 20);
    const lineHeight = Number(element.lineHeight || 1.4);

    const configs = [];
    let titleOffset = 0;

    if (element.title) {
        configs.push({
            key: `${element.id}_title`,
            config: {
                x: Number(element.x || 0),
                y: Number(element.y || 0),
                width: totalWidth,
                text: element.title,
                fontFamily: element.fontFamily || 'Poppins Light',
                fontSize: Number(element.titleFontSize || 22),
                fill: element.color || '#111111',
                align: element.align || 'left',
                fontStyle: 'bold',
                listening: false,
            },
        });
        titleOffset = Number(element.titleFontSize || 22) * 1.6;
    }

    for (let column = 0; column < columns; column += 1) {
        const slice = items.slice(column * perColumn, (column + 1) * perColumn);

        if (!slice.length) continue;

        configs.push({
            key: `${element.id}_col_${column}`,
            config: {
                x: Number(element.x || 0) + column * (columnWidth + gap),
                y: Number(element.y || 0) + titleOffset,
                width: columnWidth,
                text: slice.map((item) => item.line).join('\n'),
                fontFamily: element.fontFamily || 'Poppins Light',
                fontSize,
                fill: element.color || '#111111',
                align: element.align || 'left',
                lineHeight,
                wrap: 'none',
                listening: false,
            },
        });
    }

    return configs;
}

/** Alto aproximado del bloque de plantel (para el rectángulo de selección). */
export function squadHeight(element) {
    const items = element.items || [];
    const columns = Math.max(1, Math.min(4, Number(element.columns || 1)));
    const perColumn = Math.max(1, Math.ceil(items.length / columns));
    const fontSize = Number(element.fontSize || 20);
    const lineHeight = Number(element.lineHeight || 1.4);
    const titleOffset = element.title ? Number(element.titleFontSize || 22) * 1.6 : 0;

    return titleOffset + perColumn * fontSize * lineHeight;
}

/** Genera el data URL del QR del elemento. */
export async function qrDataUrl(element) {
    if (!element?.qr_text) return null;

    const QRCode = await qrLibrary();

    return QRCode.toDataURL(element.qr_text, {
        margin: 1,
        width: Math.max(120, Math.round(Number(element.size || 120))),
    });
}

/**
 * Dibuja un lado del diploma en un canvas y lo devuelve.
 * El stage se crea en un contenedor temporal, fuera del DOM visible.
 */
export async function renderSideToCanvas(side, options = {}) {
    const pixelRatio = Number(options.pixelRatio || 2);
    const width = Number(side.width || 1550);
    const height = Number(side.height || 1096);

    const container = document.createElement('div');
    const stage = new Konva.Stage({ container, width, height });
    const layer = new Konva.Layer();
    stage.add(layer);

    const background = await loadImage(side.background_url);

    if (background) {
        layer.add(new Konva.Image({ image: background, x: 0, y: 0, width, height }));
    }

    for (const element of side.elements || []) {
        if (element.visible === false) continue;

        if (element.type === 'text') {
            layer.add(new Konva.Text(textConfig(element)));
        } else if (element.type === 'squad') {
            squadColumnConfigs(element).forEach(({ config }) => layer.add(new Konva.Text(config)));
        } else if (element.type === 'rect') {
            layer.add(new Konva.Rect(rectConfig(element)));
        } else if (element.type === 'line') {
            layer.add(new Konva.Line(lineConfig(element)));
        } else if (element.type === 'image') {
            const image = await loadImage(element.src);
            if (image) layer.add(new Konva.Image(imageConfig(element, image)));
        } else if (element.type === 'qr') {
            const image = await loadImage(await qrDataUrl(element));
            if (image) layer.add(new Konva.Image(qrConfig(element, image)));
        }
    }

    layer.draw();

    const canvas = stage.toCanvas({ pixelRatio });
    stage.destroy();

    return canvas;
}

const orientationFor = (width, height) => (width >= height ? 'landscape' : 'portrait');

/** Descarga el diploma como PNG. */
export async function downloadPng(side, fileName, pixelRatio = 2) {
    const canvas = await renderSideToCanvas(side, { pixelRatio });
    const link = document.createElement('a');
    link.href = canvas.toDataURL('image/png');
    link.download = `${fileName}.png`;
    document.body.appendChild(link);
    link.click();
    link.remove();
}

/** Descarga el diploma como PDF de una página del tamaño del lienzo. */
export async function downloadPdf(side, fileName, pixelRatio = 2) {
    const canvas = await renderSideToCanvas(side, { pixelRatio });
    const width = Number(side.width || 1550);
    const height = Number(side.height || 1096);
    const jsPDF = await pdfLibrary();

    const pdf = new jsPDF({
        orientation: orientationFor(width, height),
        unit: 'px',
        format: [width, height],
    });

    pdf.addImage(canvas.toDataURL('image/png'), 'PNG', 0, 0, width, height);
    pdf.save(`${fileName}.pdf`);
}

/**
 * PDF multipágina: una página por diploma, respetando el tamaño de cada lienzo.
 *
 * @param {Array<{side: object, file_name: string}>} payloads
 */
export async function downloadBatchPdf(payloads, fileName, onProgress = null) {
    if (!payloads?.length) return;

    let pdf = null;

    for (let index = 0; index < payloads.length; index += 1) {
        const payload = payloads[index];
        const side = payload.side;
        const width = Number(side.width || 1550);
        const height = Number(side.height || 1096);

        if (onProgress) onProgress(index + 1, payloads.length);

        const canvas = await renderSideToCanvas(side, { pixelRatio: 2 });
        const image = canvas.toDataURL('image/png');

        if (!pdf) {
            const jsPDF = await pdfLibrary();

            pdf = new jsPDF({
                orientation: orientationFor(width, height),
                unit: 'px',
                format: [width, height],
            });
        } else {
            pdf.addPage([width, height], orientationFor(width, height));
        }

        pdf.addImage(image, 'PNG', 0, 0, width, height);
    }

    pdf.save(`${fileName}.pdf`);
}

/** Abre una ventana de impresión con una página por diploma. */
export async function printPayloads(payloads) {
    if (!payloads?.length) return;

    const images = [];

    for (const payload of payloads) {
        const canvas = await renderSideToCanvas(payload.side, { pixelRatio: 2 });
        images.push({
            data: canvas.toDataURL('image/png'),
            width: Number(payload.side.width || 1550),
            height: Number(payload.side.height || 1096),
        });
    }

    const win = window.open('', '_blank');

    if (!win) return;

    const pages = images
        .map(
            (image) => `<div class="page"><img src="${image.data}" style="width:${image.width}px;height:${image.height}px" /></div>`
        )
        .join('');

    const first = images[0];

    win.document.write(`<!doctype html><html><head><title>Diplomas</title>
        <style>
            @page { size: ${first.width}px ${first.height}px; margin: 0; }
            body { margin: 0; }
            .page { page-break-after: always; }
            .page:last-child { page-break-after: auto; }
            img { display: block; }
        </style></head><body>${pages}</body></html>`);
    win.document.close();

    win.onload = () => {
        win.focus();
        win.print();
    };
}
