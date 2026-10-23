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
    faHouseChimney,
    faMobileScreen,
    faMoneyBillWave,
    faPeopleGroup,
    faTicket,
    faTriangleExclamation,
    faTrophy,
    faUserCheck,
} from '@fortawesome/free-solid-svg-icons';
import Swal from 'sweetalert2';

const props = defineProps({
    modules: { type: Object, default: () => ({ sports: { visible: false }, rentals: { visible: false } }) },
    rental_module: { type: Object, default: () => ({ visible: false, metrics: {}, recent_rentals: [], upcoming_rentals: [] }) },
    event_types: { type: Array, default: () => [] },
    active_module: { type: String, default: 'sports' },
    metrics: { type: Object, default: () => ({}) },
    recent_editions: { type: Array, default: () => [] },
    matches_today_list: { type: Array, default: () => [] },
    upcoming_matches: { type: Array, default: () => [] },
    integration: { type: Object, default: null },
    quick_links: { type: Array, default: () => [] },
    sportsMode: { type: Boolean, default: false },
});

const page = usePage();
const permissions = computed(() => page.props.auth?.permissions || []);

const hasPermission = (permission) => {
    if (!permission) return true;
    return permissions.value.includes(permission);
};

const showSports = computed(() => props.modules?.sports?.visible === true);
const showRentals = computed(() => props.modules?.rentals?.visible === true || props.rental_module?.visible === true);
const rentalMetrics = computed(() => props.rental_module?.metrics || props.modules?.rentals?.metrics || {});
const recentRentals = computed(() => props.rental_module?.recent_rentals || props.modules?.rentals?.recent_rentals || []);
const upcomingRentals = computed(() => props.rental_module?.upcoming_rentals || props.modules?.rentals?.upcoming_rentals || []);
const hasAnyModule = computed(() => showSports.value || showRentals.value);

const introMessage = computed(() => {
    if (showSports.value && showRentals.value) {
        return props.sportsMode
            ? 'Panel de Gestión Deportiva para organizar torneos, equipos, ediciones y partidos.'
            : 'Panel operativo de Eventos sociales: gestiona torneos y ediciones deportivas, y el flujo de alquiler de local con reservas, pagos y notas de venta.';
    }
    if (showRentals.value) {
        return 'Panel de alquiler de local: reservas, adelantos, abonos, saldos pendientes y emisión de notas de venta.';
    }
    if (showSports.value) {
        return props.sportsMode
            ? 'Crea tu primer torneo para comenzar a configurar ediciones, equipos y partidos.'
            : 'Este módulo está pensado para varios tipos de evento. Hoy el flujo operativo es deportes y torneos (ediciones, fixture, landing y API móvil).';
    }
    return 'No tienes acceso a resúmenes operativos en este módulo. Contacta al administrador para asignarte permisos de listado.';
});

const statusLabel = (status) => {
    const map = {
        pending: 'Pendiente',
        in_progress: 'En curso',
        finished: 'Finalizado',
        confirmed: 'Confirmada',
        in_occupation: 'En ocupación',
        cancelled: 'Cancelada',
        completed: 'Completada',
    };
    return map[status] || status;
};

const formatMoney = (value) => `S/ ${Number(value || 0).toFixed(2)}`;

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

const rentalKpiCards = computed(() => [
    {
        label: 'Reservas activas',
        value: rentalMetrics.value.active_reservations ?? 0,
        detail: `${rentalMetrics.value.reservations_month ?? 0} registradas este mes`,
        icon: faHouseChimney,
        style: {
            background: 'linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%)',
            boxShadow: '0 18px 35px rgba(124, 58, 237, 0.25)',
        },
    },
    {
        label: 'Eventos hoy',
        value: rentalMetrics.value.events_today ?? 0,
        detail: 'Reservas con fecha de evento hoy',
        icon: faCalendarDay,
        style: {
            background: 'linear-gradient(135deg, #0891b2 0%, #0e7490 100%)',
            boxShadow: '0 18px 35px rgba(8, 145, 178, 0.24)',
        },
    },
    {
        label: 'En ocupación',
        value: rentalMetrics.value.in_occupation ?? 0,
        detail: 'Locales en uso ahora',
        icon: faUserCheck,
        style: {
            background: 'linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%)',
            boxShadow: '0 18px 35px rgba(37, 99, 235, 0.25)',
        },
    },
    {
        label: 'Saldo pendiente',
        value: formatMoney(rentalMetrics.value.pending_balance ?? 0),
        detail: `${rentalMetrics.value.advance_pending_formalization ?? 0} adelanto(s) sin NV`,
        icon: faMoneyBillWave,
        style: {
            background: 'linear-gradient(135deg, #ea580c 0%, #c2410c 100%)',
            boxShadow: '0 18px 35px rgba(234, 88, 12, 0.24)',
        },
    },
]);

const secondaryCards = computed(() => {
    const cards = [];

    if (hasPermission('even_evento_listado')) {
        cards.push({
            label: 'Eventos registrados',
            value: props.metrics.total_events ?? 0,
            icon: faBolt,
        });
    }

    if (hasPermission('even_equipos_listado')) {
        cards.push({
            label: 'Equipos',
            value: props.metrics.total_teams ?? 0,
            icon: faPeopleGroup,
        });
    }

    if (hasPermission('even_ventas_listado')) {
        cards.push({
            label: 'Tickets (mes)',
            value: props.metrics.tickets_sold_month ?? 0,
            icon: faTicket,
        });
    }

    cards.push({
        label: 'App móvil activa',
        value: props.metrics.mobile_enabled_editions ?? 0,
        icon: faMobileScreen,
    });

    return cards;
});

const visibleQuickLinks = computed(() =>
    props.quick_links.filter((link) => hasPermission(link.permission))
);

const visibleEventTypes = computed(() =>
    props.event_types.filter((type) => !type.permission || hasPermission(type.permission))
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
        house: faHouseChimney,
    };
    return map[icon] || faBolt;
};

/* ------------------------------------------------------------------
   Panel de Gestión Deportiva (cuentas de organización)
   Los datos llegan del mismo SocialeventsDashboardService que usa el
   panel compartido de Eventos; aquí solo cambia la presentación.
   ------------------------------------------------------------------ */

const formatWeekday = (date) => {
    const label = new Intl.DateTimeFormat('es-PE', { weekday: 'short' }).format(date).replace('.', '');
    return label.charAt(0).toUpperCase() + label.slice(1);
};

const formatMonth = (date) =>
    new Intl.DateTimeFormat('es-PE', { month: 'short' }).format(date).replace('.', '').toUpperCase();

const today = new Date();
const todayDay = String(today.getDate()).padStart(2, '0');
const todayMonth = formatMonth(today);
const todayWeekday = formatWeekday(today);

const userName = computed(() => page.props.auth?.user?.name || page.props.auth?.user?.full_name || 'organizador');

const canCreateTournament = computed(() => hasPermission('even_evento_nuevo'));
const canListEditions = computed(() => hasPermission('even_ediciones_listado'));
const canListEvents = computed(() => hasPermission('even_evento_listado'));
const canListTeams = computed(() => hasPermission('even_equipos_listado'));

const matchesTodayCount = computed(() => Number(props.metrics.matches_today ?? 0));
const mobileEditionsCount = computed(() => Number(props.metrics.mobile_enabled_editions ?? 0));

const sportsKpiCards = computed(() => {
    const metrics = props.metrics || {};
    const editions = Number(metrics.active_editions ?? 0);
    const landings = Number(metrics.published_landings ?? 0);
    const withoutResult = Number(metrics.matches_needing_result ?? 0);
    const protests = Number(metrics.pending_protests ?? 0);

    // Con la cuenta recién creada cada tarjeta explica el siguiente paso en
    // lugar de mostrar un detalle que todavía no aplica.
    return [
        {
            label: 'Ediciones activas',
            value: editions,
            detailLead: editions ? landings : undefined,
            detail: editions ? 'con landing publicada' : 'Tu primera edición te espera',
            icon: 'trophy',
            tone: 'lime',
        },
        {
            label: 'Partidos hoy',
            value: matchesTodayCount.value,
            detail: matchesTodayCount.value ? 'En cancha o por jugar' : 'Sin partidos programados',
            icon: 'calendar',
            tone: 'green',
        },
        {
            label: 'Sin resultado',
            value: withoutResult,
            detail: withoutResult ? 'Pendientes de marcador' : 'Todo al día',
            icon: 'clipboard',
            tone: 'amber',
        },
        {
            label: 'Protestas pendientes',
            value: protests,
            detail: protests
                ? protests === 1
                    ? 'Acta con reclamo por resolver'
                    : 'Actas con reclamo por resolver'
                : 'Sin reclamos abiertos',
            icon: 'alert',
            tone: 'coral',
        },
    ];
});

