import { computed, ref } from "vue";
import Swal2 from "sweetalert2";

/**
 * RUC del cliente principal en el formulario publico de negociacion.
 *
 * Equivalente RUC del composable de DNI/RENIEC: al consultar el RUC en SUNAT se
 * llena la razon social (y la direccion/ubicacion disponibles). La razon social
 * autollenada queda de SOLO LECTURA: solo se habilita cuando SUNAT no devolvio
 * datos, para escribirla a mano.
 *
 * @param {object}   options
 * @param {object}   options.form          Formulario de Inertia.
 * @param {string}   options.token         Token publico de la negociacion.
 * @param {object}   options.isRuc         Computed: el documento elegido es RUC.
 * @param {Array}    options.ubigeoOptions Ciudades (ubigeo) para intentar ubicar el
 *                                         distrito devuelto por SUNAT.
 * @param {Function} options.selectUbigeo  Aplica la ciudad resuelta ({district_id,
 *                                         ubigeo_description}) o null si no hubo match.
 */
export function useRucPersonCheck({ form, token, isRuc, ubigeoOptions, selectUbigeo }) {
    // 'idle'      : aun no se consulto (razon social bloqueada y vacia).
    // 'found'     : SUNAT devolvio datos (razon social bloqueada y autollenada).
    // 'verified'  : los datos vienen de una fuente interna confiable (bloqueada).
    // 'not_found' : la consulta no devolvio datos (razon social editable a mano).
    const personRucStatus = ref("idle");
    const personRucLoading = ref(false);
    const personRucNotice = ref("");

    // Razon social: de solo lectura mientras haya datos verificados.
    const personRucLocked = computed(() => personRucStatus.value !== "not_found");

    // Datos confiables cargados (SUNAT o fuente interna): habilita el envio.
    const personRucValidated = computed(() =>
        personRucStatus.value === "found" || personRucStatus.value === "verified"
    );

    /** Descarta la validacion del RUC (cambio de documento o de numero). */
    const resetPersonRucCheck = () => {
        personRucStatus.value = "idle";
        personRucNotice.value = "";
    };

    /** Los datos ya estan verificados (busqueda interna o cuenta existente): no hace falta SUNAT. */
    const markPersonRucVerified = () => {
        personRucStatus.value = "verified";
        personRucNotice.value = "";
    };

    const validatePersonRuc = () => {
        if (personRucLoading.value) return;

        const ruc = String(form.number || "").trim();

        if (ruc.length !== 11) {
            Swal2.fire({
                title: "RUC invalido",
                text: "El RUC debe tener 11 digitos.",
                icon: "warning",
                padding: "2em",
                customClass: "sweet-alerts",
            });
            return;
        }

        personRucLoading.value = true;
        personRucNotice.value = "";
        form.clearErrors?.(["number", "full_name"]);

        axios.post(route("comm_negotiations_public_validate_ruc", token), { ruc })
            .then((res) => {
                if (!res.data || !res.data.success) {
                    // Sin datos: la razon social queda editable para escribirla a mano.
                    resetPersonRucCheck();
                    personRucStatus.value = "not_found";
                    personRucNotice.value = res.data?.error || "No se encontro el RUC en SUNAT. Completa la razon social manualmente.";
                    return;
                }

                const person = res.data.person || {};

                form.full_name = person.razon_social || form.full_name;

                if (person.direccion && person.direccion !== "-") {
                    form.address = person.direccion;
                }

                // Ubicacion: se arma "Departamento-Provincia-Distrito" y se busca en el
                // ubigeo peruano para dejar la ciudad seleccionada; si no hay match
                // queda la descripcion y el cliente elige la ciudad.
                const description = [person.departamento, person.provincia, person.distrito]
                    .map((value) => (value ? String(value).trim() : ""))
                    .filter(Boolean)
                    .join("-");

                if (description) {
                    const match = (ubigeoOptions || []).find((item) => {
                        const label = String(item.ubigeo_description || "").trim().toUpperCase();
                        return label === description.toUpperCase();
                    }) ?? null;

                    form.ubigeo_description = match ? match.ubigeo_description : description;
                    selectUbigeo?.(match);
                }

                // Datos cargados: la razon social queda bloqueada.
                personRucStatus.value = "found";
                personRucNotice.value = "";

                Swal2.fire({
                    title: "RUC validado",
                    text: "La razon social fue cargada desde SUNAT y no es editable. Si algo esta mal, vuelve a buscar por RUC.",
                    icon: "success",
                    padding: "2em",
                    customClass: "sweet-alerts",
                });
            })
            .catch(() => {
                resetPersonRucCheck();
                personRucStatus.value = "not_found";
                personRucNotice.value = "No se pudo validar el RUC en este momento. Completa la razon social manualmente.";
            })
            .finally(() => {
                personRucLoading.value = false;
            });
    };

    // Al cambiar el numero de documento se limpian y re-bloquean los datos anteriores.
    const onPersonRucInput = () => {
        if (!isRuc.value) return;
        resetPersonRucCheck();
    };

    return {
        personRucStatus,
        personRucLoading,
        personRucValidated,
        personRucLocked,
        personRucNotice,
        validatePersonRuc,
        onPersonRucInput,
        resetPersonRucCheck,
        markPersonRucVerified,
    };
}
