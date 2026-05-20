<script setup>
import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
import Navigation from "@/Components/vristo/layout/Navigation.vue";
import { Link } from "@inertiajs/vue3";
import { computed } from "vue";
import apexchart from "vue3-apexcharts";
import { useAppStore } from "@/stores/index";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import {
    faBriefcase,
    faCalendarCheck,
    faChartLine,
    faClock,
    faCoins,
    faFileContract,
    faFolderOpen,
    faReceipt,
    faTriangleExclamation,
    faUsers,
} from "@fortawesome/free-solid-svg-icons";

const props = defineProps({
    metrics: { type: Object, default: () => ({}) },
    charts: { type: Object, default: () => ({}) },
    tables: { type: Object, default: () => ({}) },
});

const store = useAppStore();
const isDark = computed(() => store.theme === "dark" || store.isDarkMode);

const money = (value, currency = "PEN") => `${currency} ${Number(value || 0).toFixed(2)}`;
const shortDate = (date) => date ? new Date(`${date}T00:00:00`).toLocaleDateString("es-PE", { day: "2-digit", month: "short" }) : "-";

const baseChart = computed(() => ({
    chart: {
        toolbar: { show: false },
        fontFamily: "Inter, sans-serif",
        foreColor: isDark.value ? "#cbd5e1" : "#475569",
    },
    grid: {
        borderColor: isDark.value ? "#1f2937" : "#e5e7eb",
        strokeDashArray: 4,
    },
    tooltip: {
        theme: isDark.value ? "dark" : "light",
    },
    legend: {
        labels: {
            colors: isDark.value ? "#cbd5e1" : "#475569",
        },
    },
}));

const cashflowOptions = computed(() => ({
    ...baseChart.value,
    chart: { ...baseChart.value.chart, type: "bar", stacked: false },
    colors: ["#14b8a6", "#6366f1"],
    plotOptions: {
        bar: {
            borderRadius: 6,
            columnWidth: "45%",
        },
    },
    dataLabels: { enabled: false },
    xaxis: {
        categories: props.charts.cashflow?.labels || [],
        axisBorder: { color: isDark.value ? "#1f2937" : "#e5e7eb" },
        axisTicks: { color: isDark.value ? "#1f2937" : "#e5e7eb" },
    },
    yaxis: {
        labels: {
            formatter: (value) => Number(value || 0).toFixed(0),
        },
    },
    tooltip: {
        ...baseChart.value.tooltip,
        y: {
            formatter: (value) => money(value),
        },
    },
}));

const cashflowSeries = computed(() => [
    { name: "Proyectado", data: props.charts.cashflow?.projected || [] },
    { name: "Cobrado", data: props.charts.cashflow?.collected || [] },
]);

const statusOptions = computed(() => ({
    ...baseChart.value,
    chart: { ...baseChart.value.chart, type: "donut" },
    labels: props.charts.paymentStatus?.map((item) => item.label) || [],
    colors: ["#f59e0b", "#10b981", "#ef4444", "#38bdf8", "#8b5cf6", "#94a3b8"],
    stroke: { width: 0 },
    dataLabels: { enabled: false },
    plotOptions: {
        pie: {
            donut: {
                size: "72%",
                labels: {
                    show: true,
                    total: {
                        show: true,
                        label: "Cuotas",
                        color: isDark.value ? "#e5e7eb" : "#111827",
                    },
                },
            },
        },
    },
}));

const statusSeries = computed(() => props.charts.paymentStatus?.map((item) => item.value) || []);

const contractTypeOptions = computed(() => ({
    ...baseChart.value,
    chart: { ...baseChart.value.chart, type: "bar" },
    colors: ["#0ea5e9"],
    plotOptions: {
        bar: {
            borderRadius: 6,
            horizontal: true,
            barHeight: "45%",
        },
    },
    dataLabels: { enabled: false },
    xaxis: {
        categories: props.charts.contractTypes?.map((item) => item.label) || [],
        labels: {
            formatter: (value) => Number(value || 0).toFixed(0),
        },
    },
}));