const onboardingSteps = [
    { title: 'Crea el torneo', detail: 'Nombre y formato' },
    { title: 'Arma la edición', detail: 'Año y reglas' },
    { title: 'Suma equipos', detail: 'Planteles y jugadores' },
    { title: 'Genera el fixture', detail: 'Fechas automáticas' },
];

const quickIcons = {
    even_ediciones_nuevo: 'plus',
    even_ediciones_listado: 'layers',
    even_eventos_list: 'ball',
    even_equipos_listado: 'users',
};

const quickIcon = (name) => quickIcons[name] || 'grid';

// Mismo orden de accesos que el prototipo: crear, ediciones, equipos y eventos.
const sportsQuickLinkOrder = [
    'even_ediciones_nuevo',
    'even_ediciones_listado',
    'even_equipos_listado',
    'even_eventos_list',
];

const sportsQuickLinks = computed(() =>
    [...visibleQuickLinks.value].sort((a, b) => {
        const posA = sportsQuickLinkOrder.indexOf(a.route);
        const posB = sportsQuickLinkOrder.indexOf(b.route);
        return (posA === -1 ? 99 : posA) - (posB === -1 ? 99 : posB);
    })
);

const moduleIcons = {
    futbol: 'ball',
    house: 'grid',
    music: 'music',
    graduation: 'graduation',
    party: 'party',
};

const moduleIcon = (icon) => moduleIcons[icon] || 'grid';

const initials = (text) => {
    const words = String(text || '')
        .trim()
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2);

    return words.map((word) => word.charAt(0)).join('').toUpperCase() || 'PG';
};

const editionStatusClass = (status) => {
    if (status === 'in_progress') return 'pgd-status-live';
    if (status === 'pending') return 'pgd-status-pending';
    return 'pgd-status-soft';
};

const editionAction = (edition) => {
    if (hasPermission('even_ediciones_fixtures')) return edition.fixtures_url;
    if (hasPermission('even_ediciones_editar')) return edition.edit_url;
    return null;
};

const matchParts = (match) => {
    const [datePart = '', timePart = ''] = String(match?.match_date || '').split(' ');
    const [year, month, day] = datePart.split('-').map(Number);
    const valid = Boolean(year && month && day);

    return {
        time: timePart.slice(0, 5),
        date: valid ? new Date(year, month - 1, day) : null,
    };
};

const matchTime = (match) => matchParts(match).time || '--:--';

const matchDayLabel = (match) => {
    const { date } = matchParts(match);
    return date ? `${formatWeekday(date)} ${String(date.getDate()).padStart(2, '0')}` : 'Sin fecha';
};

const matchMonthLabel = (match) => {
    const { date } = matchParts(match);
    return date ? formatMonth(date) : '';
};

const matchStatusLabel = (status) => {
    const map = {
        pending: 'Programado',
        live: 'En juego',
        finished: 'Finalizado',
        cancelled: 'Cancelado',
        walk_over: 'W.O.',
        closed: 'Cerrado',
    };
    return map[status] || 'Programado';
};

const matchStateLabel = (match) =>
    [matchStatusLabel(match.status), match.location].filter(Boolean).join(' · ');

const upcomingMeta = (match) => [match.edition_name, match.location].filter(Boolean).join(' · ');

const matchLink = (match) =>
    match.fixtures_url && hasPermission('even_ediciones_fixtures') ? match.fixtures_url : null;
</script>

