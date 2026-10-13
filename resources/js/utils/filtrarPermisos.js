/*
 * Filtro del buscador de permisos en la pantalla de edición de roles.
 *
 * El rol se edita a partir de permisos agrupados por módulo (carpetas) que
 * llegan ya ordenados desde el servidor. Cuando el rol tiene cientos de
 * permisos, encontrar uno concreto obliga a abrir carpeta por carpeta, por eso
 * el buscador filtra en el frontend: deja solo las carpetas que contienen
 * coincidencias y, dentro de cada una, solo los permisos encontrados.
 *
 * Además del texto del buscador hay dos estados que responden a los botones
 * «Habilitados» y «No habilitados»: el primero deja ver solo los permisos que
 * tiene el rol y el segundo solo los que le faltan, siempre buscando en todas
 * las carpetas de módulos.
 *
 * Vive aparte del componente para poder probarlo sin navegador
 * (tests/js/filtrar-permisos.test.mjs).
 */

/** Se muestran todos los permisos (sin filtro de estado). */
export const ESTADO_TODOS = 'todos';

/** Se muestran solo los permisos habilitados para el rol. */
export const ESTADO_HABILITADOS = 'habilitados';

/** Se muestran solo los permisos que el rol no tiene habilitados. */
export const ESTADO_SIN_HABILITADOS = 'sin-habilitados';

/** Texto de búsqueda normalizado: sin espacios en los extremos y en minúsculas. */
export const normalizarBusqueda = (texto) => String(texto ?? '').trim().toLowerCase();

/** ¿El nombre del permiso está entre los habilitados del rol? */
export const estaHabilitado = (permiso, habilitados = []) =>
    (habilitados ?? []).includes(permiso?.name);

/**
 * Carpetas con únicamente los permisos que coinciden con el texto buscado y con
 * el estado pedido. Las carpetas sin coincidencias desaparecen y se conserva el
 * orden que traen del servidor.
 *
 * Sin texto y sin estado devuelve las carpetas tal como llegan, sin copiarlas,
 * para no tocar la lista original que se envía al actualizar el rol.
 *
 * @param {Array<{modulo_id: string, modulo_description: string, permissions: Array<{name: string}>}>} modulos
 * @param {string} texto
 * @param {'todos'|'habilitados'|'sin-habilitados'} estado
 * @param {string[]} habilitados nombres de los permisos que tiene el rol
 */
export const filtrarPermisos = (modulos, texto, estado = ESTADO_TODOS, habilitados = []) => {
    const termino = normalizarBusqueda(texto);
    const filtraPorEstado = estado === ESTADO_HABILITADOS || estado === ESTADO_SIN_HABILITADOS;

    if (termino === '' && !filtraPorEstado) {
        return modulos ?? [];
    }

    return (modulos ?? [])
        .map((modulo) => ({
            ...modulo,
            permissions: (modulo?.permissions ?? []).filter(
                (permiso) =>
                    (termino === '' ||
                        String(permiso?.name ?? '').toLowerCase().includes(termino)) &&
                    (!filtraPorEstado ||
                        estaHabilitado(permiso, habilitados) ===
                            (estado === ESTADO_HABILITADOS))
            ),
        }))
        .filter((modulo) => modulo.permissions.length > 0);
};

/** Total de permisos encontrados en todas las carpetas de los módulos. */
export const contarPermisos = (modulos) =>
    (modulos ?? []).reduce((total, modulo) => total + (modulo?.permissions?.length ?? 0), 0);

/**
 * Cuántos permisos están habilitados y cuántos no, para el texto de los dos
 * botones. Con las carpetas que ya vienen filtradas por el buscador, los
 * números coinciden con lo que se ve en pantalla.
 *
 * @returns {{habilitados: number, sinHabilitados: number, total: number}}
 */
export const contarPorEstado = (modulos, habilitados = []) => {
    let habilitadosTotal = 0;

    for (const modulo of modulos ?? []) {
        for (const permiso of modulo?.permissions ?? []) {
            if (estaHabilitado(permiso, habilitados)) habilitadosTotal++;
        }
    }

    const total = contarPermisos(modulos);

    return {
        habilitados: habilitadosTotal,
        sinHabilitados: total - habilitadosTotal,
        total,
    };
};
