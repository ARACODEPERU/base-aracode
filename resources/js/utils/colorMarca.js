/**
 * Utilidades de marca: los colores guardados en Configuraciones → Branding se
 * convierten en las variables CSS que consumen los tokens de Tailwind
 * (`--brand-primary`, `--brand-primary-light`, ...), definidos en
 * resources/css/app.css y en tailwind.config.js.
 *
 * Las variables guardan canales «R G B» (sin comas), que es el formato que
 * necesita `rgb(var(--brand-primary) / <alpha-value>)` para seguir respetando
 * la opacidad de cada utilidad de Tailwind.
 */

/** '#0188EE' (o '0188EE') a canales «1 136 238»; `null` si no es un hex válido. */
export function canalesRgb(hex) {
    const limpio = String(hex ?? '').trim().replace(/^#/, '');

    if (!/^[0-9a-fA-F]{6}$/.test(limpio)) {
        return null;
    }

    const valor = parseInt(limpio, 16);

    return `${(valor >> 16) & 255} ${(valor >> 8) & 255} ${valor & 255}`;
}

/**
 * Tinte claro de un color (fondos suaves de hover, badges y focos): el color
 * mezclado con blanco al 90 %. Sustituye a la constante `-light` de fábrica
 * cuando la instalación tiene una marca propia.
 */
export function tinteClaro(canales) {
    if (!canales) {
        return null;
    }

    return canales
        .split(' ')
        .map((canal) => Math.round(Number(canal) + (255 - Number(canal)) * 0.9))
        .join(' ');
}

/**
 * Variables CSS de marca a partir de los colores guardados. Sin color (nulo,
 * vacío o inválido) no se genera variable: el sistema conserva el valor de
 * fábrica que declara resources/css/app.css.
 */
export function variablesDeMarca({ primary = null, secondary = null } = {}) {
    const variables = {};
    const primario = canalesRgb(primary);
    const secundario = canalesRgb(secondary);

    if (primario) {
        variables['--brand-primary'] = primario;
        variables['--brand-primary-light'] = tinteClaro(primario);
    }

    if (secundario) {
        variables['--brand-secondary'] = secundario;
        variables['--brand-secondary-light'] = tinteClaro(secundario);
    }

    return variables;
}
