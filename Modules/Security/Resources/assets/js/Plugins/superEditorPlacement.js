/**
 * Dónde va el toggle de un elemento y por encima de qué debe pintarse.
 *
 * Vive aparte de los componentes para poder probarse en Node
 * (tests/js/super-editor-placement.test.mjs): aquí no se toca el DOM, solo hay
 * rectángulos y números.
 *
 * Resuelve dos problemas concretos:
 *
 *   - Colocación. El toggle no debe caer dentro de la caja clickeable del
 *     elemento que decora (dentro, un click suyo le robaría el click al
 *     elemento) ni sobre el CENTRO de otro control (ahí el click dejaría de ser
 *     del vecino y abriría el panel equivocado). Por eso se prefieren los
 *     huecos de FUERA y, entre ellos, los se prueban por coste: primero los que
 *     no pisan ninguna caja, después los que solo pisan cajas pero no centros.
 *     El hueco de dentro (`end`) es el último recurso, y el menú lateral no usa
 *     esta búsqueda porque declara su hueco en data-super-place.
 *   - Apilado. El toggle vive en el body con position: fixed; si el elemento
 *     está dentro de un menú desplegable (vue3-popper usa 9999, ant-design
 *     1050), un z-index fijo lo dejaría DETRÁS del menú. `gearZIndex()` mira los
 *     ancestros para quedar justo encima de ellos.
 */

/** Tamaños del toggle: `md` para elementos de página, `sm` para controles pequeños. */
export const TOGGLE_SIZES = {
    md: { width: 38, height: 20 },
    sm: { width: 30, height: 16 },
};

/** Por debajo de esto el toggle no debería quedar nunca: el editor es lo de arriba. */
export const MIN_GEAR_Z = 90;

const sizeOf = (name) => TOGGLE_SIZES[name] ?? TOGGLE_SIZES.md;

/**
 * Un control pequeño (icono de fila, botón del rail) recibe el toggle chico: con
 * el grande su rectángulo llegaría al centro del control vecino.
 */
export const sizeFor = (rect) => {
    if (!rect) {
        return 'md';
    }

    return rect.width < 96 || rect.height < 28 ? 'sm' : 'md';
};

const box = (top, left, size) => ({
    top,
    left,
    right: left + size.width,
    bottom: top + size.height,
    width: size.width,
    height: size.height,
});

export const centerOf = (rect) => ({
    x: (rect.left + rect.right) / 2,
    y: (rect.top + rect.bottom) / 2,
});

export const containsPoint = (rect, x, y) =>
    !!rect && x >= rect.left && x <= rect.right && y >= rect.top && y <= rect.bottom;

export const intersects = (a, b) =>
    !!a && !!b && a.left < b.right && b.left < a.right && a.top < b.bottom && b.top < a.bottom;

/**
 * Orden de preferencia cuando el hueco lo elige el editor.
 *
 * Primero FUERA del elemento y, si se puede, en su misma línea (derecha e
 * izquierda antes que arriba y abajo): fuera, el toggle no le quita ni un pixel
 * de su caja clickeable al elemento que decora —un click dentro del botón sigue
 * siendo del botón— y colocado al lado no tapa el renglón de al lado. El hueco
 * de dentro (`end`) queda como último recurso, para cuando no hay sitio fuera
 * (el borde de la ventana o un vecino pegado).
 *
 * El menú lateral no usa este orden: cada fila declara su hueco en
 * data-super-place (ver useSidebarEditor.rowAttrs), porque en el rail o junto al
 * chevron el sitio libre está, a propósito, dentro de la fila.
 */
export const PLACE_ORDER = ['outside-right', 'outside-left', 'above', 'below', 'end'];

/** Margen que se deja contra los bordes de la ventana. */
export const VIEWPORT_MARGIN = 4;

/**
 * Rectángulo del hueco pedido, o null si en ese elemento no cabe.
 *
 *   - `end`         dentro del elemento, pegado a su extremo derecho (último
 *                   recurso cuando no hay sitio fuera; el menú lateral lo
 *                   declara a mano).
 *   - `corner`      esquina superior derecha, dentro del elemento (rail de módulos).
 *   - `before-end`  dentro, dejando libre el control propio de la fila (el
 *                   chevron de las opciones con submenú).
 *   - `outside-*`, `above`, `below`  fuera de la caja del elemento.
 */
