<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import apexchart from 'vue3-apexcharts';
import { useAppStore } from '@/stores/index';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import {
    faBook,
    faBookOpen,
    faBuilding,
    faChartLine,
    faClock,
    faIdCard,
    faLayerGroup,
    faTriangleExclamation,
    faUserGroup,
    faUsers,
} from '@fortawesome/free-solid-svg-icons';

const props = defineProps({
    metrics: { type: Object, default: () => ({}) },
    charts: { type: Object, default: () => ({}) },
    tables: { type: Object, default: () => ({}) },
});

const store = useAppStore();
const isDark = computed(() => store.theme === 'dark' || store.isDarkMode);

const shortDate = (date) =>
    date
        ? new Date(`${date}T00:00:00`).toLocaleDateString('es-PE', { day: '2-digit', month: 'short' })
        : '-';

const baseChart = computed(() => ({
    chart: {
        toolbar: { show: false },
        fontFamily: 'Inter, sans-serif',
        foreColor: isDark.value ? '#cbd5e1' : '#475569',
    },
    grid: {
        borderColor: isDark.value ? '#1f2937' : '#e5e7eb',
        strokeDashArray: 4,
    },
    tooltip: {
        theme: isDark.value ? 'dark' : 'light',
    },
    legend: {
        labels: {
            colors: isDark.value ? '#cbd5e1' : '#475569',
        },
    },
}));

const newSubscriptionsOptions = computed(() => ({
    ...baseChart.value,
    chart: { ...baseChart.value.chart, type: 'bar', stacked: false },
    colors: ['#6366f1'],
    plotOptions: {
        bar: {
            borderRadius: 6,
            columnWidth: '45%',
        },
    },
    dataLabels: { enabled: false },
    xaxis: {
        categories: props.charts.newSubscriptions?.labels || [],
        axisBorder: { color: isDark.value ? '#1f2937' : '#e5e7eb' },
        axisTicks: { color: isDark.value ? '#1f2937' : '#e5e7eb' },
    },
    yaxis: {
        labels: {
            formatter: (value) => Number(value || 0).toFixed(0),
        },
    },
}));

const newSubscriptionsSeries = computed(() => [
    { name: 'Nuevas suscripciones', data: props.charts.newSubscriptions?.values || [] },
]);

const statusOptions = computed(() => ({
    ...baseChart.value,
    chart: { ...baseChart.value.chart, type: 'donut' },
    labels: props.charts.subscriptionStatus?.map((item) => item.label) || [],
    colors: ['#f59e0b', '#10b981', '#ef4444', '#38bdf8', '#8b5cf6', '#94a3b8'],
    stroke: { width: 0 },
    dataLabels: { enabled: false },
    plotOptions: {
        pie: {
            donut: {
                size: '72%',
                labels: {
                    show: true,
                    total: {
                        show: true,
                        label: 'Suscripciones',
                        color: isDark.value ? '#e5e7eb' : '#111827',
                    },
                },
            },
        },
    },
}));

const statusSeries = computed(() => props.charts.subscriptionStatus?.map((item) => item.value) || []);

const subscriberTypeOptions = computed(() => ({
    ...baseChart.value,
    chart: { ...baseChart.value.chart, type: 'bar' },
    colors: ['#0ea5e9'],
    plotOptions: {
        bar: {
            borderRadius: 6,
            horizontal: true,
            barHeight: '45%',
        },
    },
    dataLabels: { enabled: false },
    xaxis: {
        categories: props.charts.subscriberTypes?.map((item) => item.label) || [],
        labels: {
            formatter: (value) => Number(value || 0).toFixed(0),
        },
    },
}));

const subscriberTypeSeries = computed(() => [
    {
        name: 'Suscripciones',
        data: props.charts.subscriberTypes?.map((item) => item.value) || [],
    },
]);

