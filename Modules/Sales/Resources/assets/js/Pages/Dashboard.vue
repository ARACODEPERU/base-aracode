<script setup>
import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
import Navigation from "@/Components/vristo/layout/Navigation.vue";
import { Link, usePage } from "@inertiajs/vue3";
import { computed } from "vue";
import apexchart from "vue3-apexcharts";
import { useAppStore } from "@/stores/index";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import {
    faBolt,
    faCashRegister,
    faChartLine,
    faFileInvoiceDollar,
    faListOl,
    faMoneyBillTrendUp,
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
const shortDate = (date) => date ? new Date(`${date}T00:00:00`).toLocaleDateString("es-PE", { day: "2-digit", month: "short" }) : "-";

const cards = computed(() => [
    {
        label: "Ventas del dia",
        value: money(props.metrics.todayTotal),
        detail: `${props.metrics.todayCount || 0} notas emitidas hoy`,
        icon: faCashRegister,
        style: {
            background: "linear-gradient(135deg, #0284c7 0%, #0f766e 100%)",
            boxShadow: "0 18px 35px rgba(2, 132, 199, 0.26)",
        },
    },
    {
        label: "Ventas del mes",
        value: money(props.metrics.monthTotal),
        detail: `${props.metrics.monthCount || 0} operaciones registradas`,
        icon: faChartLine,
        style: {
            background: "linear-gradient(135deg, #7c3aed 0%, #2563eb 100%)",
            boxShadow: "0 18px 35px rgba(124, 58, 237, 0.24)",
        },
    },
    {
        label: "Notas de venta",
        value: compactNumber(props.metrics.monthCount),
        detail: "Documentos del mes actual",
        icon: faFileInvoiceDollar,
        style: {
            background: "linear-gradient(135deg, #ea580c 0%, #f59e0b 100%)",
            boxShadow: "0 18px 35px rgba(234, 88, 12, 0.24)",
        },
    },
    {
        label: "Ticket promedio",
        value: money(props.metrics.averageTicket),
        detail: "Promedio por nota de venta",
        icon: faMoneyBillTrendUp,
        style: {
            background: "linear-gradient(135deg, #16a34a 0%, #0891b2 100%)",
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

const salesTrendOptions = computed(() => ({
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
        categories: props.charts.salesTrend?.labels || [],
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

const salesTrendSeries = computed(() => [
    {
        name: "Ventas",
        data: props.charts.salesTrend?.totals || [],
    },
]);

const paymentOptions = computed(() => ({
    ...baseChart.value,
    chart: {
        ...baseChart.value.chart,
        type: "donut",
    },
    labels: (props.charts.paymentMethods?.items || []).map((item) => item.label),
    colors: ["#2563eb", "#14b8a6", "#f59e0b", "#8b5cf6", "#ef4444", "#64748b"],
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
                        label: "Mes",
                        color: isDark.value ? "#e5e7eb" : "#111827",
                        formatter: () => money(props.charts.paymentMethods?.monthTotal || 0),
                    },
                },
            },
        },
    },
    legend: {
        show: false,
    },
    tooltip: {
        ...baseChart.value.tooltip,
        y: {
            formatter: (value) => money(value),
        },
    },
}));

const paymentSeries = computed(() => (props.charts.paymentMethods?.items || []).map((item) => item.value));
const paymentLegend = computed(() => props.charts.paymentMethods?.items || []);
const lowStockItems = computed(() => props.tables.alerts?.lowStockItems || []);

const quickActions = computed(() => ([
    {
        label: "Notas de venta",
        description: "Listado y seguimiento del proceso de ventas.",
        route: route("sales.index"),
        permission: "punto_ventas",
        icon: faCashRegister,
    },
    {
        label: "Punto de venta rapido",
        description: "Cobro agil para mostrador o atencion directa.",
        route: route("sales_quick_sale"),
        permission: "punto_ventas",
        icon: faBolt,
    },
    {
        label: "Documento fisico",
        description: "Control de ventas registradas desde otras plataformas.",
        route: route("sale_physical_document_list"),
        permission: "sale_documento_fisico",
        icon: faFileInvoiceDollar,
    },
    {
        label: "Caja chica",
        description: "Gestion de aperturas, cierres y movimientos del dia.",
        route: route("pettycash.index"),
        permission: "caja_chica",
        icon: faMoneyBillTrendUp,
    },
    {
        label: "Reportes",
        description: "Analisis por fechas, pagos, inventario y ventas.",
        route: route("reports"),
        permission: "sale_reportes",
        icon: faChartLine,
    },
]).filter((item) => hasPermission(item.permission)));
</script>

<template>
    <AppLayout title="Ventas">
        <Navigation
            :routeModule="route('sales_dashboard')"
            titleModule="Ventas"
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
                            <h2 class="text-lg font-semibold dark:text-white">Tendencia de ventas</h2>
                            <p class="text-sm text-gray-500">
                                Ultimos 7 dias del flujo de notas de venta.
                            </p>
                        </div>
                        <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700 dark:bg-blue-500/10 dark:text-blue-300">
                            {{ props.charts.salesTrend?.counts?.reduce((sum, item) => sum + item, 0) || 0 }} operaciones
                        </span>
                    </div>
                    <apexchart
                        v-if="salesTrendSeries[0].data.length"
                        height="320"
                        :options="salesTrendOptions"
                        :series="salesTrendSeries"
                    />
                    <div v-else class="flex h-[320px] items-center justify-center text-sm text-gray-500">
                        No hay ventas registradas en el periodo.
                    </div>
                </div>

                <div class="panel">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-semibold dark:text-white">Metodo de pago</h2>
                            <p class="text-sm text-gray-500">
                                Distribucion del monto vendido durante este mes.
                            </p>
                        </div>
                        <FontAwesomeIcon :icon="faMoneyBillTrendUp" class="h-5 w-5 text-emerald-500" />
                    </div>

                    <apexchart
                        v-if="paymentSeries.length"
                        height="260"
                        :options="paymentOptions"
                        :series="paymentSeries"
                    />
                    <div v-else class="flex h-[260px] items-center justify-center text-sm text-gray-500">
                        Sin metodos de pago registrados.
                    </div>

                    <div class="mt-4 space-y-3">
                        <div
                            v-for="(item, index) in paymentLegend"
                            :key="item.label"
                            class="flex items-center justify-between gap-3 rounded-md bg-slate-50 px-3 py-2 dark:bg-slate-800/70"
                        >
                            <div class="flex min-w-0 items-center gap-2">
                                <span
                                    class="h-2.5 w-2.5 shrink-0 rounded-full"
                                    :class="[
                                        index === 0 ? 'bg-blue-600' : '',
                                        index === 1 ? 'bg-teal-500' : '',
                                        index === 2 ? 'bg-amber-500' : '',
                                        index === 3 ? 'bg-violet-500' : '',
                                        index === 4 ? 'bg-rose-500' : '',
                                        index >= 5 ? 'bg-slate-500' : '',
                                    ]"
                                ></span>
                                <span class="truncate text-sm text-slate-700 dark:text-slate-200">{{ item.label }}</span>
                            </div>
                            <span class="text-sm font-semibold text-slate-900 dark:text-white">{{ money(item.value) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">
                <div class="panel xl:col-span-2">
                    <div class="mb-4 flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-semibold dark:text-white">Ventas recientes</h2>
                            <p class="text-sm text-gray-500">
                                Ultimas operaciones registradas en el proceso de ventas.
                            </p>
                        </div>
                        <Link
                            v-if="hasPermission('punto_ventas')"
                            :href="route('sales.index')"
                            class="text-sm font-semibold text-blue-600 hover:text-blue-700 dark:text-blue-300"
                        >
                            Ver listado
                        </Link>
                    </div>

                    <div v-if="props.tables.recentSales?.length" class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-b border-slate-200 text-left text-slate-500 dark:border-slate-700 dark:text-slate-400">
                                    <th class="pb-3 pr-4 font-medium">Documento</th>
                                    <th class="pb-3 pr-4 font-medium">Cliente</th>
                                    <th class="pb-3 pr-4 font-medium">Local</th>
                                    <th class="pb-3 pr-4 font-medium">Pago</th>
                                    <th class="pb-3 pr-4 font-medium">Fecha</th>
                                    <th class="pb-3 text-right font-medium">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="sale in props.tables.recentSales"
                                    :key="sale.id"
                                    class="border-b border-slate-100 last:border-0 dark:border-slate-800"
                                >
                                    <td class="py-3 pr-4 font-semibold text-slate-800 dark:text-slate-100">{{ sale.document }}</td>
                                    <td class="py-3 pr-4 text-slate-600 dark:text-slate-300">{{ sale.client }}</td>
                                    <td class="py-3 pr-4 text-slate-600 dark:text-slate-300">{{ sale.local }}</td>
                                    <td class="py-3 pr-4 text-slate-500 dark:text-slate-400">{{ sale.paymentSummary }}</td>
                                    <td class="py-3 pr-4 text-slate-500 dark:text-slate-400">{{ shortDate(sale.date) }}</td>
                                    <td class="py-3 text-right font-semibold text-slate-900 dark:text-white">{{ money(sale.total) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="flex h-[240px] items-center justify-center text-sm text-gray-500">
                        No hay ventas recientes para mostrar.
                    </div>
                </div>

                <div class="panel">
                    <div class="mb-4">
                        <h2 class="text-lg font-semibold dark:text-white">Accesos rapidos</h2>
                        <p class="text-sm text-gray-500">
                            Atajos al flujo operativo principal del modulo.
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
                            <h2 class="text-lg font-semibold dark:text-white">Top productos</h2>
                            <p class="text-sm text-gray-500">
                                Productos con mayor salida en el mes actual.
                            </p>
                        </div>
                        <FontAwesomeIcon :icon="faListOl" class="h-5 w-5 text-amber-500" />
                    </div>

                    <div v-if="props.tables.topProducts?.length" class="space-y-3">
                        <div
                            v-for="product in props.tables.topProducts"
                            :key="`${product.id}-${product.name}`"
                            class="rounded-md border border-slate-200 bg-slate-50 px-3 py-3 dark:border-slate-700 dark:bg-slate-800/70"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate font-semibold text-slate-800 dark:text-slate-100">{{ product.name }}</p>
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                        {{ product.sku || "Sin codigo interno" }}
                                    </p>
                                </div>
                                <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">
                                    {{ compactNumber(product.units) }} uds
                                </span>
                            </div>
                            <div class="mt-3 flex items-center justify-between text-sm">
                                <span class="text-slate-500 dark:text-slate-400">Facturacion acumulada</span>
                                <span class="font-semibold text-slate-900 dark:text-white">{{ money(product.revenue) }}</span>
                            </div>
                        </div>
                    </div>
                    <div v-else class="flex h-[260px] items-center justify-center text-sm text-gray-500">
                        No hay productos vendidos en este mes.
                    </div>
                </div>

                <div class="panel">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-semibold dark:text-white">Alertas operativas</h2>
                            <p class="text-sm text-gray-500">
                                Indicadores rapidos para monitorear la operacion.
                            </p>
                        </div>
                        <FontAwesomeIcon :icon="faTriangleExclamation" class="h-5 w-5 text-rose-500" />
                    </div>

                    <div class="space-y-4">
                        <div class="rounded-md bg-rose-50 p-4 dark:bg-rose-500/10">
                            <p class="text-sm font-semibold text-rose-700 dark:text-rose-300">Stock minimo</p>
                            <p class="mt-3 text-3xl font-bold text-rose-800 dark:text-rose-200">
                                {{ props.tables.alerts?.lowStockCount || 0 }}
                            </p>
                            <p class="mt-2 text-sm text-rose-600 dark:text-rose-300/80">
                                Productos que requieren reposicion.
                            </p>
                        </div>

                        <div class="rounded-md bg-emerald-50 p-4 dark:bg-emerald-500/10">
                            <p class="text-sm font-semibold text-emerald-700 dark:text-emerald-300">Cajas abiertas</p>
                            <p class="mt-3 text-3xl font-bold text-emerald-800 dark:text-emerald-200">
                                {{ props.tables.alerts?.openPettyCashCount || 0 }}
                            </p>
                            <p class="mt-2 text-sm text-emerald-600 dark:text-emerald-300/80">
                                Cajas activas dentro del alcance visible del usuario.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="panel">
                    <div class="mb-4">
                        <h2 class="text-lg font-semibold dark:text-white">Detalle de stock minimo</h2>
                        <p class="text-sm text-gray-500">
                            Primeros productos que ya alcanzaron su nivel minimo.
                        </p>
                    </div>

                    <div v-if="lowStockItems.length" class="space-y-3">
                        <div
                            v-for="product in lowStockItems"
                            :key="product.id"
                            class="rounded-md border border-slate-200 bg-slate-50 px-3 py-3 dark:border-slate-700 dark:bg-slate-800/70"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <p class="font-semibold text-slate-800 dark:text-slate-100">{{ product.description }}</p>
                                <span class="rounded-full bg-rose-100 px-2.5 py-1 text-xs font-semibold text-rose-700 dark:bg-rose-500/15 dark:text-rose-300">
                                    {{ compactNumber(product.stock) }}
                                </span>
                            </div>
                            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                                Minimo esperado: {{ compactNumber(product.stockMin) }}
                            </p>
                        </div>
                    </div>
                    <div v-else class="flex h-[260px] items-center justify-center text-sm text-gray-500">
                        No hay productos en nivel minimo.
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
