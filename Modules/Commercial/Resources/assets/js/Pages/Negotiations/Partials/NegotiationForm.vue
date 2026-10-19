<script setup>
import FormSection from "@/Components/FormSection.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import EditorAracode from "@/Components/EditorAracode.vue";
import IconLoader from "@/Components/vristo/icon/icon-loader.vue";

import { Link, router, useForm, usePage } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";
import { Select } from "ant-design-vue";
import Swal2 from "sweetalert2";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { faCalculator, faCalendarPlus, faGift, faTrashAlt } from "@fortawesome/free-solid-svg-icons";
import { FREE_PAYMENT_METHOD, isFreeNegotiation } from "../Support/freeNegotiation.js";

const props = defineProps({
    negotiation: { type: Object, default: null },
    courses: { type: Array, default: () => [] },
    subscriptions: { type: Array, default: () => [] },
    identityDocumentTypes: { type: Array, default: () => [] },
    currencyTypes: { type: Array, default: () => [] },
    paymentMethods: { type: Array, default: () => [] },
    contactChannels: { type: Array, default: () => [] },
    companyBilleteras: { type: Array, default: () => [] },
    // Combo de moneda visible solo con PTM0004 activo; TC vigente para la conversion.
    multiCurrencyEnabled: { type: Boolean, default: false },
    exchangeRate: { type: Number, default: null },
});

const isEdit = computed(() => !!props.negotiation?.id);

// Negociacion gratuita: se entregan cursos o suscripciones que tienen costo, pero el
// precio acordado es 0. No hay nada que cobrar, asi que el formulario deja de pedir los
// datos de pago y el cliente solo completa sus datos en el enlace publico.
const isFree = computed(() => isFreeNegotiation(form.total_price));

// Estado de la negociacion al abrir el formulario: en edicion sirve para no volver a
// pedir confirmacion cuando ya era gratuita antes de este guardado.
const wasFree = isFreeNegotiation(props.negotiation?.total_price);

const currentUserName = usePage().props.auth?.user?.name ?? null;

const form = useForm({
    title: props.negotiation?.title ?? null,
    body: props.negotiation?.body ?? "",
    total_price: props.negotiation?.total_price ?? null,
    currency: props.negotiation?.currency ?? "PEN",
    payment_type: props.negotiation?.payment_type ?? "single",
    initial_amount: props.negotiation?.initial_amount ?? null,
    schedule: props.negotiation?.schedule ?? [],
    single_payment_days: props.negotiation?.single_payment_days ?? null,
    link_days: props.negotiation?.link_days ?? 2,
    contact_channel: props.negotiation?.contact_channel ?? null,
    contact_detail: props.negotiation?.contact_detail ?? props.negotiation?.creator?.name ?? currentUserName,
    email: props.negotiation?.email ?? null,
    payment_method: (props.negotiation?.payment_method === "yape" ? "billetera_digital" : props.negotiation?.payment_method) ?? "billetera_digital",
    payment_link: props.negotiation?.payment_link ?? null,
    company_billetera_ids: props.negotiation?.company_billeteras?.map((item) => item.id) ?? [],
    course_ids: props.negotiation?.items?.filter((item) => item.item_type === "course").map((item) => item.item_id) ?? [],
    subscription_ids: props.negotiation?.items?.filter((item) => item.item_type === "subscription").map((item) => item.item_id) ?? [],
    items: [],
});

const courseOptions = computed(() => props.courses.map((item) => ({
    value: item.id,
    label: `${item.description}${item.price ? ` (S/ ${item.price})` : ""}`,
})));

const subscriptionOptions = computed(() => props.subscriptions.map((item) => ({
    value: item.id,
    label: `${item.title}${subscriptionPrice(item) ? ` (S/ ${subscriptionPrice(item)})` : ""}`,
})));


