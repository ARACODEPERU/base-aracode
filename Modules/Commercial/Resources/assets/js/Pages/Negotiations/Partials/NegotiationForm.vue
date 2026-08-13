<script setup>
import FormSection from "@/Components/FormSection.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import EditorAracode from "@/Components/EditorAracode.vue";
import IconLoader from "@/Components/vristo/icon/icon-loader.vue";
import { Link, useForm, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import { Select } from "ant-design-vue";
import Swal2 from "sweetalert2";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { faCalculator, faCalendarPlus, faTrashAlt } from "@fortawesome/free-solid-svg-icons";

const props = defineProps({
    negotiation: { type: Object, default: null },
    courses: { type: Array, default: () => [] },
    subscriptions: { type: Array, default: () => [] },
    identityDocumentTypes: { type: Array, default: () => [] },
    currencyTypes: { type: Array, default: () => [] },
    paymentMethods: { type: Array, default: () => [] },
    contactChannels: { type: Array, default: () => [] },
});

const isEdit = computed(() => !!props.negotiation?.id);

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
    contact_channel: props.negotiation?.contact_channel ?? null,
    contact_detail: props.negotiation?.contact_detail ?? currentUserName,
    payment_method: props.negotiation?.payment_method ?? "yape",
    payment_link: props.negotiation?.payment_link ?? null,
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

const currencyOptions = computed(() => props.currencyTypes.map((item) => ({
    value: item.id,
    label: `${item.id} - ${item.description}${item.symbol ? ` (${item.symbol})` : ""}`,
})));

const paymentMethodOptions = computed(() => props.paymentMethods.map((item) => ({
    value: item.value,
    label: item.label,
})));

const contactChannelOptions = computed(() => props.contactChannels.map((item) => ({
    value: item.value,
    label: item.label,
})));

const filterOption = (input, option) => option.label.toLowerCase().includes(input.toLowerCase());

const subscriptionPrice = (item) => {
    const prices = Array.isArray(item?.prices) ? item.prices : null;
    const value = prices?.find((price) => typeof price === "number" || !isNaN(parseFloat(price)));
    return value ?? null;
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

const submit = () => {
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

    const options = {
        preserveScroll: true,
        onSuccess: () => {
            Swal2.fire({
                title: "Enhorabuena",
                text: isEdit.value ? "Se actualizo correctamente" : "Se guardo correctamente",
                icon: "success",
                padding: "2em",
                customClass: "sweet-alerts",
            });

            if (!isEdit.value) {
                form.reset();
                form.currency = "PEN";
                form.payment_type = "single";
                form.payment_method = "yape";
                form.body = "";
                form.schedule = [];
                form.items = [];
            }
        },
        onError: () => {
            if (form.errors.items) {
                Swal2.fire({
                    title: "Atencion",
                    text: form.errors.items,
                    icon: "warning",
                    padding: "2em",
                    customClass: "sweet-alerts",
                });
            }
        },
    };

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

            <div class="col-span-6 sm:col-span-1">
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

            <div class="col-span-6 sm:col-span-2">
                <InputLabel value="Tipo de pago *" />
                <select v-model="form.payment_type" class="form-select text-white-dark">
                    <option value="single">Pago unico</option>
                    <option value="installments">Cuotas</option>
                </select>
                <InputError :message="form.errors.payment_type" class="mt-2" />
            </div>

            <div v-if="form.payment_type === 'installments'" class="col-span-6 sm:col-span-2">
                <div class="pt-5 text-xs text-gray-500">
                    La primera cuota del cronograma corresponde al pago inicial.
                </div>
            </div>

            <div v-else class="col-span-6 sm:col-span-2">
                <InputLabel for="single_payment_days" value="Plazo de pago (dias)" />
                <TextInput id="single_payment_days" v-model="form.single_payment_days" type="number" min="1" />
                <InputError :message="form.errors.single_payment_days" class="mt-2" />
            </div>

            <template v-if="form.payment_type === 'installments'">
                <div class="col-span-6">
                    <div class="flex items-center justify-between border-t border-gray-200 dark:border-gray-700 pt-4">
                        <h3 class="font-semibold text-gray-800 dark:text-white">Cronograma provisional de cuotas</h3>
                        <button type="button" class="btn btn-outline-primary btn-sm" :disabled="scheduleComplete" :class="{ 'opacity-50 cursor-not-allowed': scheduleComplete }" @click="addScheduleRow">
                            <FontAwesomeIcon :icon="faCalendarPlus" class="mr-1 h-3 w-3" />
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

            <div class="col-span-6 sm:col-span-3">
                <InputLabel value="Medio de pago *" />
                <Select
                    v-model:value="form.payment_method"
                    :options="paymentMethodOptions"
                    style="width: 100%"
                />
                <InputError :message="form.errors.payment_method" class="mt-2" />
            </div>

            <div v-if="['mercadopago', 'enlace'].includes(form.payment_method)" class="col-span-6 sm:col-span-3">
                <InputLabel for="payment_link" value="Enlace de pago" />
                <TextInput id="payment_link" v-model="form.payment_link" type="url" placeholder="https://..." />
                <InputError :message="form.errors.payment_link" class="mt-2" />
            </div>

            <div v-else class="col-span-6 sm:col-span-3">
                <div class="pt-5 text-xs text-gray-500">
                    <template v-if="form.payment_method === 'yape'">
                        Se mostrara el numero de Yape configurado de la empresa al cliente.
                    </template>
                    <template v-else>
                        Se mostraran las cuentas bancarias de la empresa al cliente.
                    </template>
                </div>
            </div>

            <div class="col-span-6 sm:col-span-2">
                <InputLabel value="Canal de contacto" />
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
                <TextInput id="contact_detail" v-model="form.contact_detail" type="text" placeholder="Nombre del asesor que realizo la negociacion" />
                <InputError :message="form.errors.contact_detail" class="mt-2" />
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