<template>
    <AppLayout :title="sportsMode ? 'Gestión Deportiva' : 'Eventos'">
        <Navigation
            :routeModule="route('even_dashboard')"
            :titleModule="sportsMode ? 'GESTIÓN DEPORTIVA' : 'Eventos'"
            :data="[{ title: 'Dashboard' }]"
        />

        <!-- Panel de Gestión Deportiva: mismo lenguaje visual del prototipo -->
        <div v-if="sportsMode" class="pgd">
            <svg width="0" height="0" class="pgd-sprite" aria-hidden="true" focusable="false">
                <symbol id="pgd-i-trophy" viewBox="0 0 24 24">
                    <path d="M8 21h8M12 17v4M7 4h10v4a5 5 0 0 1-10 0V4Z" />
                    <path d="M7 5H4v2a3 3 0 0 0 3 3M17 5h3v2a3 3 0 0 1-3 3" />
                </symbol>
                <symbol id="pgd-i-calendar" viewBox="0 0 24 24">
                    <path d="M8 3v4M16 3v4M3 10h18M5 21h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z" />
                    <path d="M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01" />
                </symbol>
                <symbol id="pgd-i-clipboard" viewBox="0 0 24 24">
                    <path d="M9 5H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-3" />
                    <path d="M9 3h6v4H9zM8 12h8M8 16h5" />
                </symbol>
                <symbol id="pgd-i-alert" viewBox="0 0 24 24">
                    <path d="M10.3 3.9 2.6 17.2A2 2 0 0 0 4.3 20h15.4a2 2 0 0 0 1.7-2.8L13.7 3.9a2 2 0 0 0-3.4 0Z" />
                    <path d="M12 9v4M12 17h.01" />
                </symbol>
                <symbol id="pgd-i-plus" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14" /></symbol>
                <symbol id="pgd-i-arrow" viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6" /></symbol>
                <symbol id="pgd-i-chevron" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6" /></symbol>
                <symbol id="pgd-i-layers" viewBox="0 0 24 24">
                    <path d="m12 3 9 5-9 5-9-5 9-5Z" /><path d="m3 12 9 5 9-5M3 16l9 5 9-5" />
                </symbol>
                <symbol id="pgd-i-users" viewBox="0 0 24 24">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" />
                    <path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" />
                </symbol>
                <symbol id="pgd-i-ball" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="9" />
                    <path d="m12 7 4.3 3.1-1.6 5H9.3L7.7 10.1 12 7ZM12 7V3.5M16.3 10.1l3.4-1M14.7 15.1l2.1 3.1M9.3 15.1l-2.1 3.1M7.7 10.1l-3.4-1" />
                </symbol>
                <symbol id="pgd-i-clock" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 2" /></symbol>
                <symbol id="pgd-i-mobile" viewBox="0 0 24 24">
                    <rect x="6" y="2" width="12" height="20" rx="2" /><path d="M11 18h2" />
                </symbol>
                <symbol id="pgd-i-music" viewBox="0 0 24 24">
                    <path d="M9 18V5l10-2v13" /><circle cx="6" cy="18" r="3" /><circle cx="16" cy="16" r="3" />
                </symbol>
                <symbol id="pgd-i-graduation" viewBox="0 0 24 24">
                    <path d="m2 9 10-5 10 5-10 5L2 9Z" /><path d="M6 11.5V16c0 1.7 2.7 3 6 3s6-1.3 6-3v-4.5" />
                </symbol>
                <symbol id="pgd-i-party" viewBox="0 0 24 24">
                    <path d="m4 20 4.5-12 7.5 7.5L4 20ZM14 4l1 2M19 3v2M21 8l-2 1M17 11l2 1" />
                    <path d="M15 9 9 3" />
                </symbol>
                <symbol id="pgd-i-grid" viewBox="0 0 24 24">
                    <rect x="3" y="3" width="7" height="7" rx="1" /><rect x="14" y="3" width="7" height="7" rx="1" />
                    <rect x="3" y="14" width="7" height="7" rx="1" /><rect x="14" y="14" width="7" height="7" rx="1" />
                </symbol>
            </svg>

            <section class="pgd-hero" aria-labelledby="pgd-titulo">
                <div class="pgd-hero-copy">
                    <p class="pgd-kicker">Gestión deportiva</p>
                    <h1 id="pgd-titulo" class="pgd-display">
                        Tu campeonato,<br />
                        <span class="pgd-accent">
                            bajo control.
                            <svg viewBox="0 0 300 18" preserveAspectRatio="none" aria-hidden="true">
                                <path
                                    d="M3 12c60-7 118-9 186-6 39 2 74 5 108 3"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="4"
                                    stroke-linecap="round"
                                />
                            </svg>
                        </span>
                    </h1>
                    <p class="pgd-lead">
                        Hola, <strong>{{ userName }}</strong>. Organiza tus torneos, programa los partidos y comparte
                        resultados sin salir de un solo panel.
                    </p>
                    <div class="pgd-hero-actions">
                        <Link
                            v-if="canCreateTournament"
                            :href="route('even_eventos_create')"
                            class="pgd-btn pgd-btn-primary"
                        >
                            <svg class="pgd-icon pgd-icon-sm" aria-hidden="true"><use href="#pgd-i-plus" /></svg>
                            Crear torneo
                        </Link>
                        <Link
                            v-if="canListEditions"
                            :href="route('even_ediciones_listado')"
                            class="pgd-btn pgd-btn-ghost"
                        >
                            Ver ediciones
                            <svg class="pgd-icon pgd-icon-sm" aria-hidden="true"><use href="#pgd-i-arrow" /></svg>
                        </Link>
                    </div>
                </div>

                <aside class="pgd-hero-side" aria-label="Resumen de la jornada">
                    <div class="pgd-hero-side-top">
                        <div>
                            <p class="pgd-hero-side-label">Hoy en cancha</p>
                            <p class="pgd-hero-side-date">{{ todayDay }} <span>{{ todayMonth }}</span></p>
                        </div>
                        <span class="pgd-status pgd-status-live">
                            {{ matchesTodayCount ? 'Activa' : 'Al día' }}
                        </span>
                    </div>
                    <div class="pgd-hero-side-rule"></div>
                    <p class="pgd-hero-side-copy">
                        <svg class="pgd-icon pgd-icon-sm" aria-hidden="true"><use href="#pgd-i-clock" /></svg>
                        <span>
                            {{
                                matchesTodayCount
                                    ? `${matchesTodayCount} ${matchesTodayCount === 1 ? 'partido programado' : 'partidos programados'}`
                                    : 'Sin partidos todavía'
                            }}
                        </span>
                    </p>
                </aside>
            </section>

            <section class="pgd-kpis" aria-label="Indicadores del campeonato">
                <article
                    v-for="card in sportsKpiCards"
                    :key="card.label"
                    class="pgd-kpi"
                    :class="'pgd-kpi--' + card.tone"
                >
                    <div class="pgd-kpi-top">
                        <p class="pgd-kpi-label">{{ card.label }}</p>
                        <span class="pgd-kpi-icon">
                            <svg class="pgd-icon pgd-icon-sm" aria-hidden="true">
                                <use :href="'#pgd-i-' + card.icon" />
                            </svg>
                        </span>
                    </div>
                    <p class="pgd-kpi-value">{{ card.value }}</p>
                    <p class="pgd-kpi-foot">
                        <span><strong v-if="card.detailLead !== undefined">{{ card.detailLead }}</strong> {{ card.detail }}</span>
                    </p>
                </article>
            </section>

            <div class="pgd-toolbar">
                <span class="pgd-toolbar-label">Accesos rápidos</span>
                <nav class="pgd-quick-actions" aria-label="Accesos rápidos">
                    <Link
                        v-for="(link, index) in sportsQuickLinks"
                        :key="link.route"
                        :href="route(link.route)"
                        class="pgd-quick"
                        :class="{ 'pgd-quick--accent': index === 0 }"
                    >
                        <svg class="pgd-icon" aria-hidden="true">
                            <use :href="'#pgd-i-' + quickIcon(link.route)" />
                        </svg>
                        {{ link.label }}
                    </Link>
                </nav>
                <div class="pgd-mini-stats" aria-label="Resumen de la organización">
                    <template v-if="canListEvents">
                        <span class="pgd-mini-stat"><b>{{ metrics.total_events ?? 0 }}</b> eventos</span>
                        <span class="pgd-mini-stat-sep" aria-hidden="true"></span>
                    </template>
                    <template v-if="canListTeams">
                        <span class="pgd-mini-stat"><b>{{ metrics.total_teams ?? 0 }}</b> equipos</span>
                        <span class="pgd-mini-stat-sep" aria-hidden="true"></span>
                    </template>
                    <span class="pgd-mini-stat"><b>{{ mobileEditionsCount }}</b> app activa</span>
                </div>
            </div>

            <div class="pgd-main-grid">
                <section class="pgd-panel pgd-editions-panel" aria-labelledby="pgd-ediciones">
                    <header class="pgd-panel-head">
                        <div>
                            <p class="pgd-kicker">Competencias</p>
                            <h2 id="pgd-ediciones" class="pgd-panel-title">Ediciones en curso</h2>
                            <p class="pgd-panel-copy">
                                {{
                                    recent_editions.length
                                        ? 'Fixture, tabla y publicación en un solo lugar.'
                                        : 'Aquí aparecerán tus torneos apenas los crees.'
                                }}
                            </p>
                        </div>
                        <Link
                            v-if="recent_editions.length && canListEditions"
                            :href="route('even_ediciones_listado')"
                            class="pgd-link-more"
                        >
                            Ver todas
                            <svg class="pgd-icon pgd-icon-sm" aria-hidden="true"><use href="#pgd-i-arrow" /></svg>
                        </Link>
                    </header>

                    <div v-if="recent_editions.length" class="pgd-editions-table">
                        <div class="pgd-edition-row pgd-edition-row--head" aria-hidden="true">
                            <span>Edición</span>
                            <span>Estado</span>
                            <span>Equipos</span>
                            <span>Canales</span>
                            <span></span>
                        </div>

                        <article
                            v-for="(edition, index) in recent_editions"
                            :key="edition.id"
                            class="pgd-edition-row"
                        >
                            <div class="pgd-edition-name">
                                <span class="pgd-crest" :class="{ 'pgd-crest--blue': index % 2 }">{{ initials(edition.name) }}</span>
                                <div>
                                    <strong>{{ edition.name }}</strong>
                                    <small>{{ edition.year }} · {{ edition.event_title }}</small>
                                </div>
                            </div>
                            <span
                                class="pgd-status pgd-edition-center"
                                :class="editionStatusClass(edition.status)"
                            >
                                {{ statusLabel(edition.status) }}
                            </span>
                            <span class="pgd-edition-teams">{{ edition.teams_count }}</span>
                            <span class="pgd-channel-chips">
                                <span v-if="edition.landing_published" class="pgd-chip pgd-chip--lime">Web</span>
                                <span v-if="edition.mobile_enabled" class="pgd-chip">App</span>
                                <span v-if="!edition.landing_published && !edition.mobile_enabled" class="pgd-chip">—</span>
                            </span>
                            <Link v-if="editionAction(edition)" :href="editionAction(edition)" class="pgd-row-action">
                                Abrir
                                <svg class="pgd-icon pgd-icon-sm" aria-hidden="true"><use href="#pgd-i-chevron" /></svg>
                            </Link>
                            <span v-else class="pgd-row-action pgd-row-action--muted">—</span>
                        </article>
                    </div>

                    <div v-else class="pgd-onboarding">
                        <div class="pgd-onboarding-top">
                            <span class="pgd-onboarding-icon" aria-hidden="true">
                                <svg class="pgd-icon"><use href="#pgd-i-ball" /></svg>
                            </span>
                            <div>
                                <p class="pgd-kicker">Empieza aquí</p>
                                <h3>Tu primer torneo, en cuatro pasos</h3>
                                <p>No necesitas configurar nada más. Crea el torneo y el panel irá mostrando cada avance.</p>
                            </div>
                        </div>

                        <ol class="pgd-steps">
                            <li v-for="(step, index) in onboardingSteps" :key="step.title" class="pgd-step">
                                <span class="pgd-step-num">{{ String(index + 1).padStart(2, '0') }}</span>
                                <strong>{{ step.title }}</strong>
                                <small>{{ step.detail }}</small>
                            </li>
                        </ol>

                        <div class="pgd-onboarding-actions">
                            <Link
                                v-if="canCreateTournament"
                                :href="route('even_eventos_create')"
                                class="pgd-btn pgd-btn-primary"
                            >
                                <svg class="pgd-icon pgd-icon-sm" aria-hidden="true"><use href="#pgd-i-plus" /></svg>
                                Crear mi primer torneo
                            </Link>
                            <span class="pgd-onboarding-note">Puedes editarlo cuando quieras.</span>
                        </div>
                    </div>
                </section>

                <section class="pgd-panel pgd-today-panel" aria-labelledby="pgd-hoy">
                    <header class="pgd-panel-head">
                        <div>
                            <p class="pgd-kicker">Jornada</p>
                            <h2 id="pgd-hoy" class="pgd-panel-title">Hoy en cancha</h2>
                        </div>
                        <span class="pgd-today-date">{{ todayWeekday }} {{ todayDay }} · {{ todayMonth }}</span>
                    </header>

                    <div v-if="matches_today_list.length" class="pgd-match-list">
                        <article v-for="match in matches_today_list" :key="match.id" class="pgd-match">
                            <span class="pgd-match-time">{{ matchTime(match) }}</span>
                            <div>
                                <div class="pgd-match-meta">
                                    <span>{{ match.edition_name }}</span>
                                    <span v-if="match.location">· {{ match.location }}</span>
                                </div>
                                <div class="pgd-match-teams">
                                    <span class="pgd-match-team">
                                        <span class="pgd-mini-crest">{{ initials(match.team_h) }}</span>
                                        {{ match.team_h }}
                                    </span>
                                    <span class="pgd-match-vs">VS</span>
                                    <span class="pgd-match-team pgd-is-right">
                                        {{ match.team_a }}
                                        <span class="pgd-mini-crest pgd-is-lime">{{ initials(match.team_a) }}</span>
                                    </span>
                                </div>
                                <span class="pgd-match-state">{{ matchStateLabel(match) }}</span>
                            </div>
                        </article>
                    </div>

                    <div v-else class="pgd-empty-match">
                        <span class="pgd-empty-icon" aria-hidden="true">
                            <svg class="pgd-icon"><use href="#pgd-i-ball" /></svg>
                        </span>
                        <strong>No hay partidos para hoy</strong>
                        <p>Cuando tengas tu fixture, los partidos del día aparecerán aquí.</p>
                    </div>

                    <footer class="pgd-panel-footer">
                        <span class="pgd-status pgd-status-soft">
                            {{ matchesTodayCount }} programado{{ matchesTodayCount === 1 ? '' : 's' }}
                        </span>
                        <Link
                            v-if="matches_today_list.length && canListEditions"
                            :href="route('even_ediciones_listado')"
                            class="pgd-btn pgd-btn-quiet pgd-btn-small"
                        >
                            Ver ediciones
                            <svg class="pgd-icon pgd-icon-sm" aria-hidden="true"><use href="#pgd-i-arrow" /></svg>
                        </Link>
                        <Link
                            v-else-if="canCreateTournament"
                            :href="route('even_eventos_create')"
                            class="pgd-btn pgd-btn-quiet pgd-btn-small"
                        >
                            Crear torneo
                            <svg class="pgd-icon pgd-icon-sm" aria-hidden="true"><use href="#pgd-i-arrow" /></svg>
                        </Link>
                    </footer>
                </section>
            </div>

            <div class="pgd-secondary-grid">
                <section class="pgd-panel" aria-labelledby="pgd-proximos">
                    <header class="pgd-panel-head">
                        <div>
                            <p class="pgd-kicker">Agenda</p>
                            <h2 id="pgd-proximos" class="pgd-panel-title">Próximos partidos</h2>
                            <p class="pgd-panel-copy">
                                {{
                                    upcoming_matches.length
                                        ? 'Las siguientes fechas de tus torneos.'
                                        : 'Tu calendario se arma solo con el fixture.'
                                }}
                            </p>
                        </div>
                        <Link
                            v-if="canListEditions"
                            :href="route('even_ediciones_listado')"
                            class="pgd-link-more"
                        >
                            Ver ediciones
                            <svg class="pgd-icon pgd-icon-sm" aria-hidden="true"><use href="#pgd-i-arrow" /></svg>
                        </Link>
                    </header>

                    <div v-if="upcoming_matches.length" class="pgd-upcoming-list">
                        <component
                            :is="matchLink(match) ? Link : 'div'"
                            v-for="match in upcoming_matches"
                            :key="match.id"
                            :href="matchLink(match) || undefined"
                            class="pgd-upcoming-row"
                        >
                            <div class="pgd-upcoming-date">
                                <strong>{{ matchDayLabel(match) }}</strong>
                                <small>{{ matchMonthLabel(match) }}</small>
                            </div>
                            <div class="pgd-upcoming-main">
                                <strong>{{ match.team_h }} <span class="pgd-vs">vs</span> {{ match.team_a }}</strong>
                                <small>{{ upcomingMeta(match) }}</small>
                            </div>
                            <span class="pgd-upcoming-time">{{ matchTime(match) }}</span>
                            <span class="pgd-upcoming-arrow">
                                <svg class="pgd-icon pgd-icon-sm" aria-hidden="true"><use href="#pgd-i-chevron" /></svg>
                            </span>
                        </component>
                    </div>

                    <div v-else class="pgd-empty-match pgd-empty-match--short">
                        <span class="pgd-empty-icon" aria-hidden="true">
                            <svg class="pgd-icon"><use href="#pgd-i-calendar" /></svg>
                        </span>
                        <strong>Aún no hay partidos programados</strong>
                        <p>En cuanto generes el fixture, verás aquí las próximas fechas.</p>
                        <Link
                            v-if="canCreateTournament"
                            :href="route('even_eventos_create')"
                            class="pgd-btn pgd-btn-quiet pgd-btn-small pgd-empty-action"
                        >
                            Crear torneo
                            <svg class="pgd-icon pgd-icon-sm" aria-hidden="true"><use href="#pgd-i-arrow" /></svg>
                        </Link>
                    </div>
                </section>

                <section class="pgd-panel pgd-modules-panel" aria-labelledby="pgd-modulos">
                    <header class="pgd-panel-head">
                        <div>
                            <p class="pgd-kicker">Ecosistema</p>
                            <h2 id="pgd-modulos" class="pgd-panel-title">Módulos</h2>
                        </div>
                        <span class="pgd-status pgd-status-soft">{{ visibleEventTypes.length }} disponibles</span>
                    </header>

                    <div class="pgd-modules-list">
                        <div
                            v-for="type in visibleEventTypes"
                            :key="type.id"
                            class="pgd-module"
                            :class="{ 'pgd-module--active': type.enabled }"
                        >
                            <span class="pgd-module-icon">
                                <svg class="pgd-icon" aria-hidden="true">
                                    <use :href="'#pgd-i-' + moduleIcon(type.icon)" />
                                </svg>
                            </span>
                            <div class="pgd-module-copy">
                                <strong>{{ type.label }}</strong>
                                <small>{{ type.enabled ? 'Activo' : 'Próximamente' }}</small>
                            </div>
                        </div>
                    </div>

                    <div class="pgd-module-foot">
                        <span>
                            <svg class="pgd-icon" aria-hidden="true"><use href="#pgd-i-mobile" /></svg>
                            App móvil activa
                        </span>
                        <b>
                            {{
                                mobileEditionsCount
                                    ? `${mobileEditionsCount} ${mobileEditionsCount === 1 ? 'edición' : 'ediciones'}`
                                    : '0 activas'
                            }}
                        </b>
                    </div>
                </section>
            </div>
        </div>

        <div v-else class="mt-5 space-y-5">
            <div
                class="panel border p-4"
                :class="hasAnyModule ? 'border-primary/20 bg-primary/5 dark:border-primary/30 dark:bg-primary/10' : 'border-warning/30 bg-warning/10'"
            >
                <p class="text-sm text-gray-700 dark:text-gray-300">
                    {{ introMessage }}
                </p>
            </div>

            <div v-if="visibleEventTypes.length" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div
                    v-for="type in visibleEventTypes"
                    :key="type.id"
                    class="panel flex items-start gap-3 p-4 transition"
                    :class="type.enabled ? 'ring-2 ring-primary/40' : 'opacity-60'"
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

            <div v-if="sportsMode && !recent_editions.length && hasPermission('even_evento_nuevo')" class="panel border border-primary/20 p-6 text-center">
                <FontAwesomeIcon :icon="faFutbol" class="mb-3 h-8 w-8 text-primary" />
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Comienza con tu primer torneo</h2>
                <p class="mt-1 text-sm text-gray-500">Tu espacio está listo. Registra un torneo y luego crea su edición, equipos y fixture.</p>
                <Link :href="route('even_eventos_create')" class="btn btn-primary mt-4">Crear torneo</Link>
            </div>

            <!-- Bloque deportes -->
            <template v-if="showSports">
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

                <div v-if="secondaryCards.length" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
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
                                            {{ sportsMode ? 'Aún no tienes torneos activos. Crea una edición para iniciar la competición.' : 'No hay ediciones activas. Crea una desde Ediciones.' }}
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
                                <span :class="integration.mobile_enabled ? 'text-success' : 'text-gray-400'">
                                    App {{ integration.mobile_enabled ? 'habilitada' : 'deshabilitada' }}
                                </span>
                                <span class="text-gray-300">·</span>
                                <span :class="integration.landing_published ? 'text-success' : 'text-gray-400'">
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
            </template>

            <!-- Bloque alquiler de local -->
            <template v-if="showRentals">
                <div class="border-t border-gray-200 pt-2 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Alquiler de local</h2>
                    <p class="text-sm text-gray-500">Reservas, eventos del día y saldos pendientes</p>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div
                        v-for="card in rentalKpiCards"
                        :key="card.label"
                        class="relative overflow-hidden rounded-md p-5 text-white shadow-lg"
                        :style="card.style"
                    >
                        <div class="absolute -right-10 -top-10 h-28 w-28 rounded-full bg-white/10"></div>
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

                <div class="grid gap-6 lg:grid-cols-2">
                    <div class="panel p-0 overflow-hidden">
                        <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                            <h2 class="text-base font-semibold">Próximas reservas</h2>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500 dark:bg-zinc-900">
                                    <tr>
                                        <th class="px-4 py-3">Fecha</th>
                                        <th class="px-4 py-3">Local / Cliente</th>
                                        <th class="px-4 py-3">Saldo</th>
                                        <th class="px-4 py-3">Estado</th>
                                        <th class="px-4 py-3 text-right">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="!upcomingRentals.length">
                                        <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                            No hay reservas próximas.
                                        </td>
                                    </tr>
                                    <tr
                                        v-for="rental in upcomingRentals"
                                        :key="'up-' + rental.id"
                                        class="border-t border-gray-100 dark:border-gray-700"
                                    >
                                        <td class="px-4 py-3">
                                            <div class="font-medium">{{ rental.event_date_label }}</div>
                                            <div class="text-xs text-gray-500">{{ rental.schedule_label }}</div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="font-medium">{{ rental.local_name }}</div>
                                            <div class="text-xs text-gray-500">{{ rental.customer_name }}</div>
                                        </td>
                                        <td class="px-4 py-3">{{ formatMoney(rental.balance_amount) }}</td>
                                        <td class="px-4 py-3">
                                            <span class="rounded-md bg-gray-100 px-2 py-0.5 text-xs dark:bg-zinc-800">
                                                {{ statusLabel(rental.reservation_status) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <Link :href="rental.show_url" class="btn btn-outline-primary btn-xs">
                                                Ver
                                            </Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="panel p-0 overflow-hidden">
                        <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                            <h2 class="text-base font-semibold">Últimas reservas registradas</h2>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500 dark:bg-zinc-900">
                                    <tr>
                                        <th class="px-4 py-3">Local / Cliente</th>
                                        <th class="px-4 py-3">Total</th>
                                        <th class="px-4 py-3">Pagado</th>
                                        <th class="px-4 py-3">Estado</th>
                                        <th class="px-4 py-3 text-right">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="!recentRentals.length">
                                        <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                            No hay reservas registradas.
                                        </td>
                                    </tr>
                                    <tr
                                        v-for="rental in recentRentals"
                                        :key="rental.id"
                                        class="border-t border-gray-100 dark:border-gray-700"
                                    >
                                        <td class="px-4 py-3">
                                            <div class="font-medium">{{ rental.local_name }}</div>
                                            <div class="text-xs text-gray-500">{{ rental.customer_name }}</div>
                                        </td>
                                        <td class="px-4 py-3">{{ formatMoney(rental.total_price) }}</td>
                                        <td class="px-4 py-3">{{ formatMoney(rental.paid_amount) }}</td>
                                        <td class="px-4 py-3">
                                            <span class="rounded-md bg-gray-100 px-2 py-0.5 text-xs dark:bg-zinc-800">
                                                {{ statusLabel(rental.reservation_status) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <Link :href="rental.show_url" class="btn btn-outline-primary btn-xs">
                                                Ver
                                            </Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Panel de Gestión Deportiva.
   Estilos propios para no alterar el panel compartido de Eventos ni el tema
   global: la paleta, las superficies y la tipografía viven bajo .pgd. */
.pgd {
    --pgd-navy: #101d2d;
    --pgd-lime: #b8f34a;
    --pgd-white: #ffffff;
    --pgd-body: #d6e2e9;
    --pgd-muted: #94a9b9;
    --pgd-faint: #6d8397;
    --pgd-line: rgba(148, 178, 191, 0.18);
    --pgd-panel: rgba(8, 27, 41, 0.9);
    --pgd-amber: #f2c14e;
    --pgd-coral: #ff8f78;
    display: flex;
    flex-direction: column;
    gap: 14px;
    margin-top: 20px;
    color: var(--pgd-body);
    font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    font-size: 15px;
    -webkit-font-smoothing: antialiased;
}

.pgd *,
.pgd *::before,
.pgd *::after {
    box-sizing: border-box;
}

/* Solo los enlaces sin estilo propio heredan el color; los botones y chips
   conservan su tonalidad (texto oscuro sobre lima, por ejemplo). */
.pgd a:not([class]) {
    color: inherit;
    text-decoration: none;
}

.pgd :focus-visible {
    outline: 2px solid var(--pgd-lime);
    outline-offset: 3px;
    border-radius: 4px;
}

.pgd-sprite {
    position: absolute;
    width: 0;
    height: 0;
}

.pgd .pgd-icon {
    width: 18px;
    height: 18px;
    flex: none;
    fill: none;
    stroke: currentColor;
    stroke-linecap: round;
    stroke-linejoin: round;
    stroke-width: 1.8;
}

.pgd .pgd-icon-sm {
    width: 14px;
    height: 14px;
    stroke-width: 2;
}

/* --- Piezas base --- */
.pgd-kicker {
    display: inline-flex;
    align-items: center;
    gap: 9px;
    margin: 0;
    color: var(--pgd-lime);
    font-family: 'Montserrat', 'Inter', system-ui, sans-serif;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.17em;
    line-height: 1.4;
    text-transform: uppercase;
}

.pgd-kicker::before {
    content: '';
    width: 7px;
    height: 7px;
    flex: none;
    border-radius: 50%;
    background: var(--pgd-lime);
    box-shadow: 0 0 0 4px rgba(184, 243, 74, 0.12);
}

.pgd-display {
    margin: 17px 0 0;
    color: var(--pgd-white);
    font-family: 'Montserrat', 'Inter', system-ui, sans-serif;
    font-size: clamp(33px, 4.2vw, 58px);
    font-style: italic;
    font-weight: 900;
    letter-spacing: -0.035em;
    line-height: 0.94;
    text-transform: uppercase;
}

.pgd-accent {
    position: relative;
    display: inline-block;
    color: var(--pgd-lime);
}

.pgd-accent svg {
    position: absolute;
    left: -1%;
    bottom: -0.1em;
    width: 102%;
    height: 0.14em;
    color: var(--pgd-lime);
    overflow: visible;
    pointer-events: none;
}

.pgd-panel {
    position: relative;
    overflow: hidden;
    border: 1px solid var(--pgd-line);
    border-radius: 18px;
    background:
        linear-gradient(180deg, rgba(20, 49, 62, 0.42), transparent 46%),
        var(--pgd-panel);
    box-shadow: 0 24px 60px -46px rgba(0, 0, 0, 0.95);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
}

.pgd-panel::after {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: inherit;
    pointer-events: none;
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.045);
}

.pgd-panel-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 24px;
    padding: 22px 24px 18px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.075);
}

.pgd-panel-title {
    margin: 4px 0 0;
    color: var(--pgd-white);
    font-family: 'Montserrat', 'Inter', system-ui, sans-serif;
    font-size: 18px;
    font-weight: 800;
    letter-spacing: -0.02em;
    line-height: 1.25;
}

.pgd-panel-copy {
    margin: 5px 0 0;
    color: var(--pgd-muted);
    font-size: 14px;
    line-height: 1.5;
}

.pgd-link-more {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    flex: none;
    margin-top: 3px;
    color: var(--pgd-lime);
    font-family: 'Montserrat', 'Inter', system-ui, sans-serif;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    transition: gap 0.2s ease, color 0.2s ease;
}

.pgd-link-more:hover {
    gap: 11px;
    color: var(--pgd-white);
}

.pgd-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    min-height: 44px;
    padding: 0 18px;
    border: 1px solid transparent;
    border-radius: 10px;
    font-family: 'Montserrat', 'Inter', system-ui, sans-serif;
    font-size: 12px;
    font-weight: 800;
    letter-spacing: 0.07em;
    line-height: 1.2;
    text-transform: uppercase;
    cursor: pointer;
    transition: transform 0.18s ease, background-color 0.18s ease, border-color 0.18s ease, color 0.18s ease;
}

