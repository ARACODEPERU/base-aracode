<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import {
    faBolt,
    faCalendarDay,
    faClipboardList,
    faFutbol,
    faGlobe,
    faMobileScreen,
    faPeopleGroup,
    faTicket,
    faTriangleExclamation,
    faTrophy,
} from '@fortawesome/free-solid-svg-icons';
import Swal from 'sweetalert2';

const props = defineProps({
    event_types: { type: Array, default: () => [] },
    active_module: { type: String, default: 'sports' },
    metrics: { type: Object, default: () => ({}) },
    recent_editions: { type: Array, default: () => [] },
    matches_today_list: { type: Array, default: () => [] },
    upcoming_matches: { type: Array, default: () => [] },
    integration: { type: Object, default: null },
    quick_links: { type: Array, default: () => [] },
});

const page = usePage();
const permissions = computed(() => page.props.auth?.permissions || []);

const hasPermission = (permission) => {
    if (!permission) return true;
    return permissions.value.includes(permission);
};

const statusLabel = (status) => {
    const map = {
        pending: 'Pendiente',
        in_progress: 'En curso',
        finished: 'Finalizado',
    };
    return map[status] || status;
};

const kpiCards = computed(() => [
    {
        label: 'Ediciones activas',
        value: props.metrics.active_editions ?? 0,
        detail: `${props.metrics.published_landings ?? 0} con landing publicada`,
        icon: faTrophy,
        style: {
            background: 'linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%)',
            boxShadow: '0 18px 35px rgba(37, 99, 235, 0.25)',
        },
    },
    {
        label: 'Partidos hoy',
        value: props.metrics.matches_today ?? 0,
        detail: 'En ediciones en curso o pendientes',
        icon: faCalendarDay,
        style: {
            background: 'linear-gradient(135deg, #0891b2 0%, #0e7490 100%)',
            boxShadow: '0 18px 35px rgba(8, 145, 178, 0.24)',
        },
    },
    {
        label: 'Sin resultado cargado',
        value: props.metrics.matches_needing_result ?? 0,
        detail: 'Partidos pendientes de marcador',
        icon: faClipboardList,
        style: {
            background: 'linear-gradient(135deg, #ea580c 0%, #c2410c 100%)',
            boxShadow: '0 18px 35px rgba(234, 88, 12, 0.24)',
        },
    },
    {
        label: 'Protestas pendientes',
        value: props.metrics.pending_protests ?? 0,
        detail: 'Actas con reclamo por resolver',
        icon: faTriangleExclamation,
        style: {
            background: 'linear-gradient(135deg, #dc2626 0%, #b91c1c 100%)',
            boxShadow: '0 18px 35px rgba(220, 38, 38, 0.22)',
        },
    },
]);

const secondaryCards = computed(() => [
    {
        label: 'Eventos registrados',
        value: props.metrics.total_events ?? 0,
        icon: faBolt,
    },
    {
        label: 'Equipos',
        value: props.metrics.total_teams ?? 0,
        icon: faPeopleGroup,
    },
    {
        label: 'Tickets (mes)',
        value: props.metrics.tickets_sold_month ?? 0,
        icon: faTicket,
    },
    {
        label: 'App móvil activa',
        value: props.metrics.mobile_enabled_editions ?? 0,
        icon: faMobileScreen,
    },
]);

const visibleQuickLinks = computed(() =>
    props.quick_links.filter((link) => hasPermission(link.permission))
);

const copyText = async (text, label) => {
    if (!text) return;
    try {
        await navigator.clipboard.writeText(text);
        Swal.fire({
            title: 'Copiado',
            text: label,
            icon: 'success',
            timer: 1600,
            showConfirmButton: false,
        });
    } catch {
        Swal.fire({ title: 'Error', text: 'No se pudo copiar', icon: 'error' });
    }
};

const typeIcon = (icon) => {
    const map = {
        futbol: faFutbol,
        music: faTicket,
        graduation: faClipboardList,
        party: faBolt,
    };
    return map[icon] || faBolt;
};
</script>

