import { ref } from "vue";
import Swal2 from "sweetalert2";

/**
 * DNI del cliente principal en el formulario publico de negociacion.
 *
 * La consulta a RENIEC se dispara sola al completar la longitud del documento (8
 * digitos en L.E/DNI) o manualmente con el boton RENIEC. Los campos que la consulta
 * devuelve se completan y quedan de solo lectura (`locks`): el cliente no los corrige
 * a mano, solo volviendo a buscar. Si la consulta no devuelve datos (o falla), los
 * campos siguen editables para que los escriba el mismo.
 *
 * @param {object}   options
 * @param {object}   options.form             Formulario de Inertia (fuente del DNI y destino de los datos).
 * @param {string}   options.token            Token publico de la negociacion.
 * @param {object}   options.isDni            Computed: el documento elegido es L.E/DNI.
 * @param {Function} options.normalizeNumber  Deja solo digitos en el numero de documento.
 * @param {Function} [options.numberLength]   Longitud esperada del documento (por defecto 8).
 * @param {object}   options.locks            Campos ya verificados (useVerifiedIdentityLocks).
 */
export function useDniReniecCheck({ form, token, isDni, normalizeNumber, numberLength, locks }) {
    const dniLoading = ref(false);
    // La consulta devolvio datos: los nombres quedaron bloqueados por RENIEC.
    const dniValidated = ref(false);
    // La consulta ya se hizo (con datos o sin ellos): es lo que habilita el envio.
    const dniSearched = ref(false);
    const dniNotice = ref("");
    // Aviso bajo el numero mientras la consulta esta en curso o pendiente.
    const dniHint = ref("");
    // Campos que bloqueo la ultima consulta: hay que soltarlos al cambiar de numero.
    const lockedByLastSearch = ref([]);

    /** Longitud esperada del numero para el documento elegido (8 para L.E/DNI). */
    const expectedLength = () => Number(numberLength?.()) || 8;

    /**
     * Suelta los campos que bloqueo la consulta anterior. Con `clear` tambien los vacia,
     * porque pertenecen al documento que se acaba de cambiar.
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

    /** Descarta la consulta del DNI (cambio de documento o de numero). */
    const resetDniCheck = ({ clear = false } = {}) => {
        releaseLockedFields({ clear });
        dniValidated.value = false;
        dniSearched.value = false;
        dniNotice.value = "";
        dniHint.value = "";
    };

    /** La ficha ya estaba verificada (busqueda interna o cuenta existente): no hace falta RENIEC. */
    const markPersonVerified = () => {
        dniValidated.value = true;
        dniSearched.value = true;
        dniNotice.value = "";
        dniHint.value = "";
    };

    /**
     * Vuelca los datos de RENIEC en el formulario y devuelve solo los campos que la
     * consulta lleno: son los que quedan bloqueados.
     */
    const applyReniecNames = (person) => {
        const filled = [];

        if (person.names) {
            form.names = person.names;
            filled.push("names");
        }

        if (person.father_lastname) {
            form.father_lastname = person.father_lastname;
            filled.push("father_lastname");
        }

        if (person.mother_lastname) {
            form.mother_lastname = person.mother_lastname;
            filled.push("mother_lastname");
        }

        // Fallback (migo): devuelve "APELLIDOS NOMBRES" en un solo campo; se reparte.
        if (!person.names && person.full_name) {
            const parts = String(person.full_name).trim().split(/\s+/);

            if (parts.length >= 3) {
                form.father_lastname = parts[0];
                form.mother_lastname = parts[1];
                form.names = parts.slice(2).join(" ");
                filled.push("father_lastname", "mother_lastname", "names");
            } else if (parts.length === 2) {
                form.father_lastname = parts[0];
                form.names = parts[1];
                filled.push("father_lastname", "names");
            } else {
                form.names = person.full_name;
                filled.push("names");
            }
        }

        return filled.filter((field, index) => filled.indexOf(field) === index);
    };

    // Consulta el DNI del cliente principal en la API.
    const validateDni = () => {
        if (dniLoading.value) return;

        if (!form.number || String(form.number).length !== expectedLength()) {
            Swal2.fire({
                title: "DNI invalido",
                text: `El DNI debe tener ${expectedLength()} digitos.`,
                icon: "warning",
                padding: "2em",
                customClass: "sweet-alerts",
            });
            return;
        }

        // Una consulta nueva descarta lo que hubiera traido la anterior: si esta no
        // devuelve datos, los campos quedan vacios y editables.
        releaseLockedFields({ clear: true });

        dniLoading.value = true;
        dniValidated.value = false;
        dniNotice.value = "";
        dniHint.value = "";

        axios.post(route("comm_negotiations_public_validate_dni", token), {
            dni: form.number,
        }).then((res) => {
            // La consulta ya se hizo: aunque no traiga datos, el cliente puede enviar.
            dniSearched.value = true;

            if (!res.data?.success) {
                dniValidated.value = false;
                dniNotice.value = res.data?.error || "No se pudo validar el DNI. Intenta nuevamente.";
                return;
            }

            const filled = applyReniecNames(res.data.person || {});

            dniValidated.value = filled.length > 0;
            lockedByLastSearch.value = locks.lockFields(filled);

            // La consulta respondio pero sin nombres: los campos quedan editables.
            if (filled.length === 0) {
                dniNotice.value = "RENIEC no devolvio tus datos. Escribe tus nombres y apellidos.";
                return;
            }

            Swal2.fire({
                title: "DNI validado",
                text: "Tus datos fueron cargados desde RENIEC. Verificalos antes de continuar.",
                icon: "success",
                padding: "2em",
                customClass: "sweet-alerts",
            });
        }).catch(() => {
            // Sin respuesta de la API: se avisa y los campos quedan editables.
            dniSearched.value = true;
            dniValidated.value = false;
            dniNotice.value = "No se pudo validar el DNI en este momento. Intenta nuevamente.";
        }).finally(() => {
            dniLoading.value = false;
        });
    };

    // Al completar los digitos del documento la consulta se dispara sola: el cliente
    // no tiene que pulsar el boton RENIEC ni confirmar un dialogo.
    const onNumberInput = () => {
        normalizeNumber();

        if (!isDni.value) {
            resetDniCheck();
            return;
        }

        // El numero cambio: la consulta anterior deja de valer y sus datos se vacian,
        // porque son de otro documento.
        resetDniCheck({ clear: true });

        if (dniLoading.value) return;

        const dni = String(form.number || "");
        if (dni.length !== expectedLength()) return;

        dniHint.value = "Consultando RENIEC...";
        validateDni();
    };

    return {
        dniLoading,
        dniValidated,
        dniSearched,
        dniNotice,
        dniHint,
        onNumberInput,
        validateDni,
        resetDniCheck,
        markPersonVerified,
    };
}