.pgd-btn:hover {
    transform: translateY(-2px);
}

.pgd-btn-primary {
    background: var(--pgd-lime);
    color: var(--pgd-navy);
    box-shadow: 0 10px 26px -14px rgba(184, 243, 74, 0.55);
}

.pgd-btn-primary:hover {
    background: #c7fa65;
}

.pgd-btn-ghost {
    border-color: rgba(255, 255, 255, 0.25);
    background: rgba(255, 255, 255, 0.025);
    color: var(--pgd-white);
}

.pgd-btn-ghost:hover {
    border-color: var(--pgd-lime);
    color: var(--pgd-lime);
}

.pgd-btn-small {
    min-height: 36px;
    padding: 0 13px;
    border-radius: 8px;
    font-size: 10.5px;
}

.pgd-btn-quiet {
    border-color: rgba(184, 243, 74, 0.26);
    background: rgba(184, 243, 74, 0.07);
    color: var(--pgd-lime);
}

.pgd-btn-quiet:hover {
    background: rgba(184, 243, 74, 0.12);
    border-color: rgba(184, 243, 74, 0.5);
}

.pgd-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    width: max-content;
    padding: 5px 9px;
    border: 1px solid transparent;
    border-radius: 999px;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.06em;
    line-height: 1;
    text-transform: uppercase;
    white-space: nowrap;
}

