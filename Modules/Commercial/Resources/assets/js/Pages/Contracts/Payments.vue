<script setup>
import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
import Navigation from "@/Components/vristo/layout/Navigation.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import IconLoader from "@/Components/vristo/icon/icon-loader.vue";
import { Link, useForm } from "@inertiajs/vue3";
import { computed, reactive, watch } from "vue";
import Swal2 from "sweetalert2";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { faBriefcase, faCalendarPlus, faChartLine, faCoins, faFileInvoiceDollar, faPlus, faReceipt, faSave, faTrashAlt } from "@fortawesome/free-solid-svg-icons";

const props = defineProps({
    contract: { type: Object, default: () => ({}) },
    paymentTypes: { type: Array, default: () => [] },
    paymentStatuses: { type: Array, default: () => [] },
});

const numberValue = (value) => Number.parseFloat(value || 0) || 0;

const paymentRows = (payments = []) => payments?.length ? payments.map((item) => ({
    id: item.id,
    payment_number: item.payment_number,
    description: item.description,
    due_date: item.due_date,
    payment_type: item.payment_type,
    amount: Number(item.amount || 0).toFixed(2),
    interest_amount: Number(item.interest_amount || 0).toFixed(2),
    total_amount: Number(item.total_amount || 0).toFixed(2),
    balance_amount: Number(item.balance_amount ?? item.total_amount ?? 0).toFixed(2),
    status: item.status,
    paid_at: item.paid_at,
    document_payments: item.document_payments || [],
    notes: item.notes,
})) : [];

const addMonths = (dateText, months) => {
    const date = new Date(`${dateText}T00:00:00`);
    date.setMonth(date.getMonth() + months);
    return date.toISOString().slice(0, 10);
};

const monthsBetween = (startText, endText) => {
    const start = new Date(`${startText}T00:00:00`);
    const end = new Date(`${endText}T00:00:00`);
    const months = (end.getFullYear() - start.getFullYear()) * 12 + (end.getMonth() - start.getMonth());
    return end.getDate() >= start.getDate() ? months : Math.max(0, months - 1);
};

const form = useForm({
    payments: paymentRows(props.contract.payments),
});

const generator = reactive({
    mode: props.contract.contract_type === "rental" ? "monthly" : "advance_delivery",
    first_due_date: props.contract.contract_type === "rental" && props.contract.start_date ? addMonths(props.contract.start_date, 1) : props.contract.start_date ?? new Date().toISOString().slice(0, 10),
    rental_until_date: props.contract.end_date ?? props.contract.start_date ?? new Date().toISOString().slice(0, 10),
    interval_months: 1,
    advance_percentage: 50,
    delivery_percentage: 50,
    installments: 3,
    interest_percentage: 0,
    annual_free_months_per_year: 2,
});

const contractAmount = computed(() => Number(props.contract.amount || 0));
const currency = computed(() => props.contract.currency || "PEN");
const amountSum = computed(() => form.payments.reduce((sum, item) => sum + numberValue(item.amount), 0));
const interestSum = computed(() => form.payments.reduce((sum, item) => sum + numberValue(item.interest_amount), 0));
const totalSum = computed(() => form.payments.reduce((sum, item) => sum + numberValue(item.total_amount), 0));
const isRentalMode = computed(() => generator.mode === "monthly" || generator.mode === "annual");
const balance = computed(() => isRentalMode.value ? 0 : contractAmount.value - amountSum.value);

const money = (value) => `${currency.value} ${numberValue(value).toFixed(2)}`;
const isLockedPayment = (payment) => payment.status === "paid";

const updateRowTotal = (payment) => {
    payment.total_amount = (numberValue(payment.amount) + numberValue(payment.interest_amount)).toFixed(2);
    if (!payment.document_payments?.length) {
        payment.balance_amount = payment.total_amount;
    }
};

const recalculateRow = (payment) => {
    payment.amount = numberValue(payment.amount).toFixed(2);
    payment.interest_amount = numberValue(payment.interest_amount).toFixed(2);
    payment.total_amount = (numberValue(payment.amount) + numberValue(payment.interest_amount)).toFixed(2);
    if (!payment.document_payments?.length) {
        payment.balance_amount = payment.total_amount;
    }
};

