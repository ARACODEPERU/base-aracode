/**
 * Utilidades del layout del diploma.
 *
 * Semántica de coordenadas: `x` e `y` son la esquina superior izquierda del
 * elemento (para textos, el ancho define el área de alineación). Así el editor
 * y el PDF coinciden sin correcciones de centrado.
 */

export const FALLBACK_FONT = 'Poppins Light';

const base = (extra) => ({
    rotation: 0,
    visible: true,
    locked: false,
    ...extra,
});

/** Crea un elemento nuevo centrado horizontalmente en el lienzo. */
export function createElement(type, canvas, options = {}) {
    const width = canvas?.width || 1550;
    const height = canvas?.height || 1096;
    const centerX = Math.round(width / 2);
    const id = `el_${Date.now()}_${Math.floor(Math.random() * 1000)}`;

    const presets = {
        text: base({
            id,
            type: 'text',
            name: 'Texto',
            x: Math.round(centerX - width * 0.35),
            y: Math.round(height * 0.45),
            width: Math.round(width * 0.7),
            text: options.text || 'Texto del diploma',
            fontFamily: FALLBACK_FONT,
            fontSize: 40,
            color: '#0b1f2b',
            align: 'center',
            lineHeight: 1.2,
            letterSpacing: 0,
        }),
        squad: base({
            id,
            type: 'squad',
            name: 'Plantel',
            x: Math.round(width * 0.16),
            y: Math.round(height * 0.55),
            width: Math.round(width * 0.68),
            columns: 3,
            fontFamily: FALLBACK_FONT,
            fontSize: 20,
            color: '#0b1f2b',
            align: 'left',
            lineHeight: 1.45,
            gap: 28,
            showJerseyNumber: true,
            showPosition: false,
            sortBy: 'number',
            title: 'PLANTEL',
            titleFontSize: 22,
        }),
        image: base({
            id,
            type: 'image',
            name: 'Imagen',
            x: Math.round(centerX - 80),
            y: Math.round(height * 0.08),
            width: 160,
            height: 160,
            src: options.src || null,
        }),
        qr: base({
            id,
            type: 'qr',
            name: 'QR',
            x: width - 190,
            y: height - 190,
            size: 130,
            mode: 'landing',
        }),
        rect: base({
            id,
            type: 'rect',
            name: 'Marco',
            x: Math.round(width * 0.05),
            y: Math.round(height * 0.05),
            width: Math.round(width * 0.9),
            height: Math.round(height * 0.9),
            stroke: '#c8f169',
            strokeWidth: 4,
            radius: 18,
            fill: null,
        }),
        line: base({
            id,
            type: 'line',
            name: 'Línea',
            x: Math.round(centerX - width * 0.25),
            y: Math.round(height * 0.5),
            width: Math.round(width * 0.5),
            stroke: '#0b1f2b',
            strokeWidth: 2,
        }),
    };

    return { ...(presets[type] || presets.text), ...options };
}

/** Etiqueta visible en la lista de capas. */
export function elementLabel(element) {
    if (element.name) return element.name;

    return {
        text: 'Texto',
        squad: 'Plantel',
        image: 'Imagen',
        qr: 'QR',
        rect: 'Marco',
        line: 'Línea',
    }[element.type] || 'Elemento';
}

/**
 * Sustituye {{token}} por su valor real para que el editor muestre el diploma
 * como se imprimirá. Las variables sin valor se dejan visibles: así se pueden
 * colocar mientras se diseña.
 *
 * @param {string|null} text
 * @param {Record<string, string>} tokenValues
 */
export function resolveDiplomaTokens(text, tokenValues = {}) {
    const raw = String(text ?? '');

    if (!Object.keys(tokenValues).length) return raw;

    return raw.replace(/\{\{\s*([a-z_]+)\s*\}\}/gi, (match, token) => {
        const value = tokenValues[String(token).toLowerCase()];

        return value === undefined ? match : String(value);
    });
}

/** Comparación de textos del plantel (misma idea que el orden del servidor). */
const compareNames = (a, b) =>
    String(a || '').localeCompare(String(b || ''), 'es', { sensitivity: 'base' });

/**
 * Arma las líneas del plantel con la configuración actual del elemento.
 *
 * El payload sólo aporta los datos de cada jugador; el orden y los datos
 * visibles dependen del inspector y deben verse al instante en el lienzo.
 *
 * @param {object} element
 * @param {Array<{name?: string, jersey?: number|null, position?: string|null}>} source
 */
export function buildSquadItems(element, source) {
    const items = Array.isArray(source) ? source : [];

    if (!items.length) return [];

    const showJersey = element?.showJerseyNumber !== false;
    const showPosition = element?.showPosition === true;
    const sortBy = element?.sortBy || 'number';

    const rows = items.map((item) => ({
        name: item.name || '',
        jersey: item.jersey ?? null,
        position: item.position ?? null,
    }));

    rows.sort((a, b) => {
        if (sortBy === 'name') return compareNames(a.name, b.name);

        if (sortBy === 'position') {
            const byPosition = compareNames(a.position, b.position);

            return byPosition !== 0 ? byPosition : compareNames(a.name, b.name);
        }

        const byNumber = Number(a.jersey ?? 999) - Number(b.jersey ?? 999);

        return byNumber !== 0 ? byNumber : compareNames(a.name, b.name);
    });

    return rows.map((item) => {
        const parts = [];

        if (showJersey && item.jersey) parts.push(`${item.jersey}.`);

        parts.push(item.name);

        if (showPosition && item.position) {
            const position = String(item.position);
            parts.push(`(${position.charAt(0).toUpperCase()}${position.slice(1)})`);
        }

        return { ...item, line: parts.join(' ') };
    });
}

/**
 * Texto de muestra por id de elemento usando el payload real del servidor.
 * En el editor mostramos los valores reales, nunca llaves {{...}}.
 */
export function sampleTextsById(samplePayload) {
    const map = {};
    const elements = samplePayload?.side?.elements || [];

    elements.forEach((element) => {
        if (element.type === 'text') {
            map[element.id] = element.text || '';
        }
        if (element.type === 'squad') {
            map[element.id] = element.title || '';
        }
    });

    return map;
}

/** Copia profunda sencilla para no mutar el layout de props. */
export function cloneLayout(layout) {
    return JSON.parse(JSON.stringify(layout || { version: 1, elements: [] }));
}

/** Convierte valores de formulario (string) a números del layout. */
export function toNumber(value, fallback = 0) {
    if (value === null || value === undefined || String(value).trim() === '') return fallback;

    const parsed = Number(value);

    return Number.isFinite(parsed) ? parsed : fallback;
}