// Al quedar el total en 0 se limpian los datos de cobro que ya no aplican: si viajaran
// igual quedarian guardados en la negociacion sin que el cliente llegue a usarlos.
// Los campos de pago no se ocultan: se muestran inhabilitados, para que el asesor vea
// que existen y que no aplican.
watch(isFree, (gratis) => {
    if (gratis) {
        form.payment_type = "single";
        form.payment_method = FREE_PAYMENT_METHOD;
        form.schedule = [];
        form.company_billetera_ids = [];
        form.payment_link = null;
        form.single_payment_days = null;

        return;
    }

    // Volvio a tener precio: se recupera un medio de pago elegible por el asesor.
    if (form.payment_method === FREE_PAYMENT_METHOD) {
        form.payment_method = "billetera_digital";
    }
});

// Una sola opcion por moneda: si la tabla de monedas llega con filas repetidas,
// el select mostraba la misma moneda tres veces.
const currencyOptions = computed(() => {
    const vistas = new Set();

    return props.currencyTypes.reduce((opciones, item) => {
        if (vistas.has(item.id)) return opciones;

        vistas.add(item.id);
        opciones.push({
            value: item.id,
            label: `${item.id} - ${item.description}${item.symbol ? ` (${item.symbol})` : ""}`,
        });

        return opciones;
    }, []);

});

// El catalogo incluye 'gratis' para las etiquetas del listado y el detalle, pero no es una
// opcion elegible: solo se ofrece mientras la negociacion no tiene cobro (y esta bloqueada).
const paymentMethodOptions = computed(() => props.paymentMethods
    .filter((item) => isFree.value || item.value !== FREE_PAYMENT_METHOD)
    .map((item) => ({
        value: item.value,
        label: item.label,
    })));

// Equivalente en soles del monto ingresado, para que el asesor vea a cuanto
// equivale el precio total (y cada cuota) cuando elige trabajar en dolares.
const isUsd = computed(() => String(form.currency).toUpperCase() === 'USD');
const tcValido = computed(() => Number(props.exchangeRate) > 0);
const equivalentPenTotal = computed(() => {
    if (!isUsd.value || !tcValido.value) return null;
    return (Number(form.total_price || 0) * Number(props.exchangeRate)).toFixed(2);
});
const equivalentPenSchedule = computed(() => {
    if (!isUsd.value || !tcValido.value) return null;
    return (Number(scheduleSum.value || 0) * Number(props.exchangeRate)).toFixed(2);
});

const billeteraOptions = computed(() => props.companyBilleteras.map((item) => ({
    value: item.id,
    label: `${item.nombre || 'Billetera'} - ${item.titular || ''}`.trim(),
})));

const contactChannelOptions = computed(() => props.contactChannels.map((item) => ({
    value: item.value,
    label: item.label,
})));

const filterOption = (input, option) => option.label.toLowerCase().includes(input.toLowerCase());

const subscriptionPrice = (item) => {
    // Si el backend ya envia el precio normalizado (PEN), lo usamos directamente.
    if (item?.price !== undefined && item?.price !== null && item?.price !== '') {
        return Number(item.price);
    }

    // Fallback: prices viene como array de objetos [{currency, amount, detail}] u array de numeros.
    const prices = Array.isArray(item?.prices) ? item.prices : null;
    if (!prices || !prices.length) return null;

    // Siempre prioriza el precio en soles (PEN).
    const pen = prices.find((p) => String(p?.currency).toUpperCase() === "PEN");
    const price = pen
        ? pen.amount
        : prices.find((p) => typeof p === "number" || !isNaN(parseFloat(p)));

    return price !== undefined && price !== null ? Number(price) : null;
};

const selectedItemsTotal = computed(() => {
    let total = 0;

    props.courses.forEach((course) => {
        if (form.course_ids.includes(course.id) && course.price) {
            total += Number(course.price);
        }
    });

    props.subscriptions.forEach((sub) => {
        if (form.subscription_ids.includes(sub.id)) {
            const price = subscriptionPrice(sub);
            if (price) total += Number(price);
        }
    });

    return total;
});

const useSuggestedTotal = () => {
    form.total_price = Number(selectedItemsTotal.value.toFixed(2));
};

const scheduleSum = computed(() => {
    return (form.schedule || []).reduce((acc, row) => acc + (Number(row.amount) || 0), 0);
});

