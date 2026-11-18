import '../css/aracodito.css';

/**
 * Aracodito — asistente virtual global.
 *
 * El robot se crea en <body> solo cuando se muestra un mensaje y se retira
 * al ocultarlo. Expone:
 *
 *   window.Aracodito.showMessage('Guardado correctamente', 'success');
 *   window.Aracodito.showMessage('Revisa el formulario', 'error', { duration: 0 });
 *   window.Aracodito.showMessage('¿Necesitas ayuda?', 'info', { ia: true });
 *
 * Botones de acción (equivalentes a los de Swal):
 *   const result = await window.Aracodito.showMessage('¿Eliminar el registro?', 'warning', {
 *       duration: 0,
 *       showCancelButton: true,
 *       confirmButtonText: 'Sí, eliminar',
 *       cancelButtonText: 'Cancelar',
 *   });
 *   if (result.isConfirmed) { ... }
 *
 * Confirmación con proceso asíncrono:
 *   const result = await window.Aracodito.showMessage('¿Eliminar el registro?', 'warning', {
 *       duration: 0,
 *       showCancelButton: true,
 *       onConfirm: async () => {
 *           const { data } = await axios.delete(url);
 *           return { message: data.message, type: 'success' };
 *       },
 *   });
 *
 * Mientras `onConfirm` se ejecuta, Aracodito permanece visible con una mini
 * terminal de consola en el pecho y una cara sonriente. El proceso puede
 * devolver un texto o un objeto { message, type, duration, ... } para el
 * mensaje final.
 *
 * `tipo`: 'success' | 'warning' | 'error' | 'confirm' | 'help' | 'info' | 'question'.
 * También acepta los alias en español: confirmacion, ayuda, informativo, pregunta.
 * `opciones`:
 *   - audio: reproduce el mensaje con la Web Speech API y anima la boca.
 *            Si se omite, se usa window.USER_CONFIG.aracodito_audio_enabled.
 *   - ia: muestra la etiqueta «IA» en la burbuja.
 *   - duration: milisegundos antes de ocultar la burbuja (0 = permanece).
 *   - showConfirmButton / showCancelButton: muestran OK y Cancelar.
 *   - confirmButtonText / cancelButtonText: cambian las etiquetas.
 *   - buttons / actions: arreglo personalizado de { label, value, action, variant, onClick }.
 *   - onAction: callback global al pulsar un botón.
 *   - onConfirm: función async que ejecuta el proceso al confirmar. Aracodito
 *                espera su resultado y después muestra el mensaje final.
 *   - loadingText, successMessage, errorMessage: textos del proceso.
 *   - minLoadingDuration: tiempo mínimo visible de la terminal (650 ms por defecto).
 *   - resultDuration: duración del mensaje final.
 */

const TYPES = ['success', 'warning', 'error', 'confirm', 'help', 'info', 'question'];

const TYPE_ALIASES = {
    confirmacion: 'confirm',
    'confirmación': 'confirm',
    ayuda: 'help',
    informativo: 'info',
    pregunta: 'question',
    warn: 'warning',
};
const DEFAULT_DURATION = 6000;

const STYLE_ALIASES = {
    clasico: 'classic',
    'clásico': 'classic',
    retro: 'pixel',
    pixelado: 'pixel',
    'pixel-art': 'pixel',
};

function normalizeType(tipo) {
    const requestedType = String(tipo || 'info').trim().toLowerCase();
    return TYPE_ALIASES[requestedType] || (TYPES.includes(requestedType) ? requestedType : 'info');
}

function normalizeStyle(style) {
    const requestedStyle = String(style || 'classic').trim().toLowerCase();
    const normalized = STYLE_ALIASES[requestedStyle] || requestedStyle;

    return normalized === 'pixel' ? 'pixel' : 'classic';
}

const CLASSIC_ICONS = {
    warning:
        '<svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 6a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 6zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>',
    confirm:
        '<svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 1.944A11.954 11.954 0 012.166 5.5c-.14.49-.216.998-.216 1.514 0 4.478 2.944 8.276 7.03 9.622a1.75 1.75 0 002.04 0c4.086-1.346 7.03-5.144 7.03-9.622 0-.516-.076-1.024-.216-1.514A11.954 11.954 0 0110 1.944zm3.78 6.32a.75.75 0 10-1.06-1.06L9.25 10.69 7.28 8.72a.75.75 0 10-1.06 1.06l2.5 2.5a.75.75 0 001.06 0l4-4z" clip-rule="evenodd"/></svg>',
    help:
        '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true"><circle cx="10" cy="10" r="7.25"/><circle cx="10" cy="10" r="3"/><path d="M10 2.75v4.25M10 13v4.25M2.75 10H7M13 10h4.25"/></svg>',
    question:
        '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 3h12a2 2 0 012 2v8a2 2 0 01-2 2h-6l-4 3v-3H4a2 2 0 01-2-2V5a2 2 0 012-2z"/><path d="M8.1 7.4a2 2 0 113.5 1.5c-.8.6-1.6 1-1.6 2.1"/><path d="M10 14h.01"/></svg>',
    success:
        '<svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>',
    error:
        '<svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>',
    info:
        '<svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd"/></svg>',
};

