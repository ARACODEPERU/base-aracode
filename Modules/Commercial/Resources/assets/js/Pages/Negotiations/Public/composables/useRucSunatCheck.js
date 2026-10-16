import { ref } from "vue";
import Swal2 from "sweetalert2";

/**
 * RUC del cliente principal en el formulario publico de negociacion.
 *
 * Cuando el cliente elige RUC como tipo de documento, la consulta a SUNAT
 * (apis.net.pe/migo) llena la razon social y la direccion fiscal. Esos campos quedan
 * de solo lectura (`locks`): el cliente no los corrige a mano, solo volviendo a
 * buscar. Si la consulta no devuelve datos (o falla), siguen editables.
 *
 * La consulta tambien es lo que habilita el envio del formulario para RUC.
 *
 * @param {object} options
 * @param {object} options.form   Formulario de Inertia (fuente del RUC y destino de los datos).
 * @param {string} options.token  Token publico de la negociacion.
 * @param {object} options.locks  Campos ya verificados (useVerifiedIdentityLocks).
 */
export function useRucSunatCheck({ form, token, locks }) {
    const rucClientLoading = ref(false);
    // La consulta devolvio datos: razon social y direccion quedaron bloqueadas.
    const rucClientValidated = ref(false);
    // La consulta ya se hizo (con datos o sin ellos): es lo que habilita el envio.
    const rucClientSearched = ref(false);
    const rucClientNotice = ref("");
    // Campos que bloqueo la ultima consulta: hay que soltarlos al cambiar de numero.
    const lockedByLastSearch = ref([]);

    /**
     * Suelta los campos que bloqueo la consulta anterior. Con `clear` tambien los vacia,
     * porque pertenecen al RUC que se acaba de cambiar.
     */
    const releaseLockedFields = ({ clear = false } = {}) => {
        if (clear) {
            lockedByLastSearch.value.forEach((field) => {
                form[field] = null;
            });
        }

        locks.unlockFields(lockedByLastSearch.value);
        lockedByLastSearch.value = [];
    };

    /** Descarta la validacion del RUC (cambio de documento o de numero). */
    const resetClientRucCheck = ({ clear = false } = {}) => {
        releaseLockedFields({ clear });
        rucClientValidated.value = false;
        rucClientSearched.value = false;
        rucClientNotice.value = "";
    };

    /** Los datos ya estan verificados (busqueda interna o cuenta existente): no hace falta SUNAT. */
    const markClientVerified = () => {
        rucClientValidated.value = true;
        rucClientSearched.value = true;
        rucClientNotice.value = "";
    };

    // Consulta el RUC del cliente en la API.
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

        // Una consulta nueva descarta lo que hubiera traido la anterior: si esta no
        // devuelve datos, los campos quedan vacios y editables.
        releaseLockedFields({ clear: true });

        rucClientLoading.value = true;
        rucClientValidated.value = false;
        rucClientNotice.value = "";

        axios.post(route("comm_negotiations_public_validate_ruc", token), {
            ruc: form.number,
        }).then((res) => {
            // La consulta ya se hizo: aunque no traiga datos, el cliente puede enviar.
            rucClientSearched.value = true;

            if (!res.data?.success) {
                rucClientValidated.value = false;
                rucClientNotice.value = res.data?.error || "No se pudo validar el RUC. Intenta nuevamente.";
                return;
            }

            const person = res.data.person || {};
            const filled = [];

            if (person.razon_social) {
                form.full_name = person.razon_social;
                filled.push("full_name");
            }

            // El ubigeo SUNAT no es el district_id que usa el selector de ciudad del
            // formulario, asi que no se toca: el cliente elige su ciudad como siempre.
            if (person.direccion && person.direccion !== "-") {
                form.address = person.direccion;
                filled.push("address");
            }

            rucClientValidated.value = filled.length > 0;
            lockedByLastSearch.value = locks.lockFields(filled);

            // La consulta respondio pero sin razon social ni direccion: campos editables.
            if (filled.length === 0) {
                rucClientNotice.value = "SUNAT no devolvio los datos del RUC. Escribelos a mano.";
                return;
            }

            // El estado y la condicion se avisan como observacion, no se guardan aqui.
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
            // Sin respuesta de la API: se avisa y los campos quedan editables.
            rucClientSearched.value = true;
            rucClientValidated.value = false;
            rucClientNotice.value = "No se pudo validar el RUC en este momento. Intenta nuevamente.";
        }).finally(() => {
            rucClientLoading.value = false;
        });
    };

    // Al cambiar el RUC manualmente se invalida la consulta anterior y se vacian los
    // datos que habia traido, porque son de otro RUC.
    const onRucClientInput = () => {
        form.number = form.number ? String(form.number).replace(/\D/g, "") : null;
        resetClientRucCheck({ clear: true });
    };

    return {
        rucClientLoading,
        rucClientValidated,
        rucClientSearched,
        rucClientNotice,
        validateClientRuc,
        onRucClientInput,
        resetClientRucCheck,
        markClientVerified,
    };
}