export const slotAt = (rect, place, sizeName = 'md') => {
    if (!rect) {
        return null;
    }

    const size = sizeOf(sizeName);
    const middle = Math.round(rect.top + (rect.height - size.height) / 2);

    switch (place) {
        case 'end':
            if (rect.width < size.width + 24 || rect.height < size.height + 4) {
                return null;
            }

            return box(middle, rect.right - size.width - 6, size);
        case 'corner':
            return box(rect.top, rect.right - size.width, size);
        case 'before-end':
            // 32px de margen: el chevron (y su padding) vive en los últimos ~28px.
            return box(middle, rect.right - 32 - size.width, size);
        case 'outside-left':
            return box(middle, rect.left - size.width - 6, size);
        case 'above':
            return box(rect.top - size.height - 4, rect.right - size.width, size);
        case 'below':
            return box(rect.bottom + 4, rect.right - size.width, size);
        case 'outside-right':
        default:
            return box(middle, rect.right + 6, size);
    }
};

/**
 * Coste de un hueco: 0 libre, 1 pisa cajas pero ningún centro, 2 pisa el centro
 * de un control (o el del propio elemento), 3 no cabe.
 */
export const slotCost = (slot, ownCenter, blockers = []) => {
    if (!slot) {
        return 3;
    }

    if (ownCenter && containsPoint(slot, ownCenter.x, ownCenter.y)) {
        return 2;
    }

    let cost = 0;

    for (const blocker of blockers) {
        const rect = blocker.rect ?? blocker;
        const center = blocker.center ?? centerOf(rect);

        if (containsPoint(slot, center.x, center.y)) {
            return 2;
        }

        if (intersects(slot, rect)) {
            cost = 1;
        }
    }

    return cost;
};

/**
 * ¿El hueco cabe entero dentro de la ventana? Un hueco que se sale del borde
 * acabaría recortado encima del propio elemento (el recorte lo hace el DOM con
 * position:fixed), que es justo lo que se quiere evitar.
 */
export const fitsInBounds = (slot, bounds, margin = VIEWPORT_MARGIN) => {
    if (!slot) {
        return false;
    }

    if (!bounds) {
        return true;
    }

    return (
        slot.left >= margin &&
        slot.top >= margin &&
        slot.right <= bounds.width - margin &&
        slot.bottom <= bounds.height - margin
    );
};

const search = (rect, sizeName, blockers, order, bounds) => {
    const ownCenter = rect ? centerOf(rect) : null;
    let best = null;

    for (const place of order) {
        const slot = slotAt(rect, place, sizeName);

        if (!slot || (bounds && !fitsInBounds(slot, bounds))) {
            continue;
        }

        const cost = slotCost(slot, ownCenter, blockers);

        if (cost === 0) {
            return { place, rect: slot };
        }

        if (!best || cost < best.cost) {
            best = { place, rect: slot, cost };
        }
    }

    return best;
};

/**
 * @param {{left:number,top:number,right:number,bottom:number}} rect  Caja del elemento decorado.
 * @param {string} sizeName  'md' | 'sm'
 * @param {Array<{rect:object,center?:{x:number,y:number}}>} blockers  Otros controles.
 * @param {{width:number,height:number}|null} bounds  Ventana visible, para no colocar el toggle fuera de ella.
 * @param {string[]} order  Orden de preferencia (por defecto PLACE_ORDER).
 * @returns {{place: string, rect: object}}  Hueco elegido (`rect` puede ser null si el elemento no tiene caja).
 */
export const pickSlot = (rect, sizeName = 'md', blockers = [], bounds = null, order = PLACE_ORDER) => {
    // Primero se buscan huecos que quepan en la ventana; si ninguno lo hace (un
    // elemento enorme o pegado a los cuatro bordes), se repite la búsqueda sin
    // esa exigencia y gana el hueco de siempre.
    const picked = search(rect, sizeName, blockers, order, bounds) ?? search(rect, sizeName, blockers, order, null);

    return picked
        ? { place: picked.place, rect: picked.rect }
        : { place: 'outside-right', rect: slotAt(rect, 'outside-right', sizeName) };
};

const defaultReader = (node) => (typeof window === 'undefined' ? null : window.getComputedStyle(node));

/**
 * Mayor z-index de los ancestros que crean contexto de apilado. Un menú
 * desplegado está en esa lista, así que el toggle puede quedar encima de él.
 *
 * @param {Element} el
 * @param {(node: Element) => CSSStyleDeclaration|null} readStyle  Inyectable para probarlo en Node.
 */
export const stackingZIndexOf = (el, readStyle = defaultReader) => {
    let node = el;
    let max = 0;

    while (node && node.nodeType === 1) {
        const style = readStyle(node);
        const zIndex = style && style.position && style.position !== 'static'
            ? Number.parseInt(style.zIndex, 10)
            : Number.NaN;

        if (Number.isFinite(zIndex) && zIndex > max) {
            max = zIndex;
        }

        node = node.parentElement;
    }

    return max;
};

/** z-index del toggle: justo por encima de lo que lo contiene, nunca por debajo del editor. */
export const gearZIndex = (el, readStyle = defaultReader) =>
    Math.max(MIN_GEAR_Z, stackingZIndexOf(el, readStyle) + 1);