<template>
    <AppLayout title="Eventos">
        <Navigation
            :routeModule="route('even_dashboard')"
            titleModule="Eventos"
            :data="[{ title: 'Dashboard' }]"
        />

        <div class="mt-5 space-y-5">
            <div class="panel border border-primary/20 bg-primary/5 p-4 dark:border-primary/30 dark:bg-primary/10">
                <p class="text-sm text-gray-700 dark:text-gray-300">
                    Este módulo está pensado para varios tipos de evento. Hoy el flujo operativo es
                    <strong>deportes y torneos</strong> (ediciones, fixture, landing y API móvil). Los demás tipos se
                    habilitarán cuando tengan su propio proceso.
                </p>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div
                    v-for="type in event_types"
                    :key="type.id"
                    class="panel flex items-start gap-3 p-4 transition"
                    :class="
                        type.enabled
                            ? 'ring-2 ring-primary/40'
                            : 'opacity-60'
                    "
                >
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg"
                        :class="type.enabled ? 'bg-primary/15 text-primary' : 'bg-gray-100 text-gray-400 dark:bg-zinc-800'"
                    >
                        <FontAwesomeIcon :icon="typeIcon(type.icon)" class="h-4 w-4" />
                    </div>
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="font-semibold text-gray-900 dark:text-white">{{ type.label }}</h3>
                            <span
                                v-if="type.enabled"
                                class="rounded-full bg-success/15 px-2 py-0.5 text-xs font-medium text-success"
                            >
                                Activo
                            </span>
                            <span
                                v-else
                                class="rounded-full bg-gray-200 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-zinc-700 dark:text-gray-400"
                            >
                                Próximamente
                            </span>
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ type.description }}</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div
                    v-for="card in kpiCards"
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
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-md bg-white/20 text-white">
                            <FontAwesomeIcon :icon="card.icon" class="h-5 w-5" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div
                    v-for="card in secondaryCards"
                    :key="card.label"
                    class="panel flex items-center gap-3 p-4"
                >
                    <div class="rounded-lg bg-gray-100 p-2.5 dark:bg-zinc-800">
                        <FontAwesomeIcon :icon="card.icon" class="h-4 w-4 text-primary" />
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ card.label }}</p>
                        <p class="text-xl font-bold text-gray-900 dark:text-white">{{ card.value }}</p>
                    </div>
                </div>
            </div>

            <div v-if="visibleQuickLinks.length" class="flex flex-wrap gap-2">
                <Link
                    v-for="link in visibleQuickLinks"
                    :key="link.route"
                    :href="route(link.route)"
                    class="btn btn-outline-primary btn-sm"
                >
                    {{ link.label }}
                </Link>
            </div>

            <div class="grid gap-6 xl:grid-cols-3">
                <div class="panel p-0 xl:col-span-2 overflow-hidden">
                    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Ediciones en curso</h2>
                        <p class="text-sm text-gray-500">Accesos rápidos a fixture, edición y publicación</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500 dark:bg-zinc-900">
                                <tr>
                                    <th class="px-4 py-3">Edición</th>
                                    <th class="px-4 py-3">Estado</th>
                                    <th class="px-4 py-3">Equipos</th>
                                    <th class="px-4 py-3">Web / App</th>
                                    <th class="px-4 py-3 text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="!recent_editions.length">
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                        No hay ediciones activas. Crea una desde Ediciones.
                                    </td>
                                </tr>
                                <tr
                                    v-for="edition in recent_editions"
                                    :key="edition.id"
                                    class="border-t border-gray-100 dark:border-gray-700"
                                >
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-900 dark:text-white">{{ edition.name }}</div>
                                        <div class="text-xs text-gray-500">{{ edition.event_title }} · {{ edition.year }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="rounded-md bg-gray-100 px-2 py-0.5 text-xs dark:bg-zinc-800">
                                            {{ statusLabel(edition.status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">{{ edition.teams_count }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap gap-1">
                                            <span
                                                v-if="edition.landing_published"
                                                class="rounded bg-primary/10 px-1.5 py-0.5 text-xs text-primary"
                                            >
                                                Web
                                            </span>
                                            <span
                                                v-if="edition.mobile_enabled"
                                                class="rounded bg-success/10 px-1.5 py-0.5 text-xs text-success"
                                            >
                                                App
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex flex-wrap justify-end gap-1">
                                            <Link
                                                v-if="hasPermission('even_ediciones_editar')"
                                                :href="edition.edit_url"
                                                class="btn btn-outline-primary btn-xs"
                                            >
                                                Editar
                                            </Link>
                                            <Link
                                                v-if="hasPermission('even_ediciones_fixtures')"
                                                :href="edition.fixtures_url"
                                                class="btn btn-outline-secondary btn-xs"
                                            >
                                                Fixture
                                            </Link>
                                            <button
                                                v-if="edition.landing_url"
                                                type="button"
                                                class="btn btn-outline-secondary btn-xs"
                                                @click="copyText(edition.landing_url, 'Enlace de landing copiado')"
                                            >
                                                Landing
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div v-if="integration" class="panel p-5">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Integración app móvil</h2>
                    <p class="mt-1 text-sm text-gray-500">
                        Edición de referencia: <strong>{{ integration.edition_name }}</strong>
                    </p>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div>
                            <dt class="text-xs font-medium uppercase text-gray-500">API pública (agregador)</dt>
                            <dd class="mt-1 break-all font-mono text-xs text-primary">{{ integration.api_public_url }}</dd>
                            <button
                                type="button"
                                class="btn btn-outline-primary btn-xs mt-2"
                                @click="copyText(integration.api_public_url, 'URL API copiada')"
                            >
                                Copiar API
                            </button>
                        </div>
                        <div v-if="integration.landing_url">
                            <dt class="text-xs font-medium uppercase text-gray-500">Landing web</dt>
                            <dd class="mt-1 break-all text-xs">{{ integration.landing_url }}</dd>
                            <a
                                :href="integration.landing_url"
                                target="_blank"
                                rel="noopener"
                                class="btn btn-outline-secondary btn-xs mt-2 inline-flex items-center gap-1"
                            >
                                <FontAwesomeIcon :icon="faGlobe" class="h-3 w-3" />
                                Abrir
                            </a>
                        </div>
                        <div class="flex gap-2 text-xs">
                            <span
                                :class="integration.mobile_enabled ? 'text-success' : 'text-gray-400'"
                            >
                                App {{ integration.mobile_enabled ? 'habilitada' : 'deshabilitada' }}
                            </span>
                            <span class="text-gray-300">·</span>
                            <span
                                :class="integration.landing_published ? 'text-success' : 'text-gray-400'"
                            >
                                Landing {{ integration.landing_published ? 'publicada' : 'no publicada' }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-500">
                            En la app del tenant configura <code class="text-primary">apiBaseUrl</code> como
                            {{ integration.app_base_hint }}
                        </p>
                    </dl>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="panel p-0 overflow-hidden">
                    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                        <h2 class="text-base font-semibold">Partidos de hoy</h2>
                    </div>
                    <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                        <li v-if="!matches_today_list.length" class="px-5 py-6 text-center text-sm text-gray-500">
                            No hay partidos programados para hoy.
                        </li>
                        <li
                            v-for="match in matches_today_list"
                            :key="match.id"
                            class="flex flex-wrap items-center justify-between gap-2 px-5 py-3 text-sm"
                        >
                            <div>
                                <div class="font-medium">{{ match.team_h }} vs {{ match.team_a }}</div>
                                <div class="text-xs text-gray-500">
                                    {{ match.edition_name }} · {{ match.match_date_label }}
                                </div>
                            </div>
                            <Link
                                v-if="match.fixtures_url && hasPermission('even_ediciones_fixtures')"
                                :href="match.fixtures_url"
                                class="btn btn-outline-primary btn-xs"
                            >
                                Fixture
                            </Link>
                        </li>
                    </ul>
                </div>

                <div class="panel p-0 overflow-hidden">
                    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                        <h2 class="text-base font-semibold">Próximos partidos</h2>
                    </div>
                    <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                        <li v-if="!upcoming_matches.length" class="px-5 py-6 text-center text-sm text-gray-500">
                            No hay partidos próximos en ediciones activas.
                        </li>
                        <li
                            v-for="match in upcoming_matches"
                            :key="'up-' + match.id"
                            class="flex flex-wrap items-center justify-between gap-2 px-5 py-3 text-sm"
                        >
                            <div>
                                <div class="font-medium">{{ match.team_h }} vs {{ match.team_a }}</div>
                                <div class="text-xs text-gray-500">
                                    {{ match.edition_name }} · {{ match.match_date_label }}
                                </div>
                            </div>
                            <Link
                                v-if="match.fixtures_url && hasPermission('even_ediciones_fixtures')"
                                :href="match.fixtures_url"
                                class="btn btn-outline-primary btn-xs"
                            >
                                Ver
                            </Link>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