.pgd-status::before {
    content: '';
    width: 5px;
    height: 5px;
    flex: none;
    border-radius: 50%;
    background: currentColor;
}

.pgd-status-live {
    border-color: rgba(184, 243, 74, 0.25);
    background: rgba(184, 243, 74, 0.11);
    color: var(--pgd-lime);
}

.pgd-status-soft {
    border-color: rgba(255, 255, 255, 0.12);
    background: rgba(255, 255, 255, 0.05);
    color: #c0ced8;
}

.pgd-status-pending {
    border-color: rgba(242, 193, 78, 0.25);
    background: rgba(242, 193, 78, 0.1);
    color: var(--pgd-amber);
}

/* --- Hero --- */
.pgd-hero {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    align-items: end;
    gap: 42px;
    padding: 8px 2px 10px;
}

.pgd-hero-copy {
    max-width: 760px;
}

.pgd-lead {
    max-width: 590px;
    margin: 19px 0 0;
    color: #c7d6df;
    font-size: 15px;
    line-height: 1.65;
}

.pgd-lead strong {
    color: var(--pgd-white);
    font-weight: 600;
}

.pgd-hero-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 23px;
}

.pgd-hero-side {
    width: 208px;
    padding: 18px 19px;
    border: 1px solid rgba(184, 243, 74, 0.19);
    border-radius: 16px;
    background: rgba(6, 25, 34, 0.58);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
}