const PIXEL_ICONS = {
    // Iconos de 20×20 construidos con bloques de 4 px para el lenguaje pixel art.
    warning:
        '<svg viewBox="0 0 20 20" fill="currentColor" shape-rendering="crispEdges" aria-hidden="true"><path fill-rule="evenodd" d="M8 0h4v4h4v4h4v12H0V8h4V4h4V0Zm0 6h4v6H8V6Zm0 9h4v4H8v-4Z" clip-rule="evenodd"/></svg>',
    confirm:
        '<svg viewBox="0 0 20 20" fill="currentColor" shape-rendering="crispEdges" aria-hidden="true"><path d="M2 0h16v4h2v10h-2v4h-4v2H6v-2H2v-4H0V4h2V0Z"/></svg>',
    help:
        '<svg viewBox="0 0 20 20" fill="currentColor" shape-rendering="crispEdges" aria-hidden="true"><path d="M4 2h12v4H4zM12 6h4v6h-4zM8 10h4v4H8zM8 16h4v4H8z"/></svg>',
    question:
        '<svg viewBox="0 0 20 20" fill="currentColor" shape-rendering="crispEdges" aria-hidden="true"><path d="M4 2h12v4H4zM12 6h4v6h-4zM8 10h4v4H8zM8 16h4v4H8z"/></svg>',
    success:
        '<svg viewBox="0 0 20 20" fill="currentColor" shape-rendering="crispEdges" aria-hidden="true"><path d="M2 10h4v4H2zM6 14h4v4H6zM10 10h4v4h-4zM14 6h4v4h-4z"/></svg>',
    error:
        '<svg viewBox="0 0 20 20" fill="currentColor" shape-rendering="crispEdges" aria-hidden="true"><path d="M2 2h4v4H2zM6 6h4v4H6zM10 10h4v4h-4zM14 14h4v4h-4zM14 2h4v4h-4zM10 6h4v4h-4zM6 10h4v4H6zM2 14h4v4H2z"/></svg>',
    info:
        '<svg viewBox="0 0 20 20" fill="currentColor" shape-rendering="crispEdges" aria-hidden="true"><path d="M8 2h4v4H8zM8 8h4v10H8z"/></svg>',
};