const scheduleSumMatches = computed(() => {
    if (form.payment_type !== "installments") return true;
    const total = Number(form.total_price) || 0;
    return Math.abs(scheduleSum.value - total) < 0.01;
});

const scheduleComplete = computed(() => {
    if (form.total_price === null || form.total_price === "" || form.total_price === undefined) return false;
    const total = Number(form.total_price) || 0;
    return scheduleSum.value >= total - 0.01;
});

const addMonths = (dateStr, months) => {
    if (!dateStr) return new Date().toISOString().slice(0, 10);

    const [year, month, day] = dateStr.split("-").map(Number);
    const targetMonth = month - 1 + months;
    const targetYear = year + Math.floor(targetMonth / 12);
    const monthIndex = ((targetMonth % 12) + 12) % 12;
    const lastDay = new Date(targetYear, monthIndex + 1, 0).getDate();
    const safeDay = Math.min(day, lastDay);

    return `${targetYear}-${String(monthIndex + 1).padStart(2, "0")}-${String(safeDay).padStart(2, "0")}`;
};

const addScheduleRow = () => {
    if (scheduleComplete.value) return;

    const lastDate = form.schedule.length ? form.schedule[form.schedule.length - 1].due_date : null;

    form.schedule = [
        ...form.schedule,
        { due_date: addMonths(lastDate, 1), amount: null },
    ];
};

const removeScheduleRow = (index) => {
    form.schedule = form.schedule.filter((_, itemIndex) => itemIndex !== index);
};

const buildItems = () => {
    const items = [];

    props.courses.forEach((course) => {
        if (form.course_ids.includes(course.id)) {
            items.push({
                item_type: "course",
                item_id: course.id,
                title: course.description,
                price: course.price ?? null,
            });
        }
    });

    props.subscriptions.forEach((sub) => {
        if (form.subscription_ids.includes(sub.id)) {
            items.push({
                item_type: "subscription",
                item_id: sub.id,
                title: sub.title,
                price: subscriptionPrice(sub),
            });
        }
    });

    return items;
};

/**
 * Modal bloqueante que se muestra mientras el guardado esta en curso.
 * Se cierra en onSuccess/onError, nunca por clic fuera ni con la tecla Esc.
 */
const showContractLoader = () => {
    Swal2.fire({
        title: "Validando contrato...",
        html: '<p class="text-sm text-gray-500">Estamos validando el contrato y registrando la negociacion.</p>',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => Swal2.showLoading(),
        padding: "2em",
        customClass: "sweet-alerts",
    });
};

const closeContractLoader = () => {
    if (Swal2.isVisible()) {
        Swal2.close();
    }
};

/** Primer error disponible del formulario, para el aviso cuando el guardado falla. */
const negotiationFormError = () => {
    if (form.errors.items) return form.errors.items;

    const first = Object.values(form.errors)[0];

    return first ?? "No se pudo registrar la negociacion. Verifica los datos e intentalo nuevamente.";
};

/**
 * Aviso previo del guardado gratis: el cliente no pagara nada ni subira voucher, asi
 * que se pide confirmacion antes de crear la negociacion.
 *
 * No se vuelve a preguntar cuando la negociacion ya era gratuita (edicion): solo se
 * confirma el cambio real, es decir el paso de una negociacion con cobro a gratis.
 */
const confirmFreeNegotiation = async () => {
    if (!isFree.value) return true;
    if (isEdit.value && wasFree) return true;

    const result = await Swal2.fire({
        title: "Vas a generar una negociacion Gratis",
        html: '<p class="text-sm text-gray-500">El precio acordado es <strong>0</strong>: el cliente recibira los cursos o suscripciones sin costo y al abrir el enlace solo completara sus datos, sin voucher ni datos de pago. ¿Estas seguro?</p>',
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Si, generar gratis",
        cancelButtonText: "No, revisar",
        reverseButtons: true,
        allowOutsideClick: false,
        padding: "2em",
        customClass: "sweet-alerts",
    });

    return result.isConfirmed;
};

