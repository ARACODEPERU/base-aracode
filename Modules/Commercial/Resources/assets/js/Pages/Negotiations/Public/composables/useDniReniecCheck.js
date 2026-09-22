import { ref } from "vue";
import Swal2 from "sweetalert2";

/**
 * DNI del cliente principal en el formulario publico de negociacion.
 *
 * Al completar los 8 digitos se avisa y se ofrece rellenar nombres y apellidos con
 * RENIEC (la API la expone el propio modulo del publico). La validacion tambien es la
 * que habilita el envio del formulario.
 *
 * @param {object}   options
 * @param {object}   options.form            Formulario de Inertia (fuente del DNI).
 * @param {string}   options.token           Token publico de la negociacion.
 * @param {object}   options.isDni           Computed: el documento elegido es DNI.
 * @param {Function} options.normalizeNumber Deja solo digitos en el numero de documento.
 */
export function useDniReniecCheck({ form, token, isDni, normalizeNumber }) {
    const dniLoading = ref(false);
    const dniValidated = ref(false);
    const dniNotice = ref("");
    // Aviso para rellenar los datos con RENIEC al completar el DNI.
    const dniHint = ref("");
    // DNI para el que ya se pregunto si queria rellenar con RENIEC (no repetir).
    const reniecAskedFor = ref("");

    /** Descarta la validacion del DNI (cambio de documento o de numero). */
    const resetDniCheck = () => {
        dniValidated.value = false;
        dniNotice.value = "";
        dniHint.value = "";
        reniecAskedFor.value = "";
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

        if (!form.number || String(form.number).length !== 8) {
            Swal2.fire({
                title: "DNI invalido",
                text: "El DNI debe tener 8 digitos.",
                icon: "warning",
                padding: "2em",
                customClass: "sweet-alerts",
            });
            return;
        }

        dniLoading.value = true;
        dniNotice.value = "";

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

    // Al cambiar el DNI manualmente se invalida la validacion anterior y, al completar
    // los 8 digitos, se ofrece rellenar los nombres con RENIEC.
    const onNumberInput = () => {
        normalizeNumber();
        resetDniCheck();

        if (!isDni.value) return;

        const dni = String(form.number || "");
        if (dni.length !== 8) return;

        dniHint.value = "Puedes rellenar tus nombres y apellidos automaticamente con RENIEC.";

        if (dniLoading.value || dniValidated.value || reniecAskedFor.value === dni) return;

        reniecAskedFor.value = dni;

        Swal2.fire({
            title: "Rellenar datos con RENIEC",
            text: "¿Deseas completar tus nombres y apellidos con los datos de RENIEC?",
            icon: "question",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Si, rellenar con RENIEC",
            cancelButtonText: "No, escribir manualmente",
            padding: "2em",
            customClass: "sweet-alerts",
        }).then((result) => {
            if (result.isConfirmed) {
                validateDni();
            }
        });
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