.pgd-hero-side-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 18px;
}

.pgd-hero-side-label {
    margin: 0;
    color: var(--pgd-muted);
    font-family: 'Montserrat', 'Inter', system-ui, sans-serif;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.13em;
    text-transform: uppercase;
}

.pgd-hero-side-date {
    margin: 4px 0 0;
    color: var(--pgd-white);
    font-family: 'Montserrat', 'Inter', system-ui, sans-serif;
    font-size: 28px;
    font-weight: 800;
    letter-spacing: -0.03em;
    line-height: 1;
}

.pgd-hero-side-date span {
    color: var(--pgd-lime);
    font-size: 13px;
    font-style: italic;
    letter-spacing: 0.02em;
}

.pgd-hero-side-rule {
    height: 1px;
    margin: 15px 0;
    background: rgba(255, 255, 255, 0.09);
}

.pgd-hero-side-copy {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0;
    color: #c4d2dc;
    font-size: 12px;
    line-height: 1.45;
}

.pgd-hero-side-copy .pgd-icon {
    color: var(--pgd-lime);
}

/* --- Indicadores --- */
.pgd-kpis {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
}

.pgd-kpi {
    position: relative;
    min-height: 150px;
    padding: 19px 19px 17px;
    overflow: hidden;
    border: 1px solid rgba(159, 186, 194, 0.18);
    border-radius: 15px;
    background:
        linear-gradient(150deg, rgba(255, 255, 255, 0.055), transparent 44%),
        rgba(8, 28, 41, 0.88);
    box-shadow: 0 18px 42px -38px rgba(0, 0, 0, 0.9);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
}

.pgd-kpi::before {
    content: '';
    position: absolute;
    top: 0;
    left: 19px;
    width: 39px;
    height: 2px;
    border-radius: 0 0 3px 3px;
    background: var(--accent);
    box-shadow: 0 0 18px color-mix(in srgb, var(--accent) 55%, transparent);
}

.pgd-kpi::after {
    content: '';
    position: absolute;
    right: -28px;
    bottom: -42px;
    width: 120px;
    height: 120px;
    border-radius: 50%;
    border: 1px solid color-mix(in srgb, var(--accent) 24%, transparent);
    background: radial-gradient(circle, color-mix(in srgb, var(--accent) 10%, transparent), transparent 67%);
    pointer-events: none;
}

.pgd-kpi-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.pgd-kpi-label {
    margin: 0;
    color: #b4c5d0;
    font-family: 'Montserrat', 'Inter', system-ui, sans-serif;
    font-size: 11.5px;
    font-weight: 800;
    letter-spacing: 0.105em;
    line-height: 1.35;
    text-transform: uppercase;
}

.pgd-kpi-icon {
    display: grid;
    place-items: center;
    width: 33px;
    height: 33px;
    flex: none;
    border: 1px solid color-mix(in srgb, var(--accent) 25%, transparent);
    border-radius: 9px;
    background: color-mix(in srgb, var(--accent) 10%, transparent);
    color: var(--accent);
}

.pgd-kpi-value {
    margin: 17px 0 0;
    color: var(--pgd-white);
    font-family: 'Montserrat', 'Inter', system-ui, sans-serif;
    font-size: 38px;
    font-weight: 800;
    letter-spacing: -0.045em;
    line-height: 1;
}

.pgd-kpi-foot {
    display: flex;
    align-items: center;
    gap: 6px;
    margin: 7px 0 0;
    color: var(--pgd-faint);
    font-size: 12.5px;
    line-height: 1.35;
}

.pgd-kpi-foot strong {
    color: color-mix(in srgb, var(--accent) 82%, white);
    font-weight: 600;
}

.pgd-kpi--lime {
    --accent: var(--pgd-lime);
}

.pgd-kpi--green {
    --accent: #62d99b;
}

.pgd-kpi--amber {
    --accent: var(--pgd-amber);
}

.pgd-kpi--coral {
    --accent: var(--pgd-coral);
}

/* --- Accesos rápidos --- */
.pgd-toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    padding: 12px 14px;
    border: 1px solid rgba(148, 178, 191, 0.15);
    border-radius: 13px;
    background: rgba(4, 21, 31, 0.48);
}

.pgd-toolbar-label {
    flex: none;
    padding-left: 4px;
    color: var(--pgd-faint);
    font-family: 'Montserrat', 'Inter', system-ui, sans-serif;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.13em;
    text-transform: uppercase;
}

.pgd-quick-actions {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 6px;
}

.pgd-quick {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 8px 11px;
    border: 1px solid transparent;
    border-radius: 8px;
    color: #c4d2dc;
    font-family: 'Montserrat', 'Inter', system-ui, sans-serif;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.025em;
    transition: border-color 0.18s ease, background-color 0.18s ease, color 0.18s ease;
}

.pgd-quick .pgd-icon {
    width: 14px;
    height: 14px;
    color: #7f9bac;
    transition: color 0.18s ease;
}

.pgd-quick:hover {
    border-color: rgba(184, 243, 74, 0.26);
    background: rgba(184, 243, 74, 0.06);
    color: var(--pgd-white);
}

.pgd-quick:hover .pgd-icon {
    color: var(--pgd-lime);
}

.pgd-quick--accent {
    border-color: rgba(184, 243, 74, 0.2);
    color: var(--pgd-lime);
}

.pgd-quick--accent .pgd-icon {
    color: var(--pgd-lime);
}

.pgd-mini-stats {
    display: flex;
    align-items: center;
    gap: 16px;
    flex: none;
}

.pgd-mini-stat {
    display: inline-flex;
    align-items: baseline;
    gap: 5px;
    color: var(--pgd-faint);
    font-size: 11px;
    white-space: nowrap;
}

.pgd-mini-stat b {
    color: #dfe9ef;
    font-family: 'Montserrat', 'Inter', system-ui, sans-serif;
    font-size: 13px;
    font-weight: 800;
}

.pgd-mini-stat-sep {
    width: 1px;
    height: 15px;
    background: rgba(255, 255, 255, 0.1);
}

