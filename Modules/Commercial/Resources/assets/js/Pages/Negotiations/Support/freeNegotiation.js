/**
 * Negociacion gratuita: se le entregan uno o mas cursos o suscripciones que tienen
 * costo, pero el precio acordado es 0. En ese caso el cliente solo completa sus datos:
 * no hay nada que cobrar y no debe adjuntar ningun voucher.
 *
 * La regla vive en un solo lugar (este modulo) porque la comparten el formulario de
 * creacion, la pagina publica del cliente y la pantalla de proceso.
 */

/**
 * Valor con el que se guarda el medio de pago de una negociacion gratuita: deja
 * explicito en el listado, el detalle y los correos que no hubo cobro.
 */
export const FREE_PAYMENT_METHOD = "gratis";

/**
 * Indica si el precio total acordado convierte la negociacion en gratuita.
 *
 * El total puede llegar como numero, como texto del input ('0', '0.00'), o vacio
 * cuando el asesor todavia no lo escribio. Un campo vacio NO es gratis: la
 * negociacion aun no tiene precio. Un dato ilegible tampoco lo es, para no saltarse
 * el cobro por un valor invalido.
 */
export const isFreeNegotiation = (totalPrice) => {
    if (totalPrice === null || totalPrice === undefined) return false;
    if (typeof totalPrice === "string" && totalPrice.trim() === "") return false;

    const total = Number(totalPrice);

    if (! Number.isFinite(total)) return false;

    return total === 0;
};

/** Etiqueta del total para los textos visibles: "Gratis" en vez de un monto en cero. */
export const totalPriceLabel = (currency, totalPrice) =>
    isFreeNegotiation(totalPrice) ? "Gratis" : `${currency ?? "PEN"} ${Number(totalPrice || 0).toFixed(2)}`;