const normalizeRows = () => {
    form.payments = form.payments.map((item, index) => ({
        ...item,
        payment_number: index + 1,
        amount: numberValue(item.amount).toFixed(2),
        interest_amount: numberValue(item.interest_amount).toFixed(2),
        total_amount: (numberValue(item.amount) + numberValue(item.interest_amount)).toFixed(2),
        balance_amount: item.document_payments?.length ? item.balance_amount : (numberValue(item.amount) + numberValue(item.interest_amount)).toFixed(2),
        status: item.status || "pending",
    }));
};

const addPayment = (payment = {}) => {
    const amount = numberValue(payment.amount);
    const interest = numberValue(payment.interest_amount);

    form.payments.push({
        id: payment.id ?? null,
        payment_number: form.payments.length + 1,
        description: payment.description ?? `Pago ${form.payments.length + 1}`,
        due_date: payment.due_date ?? generator.first_due_date,
        payment_type: payment.payment_type ?? "custom",
        amount: amount.toFixed(2),
        interest_amount: interest.toFixed(2),
        total_amount: (amount + interest).toFixed(2),
        balance_amount: (amount + interest).toFixed(2),
        status: payment.status ?? "pending",
        paid_at: payment.paid_at ?? null,
        document_payments: payment.document_payments ?? [],
        notes: payment.notes ?? null,
    });
};

const removePayment = (index) => {
    form.payments.splice(index, 1);
    normalizeRows();
};

const generateRentalSchedule = () => {
    form.clearErrors();
    form.payments = [];

    const firstDate = generator.first_due_date;
    const untilDate = generator.rental_until_date || firstDate;
    const first = new Date(`${firstDate}T00:00:00`);
    const until = new Date(`${untilDate}T00:00:00`);

    if (generator.mode === "annual") {
        const totalMonths = Math.max(1, monthsBetween(firstDate, untilDate));
        const years = Math.max(1, Math.ceil(totalMonths / 12));
        const coveredMonths = years * 12;
        const freeMonths = Math.min(coveredMonths, Math.max(0, numberValue(generator.annual_free_months_per_year)) * years);
        const chargeableMonths = Math.max(0, coveredMonths - freeMonths);

        addPayment({
            description: `Pago anual adelantado (${chargeableMonths} meses cobrados, ${freeMonths} gratis)`,
            due_date: firstDate,
            payment_type: "annual",
            amount: contractAmount.value * chargeableMonths,
        });

        normalizeRows();
        return;
    }

    const maxPayments = 240;
    let index = 0;

    while (index < maxPayments) {
        const dueDate = addMonths(firstDate, index);
        const current = new Date(`${dueDate}T00:00:00`);

        if (index > 0 && current > until) break;

        addPayment({
            description: `${generator.mode === "annual" ? "Anualidad" : "Mensualidad"} ${index + 1}`,
            due_date: current < first ? firstDate : dueDate,
            payment_type: generator.mode,
            amount: contractAmount.value,
        });

        if (until <= first) break;
        index++;
    }

    normalizeRows();
};

const generateAdvanceDeliverySchedule = () => {
    form.clearErrors();
    form.payments = [];

    const advance = Math.min(100, Math.max(0, numberValue(generator.advance_percentage)));
    const delivery = Math.max(0, 100 - advance);

    addPayment({
        description: `Adelanto ${advance}%`,
        due_date: generator.first_due_date,
        payment_type: "initial",
        amount: contractAmount.value * (advance / 100),
    });

    addPayment({
        description: `Contra entrega ${delivery}%`,
        due_date: addMonths(generator.first_due_date, Math.max(1, Number.parseInt(generator.interval_months || 1))),
        payment_type: "delivery",
        amount: contractAmount.value * (delivery / 100),
    });

    normalizeRows();
};

const generateSchedule = () => {
    form.clearErrors();
    form.payments = [];

    if (generator.mode === "installments") {
        const installments = Math.max(1, Number.parseInt(generator.installments || 1));
        const interest = contractAmount.value * (numberValue(generator.interest_percentage) / 100);
        const amountPerInstallment = contractAmount.value / installments;
        const interestPerInstallment = interest / installments;

        for (let index = 0; index < installments; index++) {
            addPayment({
                description: `Cuota ${index + 1}`,
                due_date: addMonths(generator.first_due_date, index * Math.max(1, Number.parseInt(generator.interval_months || 1))),
                payment_type: "installment",
                amount: amountPerInstallment,
                interest_amount: interestPerInstallment,
            });
        }
    }

    normalizeRows();
};

