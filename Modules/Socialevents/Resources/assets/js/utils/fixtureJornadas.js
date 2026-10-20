/**
 * Jornadas de un cruce de ida y vuelta.
 *
 * Un cruce ocupa dos jornadas: la ida en una fecha y la vuelta en la
 * siguiente. El campo del formulario tiene máscara de dos dígitos, así que la
 * jornada nunca pasa de 99.
 */

export const JORNADA_MAXIMA = 99;

/**
 * Jornada de la vuelta sugerida a partir de la jornada de la ida.
 *
 * Sin jornada de ida se asume la primera (la ida arranca el cruce), así que la
 * vuelta cae en la segunda. Si la ida ya está en la última jornada posible, la
 * vuelta se queda en el máximo en vez de desbordar el campo.
 */
export function jornadaVuelta(ida) {
    const base = Number(ida);
    const desde = Number.isFinite(base) && base >= 1 ? Math.floor(base) : 1;

    return Math.min(JORNADA_MAXIMA, desde + 1);
}

/**
 * Regla de negocio: la vuelta se juega después de la ida, nunca en la misma
 * jornada ni en una anterior.
 */
export function jornadaVueltaValida(ida, vuelta) {
    const base = Number(ida);
    const regreso = Number(vuelta);

    if (! Number.isFinite(base) || base < 1 || ! Number.isFinite(regreso)) {
        return false;
    }

    return regreso > base && regreso >= 1 && regreso <= JORNADA_MAXIMA;
}
