/**
 * Contador de vistas de artículos del blog.
 *
 * El conteo NO ocurre al renderizar: el render público (/article/{url}) y el
 * panel del alumno (/blog/article/{url}/show) solo muestran el artículo, y este
 * módulo avisa al endpoint POST /blog/{url}/vista (blog_article_view) cuando
 * corresponde. La regla es: una vista por navegador cada 24 horas por artículo.
 *
 * El registro vive en localStorage (clave `blog_viewed_articles`) como un mapa
 * { "<slug>": <timestamp> }, así que la misma visita no se cuenta ni pasando de
 * la web pública al panel. Si el navegador no permite localStorage, no se cuenta
 * nada (mejor no contar que inflar el contador en cada carga).
 *
 * Lo usan `resources/js/webpage.js` (web pública) y
 * `Modules/Blog/Resources/assets/js/Pages/articles/Show.vue` (panel del alumno).
 */

export const VIEW_STORAGE_KEY = 'blog_viewed_articles';
export const VIEW_TTL_MS = 24 * 60 * 60 * 1000;

export function viewFallbackUrl(slug) {
    return '/blog/' + encodeURIComponent(slug) + '/vista';
}

function defaultStorage() {
    try {
        return typeof window !== 'undefined' ? window.localStorage : null;
    } catch (error) {
        // Safari en modo privado o storage bloqueado por el navegador.
        return null;
    }
}

/** Mapa completo de visitas registradas (tolerante a datos corruptos). */
export function readViewRegistry(storage = defaultStorage()) {
    if (!storage) {
        return {};
    }

    try {
        const raw = storage.getItem(VIEW_STORAGE_KEY);
        const parsed = raw ? JSON.parse(raw) : null;

        return parsed && typeof parsed === 'object' && !Array.isArray(parsed) ? parsed : {};
    } catch (error) {
        return {};
    }
}

function writeViewRegistry(registry, storage = defaultStorage()) {
    if (!storage) {
        return false;
    }

    try {
        storage.setItem(VIEW_STORAGE_KEY, JSON.stringify(registry));

        return true;
    } catch (error) {
        return false;
    }
}

/** ¿Este navegador ya contó este artículo dentro de las últimas 24 horas? */
export function hasRecentView(slug, { storage = defaultStorage(), now = Date.now() } = {}) {
    if (!slug) {
        return true;
    }

    const timestamp = readViewRegistry(storage)[slug];

    if (typeof timestamp !== 'number' || !isFinite(timestamp)) {
        return false;
    }

    return now - timestamp < VIEW_TTL_MS;
}

/** Guarda la marca de "ya contado" para este artículo. */
export function markView(slug, { storage = defaultStorage(), now = Date.now() } = {}) {
    if (!slug) {
        return false;
    }

    const registry = readViewRegistry(storage);
    registry[slug] = now;
    pruneViewRegistry(registry, now);

    return writeViewRegistry(registry, storage);
}

/** Quita la marca (se usa cuando el POST falla, para reintentar en otra carga). */
export function forgetView(slug, { storage = defaultStorage() } = {}) {
    if (!slug) {
        return false;
    }

    const registry = readViewRegistry(storage);

    if (!(slug in registry)) {
        return true;
    }

    delete registry[slug];

    return writeViewRegistry(registry, storage);
}

/** Descarta marcas vencidas para que el mapa no crezca sin límite. */
function pruneViewRegistry(registry, now) {
    Object.keys(registry).forEach((key) => {
        const timestamp = registry[key];

        if (typeof timestamp !== 'number' || now - timestamp >= VIEW_TTL_MS) {
            delete registry[key];
        }
    });

    return registry;
}

/**
 * URL del endpoint. Usa Ziggy cuando está disponible (panel) y cae al prefijo
 * del blog si la ruta no está publicada, para no romper la página.
 */
export function resolveViewUrl(slug) {
    if (typeof window !== 'undefined' && typeof window.route === 'function') {
        try {
            const url = window.route('blog_article_view', slug);

            if (url) {
                return url;
            }
        } catch (error) {
            // Ziggy no conoce blog_article_view: se usa el respaldo.
        }
    }

    return viewFallbackUrl(slug);
}

function csrfToken() {
    const meta = typeof document !== 'undefined' ? document.querySelector('meta[name="csrf-token"]') : null;

    return meta ? meta.getAttribute('content') || '' : '';
}

/**
 * Registra la vista si corresponde. Nunca lanza: devuelve `true` solo cuando el
 * servidor confirmó el conteo y además deja la marca de 24 horas.
 *
 * @param {Object} options
 * @param {string} options.slug   slug del artículo (clave del registro local)
 * @param {string} [options.url]  URL del endpoint; si falta se resuelve con Ziggy
 * @param {Function} [options.onCounted] recibe el número de vistas confirmado
 */
export async function registerBlogArticleView({ slug, url, onCounted, storage, now, fetcher } = {}) {
    if (!slug) {
        return false;
    }

    // Sin un lugar donde recordar la marca (localStorage bloqueado o ausente) no se
    // cuenta: el POST iria en cada carga y el contador se inflaria.
    const resolvedStorage = storage === undefined ? defaultStorage() : storage;

    if (!resolvedStorage) {
        return false;
    }

    const options = { storage: resolvedStorage, now };

    if (hasRecentView(slug, options)) {
        return false;
    }

    const target = url || resolveViewUrl(slug);
    const request = fetcher || (typeof fetch === 'function' ? fetch : null);

    if (!target || !request) {
        return false;
    }

    try {
        const response = await request(target, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin',
            body: JSON.stringify({}),
        });

        const data = await response.json().catch(() => null);

        if (!response.ok || !data || data.success !== true) {
            // La vista NO se cuenta: no se marca, así se reintenta en otra carga.
            return false;
        }

        markView(slug, options);

        if (typeof onCounted === 'function') {
            onCounted(Number(data.views) || 0);
        }

        return true;
    } catch (error) {
        return false;
    }
}

/**
 * Arranque en la página pública del artículo: si el contador está en pantalla,
 * registra la visita y refresca el número sin recargar.
 */
export function initBlogArticleViewCounter() {
    if (typeof document === 'undefined') {
        return;
    }

    const holder = document.getElementById('article-views-count');

    if (!holder) {
        return;
    }

    registerBlogArticleView({
        slug: holder.getAttribute('data-slug'),
        url: holder.getAttribute('data-url'),
        onCounted: (views) => {
            if (views > 0) {
                holder.textContent = views;
            }
        },
    });
}