watch(() => generator.mode, (mode) => {
    if (!props.contract.start_date) return;

    if (mode === "monthly") {
        generator.first_due_date = addMonths(props.contract.start_date, 1);
    }

    if (mode === "annual") {
        generator.first_due_date = props.contract.start_date;
    }
});

watch(() => [
    generator.mode,
    generator.first_due_date,
    generator.rental_until_date,
    generator.annual_free_months_per_year,
    generator.advance_percentage,
    generator.interval_months,
], () => {
    if (generator.mode === "monthly" || generator.mode === "annual") {
        generateRentalSchedule();
    }

    if (generator.mode === "advance_delivery") {
        generateAdvanceDeliverySchedule();
    }
});

if (!props.contract.payments?.length) {
    if (generator.mode === "monthly" || generator.mode === "annual") {
        generateRentalSchedule();
    }

    if (generator.mode === "advance_delivery") {
        generateAdvanceDeliverySchedule();
    }
}

const saveSchedule = () => {
    normalizeRows();

    Swal2.fire({
        title: "Estas seguro?",
        text: "Se guardara el cronograma de pagos del contrato.",
        icon: "question",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Si, guardar",
        cancelButtonText: "Cancelar",
        padding: "2em",
        customClass: "sweet-alerts",
    }).then((result) => {
        if (!result.isConfirmed) return;

        form.post(route("comm_contracts_payments_store", props.contract.id), {
            preserveScroll: true,
            onSuccess: (page) => {
                form.payments = paymentRows(page.props.contract?.payments || props.contract.payments);
                form.clearErrors();

                Swal2.fire({
                    title: "Enhorabuena",
                    text: "Cronograma guardado correctamente",
                    icon: "success",
                    padding: "2em",
                    customClass: "sweet-alerts",
                });
            },
        });
    });
};
</script>