const ROBOT_SVG = `
<svg class="aracodito__sprite" viewBox="0 0 120 130" role="img" aria-hidden="true" focusable="false" shape-rendering="geometricPrecision">
    <defs>
        <pattern id="aracodito-pixel-grid" width="4" height="4" patternUnits="userSpaceOnUse">
            <path d="M 4 0 H 0 V 4" fill="none" stroke="#7dd3fc" stroke-width="0.35" opacity="0.3" />
        </pattern>
    </defs>
    <g class="aracodito__head">
        <g fill="#071426" shape-rendering="crispEdges">
            <rect x="52" y="0" width="16" height="12" />
            <rect x="58" y="12" width="4" height="8" />
            <rect x="32" y="16" width="56" height="8" />
            <rect x="24" y="24" width="72" height="12" />
            <rect x="20" y="36" width="80" height="48" />
            <rect x="24" y="84" width="72" height="8" />
            <rect x="32" y="92" width="56" height="4" />
            <rect x="16" y="44" width="8" height="20" />
            <rect x="96" y="44" width="8" height="20" />
        </g>
        <g fill="#2563eb" shape-rendering="crispEdges">
            <rect x="36" y="20" width="48" height="8" />
            <rect x="28" y="28" width="64" height="8" />
            <rect x="24" y="40" width="72" height="40" />
            <rect x="28" y="80" width="64" height="8" />
            <rect x="36" y="88" width="48" height="4" />
            <rect x="20" y="48" width="4" height="12" />
            <rect x="96" y="48" width="4" height="12" />
        </g>
        <g fill="#60a5fa" shape-rendering="crispEdges">
            <rect x="36" y="20" width="32" height="4" />
            <rect x="28" y="28" width="8" height="8" />
            <rect x="24" y="40" width="8" height="32" />
        </g>
        <g fill="#1d4ed8" shape-rendering="crispEdges">
            <rect x="88" y="40" width="8" height="40" />
            <rect x="28" y="80" width="64" height="4" />
            <rect x="36" y="88" width="48" height="4" />
        </g>
        <g class="aracodito__pixel-grid" fill="url(#aracodito-pixel-grid)" shape-rendering="crispEdges">
            <rect x="36" y="20" width="48" height="8" />
            <rect x="28" y="28" width="64" height="8" />
            <rect x="24" y="40" width="72" height="40" />
            <rect x="28" y="80" width="64" height="8" />
            <rect x="36" y="88" width="48" height="4" />
            <rect x="20" y="48" width="4" height="12" />
            <rect x="96" y="48" width="4" height="12" />
        </g>
        <rect x="56" y="4" width="8" height="4" fill="#7dd3fc" shape-rendering="crispEdges" />
        <rect x="58" y="12" width="4" height="8" fill="#38bdf8" shape-rendering="crispEdges" />
        <g shape-rendering="crispEdges">
            <rect x="32" y="36" width="56" height="40" fill="#071426" />
            <rect x="36" y="40" width="48" height="32" fill="#050d18" />
            <rect x="36" y="40" width="48" height="4" fill="#0d2138" />
        </g>
        <g class="aracodito__eyes" fill="#38bdf8" shape-rendering="crispEdges">
            <rect x="44" y="48" width="8" height="8" />
            <rect x="44" y="48" width="4" height="4" fill="#e0f2fe" />
            <rect x="68" y="48" width="8" height="8" />
            <rect x="68" y="48" width="4" height="4" fill="#e0f2fe" />
        </g>
        <g class="aracodito__eyes-happy" fill="#7dd3fc" shape-rendering="crispEdges">
            <rect x="44" y="52" width="4" height="4" />
            <rect x="48" y="48" width="4" height="4" />
            <rect x="52" y="52" width="4" height="4" />
            <rect x="68" y="52" width="4" height="4" />
            <rect x="72" y="48" width="4" height="4" />
            <rect x="76" y="52" width="4" height="4" />
        </g>
        <rect class="aracodito__mouth" x="54" y="64" width="12" height="4" fill="#7dd3fc" shape-rendering="crispEdges" />
        <g class="aracodito__mouth-smile" fill="#7dd3fc" shape-rendering="crispEdges">
            <rect x="50" y="64" width="4" height="4" />
            <rect x="54" y="68" width="12" height="4" />
            <rect x="70" y="64" width="4" height="4" />
        </g>
    </g>
    <rect x="52" y="88" width="16" height="8" fill="#071426" shape-rendering="crispEdges" />
    <rect x="56" y="92" width="8" height="4" fill="#1d4ed8" shape-rendering="crispEdges" />
    <rect x="28" y="96" width="64" height="28" fill="#071426" shape-rendering="crispEdges" />
    <rect x="32" y="96" width="56" height="24" fill="#1e40af" shape-rendering="crispEdges" />
    <rect x="32" y="96" width="56" height="4" fill="#2563eb" shape-rendering="crispEdges" />
    <rect x="80" y="96" width="8" height="24" fill="#172554" shape-rendering="crispEdges" />
    <rect class="aracodito__pixel-grid" x="32" y="96" width="56" height="24" fill="url(#aracodito-pixel-grid)" shape-rendering="crispEdges" />
    <g class="aracodito__chest-core" shape-rendering="crispEdges">
        <rect x="52" y="100" width="16" height="16" fill="#071426" />
        <rect x="56" y="96" width="8" height="4" fill="#071426" />
        <rect x="56" y="116" width="8" height="4" fill="#071426" />
        <rect x="52" y="104" width="16" height="8" fill="#38bdf8" />
        <rect x="56" y="100" width="8" height="16" fill="#38bdf8" />
        <rect x="56" y="104" width="8" height="8" fill="#7dd3fc" />
        <rect x="56" y="104" width="4" height="4" fill="#e0f2fe" />
    </g>
    <g class="aracodito__chest-screen" aria-hidden="true">
        <rect class="aracodito__chest-screen-bezel" x="39" y="100" width="42" height="18" fill="#071426" stroke="#7dd3fc" stroke-width="0.8" shape-rendering="crispEdges" />
        <rect x="40.8" y="101.5" width="38.5" height="14.9" fill="#050d18" />
        <rect x="40.8" y="101.5" width="38.5" height="3.2" fill="#0d2138" />
        <rect x="42.4" y="102.6" width="1.2" height="1.2" fill="#fb7185" />
        <rect x="44.7" y="102.6" width="1.2" height="1.2" fill="#fbbf24" />
        <rect x="46.9" y="102.6" width="1.2" height="1.2" fill="#4ade80" />
        <text x="63.6" y="104.1" text-anchor="middle" font-family="monospace" font-size="2.1" font-weight="700" fill="#7dd3fc">ac_build</text>
        <text x="42.2" y="108.1" font-family="monospace" font-size="2.7" font-weight="700" fill="#7dd3fc">&gt; build</text>
        <rect class="aracodito__terminal-cursor" x="54.3" y="105.7" width="1.1" height="2.4" fill="#7dd3fc" />
        <g class="aracodito__terminal-code">
            <rect class="aracodito__terminal-line aracodito__terminal-line--1" x="42.2" y="109.3" width="22.3" height="0.9" fill="#38bdf8" />
            <rect class="aracodito__terminal-line aracodito__terminal-line--2" x="42.2" y="111.1" width="28.6" height="0.9" fill="#60a5fa" />
            <rect class="aracodito__terminal-line aracodito__terminal-line--3" x="42.2" y="112.9" width="17" height="0.9" fill="#818cf8" />
        </g>
        <rect class="aracodito__terminal-progress-track" x="42.2" y="114.8" width="35.7" height="0.9" fill="#1e3a5f" />
        <rect class="aracodito__terminal-progress" x="42.2" y="114.8" width="35.7" height="0.9" fill="#4ade80" />
    </g>
</svg>`;

