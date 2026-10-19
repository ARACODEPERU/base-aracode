/**
 * Cruces del todos contra todos.
 *
 * El generador del servidor solo crea los cruces que faltan, así que antes de
 * mandarle la petición la pantalla necesita saber si ya está todo. Estas
 * funciones comparan los equipos elegidos contra los partidos registrados,
 * sin importar el orden local/visitante (el cruce es el mismo par de equipos).
 */

/** Clave estable de un cruce: los dos ids ordenados (2-7 es el mismo cruce que 7-2). */
export const claveParEquipos = (equipoA, equipoB) => {
    const ids = [Number(equipoA), Number(equipoB)].sort((a, b) => a - b);

    return `${ids[0]}-${ids[1]}`;
};

/** Pares ya registrados en una lista de partidos (los que no tienen ambos equipos se ignoran). */
export const paresDePartidos = (partidos = []) => {
    const pares = new Set();

    (partidos ?? []).forEach((partido) => {
        if (partido?.team_h_id && partido?.team_a_id) {
            pares.add(claveParEquipos(partido.team_h_id, partido.team_a_id));
        }
    });

    return pares;
};

/** Pares ya registrados en una fase del fixture, tal como llega del servidor (`league.1`, `league.2`, ...). */
export const paresDeFases = (jornadas = {}) => {
    const pares = new Set();

    Object.values(jornadas ?? {}).forEach((partidos) => {
        paresDePartidos(partidos).forEach((par) => pares.add(par));
    });

    return pares;
};

/** Cruces entre los equipos elegidos que todavía no están registrados. */
export const faltanCruces = (equipos = [], paresRegistrados = new Set()) => {
    const ids = [...new Set((equipos ?? []).map(Number).filter(Boolean))];
    const faltantes = [];

    for (let i = 0; i < ids.length; i++) {
        for (let j = i + 1; j < ids.length; j++) {
            const par = claveParEquipos(ids[i], ids[j]);

            if (!paresRegistrados.has(par)) faltantes.push([ids[i], ids[j]]);
        }
    }

    return faltantes;
};

/** true cuando ya está registrado cada cruce entre los equipos elegidos. */
export const crucesCompletos = (equipos = [], paresRegistrados = new Set()) => {
    const ids = [...new Set((equipos ?? []).map(Number).filter(Boolean))];

    return ids.length >= 2 && faltanCruces(ids, paresRegistrados).length === 0;
};
