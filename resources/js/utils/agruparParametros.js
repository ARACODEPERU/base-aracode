/*
 * Agrupación y orden de los parámetros del sistema por el prefijo de su código.
 *
 * Los códigos empiezan por letras que dicen a qué grupo pertenecen (P de
 * generales, PC, PHD, PN, PTM, PW de página web, SC de credenciales) y siguen
 * con un número. El prefijo lo calcula el servidor (App\Support\OrdenDeParametros)
 * y viaja en cada parámetro; aquí solo se agrupa, se filtra y se ordena para la
 * pantalla de Parámetros (pestañas por prefijo y botón «Ordenar»).
 *
 * Vive aparte del componente para poder probarlo sin navegador
 * (tests/js/agrupar-parametros.test.mjs).
 */

/**
 * Prefijo de un parámetro, en mayúsculas. Cadena vacía si el código no empieza
 * por letras: esos parámetros forman un grupo propio («Sin prefijo») que va
 * primero.
 */
export const prefijoDe = (parametro) => String(parametro?.prefix ?? '').toUpperCase();

/**
 * Una entrada por prefijo, en el orden en que llegan los parámetros (el
 * servidor los manda ya ordenados por prefijo), con cuántos tiene cada grupo.
 *
 * @returns {Array<{prefijo: string, total: number}>}
 */
export const agruparPorPrefijo = (parametros) => {
    const grupos = new Map();

    for (const parametro of parametros ?? []) {
        const prefijo = prefijoDe(parametro);
        grupos.set(prefijo, (grupos.get(prefijo) ?? 0) + 1);
    }

    return [...grupos.entries()].map(([prefijo, total]) => ({ prefijo, total }));
};

/** Parámetros de un prefijo concreto. */
export const filtrarPorPrefijo = (parametros, prefijo) =>
    (parametros ?? []).filter((parametro) => prefijoDe(parametro) === String(prefijo ?? '').toUpperCase());

/**
 * Orden alternativo al del servidor: alfabético por el código completo, que es
 * como se leía antes («P000023» y «PW00001» mezclados por los dígitos).
 */
export const ordenarPorCodigo = (parametros) =>
    [...(parametros ?? [])].sort((a, b) =>
        String(a?.parameter_code ?? '').localeCompare(String(b?.parameter_code ?? ''))
    );