const CLASSIC_ROBOT_SVG = `
<svg viewBox="0 0 120 130" role="img" aria-hidden="true" focusable="false">
    <defs>
        <linearGradient id="aracodito-widget-body" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0%" stop-color="#7dd3fc" />
            <stop offset="100%" stop-color="#2563eb" />
        </linearGradient>
    </defs>
    <g class="aracodito__head">
        <line x1="60" y1="14" x2="60" y2="26" stroke="#dbeafe" stroke-width="4" stroke-linecap="round" />
        <circle cx="60" cy="10" r="5" fill="#38bdf8" />
        <rect x="22" y="26" width="76" height="62" rx="24" fill="url(#aracodito-widget-body)" />
        <rect x="32" y="42" width="56" height="28" rx="14" fill="#0f172a" />
        <g class="aracodito__eyes">
            <circle cx="48" cy="56" r="5" fill="#38bdf8" />
            <circle cx="72" cy="56" r="5" fill="#38bdf8" />
        </g>
        <g class="aracodito__eyes-happy">
            <path d="M43.5 57.5 Q48 52.8 52.5 57.5" fill="none" stroke="#38bdf8" stroke-width="2.4" stroke-linecap="round" />
            <path d="M67.5 57.5 Q72 52.8 76.5 57.5" fill="none" stroke="#38bdf8" stroke-width="2.4" stroke-linecap="round" />
        </g>
        <rect class="aracodito__mouth" x="52" y="64" width="16" height="4" rx="2" fill="#7dd3fc" />
        <path class="aracodito__mouth-smile" d="M52 64.4 Q60 69.6 68 64.4" fill="none" stroke="#7dd3fc" stroke-width="2.2" stroke-linecap="round" />
        <rect x="14" y="48" width="8" height="20" rx="4" fill="#2563eb" />
        <rect x="98" y="48" width="8" height="20" rx="4" fill="#2563eb" />
    </g>
    <rect x="52" y="88" width="16" height="8" fill="#1d4ed8" />
    <rect x="34" y="96" width="52" height="26" rx="12" fill="#1e40af" />
    <circle class="aracodito__chest-core" cx="60" cy="109" r="6" fill="#38bdf8" />
    <g class="aracodito__chest-screen" aria-hidden="true">
        <rect class="aracodito__chest-screen-bezel" x="39" y="100" width="42" height="18" rx="3.8" fill="#123a6b" stroke="#7dd3fc" stroke-width="0.8" />
        <rect x="40.8" y="101.5" width="38.5" height="14.9" rx="2" fill="#050d18" />
        <rect x="40.8" y="101.5" width="38.5" height="3.2" rx="2" fill="#0d2138" />
        <circle cx="43" cy="103.2" r="0.6" fill="#fb7185" />
        <circle cx="45.3" cy="103.2" r="0.6" fill="#fbbf24" />
        <circle cx="47.5" cy="103.2" r="0.6" fill="#4ade80" />
        <text x="63.6" y="104.1" text-anchor="middle" font-family="monospace" font-size="2.1" fill="#7dd3fc">ac_build</text>
        <text x="42.2" y="108.1" font-family="monospace" font-size="2.7" fill="#7dd3fc">&gt; build</text>
        <rect class="aracodito__terminal-cursor" x="54.3" y="105.7" width="1.1" height="2.4" rx="0.4" fill="#7dd3fc" />
        <g class="aracodito__terminal-code">
            <rect class="aracodito__terminal-line aracodito__terminal-line--1" x="42.2" y="109.3" width="22.3" height="0.9" rx="0.45" fill="#38bdf8" />
            <rect class="aracodito__terminal-line aracodito__terminal-line--2" x="42.2" y="111.1" width="28.6" height="0.9" rx="0.45" fill="#60a5fa" />
            <rect class="aracodito__terminal-line aracodito__terminal-line--3" x="42.2" y="112.9" width="17" height="0.9" rx="0.45" fill="#818cf8" />
        </g>
        <rect class="aracodito__terminal-progress-track" x="42.2" y="114.8" width="35.7" height="0.9" rx="0.45" fill="#1e3a5f" />
        <rect class="aracodito__terminal-progress" x="42.2" y="114.8" width="35.7" height="0.9" rx="0.45" fill="#4ade80" />
    </g>
</svg>`;