const contractTypeSeries = computed(() => [
    {
        name: "Contratos",
        data: props.charts.contractTypes?.map((item) => item.value) || [],
    },
]);

const cards = computed(() => [
    {
        label: "Clientes",
        value: props.metrics.clients || 0,
        detail: "Registrados como clientes",
        icon: faUsers,
        classes: "text-white",
        style: {
            background: "linear-gradient(135deg, #0891b2 0%, #0369a1 100%)",
            boxShadow: "0 18px 35px rgba(8, 145, 178, 0.28)",
        },
        iconClass: "bg-white/20 text-white",
        chipClass: "bg-white/20 text-white",
        chip: "Base activa",
    },
    {
        label: "Contratos activos",
        value: props.metrics.activeContracts || 0,
        detail: `${props.metrics.expiringContracts || 0} vencen en 30 dias`,
        icon: faFileContract,
        classes: "text-white",
        style: {
            background: "linear-gradient(135deg, #7c3aed 0%, #a21caf 100%)",
            boxShadow: "0 18px 35px rgba(124, 58, 237, 0.28)",
        },
        iconClass: "bg-white/20 text-white",
        chipClass: "bg-white/20 text-white",
        chip: `${props.metrics.expiringContracts || 0} por vencer`,
    },
    {
        label: "Pendiente por cobrar",
        value: money(props.metrics.pendingAmount),
        detail: "Saldo abierto de cuotas",
        icon: faCoins,
        classes: "from-emerald-500 to-teal-500 text-white shadow-emerald-200/60 dark:shadow-emerald-950/40",
        iconClass: "bg-white/20 text-white",
        chipClass: "bg-white/20 text-white",
        chip: "Por cobrar",
    },
    {
        label: "Vencido",
        value: money(props.metrics.overdueAmount),
        detail: `${props.metrics.paymentsDueToday || 0} cuotas vencen hoy`,
        icon: faTriangleExclamation,
        classes: "from-rose-500 to-orange-400 text-white shadow-rose-200/60 dark:shadow-rose-950/40",
        iconClass: "bg-white/20 text-white",
        chipClass: "bg-white/20 text-white",
        chip: `${props.metrics.paymentsDueToday || 0} hoy`,
    },
]);
</script>

