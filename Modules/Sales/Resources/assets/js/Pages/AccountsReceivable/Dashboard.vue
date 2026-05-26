<script setup>
import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
import Navigation from "@/Components/vristo/layout/Navigation.vue";
import { Link, usePage } from "@inertiajs/vue3";
import { computed } from "vue";
import apexchart from "vue3-apexcharts";
import { useAppStore } from "@/stores/index";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import {
    faArrowTrendUp,
    faCalendarDay,
    faChartColumn,
    faClock,
    faCoins,
    faFileCircleQuestion,
    faGauge,
    faListCheck,
    faTriangleExclamation,
    faWallet,
} from "@fortawesome/free-solid-svg-icons";

const props = defineProps({
    metrics: { type: Object, default: () => ({}) },
    charts: { type: Object, default: () => ({}) },
    tables: { type: Object, default: () => ({}) },
});

const store = useAppStore();
const page = usePage();

const permissions = computed(() => page.props.auth?.permissions || []);
const isDark = computed(() => store.theme === "dark" || store.isDarkMode);

const hasPermission = (permission) => {
    if (!permission) {
        return true;
    }

    if (Array.isArray(permission)) {
        return permission.some((item) => permissions.value.includes(item));
    }

    return permissions.value.includes(permission);
};

const money = (value) => `S/ ${Number(value || 0).toFixed(2)}`;
const compactNumber = (value) => Number(value || 0).toLocaleString("es-PE", { maximumFractionDigits: 0 });
const shortDate = (date) => {
    if (!date) {
        return "-";
    }

    const normalizedDate = typeof date === "string" ? date.replace(" ", "T") : date;
    const parsedDate = new Date(normalizedDate);

    return Number.isNaN(parsedDate.getTime())
        ? "-"
        : parsedDate.toLocaleDateString("es-PE", { day: "2-digit", month: "short" });
};

const totalPortfolio = computed(() => Number(props.metrics.portfolioTotal || 0));
const creditShare = computed(() => {
    if (!totalPortfolio.value) {
        return 0;
    }

    return Math.round((Number(props.metrics.creditOpenTotal || 0) / totalPortfolio.value) * 100);
});

const installmentShare = computed(() => {
    if (!totalPortfolio.value) {
        return 0;
    }

    return Math.round((Number(props.metrics.installmentOpenTotal || 0) / totalPortfolio.value) * 100);
});

const summaryCards = computed(() => [
    {
        label: "Vencido",
        value: money(props.metrics.overdueTotal),
        detail: `${props.metrics.overdueCasesCount || 0} casos requieren atencion`,
        icon: faTriangleExclamation,
        classes: "border-rose-200 bg-rose-50 text-rose-900 dark:border-rose-900/60 dark:bg-rose-950/30 dark:text-rose-100",
        iconClass: "bg-rose-100 text-rose-700 dark:bg-rose-900/70 dark:text-rose-300",
    },
    {
        label: "Por vencer 7 dias",
        value: money(props.metrics.dueSoonTotal),
        detail: "Compromisos proximos del flujo visible",
        icon: faCalendarDay,
        classes: "border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-100",
        iconClass: "bg-amber-100 text-amber-700 dark:bg-amber-900/70 dark:text-amber-300",
    },
    {
        label: "Recuperado este mes",
        value: money(props.metrics.recoveredThisMonth),
        detail: "Cobranza efectivamente registrada",
        icon: faArrowTrendUp,
        classes: "border-emerald-200 bg-emerald-50 text-emerald-900 dark:border-emerald-900/60 dark:bg-emerald-950/30 dark:text-emerald-100",
        iconClass: "bg-emerald-100 text-emerald-700 dark:bg-emerald-900/70 dark:text-emerald-300",
    },
]);

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
}));