let root = null;
let bubble = null;
let icon = null;
let text = null;
let badge = null;
let actions = null;
let closeButton = null;
let avatar = null;
let currentActions = [];
let currentResolve = null;
let currentOnAction = null;
let currentConfirm = null;
let currentOptions = null;
let loadToken = 0;
let hideTimer = null;
let removeTimer = null;
let speechToken = 0;
let currentStyle = 'classic';
let currentType = 'info';

function getIcons() {
    return currentStyle === 'pixel' ? PIXEL_ICONS : CLASSIC_ICONS;
}

/**
 * Aplica el estilo visual al widget y cambia el sprite sin remontar el aviso.
 */
function applyStyle(style) {
    currentStyle = normalizeStyle(style);

    if (root) {
        root.classList.toggle('aracodito--pixel', currentStyle === 'pixel');
        root.classList.toggle('aracodito--classic', currentStyle !== 'pixel');

        if (avatar) {
            avatar.innerHTML = currentStyle === 'pixel' ? ROBOT_SVG : CLASSIC_ROBOT_SVG;
        }

        if (icon) {
            icon.innerHTML = getIcons()[currentType];
        }
    }

    return currentStyle;
}

/**
 * Crea el widget dentro de <body> si aún no existe.
 */
function mount() {
    if (typeof document === 'undefined') {
        return null;
    }

    if (!document.body) {
        return null;
    }

    if (root && root.isConnected) {
        clearTimeout(removeTimer);
        removeTimer = null;
        return root;
    }

    root = document.createElement('div');
    root.id = 'aracodito-widget';
    root.className = `aracodito--info aracodito--${currentStyle}`;
    root.innerHTML = `
        <div class="aracodito__bubble" role="status" aria-live="polite" aria-atomic="true">
            <button type="button" class="aracodito__close" aria-label="Cerrar mensaje de Aracodito" title="Cerrar">
                <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="square" aria-hidden="true">
                    <path d="M5 5l10 10M15 5L5 15" />
                </svg>
            </button>
            <span class="aracodito__tail" aria-hidden="true">
                <svg viewBox="0 0 24 20" shape-rendering="crispEdges">
                    <g class="aracodito__tail-outline">
                        <rect width="24" height="4" />
                        <rect x="2" y="4" width="20" height="4" />
                        <rect x="4" y="8" width="16" height="4" />
                        <rect x="6" y="12" width="12" height="4" />
                        <rect x="8" y="16" width="8" height="4" />
                    </g>
                    <g class="aracodito__tail-fill">
                        <rect x="2" width="20" height="4" />
                        <rect x="4" y="4" width="16" height="4" />
                        <rect x="6" y="8" width="12" height="4" />
                        <rect x="8" y="12" width="8" height="4" />
                        <rect x="10" y="16" width="4" height="4" />
                    </g>
                </svg>
            </span>
            <span class="aracodito__icon" aria-hidden="true"></span>
            <div class="aracodito__content">
                <p class="aracodito__text"></p>
                <span class="aracodito__badge" hidden>IA</span>
                <div class="aracodito__actions" hidden></div>
            </div>
        </div>
        <button type="button" class="aracodito__avatar" aria-label="Aracodito, asistente virtual">
            ${currentStyle === 'pixel' ? ROBOT_SVG : CLASSIC_ROBOT_SVG}
        </button>
    `;

    document.body.appendChild(root);

    bubble = root.querySelector('.aracodito__bubble');
    icon = root.querySelector('.aracodito__icon');
    text = root.querySelector('.aracodito__text');
    badge = root.querySelector('.aracodito__badge');
    actions = root.querySelector('.aracodito__actions');
    closeButton = root.querySelector('.aracodito__close');
    avatar = root.querySelector('.aracodito__avatar');

    // La X, el robot y los botones de acción cierran el mensaje actual.
    closeButton.addEventListener('click', () => hide('close'));
    avatar.addEventListener('click', () => hide('avatar'));
    actions.addEventListener('click', handleActionClick);

    return root;
}