const cards = computed(() => [
    {
        label: 'Suscripciones activas',
        value: props.metrics.activeSubscriptions || 0,
        detail: 'Vigentes según fechas y estado',
        icon: faIdCard,
        classes: 'text-white',
        style: {
            background: 'linear-gradient(135deg, #7c3aed 0%, #a21caf 100%)',
            boxShadow: '0 18px 35px rgba(124, 58, 237, 0.28)',
        },
        iconClass: 'bg-white/20 text-white',
        chipClass: 'bg-white/20 text-white',
        chip: `${props.metrics.expiringSubscriptions || 0} por vencer`,
    },
    {
        label: 'Lectores',
        value: props.metrics.readers || 0,
        detail: `${props.metrics.readersInOrganizations || 0} en organizaciones`,
        icon: faUsers,
        classes: 'text-white',
        style: {
            background: 'linear-gradient(135deg, #0891b2 0%, #0369a1 100%)',
            boxShadow: '0 18px 35px rgba(8, 145, 178, 0.28)',
        },
        iconClass: 'bg-white/20 text-white',
        chipClass: 'bg-white/20 text-white',
        chip: 'Usuarios lector',
    },
    {
        label: 'Organizaciones activas',
        value: props.metrics.activeOrganizations || 0,
        detail: `${props.metrics.organizationMembers || 0} miembros en total`,
        icon: faBuilding,
        classes: 'from-emerald-500 to-teal-500 text-white shadow-emerald-200/60 dark:shadow-emerald-950/40',
        iconClass: 'bg-white/20 text-white',
        chipClass: 'bg-white/20 text-white',
        chip: 'Corporativas',
    },
    {
        label: 'Catálogo',
        value: props.metrics.availableBooks || 0,
        detail: `${props.metrics.activePlans || 0} planes activos`,
        icon: faBook,
        classes: 'from-rose-500 to-orange-400 text-white shadow-rose-200/60 dark:shadow-rose-950/40',
        iconClass: 'bg-white/20 text-white',
        chipClass: 'bg-white/20 text-white',
        chip: 'Libros disponibles',
    },
]);

</script>

