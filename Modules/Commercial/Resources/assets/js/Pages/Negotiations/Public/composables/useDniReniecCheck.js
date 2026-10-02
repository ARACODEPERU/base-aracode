import { ref } from "vue";
import Swal2 from "sweetalert2";

/**
 * DNI del cliente principal en el formulario publico de negociacion.
 *
 * Mientras el numero no se valide, los campos de nombres y apellidos permanecen
 * bloqueados (`dniValidated` es la senal que los habilita). La consulta a RENIEC se
 * dispara sola cuando se completa la longitud del documento (8 digitos en L.E/DNI)
 * o manualmente con el boton RENIEC. La validacion tambien es la que habilita el
 * envio del formulario.
 *
 * @param {object}   options
 * @param {object}   options.form             Formulario de Inertia (fuente del DNI).
 * @param {string}   options.token            Token publico de la negociacion.
 * @param {object}   options.isDni            Computed: el documento elegido es L.E/DNI.
 * @param {Function} options.normalizeNumber  Deja solo digitos en el numero de documento.
 * @param {Function} [options.numberLength]   Longitud esperada del documento (por defecto 8).
 */
export function useDniReniecCheck({ form, token, isDni, normalizeNumber, numberLength }) {
    const dniLoading = ref(false);
    const dniValidated = ref(false);
    const dniNotice = ref("");
    // Aviso bajo el numero mientras la consulta esta en curso o pendiente.
    const dniHint = ref("");

    /** Longitud esperada del numero para el documento elegido (8 para L.E/DNI). */
    const expectedLength = () => Number(numberLength?.()) || 8;

    /** Descarta la validacion del DNI (cambio de documento o de numero). */
    const resetDniCheck = () => {
        dniValidated.value = false;
        dniNotice.value = "";
        dniHint.value = "";
    };

    /** Los datos ya estan verificados (busqueda interna o cuenta existente): no hace falta RENIEC. */
    const markPersonVerified = () => {
        dniValidated.value = true;
        dniNotice.value = "";
        dniHint.value = "";
    };

    const applyReniecNames = (person) => {
        if (person.names) form.names = person.names;
        if (person.father_lastname) form.father_lastname = person.father_lastname;
        if (person.mother_lastname) form.mother_lastname = person.mother_lastname;

        // Fallback (migo): devuelve "APELLIDOS NOMBRES" en un solo campo; se reparte.
        if (!person.names && person.full_name) {
            const parts = String(person.full_name).trim().split(/\s+/);
            if (parts.length >= 3) {
                form.father_lastname = parts[0];
                form.mother_lastname = parts[1];
                form.names = parts.slice(2).join(" ");
            } else if (parts.length === 2) {
                form.father_lastname = parts[0];
                form.names = parts[1];
            } else {
                form.names = person.full_name;
            }
        }
    };

    // Consulta el DNI del cliente principal en la API (obligatoria para enviar).
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

        dniLoading.value = true;
        dniNotice.value = "";
        dniHint.value = "";

        axios.post(route("comm_negotiations_public_validate_dni", token), {
            dni: form.number,
        }).then((res) => {
            if (!res.data?.success) {
                dniValidated.value = false;
                dniNotice.value = res.data?.error || "No se pudo validar el DNI. Intenta nuevamente.";
                return;
            }

            applyReniecNames(res.data.person || {});

            // Los datos ya se cargaron desde RENIEC: el aviso deja de tener sentido.
            markPersonVerified();

            Swal2.fire({
                title: "DNI validado",
                text: "Tus datos fueron cargados desde RENIEC. Verificalos antes de continuar.",
                icon: "success",
                padding: "2em",
                customClass: "sweet-alerts",
            });
        }).catch(() => {
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

        // El numero cambio: la validacion anterior deja de valer.
        resetDniCheck();

        if (dniLoading.value) return;

        const dni = String(form.number || "");
        if (dni.length !== expectedLength()) return;

        dniHint.value = "Consultando RENIEC...";
        validateDni();
    };

    return {
        dniLoading,
        dniValidated,
        dniNotice,
        dniHint,
        onNumberInput,
        validateDni,
        resetDniCheck,
        markPersonVerified,
    };
}