function getUserConfig() {
    return (typeof window !== 'undefined' && window.USER_CONFIG) || {};
}

function getSpeechSynthesis() {
    return typeof window !== 'undefined' && 'speechSynthesis' in window ? window.speechSynthesis : null;
}

/**
 * Detiene la voz en curso (si la hay) y apaga la animación de la boca.
 */
function stopSpeaking() {
    speechToken += 1;

    const synth = getSpeechSynthesis();
    if (synth) {
        try {
            synth.cancel();
        } catch (error) {
            // Algunos navegadores no implementan cancel(); se ignora.
        }
    }

    if (root) {
        root.classList.remove('is-talking');
    }
}

/**
 * Reproduce el mensaje con la Web Speech API y mueve la boca mientras habla.
 *
 * @returns {boolean} true si el navegador pudo iniciar la síntesis.
 */
function speak(message) {
    const synth = getSpeechSynthesis();
    const Utterance = typeof window !== 'undefined' ? window.SpeechSynthesisUtterance : null;

    if (!synth || typeof Utterance !== 'function' || !message) {
        return false;
    }

    stopSpeaking();

    // Identifica esta reproducción: los callbacks de una voz anterior no deben
    // apagar la animación de la voz nueva.
    const token = ++speechToken;
    const utterance = new Utterance(message);
    const lang = (typeof document !== 'undefined' && document.documentElement.lang) || (typeof navigator !== 'undefined' ? navigator.language : '') || 'es-ES';
    utterance.lang = lang;

    const finish = () => {
        if (token === speechToken && root) {
            root.classList.remove('is-talking');
        }
    };

    utterance.onend = finish;
    utterance.onerror = finish;

    // La clase se agrega antes de speak(): hay navegadores que no disparan
    // onstart cuando no encuentran voces para el idioma.
    if (root) {
        root.classList.add('is-talking');
    }

    try {
        synth.speak(utterance);
    } catch (error) {
        finish();
        return false;
    }

    return true;
}

function settle(result) {
    const resolve = currentResolve;

    currentResolve = null;
    currentActions = [];
    currentOnAction = null;
    currentConfirm = null;
    currentOptions = null;

    if (typeof resolve === 'function') {
        resolve(result);
    }
}

function normalizeAction(action, index) {
    const source = typeof action === 'string'
        ? { label: action }
        : (action && typeof action === 'object' ? action : {});
    const value = source.value ?? (source.action === 'confirm' ? true : source.action === 'cancel' ? false : undefined);
    const kind = source.action || (value === true ? 'confirm' : value === false ? 'cancel' : 'custom');
    const fallbackLabel = kind === 'confirm' ? 'OK' : kind === 'cancel' ? 'Cancelar' : `Acción ${index + 1}`;

    return {
        ...source,
        label: String(source.label ?? source.text ?? fallbackLabel),
        value,
        action: kind,
        variant: source.variant || (kind === 'confirm' ? 'primary' : 'secondary'),
    };
}

function resolveActions(options) {
    const custom = Array.isArray(options.buttons)
        ? options.buttons
        : Array.isArray(options.actions)
            ? options.actions
            : null;

    if (custom) {
        return custom.map(normalizeAction);
    }

    const list = [];

    if (options.showCancelButton === true) {
        list.push(normalizeAction({
            label: options.cancelButtonText || 'Cancelar',
            value: false,
            action: 'cancel',
        }, list.length));
    }

    const showConfirm = options.showConfirmButton !== false && (
        options.showConfirmButton === true
        || options.showCancelButton === true
        || options.confirmButtonText !== undefined
        || typeof options.onConfirm === 'function'
    );

    if (showConfirm) {
        list.push(normalizeAction({
            label: options.confirmButtonText || 'OK',
            value: true,
            action: 'confirm',
        }, list.length));
    }

    return list;
}

function renderActions(list) {
    if (!actions) {
        return;
    }

    actions.innerHTML = '';
    actions.hidden = list.length === 0;

    list.forEach((action, index) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = `aracodito__action aracodito__action--${action.variant}`;
        button.textContent = action.label;
        button.dataset.index = String(index);

        if (action.ariaLabel) {
            button.setAttribute('aria-label', action.ariaLabel);
        }

        actions.appendChild(button);
    });
}

