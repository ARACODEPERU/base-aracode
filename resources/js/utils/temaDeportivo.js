/**
 * Tema deportivo (PW00001 = 2).
 *
 * Los layouts publican la clase `theme-torneos` en <html> y las variables de
 * marca en la raíz. Inertia monta la página nueva antes de desmontar la
 * anterior, así que el desmontaje del layout viejo borraba lo que el nuevo ya
 * había aplicado: la pantalla quedaba sin el fondo ni las tarjetas del tema
 * hasta recargar con F5.
 *
 * Cada layout toma una reserva al montarse y la libera al desmontarse. La
 * liberación avisa si ya no queda ninguna otra vista con layout montada, que
 * es el único momento en el que corresponde limpiar <html>.
 */
let reservasActivas = 0;

/**
 * Registra un layout con tema y devuelve su función de liberación.
 *
 * La función devuelve `true` cuando este era el último layout montado (hay que
 * limpiar la clase y las variables de la raíz) y `false` cuando otra vista ya
 * tomó el relevo. Es idempotente para que un doble desmontaje no descuadre el
 * contador.
 */
export function reservarTemaDeportivo() {
    reservasActivas += 1;
    let liberada = false;

    return () => {
        if (liberada) {
            return false;
        }

        liberada = true;
        reservasActivas = Math.max(0, reservasActivas - 1);

        return reservasActivas === 0;
    };
}
