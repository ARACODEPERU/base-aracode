let cachedCsrfToken = null;

export function setCsrfToken(token) {
    cachedCsrfToken = token || null;
    syncCsrfMeta(token);
}

export function getCsrfToken() {
    if (cachedCsrfToken) {
        return cachedCsrfToken;
    }

    const metaToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    return metaToken || null;
}

export function syncCsrfMeta(token) {
    const resolved = token || cachedCsrfToken;
    if (!resolved) return;

    const meta = document.querySelector('meta[name="csrf-token"]');
    if (meta) {
        meta.setAttribute('content', resolved);
    }
}

export function withCsrfPayload(data = {}) {
    const token = getCsrfToken();
    if (!token) return data;

    if (data instanceof FormData) {
        if (!data.has('_token')) {
            data.append('_token', token);
        }
        return data;
    }

    return { ...data, _token: token };
}

export function applyCsrfToAxiosConfig(config) {
    const token = getCsrfToken();
    if (!token) return config;

    const method = config.method?.toLowerCase() ?? 'get';
    if (!['post', 'put', 'patch', 'delete'].includes(method)) {
        return config;
    }

    config.headers = config.headers ?? {};
    if (typeof config.headers.set === 'function') {
        config.headers.set('X-CSRF-TOKEN', token);
    } else {
        config.headers['X-CSRF-TOKEN'] = token;
    }

    if (config.data instanceof FormData) {
        if (!config.data.has('_token')) {
            config.data.append('_token', token);
        }
    } else if (config.data && typeof config.data === 'object' && !(config.data instanceof URLSearchParams)) {
        config.data = { ...config.data, _token: token };
    } else if (config.data == null || config.data === '') {
        config.data = { _token: token };
    }

    return config;
}