<template>
    <AppLayout title="Biblio Data">
        <Navigation
            :routeModule="route('bib_dashboard')"
            titleModule="Biblio Data"
            :data="[{ title: 'Dashboard' }]"
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
                                <span
                                    class="inline-flex items-center rounded px-2.5 py-1 text-xs font-semibold"
                                    :class="card.chipClass"
                                >
                                    {{ card.chip }}
                                </span>
                                <span>{{ card.detail }}</span>
                            </div>
                        </div>
                        <div
                            class="relative flex h-11 w-11 shrink-0 items-center justify-center rounded-md shadow-sm"
                            :class="card.iconClass"
                        >
                            <FontAwesomeIcon :icon="card.icon" class="h-5 w-5" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">
                <div class="panel xl:col-span-2">
                    <div class="mb-4 flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-semibold dark:text-white">Altas mensuales</h2>
                            <p class="text-sm text-gray-500">Nuevas suscripciones en los últimos 6 meses.</p>
                        </div>
                        <FontAwesomeIcon :icon="faChartLine" class="h-5 w-5 text-indigo-500" />
                    </div>
                    <apexchart height="320" :options="newSubscriptionsOptions" :series="newSubscriptionsSeries" />
                </div>

                <div class="panel">
                    <div class="mb-4 flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-semibold dark:text-white">Estado de suscripciones</h2>
                            <p class="text-sm text-gray-500">Distribución actual del portafolio.</p>
                        </div>
                        <FontAwesomeIcon :icon="faIdCard" class="h-5 w-5 text-violet-500" />
                    </div>
                    <apexchart
                        v-if="statusSeries.length"
                        height="320"
                        :options="statusOptions"
                        :series="statusSeries"
                    />
                    <div v-else class="flex h-[320px] items-center justify-center text-sm text-gray-500">
                        Sin suscripciones registradas.
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">
                <div class="panel">
                    <div class="mb-4 flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-semibold dark:text-white">Tipo de suscriptor</h2>
                            <p class="text-sm text-gray-500">Individual vs organización.</p>
                        </div>
                        <FontAwesomeIcon :icon="faUserGroup" class="h-5 w-5 text-sky-500" />
                    </div>
                    <apexchart
                        v-if="subscriberTypeSeries[0].data.length"
                        height="260"
                        :options="subscriberTypeOptions"
                        :series="subscriberTypeSeries"
                    />
                    <div v-else class="flex h-[260px] items-center justify-center text-sm text-gray-500">
                        Sin suscripciones registradas.
                    </div>
                </div>

                <div class="panel xl:col-span-2">
                    <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-lg font-semibold dark:text-white">Alertas operativas</h2>
                            <p class="text-sm text-gray-500">
                                Corporativas sin beneficiarios y pendientes antiguas.
                            </p>
                        </div>
                        <div
                            class="rounded-md border border-orange-200 bg-orange-50 px-3 py-2 text-sm font-semibold text-orange-700 dark:border-orange-900/60 dark:bg-orange-950/30 dark:text-orange-300"
                        >
                            {{ metrics.orgWithoutBeneficiaries || 0 }} sin beneficiarios
                            <span v-if="metrics.stalePendingSubscriptions" class="ml-2 opacity-90">
                                · {{ metrics.stalePendingSubscriptions }} pendientes +7 días
                            </span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <Link
                            v-for="sub in tables.orgWithoutBeneficiaries"
                            :key="'org-' + sub.id"
                            v-can="'bib_suscripciones_editar'"
                            :href="route('bib_subscriptions_edit', sub.id)"
                            class="rounded-md border border-gray-200 p-3 transition hover:border-orange-300 hover:bg-orange-50 dark:border-gray-800 dark:hover:border-orange-700 dark:hover:bg-orange-950/20"
                        >
                            <div class="flex items-start gap-3">
                                <div
                                    class="mt-1 flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-orange-100 text-orange-600 dark:bg-orange-900/50 dark:text-orange-300"
                                >
                                    <FontAwesomeIcon :icon="faTriangleExclamation" />
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate font-semibold dark:text-white">{{ sub.subscriber }}</p>
                                    <p class="truncate text-xs text-gray-500">
                                        {{ sub.plan || 'Sin plan' }} · {{ sub.book || 'Sin libro' }}
                                    </p>
                                    <p class="mt-1 text-xs text-orange-600 dark:text-orange-300">
                                        Sin beneficiarios asignados
                                    </p>
                                </div>
                            </div>
                        </Link>
                        <div
                            v-if="!tables.orgWithoutBeneficiaries?.length"
                            class="rounded-md border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 dark:border-gray-700 md:col-span-2"
                        >
                            No hay suscripciones corporativas sin beneficiarios.
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
                <div class="panel">
                    <div class="mb-4 flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-semibold dark:text-white">Suscripciones por vencer</h2>
                            <p class="text-sm text-gray-500">Vencen en los próximos 30 días.</p>
                        </div>
                        <FontAwesomeIcon :icon="faClock" class="h-5 w-5 text-sky-500" />
                    </div>

                    <div class="space-y-3">
                        <Link
                            v-for="sub in tables.expiringSubscriptions"
                            :key="sub.id"
                            v-can="'bib_suscripciones_editar'"
                            :href="route('bib_subscriptions_edit', sub.id)"
                            class="block rounded-md border border-gray-200 p-3 transition hover:border-sky-300 hover:bg-sky-50 dark:border-gray-800 dark:hover:border-sky-700 dark:hover:bg-sky-950/20"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate font-semibold dark:text-white">{{ sub.subscriber }}</p>
                                    <p class="truncate text-xs text-gray-500">
                                        {{ sub.plan || 'Sin plan' }} · {{ sub.book || 'Sin libro' }}
                                    </p>
                                </div>
                                <div class="text-right shrink-0">
                                    <p class="text-xs font-semibold text-sky-600 dark:text-sky-300">
                                        {{ shortDate(sub.ends_at) }}
                                    </p>
                                    <p class="text-xs text-gray-500">{{ sub.days_left }} días</p>
                                </div>
                            </div>
                        </Link>
                        <div
                            v-if="!tables.expiringSubscriptions?.length"
                            class="rounded-md border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 dark:border-gray-700"
                        >
                            No hay suscripciones por vencer en los próximos 30 días.
                        </div>
                    </div>
                </div>

                <div class="panel">
                    <div class="mb-4 flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-semibold dark:text-white">Libros más suscritos</h2>
                            <p class="text-sm text-gray-500">Top 5 por suscripciones activas.</p>
                        </div>
                        <FontAwesomeIcon :icon="faBookOpen" class="h-5 w-5 text-emerald-500" />
                    </div>

                    <div class="space-y-2">
                        <Link
                            v-for="book in tables.topBooksBySubscriptions"
                            :key="book.book_id"
                            v-can="'bib_libros_editar'"
                            :href="route('bib_books_edit', book.book_id)"
                            class="flex items-center justify-between rounded-md bg-gray-50 px-3 py-2 transition hover:bg-emerald-50 dark:bg-gray-900/60 dark:hover:bg-emerald-950/20"
                        >
                            <div class="min-w-0">
                                <p class="truncate font-semibold dark:text-white">{{ book.title }}</p>
                                <p v-if="book.code_name" class="text-xs text-gray-500">{{ book.code_name }}</p>
                            </div>
                            <span class="badge bg-success shrink-0">{{ book.total }} activas</span>
                        </Link>
                        <div
                            v-if="!tables.topBooksBySubscriptions?.length"
                            class="rounded-md border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 dark:border-gray-700"
                        >
                            Sin datos de suscripciones por libro.
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
                <div class="panel">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-lg font-semibold dark:text-white">Suscripciones recientes</h2>
                        <Link
                            v-can="'bib_suscripciones_listar'"
                            :href="route('bib_subscriptions')"
                            class="text-sm font-semibold text-sky-600 hover:underline dark:text-sky-300"
                        >
                            Ver todas
                        </Link>
                    </div>
                    <div class="space-y-2">
                        <Link
                            v-for="sub in tables.recentSubscriptions"
                            :key="'recent-' + sub.id"
                            v-can="'bib_suscripciones_editar'"
                            :href="route('bib_subscriptions_edit', sub.id)"
                            class="flex items-center justify-between rounded-md bg-gray-50 px-3 py-2 transition hover:bg-sky-50 dark:bg-gray-900/60 dark:hover:bg-sky-950/20"
                        >
                            <div class="min-w-0">
                                <p class="truncate font-semibold dark:text-white">{{ sub.subscriber }}</p>
                                <p class="truncate text-xs text-gray-500">
                                    {{ sub.plan || 'Sin plan' }} · {{ sub.book || 'Sin libro' }}
                                </p>
                            </div>
                            <p class="text-xs text-gray-500 shrink-0 ml-2">{{ shortDate(sub.created_at) }}</p>
                        </Link>
                        <div
                            v-if="!tables.recentSubscriptions?.length"
                            class="rounded-md border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 dark:border-gray-700"
                        >
                            Sin suscripciones recientes.
                        </div>
                    </div>
                </div>

                <div class="panel">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-lg font-semibold dark:text-white">Accesos rápidos</h2>
                    </div>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <Link
                            v-can="'bib_suscripciones_listar'"
                            :href="route('bib_subscriptions')"
                            class="flex items-center gap-3 rounded-md border border-gray-200 p-4 transition hover:border-violet-300 hover:bg-violet-50 dark:border-gray-800 dark:hover:border-violet-700 dark:hover:bg-violet-950/20"
                        >
                            <div class="flex h-10 w-10 items-center justify-center rounded-md bg-violet-100 text-violet-600 dark:bg-violet-900/50 dark:text-violet-300">
                                <FontAwesomeIcon :icon="faIdCard" />
                            </div>
                            <div>
                                <p class="font-semibold dark:text-white">Suscripciones</p>
                                <p class="text-xs text-gray-500">Gestionar altas y renovaciones</p>
                            </div>
                        </Link>
                        <Link
                            v-can="'bib_organizaciones_listar'"
                            :href="route('bib_organizations')"
                            class="flex items-center gap-3 rounded-md border border-gray-200 p-4 transition hover:border-teal-300 hover:bg-teal-50 dark:border-gray-800 dark:hover:border-teal-700 dark:hover:bg-teal-950/20"
                        >
                            <div class="flex h-10 w-10 items-center justify-center rounded-md bg-teal-100 text-teal-600 dark:bg-teal-900/50 dark:text-teal-300">
                                <FontAwesomeIcon :icon="faBuilding" />
                            </div>
                            <div>
                                <p class="font-semibold dark:text-white">Organizaciones</p>
                                <p class="text-xs text-gray-500">Empresas y miembros</p>
                            </div>
                        </Link>
                        <Link
                            v-can="'bib_planes_listar'"
                            :href="route('bib_subscription_plans')"
                            class="flex items-center gap-3 rounded-md border border-gray-200 p-4 transition hover:border-sky-300 hover:bg-sky-50 dark:border-gray-800 dark:hover:border-sky-700 dark:hover:bg-sky-950/20"
                        >
                            <div class="flex h-10 w-10 items-center justify-center rounded-md bg-sky-100 text-sky-600 dark:bg-sky-900/50 dark:text-sky-300">
                                <FontAwesomeIcon :icon="faLayerGroup" />
                            </div>
                            <div>
                                <p class="font-semibold dark:text-white">Planes</p>
                                <p class="text-xs text-gray-500">Configuración de planes</p>
                            </div>
                        </Link>
                        <Link
                            v-can="'bib_libros_listado'"
                            :href="route('bib_books')"
                            class="flex items-center gap-3 rounded-md border border-gray-200 p-4 transition hover:border-rose-300 hover:bg-rose-50 dark:border-gray-800 dark:hover:border-rose-700 dark:hover:bg-rose-950/20"
                        >
                            <div class="flex h-10 w-10 items-center justify-center rounded-md bg-rose-100 text-rose-600 dark:bg-rose-900/50 dark:text-rose-300">
                                <FontAwesomeIcon :icon="faBook" />
                            </div>
                            <div>
                                <p class="font-semibold dark:text-white">Libros</p>
                                <p class="text-xs text-gray-500">Catálogo digital</p>
                            </div>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
