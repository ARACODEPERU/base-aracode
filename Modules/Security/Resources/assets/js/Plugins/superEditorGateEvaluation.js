/**
 * Evaluación de las directivas de gate, sin DOM ni Vue.
 *
 * Vive aparte de superEditorDirectives.js para poder probarse en Node
 * (tests/js/super-editor-gate-evaluation.test.mjs), sobre todo la promesa
 * central: con el Modo Super Editor APAGADO el comportamiento es exactamente el
 * de vue-gates, y con el modo ENCENDIDO nunca se elimina un elemento del DOM.
 */

/** Equivalente a lodash.startCase, que vue-gates usa en su parseCondition. */
export const startCase = (value = '') =>
    String(value)
        .replace(/([a-z0-9])([A-Z])/g, '$1 $2')
        .replace(/[^A-Za-z0-9]+/g, ' ')
        .trim()
        .split(/\s+/)
        .filter(Boolean)
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');

/**
 * Nombre del método de $gates que vue-gates invoca para esta directiva.
 * v-can -> hasPermission, v-permission -> hasPermission, v-can:hasAll -> hasAllPermissions.
 */
export const parseCondition = (name, arg) => {
    let suffix = name === 'can' ? 'permission' : name;
    let method = 'has';

    if (arg) {
        if (arg === 'unless') {
            method = 'unless';
        } else if (arg !== 'has') {
            method += startCase(arg);
        }
    }

    if (method === 'hasAll') {
        suffix += 's';
    }

    return `${method}${startCase(suffix)}`;
};

/** Permiso detrás de un binding, aceptando string, array u objeto con metadatos. */
export const permissionOf = (value) => {
    if (Array.isArray(value)) {
        return value[0] ?? null;
    }

    if (value && typeof value === 'object') {
        return value.perm ?? value.permission ?? null;
    }

    return typeof value === 'string' ? value : null;
};

/** Metadatos explícitos que puede declarar el binding ({label, kind}). */
export const metaOf = (value) => (value && typeof value === 'object' ? value : {});

export const humanize = (permission) =>
    String(permission || '')
        .split('_')
        .filter(Boolean)
        .join(' ');

export const kindForTag = (tag) => {
    const normalized = String(tag || '').toLowerCase();

    if (normalized === 'button') {
        return 'botón';
    }

    if (normalized === 'a') {
        return 'enlace';
    }

    return 'elemento';
};

/** Etiqueta legible: el texto visible del elemento y, si no hay, el permiso. */
export const labelForText = (text, permission) => {
    const normalized = String(text || '').replace(/\s+/g, ' ').trim();

    if (normalized) {
        return normalized.slice(0, 80);
    }

    return humanize(permission) || permission || 'elemento';
};

/**
 * Decide qué hacer con el elemento.
 *
 * @returns {{action: 'keep'|'remove'|'assign', attributes?: object, decorate?: object|null}}
 *   - keep:   se deja el elemento tal cual (el gate lo permite, o estamos en modo editor).
 *   - remove: se elimina del DOM (comportamiento de vue-gates).
 *   - assign: se copian los modificadores al elemento (comportamiento de vue-gates).
 */
export const evaluateGate = ({ gate, name, arg = null, value, modifiers = null, editorActive = false }) => {
    if (editorActive) {
        const permission = permissionOf(value);

        return {
            action: 'keep',
            decorate: permission
                ? {
                      permission,
                      kind: metaOf(value).kind || null,
                      label: metaOf(value).label || null,
                  }
                : null,
        };
    }

    const method = parseCondition(name, arg);
    const isValid = typeof gate?.[method] === 'function' ? gate[method](value) : false;

    if (isValid) {
        return { action: 'keep' };
    }

    const modifierKeys = modifiers ? Object.keys(modifiers) : [];

    if (modifierKeys.length === 0) {
        return { action: 'remove' };
    }

    return { action: 'assign', attributes: modifiers };
};
