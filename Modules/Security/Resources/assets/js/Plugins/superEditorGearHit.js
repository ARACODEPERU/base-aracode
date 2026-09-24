/**
 * ¿Este click pertenece al engrane?
 *
 * Los engranes del Modo Super Editor son transparentes al puntero
 * (`pointer-events: none`) para que su caja no le robe el click al elemento que
 * decoran ni al que tienen al lado: por eso la pertenencia no la puede decidir
 * el DOM y la decide la geometría del rectángulo visible del engrane.
 *
 * El único click que no trae coordenadas es el de teclado (Enter/Espacio sobre
 * el botón, `detail === 0`); ahí sí se mira el destino del evento.
 *
 * Vive aparte de los componentes para poder probarse en Node
 * (tests/js/super-editor-gear-hit.test.mjs).
 */

/** ¿El punto cae dentro del rectángulo? Los bordes cuentan como dentro. */
export const pointInsideRect = (rect, x, y) => {
    if (!rect) {
        return false;
    }

    return x >= rect.left && x <= rect.right && y >= rect.top && y <= rect.bottom;
};

/** ¿El puntero del evento está encima del engrane? */
export const pointerOnGear = (event, rect) =>
    !!event && pointInsideRect(rect, event.clientX, event.clientY);

/**
 * @param {MouseEvent} event
 * @param {DOMRect|null} rect  Rectángulo actual del engrane (null si no se ve).
 * @param {boolean} active     ¿Hay un engrane visible al que pertenecer?
 */
export const gearOwnsClick = (event, rect, active = false) => {
    if (!active || !event) {
        return false;
    }

    if (event.detail === 0) {
        return !!event.target?.closest?.('[data-super-gear]');
    }

    return pointerOnGear(event, rect);
};