<template>
    <AppLayout title="Cronograma de pagos">
        <Navigation :routeModule="route('comm_dashboard')" titleModule="Comercial"
            :data="[
                {route: route('comm_contracts'), title: 'Contratos'},
                {title: 'Cronograma'}
            ]"
        />

        <div class="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-3">
            <div class="panel lg:col-span-2">
                <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold dark:text-white">{{ contract.title }}</h2>
                        <p class="text-sm text-gray-500">{{ contract.client?.full_name }} - {{ contract.client?.number }}</p>
                    </div>
                    <Link :href="route('comm_contracts')" class="btn btn-success">
                        Ir al listado
                    </Link>
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                    <div class="sm:col-span-3 rounded-md border border-l-4 border-emerald-200 border-l-emerald-500 bg-emerald-50 p-4 dark:border-emerald-900/60 dark:border-l-emerald-400 dark:bg-emerald-950/30">
                        <div class="flex items-start gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-emerald-100 text-emerald-700 dark:bg-emerald-900/70 dark:text-emerald-300">
                                <FontAwesomeIcon :icon="faBriefcase" class="h-5 w-5" />
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-semibold uppercase text-emerald-700 dark:text-emerald-300">Servicio</p>
                                <p class="mt-1 truncate font-semibold text-emerald-950 dark:text-emerald-50">{{ contract.service?.description || 'Sin servicio' }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-md border border-l-4 border-blue-200 border-l-blue-500 bg-blue-50 p-4 dark:border-blue-900/60 dark:border-l-blue-400 dark:bg-blue-950/30">
                        <div class="flex items-start gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-blue-100 text-blue-700 dark:bg-blue-900/70 dark:text-blue-300">
                                <FontAwesomeIcon :icon="faCoins" class="h-5 w-5" />
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase text-blue-700 dark:text-blue-300">{{ isRentalMode ? 'Valor por pago' : 'Monto contrato' }}</p>
                                <p class="mt-1 font-semibold text-blue-950 dark:text-blue-50">{{ money(contract.amount) }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-md border border-l-4 border-amber-200 border-l-amber-500 bg-amber-50 p-4 dark:border-amber-900/60 dark:border-l-amber-400 dark:bg-amber-950/30">
                        <div class="flex items-start gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-amber-100 text-amber-700 dark:bg-amber-900/70 dark:text-amber-300">
                                <FontAwesomeIcon :icon="faReceipt" class="h-5 w-5" />
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase text-amber-700 dark:text-amber-300">Capital programado</p>
                                <p class="mt-1 font-semibold" :class="!isRentalMode && Math.abs(balance) > 0.01 ? 'text-amber-700 dark:text-amber-300' : 'text-amber-950 dark:text-amber-50'">
                                    {{ money(amountSum) }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-md border border-l-4 border-rose-200 border-l-rose-500 bg-rose-50 p-4 dark:border-rose-900/60 dark:border-l-rose-400 dark:bg-rose-950/30">
                        <div class="flex items-start gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-rose-100 text-rose-700 dark:bg-rose-900/70 dark:text-rose-300">
                                <FontAwesomeIcon :icon="faChartLine" class="h-5 w-5" />
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase text-rose-700 dark:text-rose-300">Total con interes</p>
                                <p class="mt-1 font-semibold text-rose-950 dark:text-rose-50">{{ money(totalSum) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="panel">
                <h3 class="mb-4 font-semibold dark:text-white">Generador</h3>

                <div class="grid grid-cols-1 gap-3">
                    <div>
                        <InputLabel value="Modalidad" />
                        <select v-model="generator.mode" class="form-select">
                            <option value="monthly">Alquiler mensual</option>
                            <option value="annual">Alquiler anual</option>
                            <option value="advance_delivery">Adelanto + entrega</option>
                            <option value="installments">Cuotas iguales</option>
                        </select>
                    </div>

                    <div>
                        <InputLabel value="Primera fecha de pago" />
                        <TextInput v-model="generator.first_due_date" type="date" />
                    </div>

                    <div v-if="generator.mode === 'monthly' || generator.mode === 'annual'">
                        <InputLabel value="Generar hasta" />
                        <TextInput v-model="generator.rental_until_date" type="date" class="opacity-70" disabled />
                    </div>

                    <div v-if="generator.mode === 'annual'">
                        <InputLabel value="Meses gratis por ano" />
                        <TextInput v-model="generator.annual_free_months_per_year" type="number" min="0" max="12" step="1" />
                    </div>

                    <p v-if="generator.mode === 'monthly' || generator.mode === 'annual'" class="text-xs text-gray-500">
                        <template v-if="generator.mode === 'monthly'">
                            El cronograma se genera automaticamente desde la primera fecha de pago hasta la fecha fin del contrato. Cada mensualidad usa el valor del servicio.
                        </template>
                        <template v-else>
                            El pago anual es adelantado. Se calcula con el valor mensual del servicio por los meses cobrados, descontando los meses gratis configurados.
                        </template>
                    </p>

                    <div v-if="generator.mode === 'advance_delivery' || generator.mode === 'installments'">
                        <InputLabel value="Intervalo en meses" />
                        <TextInput v-model="generator.interval_months" type="number" min="1" />
                    </div>

                    <div v-if="generator.mode === 'advance_delivery'">
                        <InputLabel value="Porcentaje de adelanto" />
                        <TextInput v-model="generator.advance_percentage" type="number" min="0" max="100" step="0.01" />
                    </div>

                    <div v-if="generator.mode === 'installments'">
                        <InputLabel value="Cantidad de cuotas" />
                        <TextInput v-model="generator.installments" type="number" min="1" />
                    </div>

                    <div v-if="generator.mode === 'installments'">
                        <InputLabel value="Interes %" />
                        <TextInput v-model="generator.interest_percentage" type="number" min="0" step="0.01" />
                    </div>

                    <button v-if="generator.mode === 'installments'" type="button" class="btn btn-primary" @click="generateSchedule">
                        <FontAwesomeIcon :icon="faCalendarPlus" class="mr-2 h-4 w-4" />
                        Generar cronograma
                    </button>
                </div>
            </div>
        </div>

        <div class="panel mt-5">
            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="font-semibold dark:text-white">Pagos programados</h3>
                    <p class="text-sm text-gray-500">
                        <template v-if="isRentalMode">
                            Cada pago: {{ money(contract.amount) }}
                        </template>
                        <template v-else>
                            Diferencia capital: {{ money(balance) }}
                        </template>
                        - Interes: {{ money(interestSum) }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button type="button" class="btn btn-outline-primary" @click="addPayment()">
                        <FontAwesomeIcon :icon="faPlus" class="mr-2 h-4 w-4" />
                        Agregar pago
                    </button>
                    <button type="button" class="btn btn-success" :class="{ 'opacity-50': form.processing }" :disabled="form.processing" @click="saveSchedule">
                        <IconLoader v-if="form.processing" class="w-4 h-4 mr-2 animate-spin" />
                        <FontAwesomeIcon v-else :icon="faSave" class="mr-2 h-4 w-4" />
                        Guardar cronograma
                    </button>
                </div>
            </div>

            <InputError :message="form.errors.payments" class="mb-3" />

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Descripcion</th>
                            <th>Fecha vencimiento</th>
                            <th>Tipo</th>
                            <th>Capital</th>
                            <th>Interes</th>
                            <th>Total</th>
                            <th>Saldo</th>
                            <th>Estado</th>
                            <th>Pagado el</th>
                            <th>Documento</th>
                            <th>Notas</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="!form.payments.length">
                            <td colspan="13" class="text-center text-gray-500">
                                Genera o agrega pagos para armar el cronograma.
                            </td>
                        </tr>
                        <tr v-for="(payment, index) in form.payments" :key="index">
                            <td>{{ index + 1 }}</td>
                            <td>
                                <input v-model="payment.description" class="form-input min-w-[170px]" :class="{ 'opacity-60': isLockedPayment(payment) }" :disabled="isLockedPayment(payment)" type="text" />
                            </td>
                            <td>
                                <input v-model="payment.due_date" class="form-input min-w-[140px]" :class="{ 'opacity-60': isLockedPayment(payment) }" :disabled="isLockedPayment(payment)" type="date" />
                            </td>
                            <td>
                                <select v-model="payment.payment_type" class="form-select min-w-[150px]" :class="{ 'opacity-60': isLockedPayment(payment) }" :disabled="isLockedPayment(payment)">
                                    <option v-for="type in paymentTypes" :key="type.value" :value="type.value">
                                        {{ type.label }}
                                    </option>
                                </select>
                            </td>
                            <td>
                                <input v-model="payment.amount" class="form-input min-w-[120px]" :class="{ 'opacity-60': isLockedPayment(payment) }" :disabled="isLockedPayment(payment)" type="number" min="0" step="0.01" @input="updateRowTotal(payment)" @blur="recalculateRow(payment)" />
                            </td>
                            <td>
                                <input v-model="payment.interest_amount" class="form-input min-w-[120px]" :class="{ 'opacity-60': isLockedPayment(payment) }" :disabled="isLockedPayment(payment)" type="number" min="0" step="0.01" @input="updateRowTotal(payment)" @blur="recalculateRow(payment)" />
                            </td>
                            <td class="font-semibold">{{ money(payment.total_amount) }}</td>
                            <td class="font-semibold" :class="numberValue(payment.balance_amount) > 0 ? 'text-amber-600' : 'text-emerald-600'">
                                {{ money(payment.balance_amount ?? payment.total_amount) }}
                            </td>
                            <td>
                                <select v-model="payment.status" class="form-select min-w-[130px]" :class="{ 'opacity-60': isLockedPayment(payment) }" :disabled="isLockedPayment(payment)">
                                    <option v-for="status in paymentStatuses" :key="status.value" :value="status.value">
                                        {{ status.label }}
                                    </option>
                                </select>
                            </td>
                            <td>
                                <input v-model="payment.paid_at" class="form-input min-w-[140px]" :class="{ 'opacity-60': isLockedPayment(payment) }" :disabled="isLockedPayment(payment)" type="date" />
                            </td>
                            <td>
                                <div class="min-w-[160px] text-xs text-gray-500">
                                    <template v-if="payment.document_payments?.length">
                                        <div v-for="document in payment.document_payments" :key="document.document_id">
                                            {{ document.serie }}-{{ document.correlativo }}
                                        </div>
                                    </template>
                                    <span v-else>Sin documento</span>
                                </div>
                            </td>
                            <td>
                                <input v-model="payment.notes" class="form-input min-w-[170px]" :class="{ 'opacity-60': isLockedPayment(payment) }" :disabled="isLockedPayment(payment)" type="text" />
                            </td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <Link
                                        v-if="payment.id && !['paid', 'cancelled'].includes(payment.status) && numberValue(payment.balance_amount ?? payment.total_amount) > 0"
                                        :href="route('comm_contract_payment_document_create', payment.id)"
                                        class="btn btn-primary btn-sm"
                                        v-tippy="{ content: 'Generar documento de venta', placement: 'bottom'}"
                                    >
                                        <FontAwesomeIcon :icon="faFileInvoiceDollar" />
                                    </Link>
                                    <button type="button" class="btn btn-danger btn-sm" @click="removePayment(index)"
                                        v-tippy="{ content: 'Eliminar pago', placement: 'bottom'}"
                                    >
                                        <FontAwesomeIcon :icon="faTrashAlt" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