/* --- Estructura principal --- */
.pgd-main-grid,
.pgd-secondary-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.55fr) minmax(320px, 0.82fr);
    gap: 14px;
}

/* --- Ediciones --- */
.pgd-editions-panel {
    min-height: 364px;
}

.pgd-editions-table {
    padding: 5px 24px 10px;
}

.pgd-edition-row {
    display: grid;
    grid-template-columns: minmax(0, 1.7fr) 84px 48px 102px 74px;
    align-items: center;
    gap: 12px;
    min-height: 64px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.065);
    color: #cbd8e0;
}

.pgd-edition-row:last-child {
    border-bottom: 0;
}

.pgd-edition-row--head {
    min-height: 35px;
    color: #8199aa;
    font-family: 'Montserrat', 'Inter', system-ui, sans-serif;
    font-size: 10.5px;
    font-weight: 800;
    letter-spacing: 0.12em;
    text-transform: uppercase;
}

.pgd-edition-row--head span:nth-child(n + 2) {
    text-align: center;
}

.pgd-edition-name {
    display: flex;
    align-items: center;
    gap: 11px;
    min-width: 0;
}

/* El texto largo se recorta con puntos suspensivos dentro de su columna. */
.pgd-edition-name > div {
    min-width: 0;
}

.pgd-crest {
    display: grid;
    place-items: center;
    width: 33px;
    height: 33px;
    flex: none;
    border: 1px solid rgba(184, 243, 74, 0.2);
    border-radius: 9px;
    background: rgba(184, 243, 74, 0.1);
    color: var(--pgd-lime);
    font-family: 'Montserrat', 'Inter', system-ui, sans-serif;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.02em;
}

.pgd-crest--blue {
    border-color: rgba(115, 172, 212, 0.23);
    background: rgba(70, 126, 168, 0.18);
    color: #a9d6f0;
}

.pgd-edition-name strong {
    display: block;
    overflow: hidden;
    color: var(--pgd-white);
    font-size: 14px;
    font-weight: 600;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.pgd-edition-name small {
    display: block;
    margin-top: 2px;
    overflow: hidden;
    color: var(--pgd-faint);
    font-size: 11.5px;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.pgd-edition-center {
    text-align: center;
}

.pgd-edition-teams {
    color: #dbe6ec;
    font-family: 'Montserrat', 'Inter', system-ui, sans-serif;
    font-size: 14px;
    font-weight: 700;
    text-align: center;
}

.pgd-channel-chips {
    display: flex;
    justify-content: center;
    gap: 4px;
}

.pgd-chip {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 28px;
    padding: 4px 6px;
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 5px;
    color: #8299a9;
    font-size: 9px;
    font-weight: 700;
    letter-spacing: 0.03em;
    line-height: 1;
}

.pgd-chip--lime {
    border-color: rgba(184, 243, 74, 0.24);
    background: rgba(184, 243, 74, 0.09);
    color: var(--pgd-lime);
}

.pgd-row-action {
    display: inline-flex;
    align-items: center;
    justify-content: flex-end;
    gap: 5px;
    color: var(--pgd-muted);
    font-family: 'Montserrat', 'Inter', system-ui, sans-serif;
    font-size: 10px;
    font-weight: 700;
    transition: color 0.18s ease;
}

.pgd-row-action .pgd-icon {
    transition: transform 0.18s ease;
}

.pgd-row-action:hover {
    color: var(--pgd-lime);
}

.pgd-row-action:hover .pgd-icon {
    transform: translateX(3px);
}

.pgd-row-action--muted {
    color: var(--pgd-faint);
}

/* --- Onboarding de la cuenta nueva --- */
.pgd-onboarding {
    padding: 20px 24px 23px;
}

.pgd-onboarding-top {
    display: flex;
    align-items: flex-start;
    gap: 14px;
}

.pgd-onboarding-icon {
    display: grid;
    place-items: center;
    width: 48px;
    height: 48px;
    flex: none;
    border: 1px solid rgba(184, 243, 74, 0.25);
    border-radius: 13px;
    background: rgba(184, 243, 74, 0.09);
    color: var(--pgd-lime);
}

.pgd-onboarding-icon .pgd-icon {
    width: 25px;
    height: 25px;
}

.pgd-onboarding h3 {
    margin: 5px 0 0;
    color: var(--pgd-white);
    font-family: 'Montserrat', 'Inter', system-ui, sans-serif;
    font-size: 22px;
    font-weight: 800;
    letter-spacing: -0.03em;
    line-height: 1.1;
}

.pgd-onboarding p {
    max-width: 520px;
    margin: 6px 0 0;
    color: var(--pgd-muted);
    font-size: 12.5px;
    line-height: 1.55;
}

.pgd-steps {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 8px;
    margin: 21px 0 0;
    padding: 0;
    list-style: none;
}

.pgd-step {
    position: relative;
    min-height: 92px;
    padding: 13px 12px 11px;
    border: 1px solid rgba(255, 255, 255, 0.09);
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.025);
}

.pgd-step:not(:last-child)::after {
    content: '';
    position: absolute;
    top: 22px;
    right: -9px;
    z-index: 2;
    width: 7px;
    height: 7px;
    border-top: 1px solid rgba(184, 243, 74, 0.4);
    border-right: 1px solid rgba(184, 243, 74, 0.4);
    transform: rotate(45deg);
}

.pgd-step-num {
    display: block;
    color: var(--pgd-lime);
    font-family: 'Montserrat', 'Inter', system-ui, sans-serif;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.1em;
}

.pgd-step strong {
    display: block;
    margin-top: 10px;
    color: #e8f0f4;
    font-size: 12px;
    font-weight: 600;
    line-height: 1.3;
}

.pgd-step small {
    display: block;
    margin-top: 3px;
    color: var(--pgd-faint);
    font-size: 10.5px;
    line-height: 1.35;
}

.pgd-onboarding-actions {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 14px;
    margin-top: 18px;
}

.pgd-onboarding-note {
    color: var(--pgd-faint);
    font-size: 10.5px;
}

/* --- Partidos de hoy --- */
.pgd-today-panel {
    display: flex;
    flex-direction: column;
    min-height: 364px;
}

.pgd-today-date {
    flex: none;
    padding-top: 4px;
    color: var(--pgd-lime);
    font-family: 'Montserrat', 'Inter', system-ui, sans-serif;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.1em;
    text-transform: uppercase;
}

.pgd-match-list {
    padding: 4px 18px 10px;
}

.pgd-match {
    display: grid;
    grid-template-columns: 45px minmax(0, 1fr);
    gap: 10px;
    padding: 14px 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.065);
}

.pgd-match:last-child {
    border-bottom: 0;
}

.pgd-match-time {
    padding-top: 2px;
    color: var(--pgd-lime);
    font-family: 'Montserrat', 'Inter', system-ui, sans-serif;
    font-size: 12px;
    font-weight: 800;
    letter-spacing: -0.01em;
}

.pgd-match-meta {
    display: flex;
    align-items: center;
    gap: 7px;
    margin-bottom: 7px;
    color: var(--pgd-faint);
    font-size: 10.5px;
    font-weight: 500;
    letter-spacing: 0.02em;
}

