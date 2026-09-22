import { ref, watch } from "vue";
import Swal2 from "sweetalert2";

/**
 * Correo con cuenta existente en el formulario publico de negociacion.
 *
 * Si el correo ya tiene cuenta se avisa y el cliente decide: si acepta se cargan los
 * datos de esa cuenta y el correo se mantiene, si no se borra para que escriba otro.
 * Mientras se envia el formulario el chequeo se silencia (`markSubmitting`) porque el
 * blur del campo al pulsar Guardar abria un dialogo encima del resumen.
 *
 * @param {object}   options
 * @param {object}   options.form       Formulario de Inertia (fuente del correo).
 * @param {string}   options.token      Token publico de la negociacion.
 * @param {Function} options.loadPerson Carga la ficha de la persona de esa cuenta.
 */
export function useEmailAccountCheck({ form, token, loadPerson }) {
    // Correo (normalizado) que el cliente ya confirmo como cuenta existente. Si escribe
    // otro correo hay que volver a confirmar.
    const emailAccountConfirmed = ref("");
    const accountNotice = ref("");
    const suppressEmailCheck = ref(false);

    const normalizeEmail = (value) => String(value || "").trim().toLowerCase();

    /** El formulario se esta enviando: no se abren dialogos de correo encima del resumen. */
    const markSubmitting = () => {
        suppressEmailCheck.value = true;
    };

    /** Al volver al formulario el aviso del correo puede volver a mostrarse. */
    const markEmailCheckAvailable = () => {
        suppressEmailCheck.value = false;
    };

    // Verifica si el correo ya tiene cuenta registrada: pregunta y carga los datos.
    const checkEmailAccount = () => {
        const email = String(form.email || "").trim();
        if (!email || !/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email)) return;

        // Ya respondido por el cliente, o el formulario se esta enviando: no se vuelve a preguntar.
        if (suppressEmailCheck.value || normalizeEmail(email) === emailAccountConfirmed.value) return;

        axios.post(route("comm_negotiations_public_check_email", token), {
            email,
        }).then((res) => {
            if (!res.data?.exists) {
                accountNotice.value = "";
                return;
            }

            accountNotice.value = res.data.message || "Este correo ya tiene una cuenta registrada.";

            Swal2.fire({
                title: "Cuenta existente",
                text: res.data.message || "Este correo ya tiene una cuenta registrada. ¿Deseas continuar con ella?",
                icon: "question",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Si, usar mi cuenta",
                cancelButtonText: "No, seguir como nuevo",
                padding: "2em",
                customClass: "sweet-alerts",
            }).then((result) => {
                if (!result.isConfirmed) {
                    // No esta seguro de usar esa cuenta: se borra el correo para que
                    // escriba otro y no arrastre la cuenta existente.
                    accountNotice.value = "";
                    emailAccountConfirmed.value = "";
                    form.email = null;

                    Swal2.fire({
                        title: "Correo eliminado",
                        text: "Ingresa otro correo electronico para continuar.",
                        icon: "info",
                        padding: "2em",
                        customClass: "sweet-alerts",
                    });

                    return;
                }

                if (res.data.person) {
                    loadPerson(res.data.person);

                    Swal2.fire({
                        title: "Datos cargados",
                        text: "Continuaras con tu cuenta existente. Revisa que tus datos esten actualizados.",
                        icon: "success",
                        padding: "2em",
                        customClass: "sweet-alerts",
                    });
                }

                // El correo (y la cuenta) ya quedaron confirmados por el cliente.
                emailAccountConfirmed.value = normalizeEmail(form.email);
            });
        }).catch(() => {
            // Fallo de red: no bloquea el flujo, la cuenta se resuelve al procesar.
        });
    };

    // Si el cliente cambia el correo, la confirmacion anterior deja de valer: al salir
    // del campo se vuelve a verificar contra la cuenta nueva.
    watch(() => form.email, (value) => {
        if (normalizeEmail(value) === emailAccountConfirmed.value) return;

        emailAccountConfirmed.value = "";
        accountNotice.value = "";
    });

    return {
        accountNotice,
        markSubmitting,
        markEmailCheckAvailable,
        checkEmailAccount,
    };
}
