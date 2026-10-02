import { ref } from "vue";
import Swal2 from "sweetalert2";

/**
 * RUC del cliente principal en el formulario publico de negociacion.
 *
 * Cuando el cliente elige RUC como tipo de documento, la Razon social permanece
 * bloqueada hasta que se consulta el RUC en SUNAT (apis.net.pe/migo). La respuesta
 * rellena la razon social y la direccion, y recien entonces el campo queda editable.
 *
 * La validacion tambien es la que habilita el envio del formulario para RUC.
 *
 * @param {object}   options
 * @param {object}   options.form   Formulario de Inertia (fuente del RUC y destino de los datos).
 * @param {string}   options.token  Token publico de la negociacion.
 */
export function useRucSunatCheck({ form, token }) {
    const rucClientLoading = ref(false);
    const rucClientValidated = ref(false);
    const rucClientNotice = ref("");

    /** Descarta la validacion del RUC (cambio de documento o de numero). */
    const resetClientRucCheck = () => {
        rucClientValidated.value = false;
        rucClientNotice.value = "";
    };

    /** Los datos ya estan verificados (busqueda interna o cuenta existente): no hace falta SUNAT. */
    const markClientVerified = () => {
        rucClientValidated.value = true;
        rucClientNotice.value = "";
    };

    // Consulta el RUC del cliente en la API (obligatoria para enviar cuando el documento es RUC).
    const validateClientRuc = () => {
        if (rucClientLoading.value) return;

        if (!form.number || String(form.number).length !== 11) {
            Swal2.fire({
                title: "RUC invalido",
                text: "El RUC debe tener 11 digitos.",
                icon: "warning",
                padding: "2em",
                customClass: "sweet-alerts",
            });
            return;
        }

        rucClientLoading.value = true;
        rucClientNotice.value = "";

        axios.post(route("comm_negotiations_public_validate_ruc", token), {
            ruc: form.number,
        }).then((res) => {
            if (!res.data?.success) {
                rucClientValidated.value = false;
                rucClientNotice.value = res.data?.error || "No se pudo validar el RUC. Intenta nuevamente.";
                return;
            }

            const person = res.data.person || {};

            if (person.razon_social) form.full_name = person.razon_social;
            if (person.direccion && person.direccion !== "-") form.address = person.direccion;

            markClientVerified();

            // El ubigeo SUNAT no es el district_id que usa el selector de ciudad del
            // formulario, asi que no se toca: el cliente elige su ciudad como siempre.
            const estado = String(person.estado || "").toUpperCase();
            const condicion = String(person.condicion || "").toUpperCase();

            if (estado && estado !== "ACTIVO") {
                rucClientNotice.value = `El RUC figura como ${person.estado}. Verifica que sea correcto.`;
            } else if (condicion && condicion !== "HABIDO") {
                rucClientNotice.value = `El RUC figura como ${person.condicion}. Verifica que sea correcto.`;
            }

            Swal2.fire({
                title: "RUC validado",
                text: "Se cargaron la razon social y la direccion desde SUNAT. Verificalas antes de continuar.",
                icon: "success",
                padding: "2em",
                customClass: "sweet-alerts",
            });
        }).catch(() => {
            rucClientValidated.value = false;
            rucClientNotice.value = "No se pudo validar el RUC en este momento. Intenta nuevamente.";
        }).finally(() => {
            rucClientLoading.value = false;
        });
    };

    // Al cambiar el RUC manualmente se invalida la validacion anterior.
    const onRucClientInput = () => {
        form.number = form.number ? String(form.number).replace(/\D/g, "") : null;
        resetClientRucCheck();
    };

    return {
        rucClientLoading,
        rucClientValidated,
        rucClientNotice,
        validateClientRuc,
        onRucClientInput,
        resetClientRucCheck,
        markClientVerified,
    };
}