.pgd-match-meta span:last-child {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.pgd-match-teams {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
    align-items: center;
    gap: 7px;
}

.pgd-match-team {
    display: flex;
    align-items: center;
    gap: 6px;
    min-width: 0;
    overflow: hidden;
    color: #e1ebf0;
    font-size: 12.5px;
    font-weight: 600;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.pgd-match-team.pgd-is-right {
    justify-content: flex-end;
    text-align: right;
}

.pgd-mini-crest {
    display: grid;
    place-items: center;
    width: 20px;
    height: 20px;
    flex: none;
    border-radius: 5px;
    background: rgba(27, 148, 71, 0.5);
    color: #ecfdf5;
    font-family: 'Montserrat', 'Inter', system-ui, sans-serif;
    font-size: 7.5px;
    font-weight: 800;
}

.pgd-mini-crest.pgd-is-lime {
    background: rgba(184, 243, 74, 0.86);
    color: var(--pgd-navy);
}

.pgd-match-vs {
    color: #6f8596;
    font-family: 'Montserrat', 'Inter', system-ui, sans-serif;
    font-size: 8.5px;
    font-weight: 800;
    letter-spacing: 0.04em;
}

.pgd-match-state {
    display: inline-flex;
    margin-top: 7px;
    color: #7e96a6;
    font-size: 10.5px;
}

.pgd-panel-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-top: auto;
    padding: 13px 20px 15px;
    border-top: 1px solid rgba(255, 255, 255, 0.075);
}

.pgd-panel-footer .pgd-btn {
    min-height: 34px;
}

.pgd-empty-match {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 235px;
    padding: 26px 20px;
    text-align: center;
}

.pgd-empty-match--short {
    min-height: 210px;
}

.pgd-empty-icon {
    display: grid;
    place-items: center;
    width: 52px;
    height: 52px;
    border: 1px solid rgba(184, 243, 74, 0.22);
    border-radius: 50%;
    background: rgba(184, 243, 74, 0.07);
    color: var(--pgd-lime);
}

.pgd-empty-icon .pgd-icon {
    width: 23px;
    height: 23px;
}

.pgd-empty-match strong {
    margin-top: 15px;
    color: var(--pgd-white);
    font-family: 'Montserrat', 'Inter', system-ui, sans-serif;
    font-size: 14px;
    font-weight: 800;
}

.pgd-empty-match p {
    max-width: 225px;
    margin: 5px 0 0;
    color: var(--pgd-muted);
    font-size: 12px;
    line-height: 1.55;
}

.pgd-empty-action {
    margin-top: 15px;
}

/* --- Próximos partidos --- */
.pgd-upcoming-list {
    padding: 4px 18px 8px;
}

.pgd-upcoming-row {
    display: grid;
    grid-template-columns: 65px minmax(0, 1fr) 76px 20px;
    align-items: center;
    gap: 12px;
    min-height: 63px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.065);
}

.pgd-upcoming-row:last-child {
    border-bottom: 0;
}

.pgd-upcoming-date {
    display: flex;
    flex-direction: column;
    padding: 6px 8px;
    border-left: 2px solid rgba(184, 243, 74, 0.48);
    border-radius: 0 4px 4px 0;
    background: rgba(184, 243, 74, 0.045);
}

.pgd-upcoming-date strong {
    color: #edf5f1;
    font-family: 'Montserrat', 'Inter', system-ui, sans-serif;
    font-size: 12px;
    font-weight: 800;
    letter-spacing: 0.01em;
}

.pgd-upcoming-date small {
    margin-top: 2px;
    color: var(--pgd-faint);
    font-size: 9.5px;
    text-transform: uppercase;
}

.pgd-upcoming-main {
    min-width: 0;
}

.pgd-upcoming-main strong {
    display: block;
    overflow: hidden;
    color: #e2ebf0;
    font-size: 12px;
    font-weight: 600;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.pgd-upcoming-main small {
    display: block;
    margin-top: 3px;
    overflow: hidden;
    color: var(--pgd-faint);
    font-size: 10px;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.pgd-vs {
    color: #6f8596;
    font-weight: 400;
}

.pgd-upcoming-time {
    color: #c3d2db;
    font-family: 'Montserrat', 'Inter', system-ui, sans-serif;
    font-size: 11px;
    font-weight: 700;
    text-align: right;
}

.pgd-upcoming-arrow {
    display: flex;
    justify-content: flex-end;
    color: #647e90;
}

.pgd-upcoming-row:hover .pgd-upcoming-arrow {
    color: var(--pgd-lime);
}

/* --- Módulos --- */
.pgd-modules-panel {
    min-height: 240px;
}

.pgd-modules-list {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1px;
    margin: 18px 18px 0;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.07);
    border-radius: 9px;
    background: rgba(255, 255, 255, 0.07);
}

.pgd-module {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    min-height: 65px;
    padding: 11px;
    background: rgba(8, 28, 41, 0.9);
}

.pgd-module-icon {
    display: grid;
    place-items: center;
    width: 26px;
    height: 26px;
    flex: none;
    border-radius: 7px;
    background: rgba(255, 255, 255, 0.05);
    color: #879dab;
}

.pgd-module-icon .pgd-icon {
    width: 14px;
    height: 14px;
}

.pgd-module--active {
    background: linear-gradient(145deg, rgba(184, 243, 74, 0.08), rgba(8, 28, 41, 0.9) 72%);
}

.pgd-module--active .pgd-module-icon {
    background: rgba(184, 243, 74, 0.13);
    color: var(--pgd-lime);
}

.pgd-module-copy {
    min-width: 0;
}

.pgd-module-copy strong {
    display: block;
    overflow: hidden;
    color: #dbe6ec;
    font-size: 10.5px;
    font-weight: 600;
    line-height: 1.3;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.pgd-module-copy small {
    display: block;
    margin-top: 3px;
    color: var(--pgd-faint);
    font-size: 9px;
    line-height: 1.2;
}

.pgd-module--active .pgd-module-copy strong {
    color: var(--pgd-white);
}

.pgd-module--active .pgd-module-copy small {
    color: var(--pgd-lime);
}

.pgd-module-foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin: 13px 18px 0;
    padding-top: 12px;
    border-top: 1px solid rgba(255, 255, 255, 0.075);
    color: var(--pgd-faint);
    font-size: 10px;
}

.pgd-module-foot span {
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.pgd-module-foot .pgd-icon {
    width: 13px;
    height: 13px;
    color: #8ea6b4;
}

.pgd-module-foot b {
    color: #d7e4eb;
    font-family: 'Montserrat', 'Inter', system-ui, sans-serif;
    font-size: 11px;
}

/* --- Responsive --- */
@media (max-width: 1180px) {
    .pgd-hero {
        grid-template-columns: minmax(0, 1fr) 190px;
        gap: 26px;
    }

    .pgd-hero-side {
        width: 190px;
    }

    .pgd-kpis {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .pgd-main-grid,
    .pgd-secondary-grid {
        grid-template-columns: minmax(0, 1fr);
    }

    .pgd-today-panel {
        min-height: auto;
    }

    .pgd-match-list {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0 20px;
    }

    .pgd-match:nth-child(odd) {
        padding-right: 20px;
        border-right: 1px solid rgba(255, 255, 255, 0.065);
    }
}

@media (max-width: 820px) {
    .pgd-hero {
        grid-template-columns: minmax(0, 1fr);
        gap: 22px;
    }

    .pgd-hero-side {
        display: none;
    }

    .pgd-display {
        font-size: clamp(31px, 7vw, 47px);
    }

    .pgd-toolbar {
        align-items: flex-start;
        flex-direction: column;
        gap: 9px;
    }

    .pgd-mini-stats {
        width: 100%;
        justify-content: flex-start;
        padding: 3px 4px 0;
    }

    .pgd-edition-row {
        grid-template-columns: minmax(0, 1fr) 76px 70px;
    }

    .pgd-edition-row--head span:nth-child(3),
    .pgd-edition-row--head span:nth-child(4),
    .pgd-edition-row > .pgd-edition-teams,
    .pgd-edition-row > .pgd-channel-chips {
        display: none;
    }

    .pgd-upcoming-row {
        grid-template-columns: 58px minmax(0, 1fr) 20px;
    }

    .pgd-upcoming-time {
        display: none;
    }

    .pgd-steps {
        grid-template-columns: 1fr 1fr;
    }

    .pgd-step:nth-child(2)::after {
        display: none;
    }

    .pgd-match-list {
        display: block;
    }

    .pgd-match:nth-child(odd) {
        padding-right: 0;
        border-right: 0;
    }
}

@media (max-width: 520px) {
    .pgd-kpis {
        grid-template-columns: 1fr;
    }

    .pgd-kpi {
        min-height: 128px;
    }

    .pgd-quick-actions {
        width: 100%;
    }

    .pgd-quick {
        flex: 1 1 calc(50% - 6px);
        justify-content: flex-start;
    }

    .pgd-mini-stat-sep {
        display: none;
    }

    .pgd-mini-stats {
        flex-wrap: wrap;
        gap: 8px 14px;
    }

    .pgd-modules-list {
        grid-template-columns: 1fr;
    }

    .pgd-onboarding {
        padding: 18px;
    }

    .pgd-steps {
        grid-template-columns: 1fr;
    }

    .pgd-step:not(:last-child)::after {
        display: none;
    }

    .pgd-edition-row {
        grid-template-columns: minmax(0, 1fr) 70px;
    }

    .pgd-edition-row--head span:nth-child(2),
    .pgd-edition-row > .pgd-status {
        display: none;
    }

    .pgd-edition-row--head span:last-child {
        display: none;
    }

    .pgd-edition-row > .pgd-row-action {
        justify-self: end;
    }
}

@media (prefers-reduced-motion: reduce) {
    .pgd *,
    .pgd *::before,
    .pgd *::after {
        transition-duration: 0.001ms !important;
        animation-duration: 0.001ms !important;
    }
}
</style>
