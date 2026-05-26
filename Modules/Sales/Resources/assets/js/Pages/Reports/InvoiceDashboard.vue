<script setup>
import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
import Navigation from "@/Components/vristo/layout/Navigation.vue";
import { Link, usePage } from "@inertiajs/vue3";
import { computed } from "vue";
import apexchart from "vue3-apexcharts";
import { useAppStore } from "@/stores/index";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import {
    faArrowRotateLeft,
    faBan,
    faBolt,
    faChartLine,
    faCircleCheck,
    faFileArrowDown,
    faFileCirclePlus,
    faFileInvoice,
    faFileLines,
    faFileSignature,
    faListOl,
    faTriangleExclamation,
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

const cards = computed(() => [
    {
        label: "Emitido hoy",
        value: money(props.metrics.todayTotal),
        detail: `${props.metrics.todayCount || 0} comprobantes generados`,
        icon: faFileInvoice,
        style: {
            background: "linear-gradient(135deg, #2563eb 0%, #7c3aed 100%)",
            boxShadow: "0 18px 35px rgba(37, 99, 235, 0.24)",
        },
    },
    {
        label: "Emitido mes",
        value: money(props.metrics.monthTotal),
        detail: `${props.metrics.monthCount || 0} documentos del mes`,
        icon: faChartLine,
        style: {
            background: "linear-gradient(135deg, #0891b2 0%, #0f766e 100%)",
            boxShadow: "0 18px 35px rgba(8, 145, 178, 0.24)",
        },
    },
    {
        label: "Pendientes SUNAT",
        value: compactNumber(props.metrics.pendingSunatCount),
        detail: `${props.metrics.rejectedCount || 0} rechazados para revisar`,
        icon: faArrowRotateLeft,
        style: {
            background: "linear-gradient(135deg, #f59e0b 0%, #ea580c 100%)",
            boxShadow: "0 18px 35px rgba(245, 158, 11, 0.24)",
        },
    },
    {
        label: "Notas electronicas",
        value: compactNumber(props.metrics.notesCount),
        detail: "Credito y debito emitidos este mes",
        icon: faFileSignature,
        style: {
            background: "linear-gradient(135deg, #16a34a 0%, #0f766e 100%)",
            boxShadow: "0 18px 35px rgba(22, 163, 74, 0.24)",
        },
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
    legend: {
        labels: {
            colors: isDark.value ? "#cbd5e1" : "#475569",
        },
    },
}));

const trendOptions = computed(() => ({
    ...baseChart.value,
    chart: {
        ...baseChart.value.chart,
        type: "area",
        height: 320,
    },
    colors: ["#2563eb"],
    dataLabels: { enabled: false },
    stroke: {
        curve: "smooth",
        width: 3,
    },
    fill: {
        type: "gradient",
        gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.35,
            opacityTo: 0.04,
            stops: [0, 90, 100],
        },
    },
    xaxis: {
        categories: props.charts.emissionTrend?.labels || [],
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

const trendSeries = computed(() => [
    {
        name: "Emitido",
        data: props.charts.emissionTrend?.totals || [],
    },
]);

const statusOptions = computed(() => ({
    ...baseChart.value,
    chart: {
        ...baseChart.value.chart,
        type: "donut",
    },
    labels: (props.charts.sunatStatus?.items || []).map((item) => item.label),
    colors: ["#16a34a", "#f59e0b", "#ef4444", "#8b5cf6", "#64748b"],
    stroke: { width: 0 },
    dataLabels: { enabled: false },
    plotOptions: {
        pie: {
            donut: {
                size: "70%",
                labels: {
                    show: true,
                    total: {
                        show: true,
                        label: "Estados",
                        color: isDark.value ? "#e5e7eb" : "#111827",
                        formatter: () => compactNumber((props.charts.sunatStatus?.items || []).reduce((sum, item) => sum + item.value, 0)),
                    },
                },
            },
        },
    },
    legend: {
        show: false,
    },
}));

const statusSeries = computed(() => (props.charts.sunatStatus?.items || []).map((item) => item.value));
const statusLegend = computed(() => props.charts.sunatStatus?.items || []);
const rejectedDocuments = computed(() => props.tables.alerts?.rejectedDocuments || []);

const allQuickActions = [
    {
        label: "Crear documento",
        description: "Registrar una nueva factura o boleta electronica.",
        route: route("saledocuments_create"),
        permission: "invo_documento",
        icon: faFileCirclePlus,
    },
    {
        label: "Lista de documentos",
        description: "Revisar estados SUNAT y descargar comprobantes.",
        route: route("saledocuments_list"),
        permission: "invo_documento_lista",
        icon: faListOl,
    },
    {
        label: "Notas de credito y debito",
        description: "Gestionar correcciones y ajustes sobre documentos emitidos.",
        route: route("sale_credit_notes_list"),
        permission: "invo_nota_credito",
        icon: faFileSignature,
    },
    {
        label: "Resumen diario",
        description: "Enviar boletas pendientes en lotes del dia.",
        route: route("salesummaries_list"),
        permission: "invo_resumenes_lista",
        icon: faFileLines,
    },
    {
        label: "Comunicacion de baja",
        description: "Gestionar anulaciones que requieren envio a SUNAT.",
        route: route("low_communication_list"),
        permission: "invo_comunicacion_baja",
        icon: faFileArrowDown,
    },
];

const quickActions = computed(() => allQuickActions.filter((item) => hasPermission(item.permission)));
</script>

<template>
    <AppLayout title="Facturacion Electronica">
        <Navigation
            :routeModule="route('reports_invoice')"
            titleModule="Facturacion Electronica"
            :data="[{ title: 'Dashboard' }]"
        />

        <div class="mt-5 space-y-5">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div
                    v-for="card in cards"
                    :key="card.label"
                    class="relative overflow-hidden rounded-md p-5 text-white shadow-lg"
                    :style="card.style"
                >
                    <div class="absolute -right-10 -top-10 h-28 w-28 rounded-full bg-white/10"></div>
                    <div class="absolute -bottom-14 right-10 h-32 w-32 rounded-full bg-white/10"></div>
                    <div class="relative flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-white/90">{{ card.label }}</p>
                            <p class="mt-5 truncate text-3xl font-bold text-white">{{ card.value }}</p>
                            <p class="mt-4 text-sm text-white/90">{{ card.detail }}</p>
                        </div>
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-md bg-white/20 shadow-sm">
                            <FontAwesomeIcon :icon="card.icon" class="h-5 w-5" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">
                <div class="panel xl:col-span-2">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-semibold dark:text-white">Tendencia de emision</h2>
                            <p class="text-sm text-gray-500">
                                Ultimos 7 dias del flujo de comprobantes electronicos.
                            </p>
                        </div>
                        <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700 dark:bg-blue-500/10 dark:text-blue-300">
                            {{ props.charts.emissionTrend?.counts?.reduce((sum, item) => sum + item, 0) || 0 }} documentos
                        </span>
                    </div>
                    <apexchart
                        v-if="trendSeries[0].data.length"
                        height="320"
                        :options="trendOptions"
                        :series="trendSeries"
                    />
                    <div v-else class="flex h-[320px] items-center justify-center text-sm text-gray-500">
                        No hay comprobantes emitidos en el periodo.
                    </div>
                </div>

                <div class="panel">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-semibold dark:text-white">Estado SUNAT</h2>
                            <p class="text-sm text-gray-500">
                                Distribucion del estado de los documentos del mes.
                            </p>
                        </div>
                        <FontAwesomeIcon :icon="faCircleCheck" class="h-5 w-5 text-emerald-500" />
                    </div>

                    <apexchart
                        v-if="statusSeries.length"
                        height="260"
                        :options="statusOptions"
                        :series="statusSeries"
                    />
                    <div v-else class="flex h-[260px] items-center justify-center text-sm text-gray-500">
                        Sin estados registrados este mes.
                    </div>

                    <div class="mt-4 space-y-3">
                        <div
                            v-for="(item, index) in statusLegend"
                            :key="item.label"
                            class="flex items-center justify-between gap-3 rounded-md bg-slate-50 px-3 py-2 dark:bg-slate-800/70"
                        >
                            <div class="flex min-w-0 items-center gap-2">
                                <span
                                    class="h-2.5 w-2.5 shrink-0 rounded-full"
                                    :class="[
                                        index === 0 ? 'bg-emerald-500' : '',
                                        index === 1 ? 'bg-amber-500' : '',
                                        index === 2 ? 'bg-rose-500' : '',
                                        index === 3 ? 'bg-violet-500' : '',
                                        index >= 4 ? 'bg-slate-500' : '',
                                    ]"
                                ></span>
                                <span class="truncate text-sm text-slate-700 dark:text-slate-200">{{ item.label }}</span>
                            </div>
                            <span class="text-sm font-semibold text-slate-900 dark:text-white">{{ compactNumber(item.value) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">
                <div class="panel xl:col-span-2">
                    <div class="mb-4 flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-semibold dark:text-white">Documentos recientes</h2>
                            <p class="text-sm text-gray-500">
                                Ultimos comprobantes y notas gestionados en el proceso electronico.
                            </p>
                        </div>
                        <Link
                            v-if="hasPermission('invo_documento_lista')"
                            :href="route('saledocuments_list')"
                            class="text-sm font-semibold text-blue-600 hover:text-blue-700 dark:text-blue-300"
                        >
                            Ver listado
                        </Link>
                    </div>

                    <div v-if="props.tables.recentDocuments?.length" class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-b border-slate-200 text-left text-slate-500 dark:border-slate-700 dark:text-slate-400">
                                    <th class="pb-3 pr-4 font-medium">Tipo</th>
                                    <th class="pb-3 pr-4 font-medium">Numero</th>
                                    <th class="pb-3 pr-4 font-medium">Cliente</th>
                                    <th class="pb-3 pr-4 font-medium">Estado</th>
                                    <th class="pb-3 pr-4 font-medium">Fecha</th>
                                    <th class="pb-3 text-right font-medium">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="document in props.tables.recentDocuments"
                                    :key="document.id"
                                    class="border-b border-slate-100 last:border-0 dark:border-slate-800"
                                >
                                    <td class="py-3 pr-4 font-semibold text-slate-800 dark:text-slate-100">{{ document.type }}</td>
                                    <td class="py-3 pr-4 text-slate-600 dark:text-slate-300">{{ document.number }}</td>
                                    <td class="py-3 pr-4 text-slate-600 dark:text-slate-300">{{ document.client }}</td>
                                    <td class="py-3 pr-4">
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-700 dark:text-slate-200">
                                            {{ document.status }}
                                        </span>
                                    </td>
                                    <td class="py-3 pr-4 text-slate-500 dark:text-slate-400">{{ shortDate(document.date) }}</td>
                                    <td class="py-3 text-right font-semibold text-slate-900 dark:text-white">{{ money(document.total) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="flex h-[240px] items-center justify-center text-sm text-gray-500">
                        No hay documentos recientes para mostrar.
                    </div>
                </div>

                <div class="panel">
                    <div class="mb-4">
                        <h2 class="text-lg font-semibold dark:text-white">Accesos rapidos</h2>
                        <p class="text-sm text-gray-500">
                            Atajos al flujo operativo de facturacion electronica.
                        </p>
                    </div>

                    <div class="space-y-3">
                        <Link
                            v-for="action in quickActions"
                            :key="action.label"
                            :href="action.route"
                            class="flex items-start gap-3 rounded-md border border-slate-200 bg-slate-50 px-3 py-3 transition hover:border-blue-200 hover:bg-blue-50 dark:border-slate-700 dark:bg-slate-800/70 dark:hover:border-blue-900 dark:hover:bg-blue-950/30"
                        >
                            <div class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-200">
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

            <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">
                <div class="panel">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-semibold dark:text-white">Lotes recientes</h2>
                            <p class="text-sm text-gray-500">
                                Ultimos resumenes y comunicaciones registrados.
                            </p>
                        </div>
                        <FontAwesomeIcon :icon="faBolt" class="h-5 w-5 text-indigo-500" />
                    </div>

                    <div v-if="props.tables.recentBatches?.length" class="space-y-3">
                        <div
                            v-for="batch in props.tables.recentBatches"
                            :key="batch.id"
                            class="rounded-md border border-slate-200 bg-slate-50 px-3 py-3 dark:border-slate-700 dark:bg-slate-800/70"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-semibold text-slate-800 dark:text-slate-100">{{ batch.type }}</p>
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ batch.code }}</p>
                                </div>
                                <span class="rounded-full bg-indigo-100 px-2.5 py-1 text-xs font-semibold text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300">
                                    {{ batch.status }}
                                </span>
                            </div>
                            <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">
                                {{ shortDate(batch.date) }}
                            </p>
                        </div>
                    </div>
                    <div v-else class="flex h-[260px] items-center justify-center text-sm text-gray-500">
                        No hay lotes recientes registrados.
                    </div>
                </div>

                <div class="panel">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-semibold dark:text-white">Alertas operativas</h2>
                            <p class="text-sm text-gray-500">
                                Documentos que requieren seguimiento del proceso.
                            </p>
                        </div>
                        <FontAwesomeIcon :icon="faTriangleExclamation" class="h-5 w-5 text-rose-500" />
                    </div>

                    <div class="space-y-4">
                        <div class="rounded-md bg-amber-50 p-4 dark:bg-amber-500/10">
                            <p class="text-sm font-semibold text-amber-700 dark:text-amber-300">Boletas pendientes de resumen</p>
                            <p class="mt-3 text-3xl font-bold text-amber-800 dark:text-amber-200">
                                {{ props.tables.alerts?.pendingSummaryCount || 0 }}
                            </p>
                            <p class="mt-2 text-sm text-amber-600 dark:text-amber-300/80">
                                Documentos boleta que siguen en estado pendiente.
                            </p>
                        </div>

                        <div class="rounded-md bg-violet-50 p-4 dark:bg-violet-500/10">
                            <p class="text-sm font-semibold text-violet-700 dark:text-violet-300">Pendientes de baja</p>
                            <p class="mt-3 text-3xl font-bold text-violet-800 dark:text-violet-200">
                                {{ props.tables.alerts?.pendingLowCommunicationCount || 0 }}
                            </p>
                            <p class="mt-2 text-sm text-violet-600 dark:text-violet-300/80">
                                Documentos marcados para anulacion.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="panel">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-semibold dark:text-white">Documentos rechazados</h2>
                            <p class="text-sm text-gray-500">
                                Revisar observaciones registradas por SUNAT.
                            </p>
                        </div>
                        <FontAwesomeIcon :icon="faBan" class="h-5 w-5 text-rose-500" />
                    </div>

                    <div v-if="rejectedDocuments.length" class="space-y-3">
                        <div
                            v-for="document in rejectedDocuments"
                            :key="document.id"
                            class="rounded-md border border-slate-200 bg-slate-50 px-3 py-3 dark:border-slate-700 dark:bg-slate-800/70"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-semibold text-slate-800 dark:text-slate-100">{{ document.type }}</p>
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ document.number }}</p>
                                </div>
                                <span class="rounded-full bg-rose-100 px-2.5 py-1 text-xs font-semibold text-rose-700 dark:bg-rose-500/15 dark:text-rose-300">
                                    Rechazado
                                </span>
                            </div>
                            <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">{{ document.reason }}</p>
                        </div>
                    </div>
                    <div v-else class="flex h-[260px] items-center justify-center text-sm text-gray-500">
                        No hay documentos rechazados en el alcance actual.
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