const submit = async () => {
    form.clearErrors();

    const items = buildItems();
    if (!items.length) {
        form.setError("items", "Debe seleccionar al menos un curso o suscripcion.");
        return;
    }

    if (form.payment_type === "installments") {
        if (!form.schedule.length) {
            form.setError("schedule", "Debe registrar al menos una cuota.");
            return;
        }

        if (!scheduleSumMatches.value) {
            form.setError("schedule", "La suma de las cuotas debe ser igual al monto total acordado.");
            return;
        }

        form.clearErrors("schedule");
    }

    form.items = items;

    // Ultima confirmacion antes de guardar: solo despues del "Si" se envia la peticion.
    if (! (await confirmFreeNegotiation())) return;

    const options = {
        preserveScroll: true,
        onSuccess: () => {
            closeContractLoader();

            Swal2.fire({
                title: "Registro exitoso",
                text: isEdit.value
                    ? "La negociacion se actualizo correctamente."
                    : "La negociacion se registro correctamente.",
                icon: "success",
                confirmButtonText: "OK",
                allowOutsideClick: false,
                allowEscapeKey: false,
                padding: "2em",
                customClass: "sweet-alerts",
            }).then((result) => {
                if (result.isConfirmed) {
                    router.visit(route("comm_negotiations"));
                }
            });
        },
        // Un solo manejador de error: habia dos claves onError y la segunda anulaba a la
        // primera, de modo que el modal bloqueante nunca se cerraba y el formulario
        // quedaba atrapado detras de el cuando el guardado fallaba.
        onError: () => {
            closeContractLoader();

            Swal2.fire({
                title: "Atencion",
                text: negotiationFormError(),
                icon: "warning",
                padding: "2em",
                customClass: "sweet-alerts",
            }).then(() => {
                router.visit(route("comm_negotiations"));
            });
        },
    };

    showContractLoader();

    if (isEdit.value) {
        form.post(route("comm_negotiations_update", props.negotiation.id), options);
        return;
    }

    form.post(route("comm_negotiations_store"), options);
};
</script>