const upcomingOptions = computed(() => ({
    ...baseChart.value,
    chart: {
        ...baseChart.value.chart,
        type: "bar",
        height: 320,
    },
    colors: ["#2563eb"],
    plotOptions: {
        bar: {
            borderRadius: 8,
            columnWidth: "48%",
        },
    },
    dataLabels: { enabled: false },
    xaxis: {
        categories: props.charts.upcomingCollections?.labels || [],
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

const upcomingSeries = computed(() => [
    {
        name: "Por cobrar",
        data: props.charts.upcomingCollections?.series || [],
    },
]);

const agingOptions = computed(() => ({
    ...baseChart.value,
    chart: {
        ...baseChart.value.chart,
        type: "bar",
        height: 320,
    },
    colors: ["#8b5cf6"],
    plotOptions: {
        bar: {
            horizontal: true,
            borderRadius: 6,
            barHeight: "48%",
        },
    },
    dataLabels: { enabled: false },
    xaxis: {
        categories: props.charts.agingBuckets?.labels || [],
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

const agingSeries = computed(() => [
    {
        name: "Saldo",
        data: props.charts.agingBuckets?.series || [],
    },
]);

const quickActions = computed(() => ([
    {
        label: "Documentos al credito",
        description: "Registrar cobros y saldar deudas sin emitir un nuevo comprobante.",
        route: route("acco_document_list"),
        permission: "acco_documento_listado",
        icon: faFileCircleQuestion,
    },
    {
        label: "Gestion ventas en cuotas",
        description: "Administrar ventas financiadas y su cronograma de cuotas.",
        route: route("acco_sales_special_rates"),
        permission: "acco_pagos_cuotas_especiales",
        icon: faListCheck,
    },
    {
        label: "Nueva venta en cuotas",
        description: "Registrar una nueva venta con cronograma de pagos.",
        route: route("acco_sales_special_rates_create"),
        permission: "acco_pagos_cuotas_especiales_nuevo",
        icon: faWallet,
    },
]).filter((item) => hasPermission(item.permission)));
</script>

<template>
    <AppLayout title="Cuentas por cobrar">
        <Navigation
            :routeModule="route('acco_dashboard')"
            titleModule="Cuentas por cobrar"
            :data="[{ title: 'Dashboard' }]"
        />

        <div class="mt-5 space-y-5">
            <div class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3">
                <div class="overflow-hidden rounded-xl bg-gradient-to-br from-slate-900 via-slate-800 to-indigo-900 p-5 text-white shadow-lg">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-white/70">Cartera total</p>
                            <p class="mt-4 text-4xl font-bold">{{ money(props.metrics.portfolioTotal) }}</p>
                            <p class="mt-3 text-sm text-white/80">
                                {{ compactNumber(props.metrics.openCasesCount || 0) }} operaciones activas en seguimiento.
                            </p>
                        </div>
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white/10">
                            <FontAwesomeIcon :icon="faGauge" class="h-5 w-5" />
                        </div>
                    </div>
                </div>

                <div class="panel rounded-md border border-slate-200 dark:border-slate-700">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Documentos al credito</p>
                            <p class="mt-2 text-xl font-bold text-slate-900 dark:text-white">{{ money(props.metrics.creditOpenTotal) }}</p>
                        </div>
                        <span class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700 dark:bg-blue-500/15 dark:text-blue-300">
                            {{ creditShare }}%
                        </span>
                    </div>
                    <div class="mt-3 h-2 rounded-full bg-slate-200 dark:bg-slate-700">
                        <div class="h-2 rounded-full bg-blue-600" :style="{ width: `${creditShare}%` }"></div>
                    </div>
                </div>

                <div class="panel rounded-md border border-slate-200 dark:border-slate-700">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Ventas en cuotas</p>
                            <p class="mt-2 text-xl font-bold text-slate-900 dark:text-white">{{ money(props.metrics.installmentOpenTotal) }}</p>
                        </div>
                        <span class="rounded-full bg-violet-100 px-2.5 py-1 text-xs font-semibold text-violet-700 dark:bg-violet-500/15 dark:text-violet-300">
                            {{ installmentShare }}%
                        </span>
                    </div>
                    <div class="mt-3 h-2 rounded-full bg-slate-200 dark:bg-slate-700">
                        <div class="h-2 rounded-full bg-violet-600" :style="{ width: `${installmentShare}%` }"></div>
                    </div>
                </div>

                <div
                    v-for="card in summaryCards"
                    :key="card.label"
                    class="panel rounded-md border"
                    :class="card.classes"
                >
                    <div class="flex items-start gap-3">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-md" :class="card.iconClass">
                            <FontAwesomeIcon :icon="card.icon" class="h-5 w-5" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-[0.12em]">{{ card.label }}</p>
                            <p class="mt-2 text-2xl font-bold">{{ card.value }}</p>
                            <p class="mt-2 text-sm opacity-80">{{ card.detail }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid gap-5 sm:grid-cols-12">
                <div class="space-y-5 sm:col-span-4">
                <div class="panel">
                    <h3 class="font-semibold dark:text-white">Accesos rapidos</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Atajos a los dos procesos del submodulo.
                    </p>

                    <div class="mt-4 space-y-3">
                        <Link
                            v-for="action in quickActions"
                            :key="action.label"
                            :href="action.route"
                            class="flex items-start gap-3 rounded-md border border-slate-200 bg-slate-50 px-3 py-3 transition hover:border-indigo-200 hover:bg-indigo-50 dark:border-slate-700 dark:bg-slate-800/70 dark:hover:border-indigo-900 dark:hover:bg-indigo-950/30"
                        >
                            <div class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-200">
                                <FontAwesomeIcon :icon="action.icon" class="h-4 w-4" />
                            </div>
                            <div class="min-w-0">
                                <p class="font-semibold text-slate-800 dark:text-slate-100">{{ action.label }}</p>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ action.description }}</p>
                            </div>
                        </Link>
                    </div>
                </div>
                </div>

                <div class="space-y-5 sm:col-span-8">
                    <div class="panel">
                        <div class="mb-4 flex items-center justify-between gap-3">
                            <div>
                                <h2 class="text-lg font-semibold dark:text-white">Proyeccion de cobranza</h2>
                                <p class="text-sm text-gray-500">
                                    Monto pendiente agrupado por las proximas seis semanas.
                                </p>
                            </div>
                            <FontAwesomeIcon :icon="faChartColumn" class="h-5 w-5 text-blue-600" />
                        </div>

                        <apexchart
                            v-if="upcomingSeries[0].data.length"
                            height="320"
                            :options="upcomingOptions"
                            :series="upcomingSeries"
                        />
                        <div v-else class="flex h-[320px] items-center justify-center text-sm text-gray-500">
                            No hay proyeccion de cobranza pendiente en las proximas semanas.
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
                        <div class="panel">
                            <div class="mb-4 flex items-center justify-between gap-3">
                                <div>
                                    <h2 class="text-lg font-semibold dark:text-white">Antiguedad de cartera</h2>
                                    <p class="text-sm text-gray-500">
                                        Distribucion del saldo segun dias de atraso.
                                    </p>
                                </div>
                                <FontAwesomeIcon :icon="faClock" class="h-5 w-5 text-violet-500" />
                            </div>

                            <apexchart
                                v-if="agingSeries[0].data.length"
                                height="320"
                                :options="agingOptions"
                                :series="agingSeries"
                            />
                            <div v-else class="flex h-[320px] items-center justify-center text-sm text-gray-500">
                                No hay cartera pendiente para clasificar.
                            </div>
                        </div>

                        <div class="panel">
                            <div class="mb-4 flex items-center justify-between gap-3">
                                <div>
                                    <h2 class="text-lg font-semibold dark:text-white">Cola de prioridad</h2>
                                    <p class="text-sm text-gray-500">
                                        Casos con mayor atraso o saldo pendiente.
                                    </p>
                                </div>
                                <FontAwesomeIcon :icon="faTriangleExclamation" class="h-5 w-5 text-rose-500" />
                            </div>

                            <div v-if="props.tables.priorityQueue?.length" class="space-y-3">
                                <div
                                    v-for="item in props.tables.priorityQueue"
                                    :key="`${item.source}-${item.reference}-${item.client}`"
                                    class="rounded-md border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/70"
                                >
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="font-semibold text-slate-800 dark:text-slate-100">{{ item.client }}</p>
                                            <p class="mt-1 text-xs uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400">{{ item.source }}</p>
                                            <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">{{ item.reference }}</p>
                                        </div>
                                        <span class="rounded-full bg-rose-100 px-2.5 py-1 text-xs font-semibold text-rose-700 dark:bg-rose-500/15 dark:text-rose-300">
                                            {{ item.daysLate }} dias
                                        </span>
                                    </div>
                                    <div class="mt-3 flex items-center justify-between text-sm">
                                        <span class="text-slate-500 dark:text-slate-400">Vence {{ shortDate(item.dueDate) }}</span>
                                        <span class="font-semibold text-slate-900 dark:text-white">{{ money(item.pendingAmount) }}</span>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="flex h-[320px] items-center justify-center text-sm text-gray-500">
                                No hay casos vencidos en prioridad.
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
                        <div class="panel">
                            <div class="mb-4 flex items-center justify-between gap-3">
                                <div>
                                    <h2 class="text-lg font-semibold dark:text-white">Mayores deudores</h2>
                                    <p class="text-sm text-gray-500">
                                        Clientes con mayor saldo acumulado por cobrar.
                                    </p>
                                </div>
                                <FontAwesomeIcon :icon="faCoins" class="h-5 w-5 text-amber-500" />
                            </div>

                            <div v-if="props.tables.topDebtors?.length" class="space-y-3">
                                <div
                                    v-for="debtor in props.tables.topDebtors"
                                    :key="debtor.client"
                                    class="rounded-md border border-slate-200 bg-slate-50 px-3 py-3 dark:border-slate-700 dark:bg-slate-800/70"
                                >
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="font-semibold text-slate-800 dark:text-slate-100">{{ debtor.client }}</p>
                                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                                {{ debtor.itemsCount }} origenes de deuda combinados
                                            </p>
                                        </div>
                                        <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">
                                            {{ money(debtor.pendingAmount) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="flex h-[260px] items-center justify-center text-sm text-gray-500">
                                No hay cartera pendiente para mostrar.
                            </div>
                        </div>

                        <div class="panel">
                            <div class="mb-4 flex items-center justify-between gap-3">
                                <div>
                                    <h2 class="text-lg font-semibold dark:text-white">Recuperaciones recientes</h2>
                                    <p class="text-sm text-gray-500">
                                        Pagos aplicados recientemente en ambos procesos.
                                    </p>
                                </div>
                                <FontAwesomeIcon :icon="faArrowTrendUp" class="h-5 w-5 text-emerald-500" />
                            </div>

                            <div v-if="props.tables.recentCollections?.length" class="space-y-3">
                                <div
                                    v-for="payment in props.tables.recentCollections"
                                    :key="`${payment.source}-${payment.client}-${payment.date}-${payment.amount}`"
                                    class="rounded-md border border-slate-200 bg-slate-50 px-3 py-3 dark:border-slate-700 dark:bg-slate-800/70"
                                >
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="font-semibold text-slate-800 dark:text-slate-100">{{ payment.client }}</p>
                                            <p class="mt-1 text-xs uppercase tracking-[0.12em] text-slate-500 dark:text-slate-400">{{ payment.source }}</p>
                                            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">{{ payment.method }} · {{ payment.reference }}</p>
                                        </div>
                                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">
                                            {{ money(payment.amount) }}
                                        </span>
                                    </div>
                                    <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">{{ shortDate(payment.date) }}</p>
                                </div>
                            </div>
                            <div v-else class="flex h-[260px] items-center justify-center text-sm text-gray-500">
                                No hay recuperaciones recientes registradas.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