function delay(ms) {
    return ms > 0 ? new Promise((resolve) => setTimeout(resolve, ms)) : Promise.resolve();
}

function normalizeProcessResult(value, fallbackMessage = '') {
    if (value instanceof Error) {
        return { message: value.message || fallbackMessage, type: 'error', error: value };
    }

    if (typeof value === 'string') {
        return { message: value, type: 'success' };
    }

    if (!value || typeof value !== 'object') {
        return { message: fallbackMessage, type: 'success' };
    }

    const payload = value.data && typeof value.data === 'object' ? value.data : null;
    const message = value.message ?? value.text ?? payload?.message ?? payload?.data?.message;
    const success = value.success ?? payload?.success;
    const type = value.type ?? value.icon ?? (success === false ? 'error' : 'success');

    return {
        ...value,
        message: typeof message === 'string' && message.trim() ? message : fallbackMessage,
        type,
        success,
    };
}

function getErrorMessage(error, fallbackMessage = '') {
    if (typeof error === 'string' && error.trim()) {
        return error;
    }

    const responseData = error?.response?.data;
    const validation = responseData?.errors && typeof responseData.errors === 'object'
        ? Object.values(responseData.errors).flat()[0]
        : null;

    return responseData?.message
        || validation
        || responseData?.error
        || error?.message
        || fallbackMessage;
}

async function runConfirmation(actionResult, action, handler, options) {
    const token = ++loadToken;
    const minDuration = Math.max(0, Number(options.minLoadingDuration ?? 650) || 0);
    const loadingText = String(options.loadingText || 'Procesando…');

    clearTimeout(hideTimer);
    hideTimer = null;
    stopSpeaking();

    if (root) {
        root.classList.add('is-loading');
    }

    if (bubble) {
        bubble.classList.add('is-visible');
        bubble.setAttribute('aria-busy', 'true');
    }

    if (text) {
        text.textContent = loadingText;
    }

    if (actions) {
        actions.hidden = true;
    }

    try {
        const [outcome] = await Promise.all([
            Promise.resolve()
                .then(() => handler(actionResult, action))
                .then(
                    (value) => ({ value, error: null }),
                    (error) => ({ value: null, error })
                ),
            delay(minDuration),
        ]);

        if (token !== loadToken) {
            return;
        }

        if (outcome.error) {
            throw outcome.error;
        }

        const value = outcome.value;
        const normalized = normalizeProcessResult(value);
        const resultType = normalizeType(
            normalized.type || (normalized.success === false ? 'error' : 'success')
        );
        const isError = resultType === 'error' || normalized.success === false;
        const message = normalized.message
            || (isError
                ? (options.errorMessage || 'No se pudo completar el proceso.')
                : (options.successMessage || 'Proceso completado correctamente.'));

        const finalResult = {
            ...actionResult,
            isConfirmed: true,
            isDismissed: false,
            success: !isError,
            message,
            data: value,
        };

        settle(finalResult);

        showMessage(message, isError ? 'error' : resultType, {
            duration: normalized.duration ?? options.resultDuration ?? DEFAULT_DURATION,
            audio: options.audio,
            ia: options.ia,
            showConfirmButton: normalized.showConfirmButton ?? true,
            showCancelButton: normalized.showCancelButton ?? false,
            confirmButtonText: normalized.confirmButtonText,
            cancelButtonText: normalized.cancelButtonText,
            buttons: normalized.buttons ?? normalized.actions,
            onAction: normalized.onAction,
        });
    } catch (error) {
        if (token !== loadToken) {
            return;
        }

        const message = getErrorMessage(
            error,
            options.errorMessage || 'No se pudo completar el proceso.'
        );
        const finalResult = {
            ...actionResult,
            isConfirmed: true,
            isDismissed: false,
            success: false,
            message,
            error,
            data: null,
        };

        settle(finalResult);

        showMessage(message, 'error', {
            duration: options.errorDuration ?? options.resultDuration ?? DEFAULT_DURATION,
            audio: options.audio,
            ia: options.ia,
            showConfirmButton: true,
        });
    }
}