<template>
    <AppLayout title="Comercial">
        <Navigation :routeModule="route('comm_dashboard')" titleModule="Comercial"
            :data="[
                {title: 'Dashboard'}
            ]"
        />

        <div class="mt-5 space-y-5">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div
                    v-for="card in cards"
                    :key="card.label"
                    class="relative overflow-hidden rounded-md bg-gradient-to-br p-5 shadow-lg"
                    :class="card.classes"
                    :style="card.style"
                >
                    <div class="absolute -right-10 -top-10 h-28 w-28 rounded-full bg-white/15"></div>
                    <div class="absolute -bottom-14 right-10 h-32 w-32 rounded-full bg-white/10"></div>
                    <div class="flex items-start justify-between gap-3">
                        <div class="relative min-w-0">
                            <p class="text-sm font-semibold text-white/90">{{ card.label }}</p>
                            <p class="mt-5 truncate text-3xl font-bold text-white">{{ card.value }}</p>
                            <div class="mt-5 flex flex-wrap items-center gap-2 text-sm text-white/90">
                                <span class="inline-flex items-center rounded px-2.5 py-1 text-xs font-semibold" :class="card.chipClass">
                                    {{ card.chip }}
                                </span>
                                <span>{{ card.detail }}</span>
                            </div>
                        </div>
                        <div class="relative flex h-11 w-11 shrink-0 items-center justify-center rounded-md shadow-sm" :class="card.iconClass">
                            <FontAwesomeIcon :icon="card.icon" class="h-5 w-5" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">
                <div class="panel xl:col-span-2">
                    <div class="mb-4 flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-semibold dark:text-white">Cobranza mensual</h2>
                            <p class="text-sm text-gray-500">Proyectado contra cobrado en los ultimos 6 meses.</p>
                        </div>
                        <FontAwesomeIcon :icon="faChartLine" class="h-5 w-5 text-teal-500" />
                    </div>
                    <apexchart height="320" :options="cashflowOptions" :series="cashflowSeries" />
                </div>

                <div class="panel">
                    <div class="mb-4 flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-semibold dark:text-white">Estado de cuotas</h2>
                            <p class="text-sm text-gray-500">Distribucion actual del cronograma.</p>
                        </div>
                        <FontAwesomeIcon :icon="faReceipt" class="h-5 w-5 text-indigo-500" />
                    </div>
                    <apexchart v-if="statusSeries.length" height="320" :options="statusOptions" :series="statusSeries" />
                    <div v-else class="flex h-[320px] items-center justify-center text-sm text-gray-500">Sin cuotas registradas.</div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">
                <div class="panel">
                    <div class="mb-4 flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-semibold dark:text-white">Contratos por tipo</h2>
                            <p class="text-sm text-gray-500">Composicion del portafolio comercial.</p>
                        </div>
                        <FontAwesomeIcon :icon="faBriefcase" class="h-5 w-5 text-sky-500" />
                    </div>
                    <apexchart v-if="contractTypeSeries[0].data.length" height="260" :options="contractTypeOptions" :series="contractTypeSeries" />
                    <div v-else class="flex h-[260px] items-center justify-center text-sm text-gray-500">Sin contratos registrados.</div>
                </div>

                <div class="panel xl:col-span-2">
                    <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-lg font-semibold dark:text-white">Alertas operativas</h2>
                            <p class="text-sm text-gray-500">Contratos sin cronograma y vencimientos cercanos.</p>
                        </div>
                        <div class="rounded-md border border-orange-200 bg-orange-50 px-3 py-2 text-sm font-semibold text-orange-700 dark:border-orange-900/60 dark:bg-orange-950/30 dark:text-orange-300">
                            {{ metrics.contractsWithoutSchedule || 0 }} sin cronograma
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <Link
                            v-for="contract in tables.expiringContracts"
                            :key="contract.id"
                            :href="route('comm_contracts_edit', contract.id)"
                            class="rounded-md border border-gray-200 p-3 transition hover:border-sky-300 hover:bg-sky-50 dark:border-gray-800 dark:hover:border-sky-700 dark:hover:bg-sky-950/20"
                        >
                            <div class="flex items-start gap-3">
                                <div class="mt-1 flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-sky-100 text-sky-600 dark:bg-sky-900/50 dark:text-sky-300">
                                    <FontAwesomeIcon :icon="faClock" />
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate font-semibold dark:text-white">{{ contract.title }}</p>
                                    <p class="truncate text-xs text-gray-500">{{ contract.client || 'Sin cliente' }}</p>
                                    <p class="mt-1 text-xs text-sky-600 dark:text-sky-300">
                                        Vence {{ shortDate(contract.end_date) }} · {{ contract.days_left }} dias
                                    </p>
                                </div>
                            </div>
                        </Link>
                        <div v-if="!tables.expiringContracts?.length" class="rounded-md border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 dark:border-gray-700">
                            No hay contratos por vencer en los proximos 30 dias.
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
                <div class="panel">
                    <div class="mb-4 flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-semibold dark:text-white">Proximas cuotas</h2>
                            <p class="text-sm text-gray-500">Cobros programados para los proximos 30 dias.</p>
                        </div>
                        <FontAwesomeIcon :icon="faCalendarCheck" class="h-5 w-5 text-emerald-500" />
                    </div>

                    <div class="space-y-3">
                        <Link
                            v-for="payment in tables.upcomingPayments"
                            :key="payment.id"
                            :href="route('comm_contracts_payments', payment.contract_id)"
                            class="block rounded-md border border-gray-200 p-3 transition hover:border-emerald-300 hover:bg-emerald-50 dark:border-gray-800 dark:hover:border-emerald-700 dark:hover:bg-emerald-950/20"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate font-semibold dark:text-white">{{ payment.client || 'Sin cliente' }}</p>
                                    <p class="truncate text-xs text-gray-500">{{ payment.description }} · {{ payment.service || 'Servicio' }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-semibold text-emerald-600 dark:text-emerald-300">{{ money(payment.balance_amount, payment.currency) }}</p>
                                    <p class="text-xs text-gray-500">{{ shortDate(payment.due_date) }}</p>
                                </div>
                            </div>
                        </Link>
                        <div v-if="!tables.upcomingPayments?.length" class="rounded-md border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 dark:border-gray-700">
                            No hay cuotas proximas.
                        </div>
                    </div>
                </div>

                <div class="panel">
                    <div class="mb-4 flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-semibold dark:text-white">Cuotas vencidas</h2>
                            <p class="text-sm text-gray-500">Prioridad para seguimiento comercial.</p>
                        </div>
                        <FontAwesomeIcon :icon="faTriangleExclamation" class="h-5 w-5 text-rose-500" />
                    </div>

                    <div class="space-y-3">
                        <Link
                            v-for="payment in tables.overduePayments"
                            :key="payment.id"
                            :href="route('comm_contracts_payments', payment.contract_id)"
                            class="block rounded-md border border-gray-200 p-3 transition hover:border-rose-300 hover:bg-rose-50 dark:border-gray-800 dark:hover:border-rose-700 dark:hover:bg-rose-950/20"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate font-semibold dark:text-white">{{ payment.client || 'Sin cliente' }}</p>
                                    <p class="truncate text-xs text-gray-500">{{ payment.description }} · {{ payment.service || 'Servicio' }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-semibold text-rose-600 dark:text-rose-300">{{ money(payment.balance_amount, payment.currency) }}</p>
                                    <p class="text-xs text-gray-500">{{ shortDate(payment.due_date) }}</p>
                                </div>
                            </div>
                        </Link>
                        <div v-if="!tables.overduePayments?.length" class="rounded-md border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 dark:border-gray-700">
                            No hay cuotas vencidas.
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
                <div class="panel">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-lg font-semibold dark:text-white">Clientes recientes</h2>
                        <Link :href="route('comm_clients')" class="text-sm font-semibold text-sky-600 hover:underline dark:text-sky-300">
                            Ver todos
                        </Link>
                    </div>
                    <div class="space-y-2">
                        <div v-for="client in tables.recentClients" :key="client.id" class="flex items-center justify-between rounded-md bg-gray-50 px-3 py-2 dark:bg-gray-900/60">
                            <div class="min-w-0">
                                <p class="truncate font-semibold dark:text-white">{{ client.full_name }}</p>
                                <p class="text-xs text-gray-500">{{ client.number || 'Sin documento' }}</p>
                            </div>
                            <FontAwesomeIcon :icon="faUsers" class="text-sky-500" />
                        </div>
                        <div v-if="!tables.recentClients?.length" class="rounded-md border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 dark:border-gray-700">
                            Sin clientes recientes.
                        </div>
                    </div>
                </div>

                <div class="panel">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-lg font-semibold dark:text-white">Contratos recientes</h2>
                        <Link :href="route('comm_contracts')" class="text-sm font-semibold text-sky-600 hover:underline dark:text-sky-300">
                            Ver todos
                        </Link>
                    </div>
                    <div class="space-y-2">
                        <Link
                            v-for="contract in tables.recentContracts"
                            :key="contract.id"
                            :href="route('comm_contracts_edit', contract.id)"
                            class="flex items-center justify-between rounded-md bg-gray-50 px-3 py-2 transition hover:bg-sky-50 dark:bg-gray-900/60 dark:hover:bg-sky-950/20"
                        >
                            <div class="min-w-0">
                                <p class="truncate font-semibold dark:text-white">{{ contract.title }}</p>
                                <p class="truncate text-xs text-gray-500">{{ contract.client || 'Sin cliente' }} · {{ contract.service || 'Sin servicio' }}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold text-gray-800 dark:text-white">{{ money(contract.amount, contract.currency) }}</p>
                                <FontAwesomeIcon :icon="faFolderOpen" class="text-indigo-500" />
                            </div>
                        </Link>
                        <div v-if="!tables.recentContracts?.length" class="rounded-md border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 dark:border-gray-700">
                            Sin contratos recientes.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