<template>
    <FormSection @submitted="submit">
        <template #title>
            {{ isEdit ? "Editar negociacion" : "Nueva negociacion" }}
        </template>

        <template #description>
            Registra una negociacion con sus condiciones de pago y genera un enlace unico que el cliente podra confirmar.
        </template>

        <template #form>
            <div class="col-span-6">
                <InputLabel for="title" value="Titulo de la negociacion *" />
                <TextInput id="title" v-model="form.title" type="text" />
                <InputError :message="form.errors.title" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-3">
                <InputLabel value="Cursos" />
                <Select
                    v-model:value="form.course_ids"
                    mode="multiple"
                    :options="courseOptions"
                    :filter-option="filterOption"
                    show-search
                    allow-clear
                    placeholder="Selecciona cursos"
                    style="width: 100%"
                />
            </div>

            <div class="col-span-6 sm:col-span-3">
                <InputLabel value="Suscripciones" />
                <Select
                    v-model:value="form.subscription_ids"
                    mode="multiple"
                    :options="subscriptionOptions"
                    :filter-option="filterOption"
                    show-search
                    allow-clear
                    placeholder="Selecciona suscripciones"
                    style="width: 100%"
                />
            </div>

            <div class="col-span-6">
                <InputError :message="form.errors.items" class="mt-2" />
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <span>Total sugerido: {{ selectedItemsTotal.toFixed(2) }}</span>
                    <button type="button" class="btn btn-outline-primary btn-sm" @click="useSuggestedTotal">
                        <FontAwesomeIcon :icon="faCalculator" class="mr-1 h-3 w-3" />
                        Usar como total
                    </button>
                </div>
            </div>

            <div class="col-span-6 sm:col-span-3">
                <InputLabel for="total_price" value="Precio total *" />
                <TextInput id="total_price" v-model="form.total_price" type="number" step="0.01" min="0" />
                <InputError :message="form.errors.total_price" class="mt-2" />
            </div>

            <!-- Moneda: el combo solo aparece con el modo multi-moneda (PTM0004) activo;
                 desactivado la negociacion siempre es en soles (PEN). -->
            <div v-if="multiCurrencyEnabled" class="col-span-6 sm:col-span-1">
                <InputLabel value="Moneda *" />
                <Select
                    v-model:value="form.currency"
                    :options="currencyOptions"
                    :filter-option="filterOption"
                    show-search
                    style="width: 100%"
                />
                <InputError :message="form.errors.currency" class="mt-2" />
            </div>
            <div v-if="multiCurrencyEnabled && isUsd && tcValido && equivalentPenTotal !== null && !isFree" class="col-span-6 sm:col-span-3">
                <div class="rounded-md bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 px-3 py-2 text-xs text-blue-800 dark:text-blue-300">
                    💱 Cambio a soles: <strong>S/ {{ equivalentPenTotal }}</strong>
                    <span class="block mt-0.5">(TC SUNAT: S/ {{ Number(props.exchangeRate).toFixed(4) }} por $1)</span>
                    <span v-if="form.payment_type === 'installments' && equivalentPenSchedule !== null" class="block mt-0.5">
                        Suma de cuotas: S/ {{ equivalentPenSchedule }}
                    </span>
                </div>
            </div>

            <!-- Negociacion Gratis: los campos de cobro siguen visibles pero inhabilitados,
                 para que el asesor vea que no aplican; el cliente solo completara sus datos. -->
            <div v-if="isFree" class="col-span-6">
                <div class="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-900/30 dark:text-amber-200">
                    <FontAwesomeIcon :icon="faGift" class="mr-1 h-3.5 w-3.5" />
                    <strong>Negociacion Gratis:</strong> el total acordado es 0, asi que no se pedira ningun pago.
                    <span class="block mt-0.5">El tipo de pago, las cuotas y el medio de pago quedan inhabilitados. El cliente recibira los cursos y suscripciones seleccionados y en el enlace solo completara sus datos, sin voucher, sin comprobante y sin datos de pago.</span>
                </div>
            </div>

            <div class="col-span-6 sm:col-span-2">
                <InputLabel value="Tipo de pago *" />
                <select
                    v-model="form.payment_type"
                    class="form-select text-white-dark"
                    :disabled="isFree"
                    :class="{ 'cursor-not-allowed opacity-60': isFree }"
                >
                    <option value="single">Pago unico</option>
                    <option value="installments">Cuotas</option>
                </select>
                <p v-if="isFree" class="text-xs text-gray-500 mt-1">Inhabilitado: una negociacion gratuita no se cobra, ni al contado ni en cuotas.</p>
                <InputError :message="form.errors.payment_type" class="mt-2" />
            </div>

            <div v-if="form.payment_type === 'installments'" class="col-span-6 sm:col-span-2">
                <div class="pt-5 text-xs text-gray-500">
                    La primera cuota del cronograma corresponde al pago inicial.
                </div>
            </div>

            <div v-else class="col-span-6 sm:col-span-2">
                <InputLabel for="single_payment_days" value="Plazo de pago (dias)" />
                <TextInput
                    id="single_payment_days"
                    v-model="form.single_payment_days"
                    type="number"
                    min="1"
                    :disabled="isFree"
                    :class="{ 'cursor-not-allowed bg-gray-100 dark:bg-gray-800': isFree }"
                />
                <p v-if="isFree" class="text-xs text-gray-500 mt-1">Inhabilitado: no hay pago que programar.</p>
                <InputError :message="form.errors.single_payment_days" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-2">
                <InputLabel for="link_days" value="Vigencia del enlace (dias)" />
                <TextInput id="link_days" v-model="form.link_days" type="number" min="1" />
                <InputError :message="form.errors.link_days" class="mt-2" />
                <p class="text-xs text-gray-500 mt-1">Pasado este plazo el enlace se desactiva y la negociacion pasa a "No hubo respuesta".</p>
            </div>

            <template v-if="!isFree && form.payment_type === 'installments'">
                <div class="col-span-6">
                    <div class="flex flex-wrap items-center gap-3 border-t border-gray-200 dark:border-gray-700 pt-4">
                        <h3 class="font-semibold text-gray-800 dark:text-white">Cronograma provisional de cuotas</h3>
                        <button type="button" class="btn btn-outline-primary" :disabled="scheduleComplete" :class="{ 'opacity-50 cursor-not-allowed': scheduleComplete }" @click="addScheduleRow">
                            <FontAwesomeIcon :icon="faCalendarPlus" class="mr-1 h-4 w-4" />
                            {{ scheduleComplete ? "Total cubierto" : "Agregar cuota" }}
                        </button>
                    </div>
                    <p class="text-xs text-gray-500">Cronograma provisional y solo visual para el cliente; la cuota 1 es el pago inicial y el resto se gestionara en ventas al aprobarse.</p>
                    <div class="mt-2 flex flex-wrap items-center gap-3 text-sm">
                        <span class="text-gray-500 dark:text-gray-400">
                            Suma de cuotas: <strong class="text-gray-800 dark:text-white">{{ scheduleSum.toFixed(2) }}</strong>
                        </span>
                        <span class="text-gray-500 dark:text-gray-400">
                            Total acordado: <strong class="text-gray-800 dark:text-white">{{ Number(form.total_price || 0).toFixed(2) }}</strong>
                        </span>
                        <span v-if="!scheduleSumMatches" class="font-semibold text-red-500">No coincide</span>
                        <span v-else-if="form.schedule.length" class="font-semibold text-emerald-500">Coincide</span>
                    </div>
                    <InputError :message="form.errors.schedule" class="mt-2" />
                </div>

                <div v-for="(row, index) in form.schedule" :key="index" class="col-span-6 sm:col-span-3">
                    <div class="flex items-end gap-2">
                        <div class="flex-1">
                            <InputLabel :value="`Cuota ${index + 1} - Vencimiento`" />
                            <TextInput v-model="row.due_date" type="date" />
                        </div>
                        <div class="flex-1">
                            <InputLabel value="Monto" />
                            <TextInput v-model="row.amount" type="number" step="0.01" min="0" />
                        </div>
                        <button type="button" class="btn btn-danger btn-sm mb-1" @click="removeScheduleRow(index)">
                            <FontAwesomeIcon :icon="faTrashAlt" />
                        </button>
                    </div>
                    <InputError :message="form.errors[`schedule.${index}.due_date`] || form.errors[`schedule.${index}.amount`]" class="mt-1" />
                </div>
            </template>

            <div
                class="col-span-6"
                :class="!isFree && form.payment_method === 'billetera_digital' ? 'sm:col-span-2' : 'sm:col-span-3'"
            >
                <InputLabel value="Medio de pago *" />
                <Select
                    v-model:value="form.payment_method"
                    :options="paymentMethodOptions"
                    :disabled="isFree"
                    style="width: 100%"
                />
                <p v-if="isFree" class="text-xs text-gray-500 mt-1">Inhabilitado: la negociacion no tiene cobro, no habra ningun medio de pago que el cliente use.</p>
                <InputError :message="form.errors.payment_method" class="mt-2" />
            </div>

            <div v-if="!isFree && form.payment_method === 'enlace'" class="col-span-6 sm:col-span-3">
                <InputLabel for="payment_link" value="Enlace de pago" />
                <TextInput id="payment_link" v-model="form.payment_link" type="url" placeholder="https://..." />
                <InputError :message="form.errors.payment_link" class="mt-2" />
            </div>

            <div v-if="!isFree && form.payment_method === 'billetera_digital'" class="col-span-6 sm:col-span-4">
                <InputLabel value="Billetera digital *" />
                <Select
                    v-model:value="form.company_billetera_ids"
                    class="select-billeteras"
                    mode="multiple"
                    :options="billeteraOptions"
                    :disabled="form.payment_method !== 'billetera_digital'"
                    :filter-option="filterOption"
                    show-search
                    allow-clear
                    placeholder="Selecciona las billeteras digitales (con QR)"
                    style="width: 100%"
                />
                <InputError :message="form.errors.company_billetera_ids" class="mt-2" />
                <p class="text-xs text-gray-500 mt-1">En la confirmacion del cliente se mostrara el titular y el codigo QR de cada billetera seleccionada.</p>
            </div>

            <div v-if="!isFree && form.payment_method !== 'billetera_digital'" class="col-span-6 sm:col-span-3">
                <div class="pt-5 text-xs text-gray-500">
                    <template v-if="form.payment_method === 'transferencia'">
                        Se mostraran las cuentas bancarias de la empresa al cliente.
                    </template>
                    <template v-else-if="form.payment_method === 'mercadopago'">
                        El cliente pagara con su tarjeta (Mercado Pago) directamente en la confirmacion.
                    </template>
                    <template v-else>
                        Se mostraran los datos del pago en linea al cliente.
                    </template>
                </div>
            </div>

            <div class="col-span-6 sm:col-span-2">
                <InputLabel value="Fuente de Contacto" />
                <Select
                    v-model:value="form.contact_channel"
                    :options="contactChannelOptions"
                    allow-clear
                    style="width: 100%"
                />
                <InputError :message="form.errors.contact_channel" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="contact_detail" value="Asesor / Persona de contacto *" />
                <!-- Solo lectura: el asesor se registra con el usuario logueado y el
                     servidor lo fija por su cuenta (no se envia desde aqui). -->
                <TextInput
                    id="contact_detail"
                    :model-value="form.contact_detail"
                    type="text"
                    readonly
                    class="cursor-not-allowed bg-gray-100 dark:bg-gray-800"
                />
                <p class="text-xs text-gray-500 mt-1">Se registra automaticamente con el usuario que crea la negociacion.</p>
                <InputError :message="form.errors.contact_detail" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-2">
                <InputLabel for="email" value="Correo del cliente" />
                <TextInput id="email" v-model="form.email" type="email" placeholder="correo@cliente.com" />
                <InputError :message="form.errors.email" class="mt-2" />
                <p class="text-xs text-gray-500 mt-1">Se enviara aqui el enlace de la cotizacion.</p>
            </div>

            <div class="col-span-6">
                <InputLabel value="Detalle del acuerdo" />
                <EditorAracode
                    v-model="form.body"
                    minHeight="240px"
                    placeholder="Describe las condiciones del acuerdo..."
                />
                <InputError :message="form.errors.body" class="mt-2" />
            </div>
        </template>

        <template #actions>
            <PrimaryButton :class="{ 'opacity-50': form.processing }" :disabled="form.processing">
                <IconLoader v-if="form.processing" class="w-4 h-4 mr-2 animate-spin" />
                {{ isEdit ? "Actualizar" : "Guardar" }}
            </PrimaryButton>
            <Link :href="route('comm_negotiations')" class="btn btn-success ml-2">
                Ir al listado
            </Link>
        </template>
    </FormSection>
</template>

<style scoped>
/*
 * El select de billeteras es multiple y ant recorta el texto de cada etiqueta
 * con puntos suspensivos (overflow hidden + text-overflow ellipsis) cuando no
 * cabe en el recuadro. Le damos una caja mas alta para que las etiquetas bajen
 * de linea y permitimos que un nombre largo se parta en dos lineas dentro de
 * la etiqueta en vez de perderse.
 *
 * Los estilos de ant se inyectan en tiempo de ejecucion (cssinjs), por eso se
 * apuntan con :deep() y una clase propia: la especificidad de
 * .select-billeteras[data-v-*] .ant-select-selector queda por encima de la de
 * ant sin necesidad de !important.
 */
.select-billeteras :deep(.ant-select-selector) {
    min-height: 3.5rem;
    padding-block: 0.5rem;
}

.select-billeteras :deep(.ant-select-selection-item) {
    height: auto;
    align-items: center;
    line-height: 1.35;
    padding-block: 0.125rem;
    white-space: normal;
}

.select-billeteras :deep(.ant-select-selection-item-content) {
    white-space: normal;
    overflow-wrap: anywhere;
}
</style>