function handleActionClick(event) {
    const button = event.target.closest('.aracodito__action');

    if (!button || !actions?.contains(button)) {
        return;
    }

    const action = currentActions[Number(button.dataset.index)];

    if (!action) {
        return;
    }

    const result = {
        isConfirmed: action.action === 'confirm',
        isDismissed: action.action === 'cancel',
        ...(action.action === 'cancel' ? { dismiss: 'cancel' } : {}),
        value: action.value,
        action: action.action,
    };
    const onClick = action.onClick;
    const onAction = currentOnAction;
    const confirmHandler = action.action === 'confirm' ? currentConfirm : null;

    if (typeof confirmHandler === 'function') {
        if (typeof onClick === 'function') {
            onClick(result, action);
        }

        if (typeof onAction === 'function') {
            onAction(result, action);
        }

        // No se oculta: el proceso se ejecuta dentro de Aracodito y la burbuja
        // permanece visible con la terminal del pecho hasta recibir el resultado.
        runConfirmation(result, action, confirmHandler, currentOptions || {});
        return;
    }

    // Primero se oculta para que un callback pueda abrir un mensaje nuevo.
    hide(result);

    if (typeof onClick === 'function') {
        onClick(result, action);
    }

    if (typeof onAction === 'function') {
        onAction(result, action);
    }
}

/**
 * Muestra un mensaje en la burbuja sobre la cabeza del robot.
 */
function showMessage(mensaje, tipo = 'info', opciones = {}) {
    clearTimeout(removeTimer);
    removeTimer = null;
    loadToken += 1;

    settle({ isConfirmed: false, isDismissed: true, dismiss: 'replaced' });

    if (!mount()) {
        return Aracodito;
    }

    const options = opciones || {};
    const type = normalizeType(tipo);
    currentType = type;

    // El estilo puede venir en la llamada o en la preferencia global del usuario.
    applyStyle(options.style ?? getUserConfig().aracodito_style ?? currentStyle);

    if (root) {
        root.classList.remove('is-loading');
    }

    if (bubble) {
        bubble.removeAttribute('aria-busy');
    }

    TYPES.forEach((name) => root.classList.remove(`aracodito--${name}`));
    root.classList.add(`aracodito--${type}`);

    icon.innerHTML = getIcons()[type];

    const message = String(mensaje ?? '');
    text.textContent = message;
    badge.hidden = !options.ia;

    const nextActions = resolveActions(options);
    currentActions = nextActions;
    currentOnAction = typeof options.onAction === 'function' ? options.onAction : null;
    currentConfirm = typeof options.onConfirm === 'function' ? options.onConfirm : null;
    currentOptions = options;
    const resultPromise = nextActions.length
        ? new Promise((resolve) => {
            currentResolve = resolve;
        })
        : null;
    renderActions(nextActions);

    // options.audio manda; si no se envía, se usa la preferencia del usuario.
    const audioEnabled = options.audio ?? getUserConfig().aracodito_audio_enabled ?? false;

    clearTimeout(hideTimer);
    bubble.classList.add('is-visible');

    if (audioEnabled) {
        speak(message);
    } else {
        stopSpeaking();
    }

    const durationValue = options.duration ?? (currentConfirm ? 0 : DEFAULT_DURATION);
    const duration = Number(durationValue);
    if (Number.isFinite(duration) && duration > 0) {
        hideTimer = setTimeout(() => hide('timer'), duration);
    }

    return resultPromise || Aracodito;
}

/**
 * Oculta la burbuja y retira el robot del DOM.
 * Si había una acción pendiente, la resuelve como descartada.
 */
function hide(reason = 'close') {
    const result = reason && typeof reason === 'object'
        ? reason
        : { isConfirmed: false, isDismissed: true, dismiss: String(reason || 'close') };

    loadToken += 1;
    settle(result);

    clearTimeout(hideTimer);
    hideTimer = null;
    stopSpeaking();

    if (root) {
        root.classList.remove('is-loading');
    }

    if (bubble) {
        bubble.classList.remove('is-visible');
        bubble.removeAttribute('aria-busy');
    }

    if (actions) {
        actions.hidden = true;
    }

    // Se retira después del fundido: Aracodito no queda fijo en la esquina,
    // solo existe mientras hay un mensaje visible.
    clearTimeout(removeTimer);
    removeTimer = setTimeout(() => {
        if (root) {
            root.remove();
        }

        root = null;
        bubble = null;
        icon = null;
        text = null;
        badge = null;
        actions = null;
        closeButton = null;
        avatar = null;
        currentActions = [];
        currentOnAction = null;
        currentConfirm = null;
        currentOptions = null;
        currentType = 'info';
        removeTimer = null;
    }, 260);

    return Aracodito;
}

const Aracodito = {
    showMessage,
    hide,
    applyStyle,
    setStyle: applyStyle,
    getStyle: () => currentStyle,
    version: '1.3.0',
};

if (typeof window !== 'undefined') {
    window.Aracodito = Aracodito;
}

export default Aracodito;
