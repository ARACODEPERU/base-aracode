const posicionesConocidas = {
    first: 1,
    first_place: 1,
    primer: 1,
    primero: 1,
    second: 2,
    second_place: 2,
    segundo: 2,
    third: 3,
    third_place: 3,
    tercero: 3,
    fourth: 4,
    fourth_place: 4,
    cuarto: 4,
    fifth: 5,
    fifth_place: 5,
    quinto: 5,
    '0': 1,
};

/** Convierte las claves legadas del formulario (`first`, `second`, etc.) a puestos positivos. */
export function normalizarPuestoPremio(key, prize = {}) {
    const rawPosition = prize?.position ?? prize?.place ?? key;
    const normalizedPosition = String(rawPosition ?? '').trim().toLowerCase();
    const mappedPosition = posicionesConocidas[normalizedPosition];
    if (mappedPosition) return mappedPosition;

    const numericPosition = Number(normalizedPosition.replace(/(?:st|nd|rd|th|[.º°])$/u, ''));
    return Number.isInteger(numericPosition) && numericPosition > 0 ? numericPosition : null;
}

export function obtenerPremios(prizeDetails) {
    if (!prizeDetails || typeof prizeDetails !== 'object' || Array.isArray(prizeDetails)) return [];

    return Object.entries(prizeDetails)
        .filter(([, prize]) => prize && (prize.money || prize.gift))
        .map(([key, prize]) => ({ position: normalizarPuestoPremio(key, prize), ...prize }))
        .sort((first, second) => (first.position ?? Number.MAX_SAFE_INTEGER) - (second.position ?? Number.MAX_SAFE_INTEGER));
}

export function etiquetaPuestoPremio(position) {
    return Number.isInteger(position) && position > 0 ? `${position}°` : 'Premio';
}
