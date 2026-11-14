<script setup>
import { computed, reactive, ref, watch } from 'vue';
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import { router } from '@inertiajs/vue3';
import Swal2 from 'sweetalert2';

const props = defineProps({
    sportsMode: { type: Boolean, default: false },
    events: { type: Array, default: () => [] },
    selected: { type: Object, default: () => ({}) },
    report: { type: Object, default: null },
    sections: { type: Object, default: () => ({}) },
});

const eventId = ref(props.selected?.event_id ?? '');
const editionId = ref(props.selected?.edition_id ?? '');
const pdfOpen = ref(false);
const exporting = ref(false);

// Inertia reutiliza el componente al cambiar de edición: sincronizamos los
// selectores con la selección que devuelve el servidor.
watch(() => props.selected, (selected) => {
    eventId.value = selected?.event_id ?? '';
    editionId.value = selected?.edition_id ?? '';
});

const currentEvent = computed(() => props.events.find((event) => Number(event.id) === Number(eventId.value)) ?? null);
const availableEditions = computed(() => currentEvent.value?.editions ?? []);
const sectionList = computed(() => Object.entries(props.sections).map(([key, label]) => ({ key, label })));
const chosen = reactive(Object.fromEntries(Object.keys(props.sections).map((key) => [key, true])));
const chosenKeys = computed(() => Object.keys(props.sections).filter((key) => chosen[key]));
const allChosen = computed(() => chosenKeys.value.length === Object.keys(props.sections).length);

const surface = computed(() => (props.sportsMode
    ? 'border-[#234052] bg-[#0b1f2b] text-slate-100'
    : 'border-gray-200 bg-white text-gray-900 dark:border-gray-700 dark:bg-gray-900 dark:text-white'));
const soft = computed(() => (props.sportsMode
    ? 'border-[#2b4b5d] bg-[#122c3a]'
    : 'border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800'));
const muted = computed(() => (props.sportsMode ? 'text-slate-400' : 'text-gray-600 dark:text-gray-300'));
const strong = computed(() => (props.sportsMode ? 'text-white' : 'text-gray-900 dark:text-white'));
const accent = computed(() => (props.sportsMode ? 'text-lime-300' : 'text-emerald-700 dark:text-emerald-300'));
const tableHead = computed(() => (props.sportsMode
    ? 'bg-[#122c3a] text-slate-300'
    : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200'));
const rowBorder = computed(() => (props.sportsMode ? 'border-[#234052] text-slate-100' : 'border-gray-200 text-gray-800 dark:border-gray-700 dark:text-gray-100'));
const tableText = computed(() => (props.sportsMode ? 'text-slate-100' : 'text-gray-800 dark:text-gray-100'));
const inputClass = computed(() => (props.sportsMode
    ? 'border-[#2b4b5d] bg-[#122c3a] text-slate-100 focus:border-lime-300 focus:ring-lime-300/30'
    : 'border-gray-300 bg-white text-gray-900 focus:border-emerald-500 focus:ring-emerald-500/30 dark:border-gray-600 dark:bg-gray-800 dark:text-white'));

const num = (value, decimals = 0) => Number(value || 0).toLocaleString('es-PE', {
    minimumFractionDigits: decimals,
    maximumFractionDigits: decimals,
});

const fecha = (value) => {
    if (!value) return 'Por confirmar';
    const date = new Date(`${String(value).slice(0, 10)}T00:00:00`);
    return Number.isNaN(date.getTime())
        ? value
        : new Intl.DateTimeFormat('es-PE', { day: '2-digit', month: 'short', year: 'numeric' }).format(date);
};

const go = () => {
    router.get(route('even_reportes_temporada'), {
        event_id: eventId.value || undefined,
        edition_id: editionId.value || undefined,
    }, { preserveScroll: true });
};

const onEventChange = () => {
    editionId.value = availableEditions.value[0]?.id ?? '';
    go();
};

const onEditionChange = () => go();

const toggleAll = () => {
    const next = !allChosen.value;
    Object.keys(chosen).forEach((key) => { chosen[key] = next; });
};

const downloadPdf = async () => {
    if (!props.report || chosenKeys.value.length === 0) return;

    exporting.value = true;
    try {
        const { data } = await axios.post(route('even_reportes_temporada_pdf'), {
            edition_id: props.report.edition.id,
            sections: chosenKeys.value,
        }, { responseType: 'blob' });

        const url = URL.createObjectURL(new Blob([data], { type: 'application/pdf' }));
        const link = document.createElement('a');
        link.href = url;
        link.download = `reporte-temporada-${props.report.edition.year}.pdf`;
        document.body.appendChild(link);
        link.click();
        link.remove();
        URL.revokeObjectURL(url);
        pdfOpen.value = false;

        await Swal2.fire({
            title: 'PDF generado',
            text: 'El documento se descargó con las secciones marcadas.',
            icon: 'success',
            timer: 1700,
            showConfirmButton: false,
        });
    } catch {
        await Swal2.fire({
            title: 'No se pudo generar el PDF',
            text: 'Revisa que haya al menos una sección marcada e inténtalo de nuevo.',
            icon: 'error',
        });
    } finally {
        exporting.value = false;
    }
};

const resultClass = (result) => ({
    G: props.sportsMode ? 'bg-lime-300 text-[#08251a]' : 'bg-emerald-100 text-emerald-800',
    E: props.sportsMode ? 'bg-amber-300/20 text-amber-200' : 'bg-amber-100 text-amber-800',
    P: props.sportsMode ? 'bg-rose-400/20 text-rose-200' : 'bg-rose-100 text-rose-800',
}[result] || '');

const podiumClass = (position) => ({
    1: props.sportsMode ? 'border-amber-300/50 bg-amber-300/10' : 'border-amber-300 bg-amber-50',
    2: props.sportsMode ? 'border-slate-300/40 bg-slate-300/10' : 'border-slate-300 bg-slate-50',
    3: props.sportsMode ? 'border-orange-400/40 bg-orange-400/10' : 'border-orange-300 bg-orange-50',
    4: props.sportsMode ? 'border-[#2b4b5d] bg-[#122c3a]' : 'border-gray-200 bg-gray-50',
}[position] || '');
</script>

<template>
    <AppLayout title="Reporte de fin de temporada">
        <Navigation
            :routeModule="route('even_dashboard')"
            :titleModule="sportsMode ? 'GESTIÓN DEPORTIVA' : 'Eventos sociales'"
            :data="[{ title: 'Reporte de temporada' }]"
        />

        <main class="mx-auto mt-5 max-w-7xl space-y-5 px-3 pb-10 sm:px-5 lg:px-6">
            <section
                class="relative isolate overflow-hidden rounded-2xl border p-4 text-white sm:p-6 lg:p-8"
                :class="sportsMode
                    ? 'border-white/10 bg-[#0b1f2b] shadow-[0_26px_70px_rgba(0,0,0,0.38)]'
                    : 'border-transparent bg-gradient-to-br from-emerald-950 via-emerald-800 to-green-700 shadow-xl'"
            >
                <div class="relative flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
                    <div class="min-w-0 xl:flex-1">
                        <div class="mb-3 inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-bold uppercase tracking-[0.18em]" :class="sportsMode ? 'border-lime-300/25 bg-lime-300/10 text-lime-200' : 'border-white/20 bg-white/10 text-emerald-100'">
                            <span class="h-2 w-2 rounded-full bg-lime-300"></span> Cierre de campeonato
                        </div>
                        <h1 class="text-2xl font-black tracking-tight sm:text-4xl">Reporte <span class="text-lime-300">de temporada</span></h1>
                        <p class="mt-2 max-w-2xl text-sm sm:text-base" :class="sportsMode ? 'text-slate-300' : 'text-emerald-100'">
                            Elige el torneo y su edición para revisar la tabla, el podio, el historial de cada equipo y los rankings. Luego marca las secciones que quieres imprimir en PDF.
                        </p>
                    </div>

                    <div class="grid w-full min-w-0 gap-3 sm:grid-cols-2 xl:w-[min(100%,38rem)]">
                        <label class="block min-w-0">
                            <span class="mb-1 block text-[11px] font-bold uppercase tracking-wide" :class="sportsMode ? 'text-slate-400' : 'text-emerald-100'">Torneo</span>
                            <select v-model="eventId" class="form-select h-11 w-full rounded-xl text-sm" :class="sportsMode ? 'border border-[#2b4b5d] bg-[#122c3a] text-slate-100' : ''" @change="onEventChange">
                                <option v-for="event in events" :key="event.id" :value="event.id">{{ event.title }}</option>
                            </select>
                        </label>
                        <label class="block min-w-0">
                            <span class="mb-1 block text-[11px] font-bold uppercase tracking-wide" :class="sportsMode ? 'text-slate-400' : 'text-emerald-100'">Edición</span>
                            <select v-model="editionId" class="form-select h-11 w-full rounded-xl text-sm" :class="sportsMode ? 'border border-[#2b4b5d] bg-[#122c3a] text-slate-100' : ''" :disabled="availableEditions.length === 0" @change="onEditionChange">
                                <option v-if="availableEditions.length === 0" value="">Sin ediciones</option>
                                <option v-for="edition in availableEditions" :key="edition.id" :value="edition.id">
                                    {{ edition.name }} · {{ edition.year }} · {{ edition.status_label }}
                                </option>
                            </select>
                        </label>
                    </div>
                </div>
            </section>

            <section v-if="!report" class="rounded-2xl border border-dashed px-5 py-12 text-center" :class="soft">
                <div class="mx-auto grid h-16 w-16 place-items-center rounded-full text-3xl" :class="sportsMode ? 'bg-[#17394a]' : 'bg-emerald-100'" aria-hidden="true">📑</div>
                <h2 class="mt-4 text-lg font-extrabold" :class="strong">Todavía no hay una edición para reportar</h2>
                <p class="mx-auto mt-1 max-w-md text-sm" :class="muted">Crea una edición con equipos y partidos, o selecciona otro torneo para ver su reporte.</p>
            </section>

            <template v-else>
                <section id="seccion-summary" class="rounded-2xl border p-4 sm:p-5" :class="surface">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-xl font-black leading-tight sm:text-2xl" :class="strong">{{ report.edition.name }}</h2>
                                <span class="rounded-full border px-3 py-1 text-xs font-bold" :class="report.edition.status_finished
                                    ? (sportsMode ? 'border-lime-300/40 bg-lime-300/10 text-lime-200' : 'border-emerald-200 bg-emerald-50 text-emerald-800')
                                    : (sportsMode ? 'border-amber-300/40 bg-amber-300/10 text-amber-200' : 'border-amber-200 bg-amber-50 text-amber-800')">
                                    {{ report.edition.status_label }}
                                </span>
                            </div>
                            <p class="mt-1 text-sm" :class="muted">
                                {{ report.edition.event_title }} · {{ fecha(report.edition.start_date) }} al {{ fecha(report.edition.end_date) }} · {{ report.edition.format_label }}
                            </p>
                            <p class="mt-1 text-xs" :class="muted">Generado el {{ report.generated_at }}</p>
                        </div>

                        <button type="button" class="inline-flex min-h-11 shrink-0 items-center justify-center gap-2 rounded-xl px-5 py-2.5 text-sm font-black shadow-lg transition focus:outline-none focus:ring-2" :class="sportsMode ? 'bg-[#c8f169] text-[#08251a] hover:bg-[#d9fb8d] focus:ring-lime-300' : 'bg-emerald-700 text-white hover:bg-emerald-800 focus:ring-emerald-400'" @click="pdfOpen = true">
                            <span aria-hidden="true">🖨️</span> Exportar PDF
                        </button>
                    </div>

                    <p v-if="!report.edition.status_finished" class="mt-4 rounded-xl border px-4 py-3 text-sm" :class="sportsMode ? 'border-amber-300/30 bg-amber-300/10 text-amber-100' : 'border-amber-200 bg-amber-50 text-amber-900'">
                        Esta edición todavía no está finalizada. El reporte se puede previsualizar, pero los datos pueden cambiar hasta cerrar el campeonato.
                    </p>

                    <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-4 xl:grid-cols-8">
                        <div v-for="kpi in [
                            { label: 'Equipos', value: num(report.summary.teams), icon: '⚽' },
                            { label: 'Jugadores', value: num(report.summary.players), icon: '👥' },
                            { label: 'Partidos', value: `${num(report.summary.matches_played)}/${num(report.summary.matches_total)}`, icon: '📅' },
                            { label: 'Goles', value: num(report.summary.goals), icon: '🥅' },
                            { label: 'Promedio', value: num(report.summary.goals_average, 2), icon: '📈' },
                            { label: 'Amarillas', value: num(report.summary.yellow_cards), icon: '🟨' },
                            { label: 'Rojas', value: num(report.summary.red_cards), icon: '🟥' },
                            { label: 'Campeón', value: report.summary.champion_name || 'Por definir', icon: '🏆' },
                        ]" :key="kpi.label" class="min-w-0 rounded-xl border p-3" :class="soft">
                            <p class="text-[10px] font-extrabold uppercase tracking-wide" :class="muted">{{ kpi.icon }} {{ kpi.label }}</p>
                            <p class="mt-1 truncate text-lg font-black leading-tight" :class="kpi.label === 'Campeón' ? accent : strong">{{ kpi.value }}</p>
                        </div>
                    </div>
                </section>

                <nav class="flex flex-wrap gap-2" aria-label="Secciones del reporte">
                    <a v-for="section in sectionList" :key="section.key" :href="`#seccion-${section.key}`" class="rounded-full border px-3 py-1.5 text-xs font-bold transition" :class="sportsMode ? 'border-[#2b4b5d] bg-[#122c3a] text-slate-300 hover:border-lime-300/60 hover:text-lime-200' : 'border-gray-200 bg-white text-gray-700 hover:border-emerald-400 hover:text-emerald-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200'">
                        {{ section.label }}
                    </a>
                </nav>

                <!-- 1. Tabla de posiciones -->
                <section id="seccion-standings" class="rounded-2xl border p-4 sm:p-5" :class="surface">
                    <header class="mb-4">
                        <h2 class="text-lg font-black sm:text-xl" :class="strong">Tabla de posiciones</h2>
                        <p class="text-sm" :class="muted">Posiciones finales de la edición, con puntos extra y ajustes.</p>
                    </header>
                    <div class="overflow-x-auto">
                        <table class="se-season-table w-full min-w-[46rem] border-collapse text-sm" :class="[tableText, { 'se-season-table--sports': sportsMode }]">
                            <thead>
                                <tr :class="tableHead">
                                    <th class="px-2 py-2 text-left text-xs font-bold uppercase">#</th>
                                    <th class="px-2 py-2 text-left text-xs font-bold uppercase">Equipo</th>
                                    <th class="px-2 py-2 text-center text-xs font-bold uppercase">PJ</th>
                                    <th class="px-2 py-2 text-center text-xs font-bold uppercase">PG</th>
                                    <th class="px-2 py-2 text-center text-xs font-bold uppercase">PE</th>
                                    <th class="px-2 py-2 text-center text-xs font-bold uppercase">PP</th>
                                    <th class="px-2 py-2 text-center text-xs font-bold uppercase">GF</th>
                                    <th class="px-2 py-2 text-center text-xs font-bold uppercase">GC</th>
                                    <th class="px-2 py-2 text-center text-xs font-bold uppercase">DG</th>
                                    <th class="px-2 py-2 text-center text-xs font-bold uppercase">PTS</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in report.standings" :key="row.team_id" class="border-b" :class="rowBorder">
                                    <td class="px-2 py-2 font-black" :class="accent">{{ row.position }}</td>
                                    <td class="px-2 py-2">
                                        <div class="flex min-w-0 items-center gap-2">
                                            <span class="grid h-8 w-8 shrink-0 place-items-center overflow-hidden rounded-full" :class="sportsMode ? 'bg-[#17394a]' : 'bg-gray-100'">
                                                <img v-if="row.team_logo" :src="row.team_logo" :alt="row.team_name" class="h-8 w-8 object-contain" loading="lazy" />
                                                <span v-else class="text-xs font-black" :class="accent">{{ (row.team_short_name || row.team_name || '?').slice(0, 2).toUpperCase() }}</span>
                                            </span>
                                            <span class="truncate font-bold" :class="strong">{{ row.team_name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-2 py-2 text-center">{{ row.matches_played }}</td>
                                    <td class="px-2 py-2 text-center">{{ row.matches_won }}</td>
                                    <td class="px-2 py-2 text-center">{{ row.matches_drawn }}</td>
                                    <td class="px-2 py-2 text-center">{{ row.matches_lost }}</td>
                                    <td class="px-2 py-2 text-center">{{ row.goals_for }}</td>
                                    <td class="px-2 py-2 text-center">{{ row.goals_against }}</td>
                                    <td class="px-2 py-2 text-center">{{ row.goal_difference > 0 ? '+' : '' }}{{ row.goal_difference }}</td>
                                    <td class="px-2 py-2 text-center text-base font-black" :class="accent">{{ row.points }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p v-if="report.standings.some((row) => row.has_sanction)" class="mt-2 text-xs" :class="muted">* Los puntos incluyen ajustes administrativos por sanción.</p>
                </section>

                <!-- 2. Equipos participantes -->
                <section id="seccion-teams" class="rounded-2xl border p-4 sm:p-5" :class="surface">
                    <header class="mb-4">
                        <h2 class="text-lg font-black sm:text-xl" :class="strong">Equipos participantes</h2>
                        <p class="text-sm" :class="muted">{{ report.teams.length }} equipos inscritos en la edición.</p>
                    </header>
                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                        <article v-for="team in report.teams" :key="team.team_id" class="flex min-w-0 items-center gap-3 rounded-xl border p-3" :class="soft">
                            <span class="grid h-12 w-12 shrink-0 place-items-center overflow-hidden rounded-xl" :class="sportsMode ? 'bg-[#17394a]' : 'bg-white'">
                                <img v-if="team.team_logo" :src="team.team_logo" :alt="team.team_name" class="h-11 w-11 object-contain" loading="lazy" />
                                <span v-else class="text-sm font-black" :class="accent">{{ (team.short_name || team.team_name || '?').slice(0, 2).toUpperCase() }}</span>
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-bold" :class="strong">{{ team.team_name }}</p>
                                <p class="truncate text-xs" :class="muted">DT: {{ team.manager || 'Sin registrar' }}</p>
                                <p class="text-xs" :class="muted">{{ team.players_count }} jugadores · Puesto {{ team.position }}</p>
                            </div>
                            <span class="shrink-0 text-lg font-black" :class="accent">{{ team.points }}<span class="ml-1 text-[10px] font-bold uppercase">pts</span></span>
                        </article>
                    </div>
                </section>

                <!-- 3. Podio -->
                <section id="seccion-podium" class="rounded-2xl border p-4 sm:p-5" :class="surface">
                    <header class="mb-4">
                        <h2 class="text-lg font-black sm:text-xl" :class="strong">Podio de la temporada</h2>
                        <p class="text-sm" :class="muted">1.º y 2.º salen de la final; 3.º y 4.º del partido por el tercer puesto. Si no se jugó o terminó 0-0 sin penales, se ordenan por los puntos del torneo.</p>
                    </header>
                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <article v-for="spot in report.podium" :key="spot.team_id" class="relative overflow-hidden rounded-2xl border p-4" :class="podiumClass(spot.position)">
                            <div class="flex items-start justify-between gap-2">
                                <span class="rounded-full px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-wide" :class="sportsMode ? 'bg-black/20 text-slate-200' : 'bg-white text-gray-700'">{{ spot.label }}</span>
                                <span class="text-2xl" aria-hidden="true">{{ ['🥇', '🥈', '🥉', '🏅'][spot.position - 1] || '🏅' }}</span>
                            </div>
                            <div class="mt-3 flex items-center gap-3">
                                <span class="grid h-12 w-12 shrink-0 place-items-center overflow-hidden rounded-xl" :class="sportsMode ? 'bg-black/20' : 'bg-white'">
                                    <img v-if="spot.team_logo" :src="spot.team_logo" :alt="spot.team_name" class="h-11 w-11 object-contain" loading="lazy" />
                                    <span v-else class="text-sm font-black" :class="accent">{{ (spot.team_name || '?').slice(0, 2).toUpperCase() }}</span>
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate font-black" :class="strong">{{ spot.team_name }}</p>
                                    <p v-if="spot.is_champion" class="text-xs font-bold" :class="accent">🏆 Campeón</p>
                                    <p class="text-xs" :class="muted">{{ spot.points }} pts · {{ spot.matches_played }} PJ</p>
                                </div>
                            </div>
                            <div class="mt-3 grid grid-cols-3 gap-2 text-center text-xs">
                                <div class="rounded-lg px-1 py-1.5" :class="sportsMode ? 'bg-black/20' : 'bg-white'"><p :class="muted">Goles</p><p class="font-black" :class="strong">{{ spot.goals_for }}</p></div>
                                <div class="rounded-lg px-1 py-1.5" :class="sportsMode ? 'bg-black/20' : 'bg-white'"><p :class="muted">Recibidos</p><p class="font-black" :class="strong">{{ spot.goals_against }}</p></div>
                                <div class="rounded-lg px-1 py-1.5" :class="sportsMode ? 'bg-black/20' : 'bg-white'"><p :class="muted">Diferencia</p><p class="font-black" :class="strong">{{ spot.goal_difference > 0 ? '+' : '' }}{{ spot.goal_difference }}</p></div>
                            </div>
                        </article>
                    </div>
                </section>

                <!-- 4. Historia por equipo -->
                <section id="seccion-team_history" class="rounded-2xl border p-4 sm:p-5" :class="surface">
                    <header class="mb-4">
                        <h2 class="text-lg font-black sm:text-xl" :class="strong">Historia de partidos por equipo</h2>
                        <p class="text-sm" :class="muted">Resultados, fechas, fase y tarjetas de cada encuentro.</p>
                    </header>
                    <div class="space-y-3">
                        <details v-for="team in report.team_history" :key="team.team_id" class="group rounded-xl border" :class="soft">
                            <summary class="flex cursor-pointer list-none flex-wrap items-center justify-between gap-2 p-3">
                                <div class="flex min-w-0 items-center gap-2">
                                    <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full text-xs font-black" :class="sportsMode ? 'bg-[#17394a]' : 'bg-white'" :style="!sportsMode ? 'color:#047857' : 'color:#b8f34a'">{{ team.position }}</span>
                                    <span class="truncate font-bold" :class="strong">{{ team.team_name }}</span>
                                </div>
                                <div class="flex flex-wrap items-center gap-2 text-xs" :class="muted">
                                    <span>{{ team.totals.played }} PJ</span>
                                    <span class="font-bold" :class="accent">{{ team.totals.won }}G · {{ team.totals.drawn }}E · {{ team.totals.lost }}P</span>
                                    <span>{{ team.totals.goals_for }} GF · {{ team.totals.goals_against }} GC</span>
                                    <span :class="sportsMode ? 'text-amber-200' : 'text-amber-700'">🟨 {{ team.totals.yellow }}</span>
                                    <span :class="sportsMode ? 'text-rose-300' : 'text-rose-700'">🟥 {{ team.totals.red }}</span>
                                </div>
                            </summary>
                            <div class="border-t p-3" :class="rowBorder">
                                <p v-if="team.matches.length === 0" class="text-sm" :class="muted">Sin partidos jugados registrados.</p>
                                <div v-else class="overflow-x-auto">
                                    <table class="se-season-table w-full min-w-[44rem] border-collapse text-xs sm:text-sm" :class="[tableText, { 'se-season-table--sports': sportsMode }]">
                                        <thead>
                                            <tr :class="tableHead">
                                                <th class="px-2 py-2 text-left font-bold uppercase">Fecha</th>
                                                <th class="px-2 py-2 text-left font-bold uppercase">Fase</th>
                                                <th class="px-2 py-2 text-left font-bold uppercase">Rival</th>
                                                <th class="px-2 py-2 text-center font-bold uppercase">Cond.</th>
                                                <th class="px-2 py-2 text-center font-bold uppercase">Resultado</th>
                                                <th class="px-2 py-2 text-center font-bold uppercase">Marcador</th>
                                                <th class="px-2 py-2 text-center font-bold uppercase">🟨</th>
                                                <th class="px-2 py-2 text-center font-bold uppercase">🟥</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="match in team.matches" :key="match.match_id" class="border-b" :class="rowBorder">
                                                <td class="whitespace-nowrap px-2 py-2">{{ match.date || 'Sin fecha' }}<span v-if="match.time" class="ml-1 text-[10px]" :class="muted">{{ match.time }}</span></td>
                                                <td class="px-2 py-2">{{ match.phase_label }}<span v-if="match.round" class="ml-1 text-[10px]" :class="muted">F{{ match.round }}</span></td>
                                                <td class="px-2 py-2">{{ match.opponent }}</td>
                                                <td class="px-2 py-2 text-center">{{ match.venue }}</td>
                                                <td class="px-2 py-2 text-center"><span class="inline-flex h-6 w-6 items-center justify-center rounded-full font-black" :class="resultClass(match.result)">{{ match.result }}</span></td>
                                                <td class="whitespace-nowrap px-2 py-2 text-center font-bold">{{ match.score }}</td>
                                                <td class="px-2 py-2 text-center">{{ match.yellow }}</td>
                                                <td class="px-2 py-2 text-center">{{ match.red }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </details>
                    </div>
                </section>

                <!-- 5. Ranking de jugadores -->
                <section id="seccion-player_ranking" class="rounded-2xl border p-4 sm:p-5" :class="surface">
                    <header class="mb-4">
                        <h2 class="text-lg font-black sm:text-xl" :class="strong">Ranking de jugadores</h2>
                        <p class="text-sm" :class="muted">Los cinco mejores jugadores y los cinco mejores porteros, con su detalle partido a partido.</p>
                    </header>

                    <div v-for="group in [
                        { key: 'players', title: 'Mejores jugadores', icon: '⭐', rows: report.player_ranking.players },
                        { key: 'goalkeepers', title: 'Mejores porteros', icon: '🧤', rows: report.player_ranking.goalkeepers },
                    ]" :key="group.key" class="mb-6 last:mb-0">
                        <h3 class="mb-2 flex items-center gap-2 text-sm font-extrabold uppercase tracking-wide" :class="accent">{{ group.icon }} {{ group.title }}</h3>
                        <p v-if="group.rows.length === 0" class="text-sm" :class="muted">Sin datos suficientes para este ranking.</p>
                        <div v-else class="space-y-2">
                            <details v-for="(player, index) in group.rows" :key="player.player_id" class="rounded-xl border" :class="soft">
                                <summary class="flex cursor-pointer list-none flex-wrap items-center justify-between gap-2 p-3">
                                    <div class="flex min-w-0 items-center gap-2">
                                        <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full text-xs font-black" :class="sportsMode ? 'bg-[#17394a] text-lime-300' : 'bg-white text-emerald-700'">{{ index + 1 }}</span>
                                        <div class="min-w-0">
                                            <p class="truncate font-bold" :class="strong">{{ player.name }} <span v-if="player.jersey_number" class="text-xs font-normal" :class="muted">#{{ player.jersey_number }}</span></p>
                                            <p class="truncate text-xs" :class="muted">{{ player.team_name }}{{ player.position ? ` · ${player.position}` : '' }}</p>
                                        </div>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-2 text-xs" :class="muted">
                                        <span>{{ player.summary.matches_played || 0 }} PJ</span>
                                        <span v-if="group.key === 'players'">{{ player.summary.goals || 0 }} G · {{ player.summary.assists || 0 }} A</span>
                                        <span v-else>{{ player.summary.saves || 0 }} atajadas · {{ player.summary.clean_sheet || 0 }} VI</span>
                                        <span>{{ player.summary.mvp || 0 }} MVP</span>
                                        <span :class="sportsMode ? 'text-amber-200' : 'text-amber-700'">🟨 {{ player.cards.yellow }}</span>
                                        <span :class="sportsMode ? 'text-rose-300' : 'text-rose-700'">🟥 {{ player.cards.red }}</span>
                                        <span class="font-black" :class="accent">{{ num(player.points, 1) }} pts</span>
                                    </div>
                                </summary>
                                <div class="border-t p-3" :class="rowBorder">
                                    <p v-if="player.breakdown.length === 0" class="text-sm" :class="muted">Sin partidos con estadísticas registradas.</p>
                                    <div v-else class="overflow-x-auto">
                                        <table class="se-season-table w-full min-w-[44rem] border-collapse text-xs" :class="[tableText, { 'se-season-table--sports': sportsMode }]">
                                            <thead>
                                                <tr :class="tableHead">
                                                    <th class="px-2 py-2 text-left font-bold uppercase">Partido</th>
                                                    <th class="px-2 py-2 text-left font-bold uppercase">Fecha</th>
                                                    <th class="px-2 py-2 text-center font-bold uppercase">Goles</th>
                                                    <th class="px-2 py-2 text-center font-bold uppercase">Asist.</th>
                                                    <th class="px-2 py-2 text-center font-bold uppercase">Atajadas</th>
                                                    <th class="px-2 py-2 text-center font-bold uppercase">Min.</th>
                                                    <th class="px-2 py-2 text-center font-bold uppercase">MVP</th>
                                                    <th class="px-2 py-2 text-center font-bold uppercase">VI</th>
                                                    <th class="px-2 py-2 text-center font-bold uppercase">🟨</th>
                                                    <th class="px-2 py-2 text-center font-bold uppercase">🟥</th>
                                                    <th class="px-2 py-2 text-center font-bold uppercase">Pts</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="line in player.breakdown" :key="line.match_id" class="border-b" :class="rowBorder">
                                                    <td class="px-2 py-2">
                                                        <p class="font-semibold" :class="strong">{{ line.label }}</p>
                                                        <p class="text-[10px]" :class="muted">{{ line.phase_label }}<span v-if="line.round"> · Fecha {{ line.round }}</span></p>
                                                    </td>
                                                    <td class="whitespace-nowrap px-2 py-2">{{ line.date || '—' }}</td>
                                                    <td class="px-2 py-2 text-center font-bold">{{ line.goals }}</td>
                                                    <td class="px-2 py-2 text-center">{{ line.assists }}</td>
                                                    <td class="px-2 py-2 text-center">{{ line.saves }}</td>
                                                    <td class="px-2 py-2 text-center">{{ line.minutes_played }}</td>
                                                    <td class="px-2 py-2 text-center">{{ line.is_mvp ? '★' : '—' }}</td>
                                                    <td class="px-2 py-2 text-center">{{ line.clean_sheet ? 'Sí' : '—' }}</td>
                                                    <td class="px-2 py-2 text-center">{{ line.yellow }}</td>
                                                    <td class="px-2 py-2 text-center">{{ line.red }}</td>
                                                    <td class="px-2 py-2 text-center font-bold" :class="accent">{{ line.points ?? '—' }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </details>
                        </div>
                    </div>
                </section>

                <!-- 6. Goleadores -->
                <section id="seccion-scorers" class="rounded-2xl border p-4 sm:p-5" :class="surface">
                    <header class="mb-4">
                        <h2 class="text-lg font-black sm:text-xl" :class="strong">Goleadores</h2>
                        <p class="text-sm" :class="muted">Máximos anotadores de la edición.</p>
                    </header>
                    <p v-if="report.scorers.length === 0" class="text-sm" :class="muted">Todavía no hay goles registrados.</p>
                    <div v-else class="overflow-x-auto">
                        <table class="se-season-table w-full min-w-[36rem] border-collapse text-sm" :class="[tableText, { 'se-season-table--sports': sportsMode }]">
                            <thead>
                                <tr :class="tableHead">
                                    <th class="px-2 py-2 text-left text-xs font-bold uppercase">#</th>
                                    <th class="px-2 py-2 text-left text-xs font-bold uppercase">Jugador</th>
                                    <th class="px-2 py-2 text-left text-xs font-bold uppercase">Equipo</th>
                                    <th class="px-2 py-2 text-center text-xs font-bold uppercase">PJ</th>
                                    <th class="px-2 py-2 text-center text-xs font-bold uppercase">Goles</th>
                                    <th class="px-2 py-2 text-center text-xs font-bold uppercase">Asistencias</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(scorer, index) in report.scorers" :key="`${scorer.player_id}-${index}`" class="border-b" :class="rowBorder">
                                    <td class="px-2 py-2 font-black" :class="accent">{{ index + 1 }}</td>
                                    <td class="px-2 py-2 font-bold" :class="strong">{{ scorer.name }}</td>
                                    <td class="px-2 py-2">{{ scorer.team_name || '—' }}</td>
                                    <td class="px-2 py-2 text-center">{{ scorer.matches_played }}</td>
                                    <td class="px-2 py-2 text-center text-base font-black" :class="accent">{{ scorer.goals }}</td>
                                    <td class="px-2 py-2 text-center">{{ scorer.assists }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- 7. Disciplina -->
                <section id="seccion-discipline" class="rounded-2xl border p-4 sm:p-5" :class="surface">
                    <header class="mb-4">
                        <h2 class="text-lg font-black sm:text-xl" :class="strong">Disciplina</h2>
                        <p class="text-sm" :class="muted">Tarjetas de la edición por equipo y por jugador.</p>
                    </header>

                    <div class="grid gap-3 sm:grid-cols-3">
                        <div class="rounded-xl border p-3" :class="soft"><p class="text-xs font-bold uppercase" :class="muted">Amarillas</p><p class="mt-1 text-2xl font-black text-amber-500">{{ num(report.discipline.totals.yellow) }}</p></div>
                        <div class="rounded-xl border p-3" :class="soft"><p class="text-xs font-bold uppercase" :class="muted">Rojas</p><p class="mt-1 text-2xl font-black text-rose-500">{{ num(report.discipline.totals.red) }}</p></div>
                        <div class="rounded-xl border p-3" :class="soft"><p class="text-xs font-bold uppercase" :class="muted">Total de tarjetas</p><p class="mt-1 text-2xl font-black" :class="strong">{{ num(report.discipline.totals.total) }}</p></div>
                    </div>

                    <div class="mt-5 grid gap-5 xl:grid-cols-2">
                        <div class="min-w-0">
                            <h3 class="mb-2 text-sm font-extrabold uppercase tracking-wide" :class="accent">Por equipo</h3>
                            <div class="overflow-x-auto">
                                <table class="se-season-table w-full min-w-[22rem] border-collapse text-sm" :class="[tableText, { 'se-season-table--sports': sportsMode }]">
                                    <thead><tr :class="tableHead"><th class="px-2 py-2 text-left font-bold uppercase">Equipo</th><th class="px-2 py-2 text-center font-bold uppercase">🟨</th><th class="px-2 py-2 text-center font-bold uppercase">🟥</th><th class="px-2 py-2 text-center font-bold uppercase">Total</th></tr></thead>
                                    <tbody>
                                        <tr v-for="team in report.discipline.by_team" :key="team.team_id" class="border-b" :class="rowBorder">
                                            <td class="px-2 py-2 font-semibold" :class="strong">{{ team.team_name }}</td>
                                            <td class="px-2 py-2 text-center">{{ team.yellow }}</td>
                                            <td class="px-2 py-2 text-center">{{ team.red }}</td>
                                            <td class="px-2 py-2 text-center font-black" :class="accent">{{ team.total }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="min-w-0">
                            <h3 class="mb-2 text-sm font-extrabold uppercase tracking-wide" :class="accent">Jugadores con más tarjetas</h3>
                            <p v-if="report.discipline.top_players.length === 0" class="text-sm" :class="muted">Sin tarjetas registradas.</p>
                            <div v-else class="overflow-x-auto">
                                <table class="se-season-table w-full min-w-[22rem] border-collapse text-sm" :class="[tableText, { 'se-season-table--sports': sportsMode }]">
                                    <thead><tr :class="tableHead"><th class="px-2 py-2 text-left font-bold uppercase">Jugador</th><th class="px-2 py-2 text-left font-bold uppercase">Equipo</th><th class="px-2 py-2 text-center font-bold uppercase">🟨</th><th class="px-2 py-2 text-center font-bold uppercase">🟥</th></tr></thead>
                                    <tbody>
                                        <tr v-for="player in report.discipline.top_players" :key="player.player_id" class="border-b" :class="rowBorder">
                                            <td class="px-2 py-2 font-semibold" :class="strong">{{ player.name }}</td>
                                            <td class="px-2 py-2">{{ player.team_name || '—' }}</td>
                                            <td class="px-2 py-2 text-center">{{ player.yellow }}</td>
                                            <td class="px-2 py-2 text-center">{{ player.red }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- 8. Datos destacados -->
                <section id="seccion-highlights" class="rounded-2xl border p-4 sm:p-5" :class="surface">
                    <header class="mb-4">
                        <h2 class="text-lg font-black sm:text-xl" :class="strong">Datos destacados</h2>
                        <p class="text-sm" :class="muted">Los números que resumen la temporada.</p>
                    </header>
                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                        <div class="rounded-xl border p-4" :class="soft">
                            <p class="text-xs font-bold uppercase" :class="muted">Mejor ataque</p>
                            <p class="mt-1 truncate font-black" :class="strong">{{ report.highlights.best_attack?.team_name || 'Sin datos' }}</p>
                            <p class="text-sm" :class="accent">{{ report.highlights.best_attack ? `${report.highlights.best_attack.value} goles a favor` : '—' }}</p>
                        </div>
                        <div class="rounded-xl border p-4" :class="soft">
                            <p class="text-xs font-bold uppercase" :class="muted">Mejor defensa</p>
                            <p class="mt-1 truncate font-black" :class="strong">{{ report.highlights.best_defense?.team_name || 'Sin datos' }}</p>
                            <p class="text-sm" :class="accent">{{ report.highlights.best_defense ? `${report.highlights.best_defense.value} goles recibidos` : '—' }}</p>
                        </div>
                        <div class="rounded-xl border p-4" :class="soft">
                            <p class="text-xs font-bold uppercase" :class="muted">Equipo más disciplinado</p>
                            <p class="mt-1 truncate font-black" :class="strong">{{ report.highlights.most_disciplined?.team_name || 'Sin datos' }}</p>
                            <p class="text-sm" :class="accent">{{ report.highlights.most_disciplined ? `${report.highlights.most_disciplined.value} tarjetas` : '—' }}</p>
                        </div>
                        <div class="rounded-xl border p-4" :class="soft">
                            <p class="text-xs font-bold uppercase" :class="muted">Mayor goleada</p>
                            <p class="mt-1 font-black" :class="strong">{{ report.highlights.biggest_win?.label || 'Sin datos' }}</p>
                            <p class="text-sm" :class="muted">{{ report.highlights.biggest_win ? `${report.highlights.biggest_win.phase} · ${report.highlights.biggest_win.date || 'Sin fecha'}` : '—' }}</p>
                        </div>
                        <div class="rounded-xl border p-4" :class="soft">
                            <p class="text-xs font-bold uppercase" :class="muted">Goleador</p>
                            <p class="mt-1 truncate font-black" :class="strong">{{ report.highlights.top_scorer?.name || 'Sin datos' }}</p>
                            <p class="text-sm" :class="accent">{{ report.highlights.top_scorer ? `${report.highlights.top_scorer.goals} goles · ${report.highlights.top_scorer.team_name || 'Sin equipo'}` : '—' }}</p>
                        </div>
                        <div class="rounded-xl border p-4" :class="soft">
                            <p class="text-xs font-bold uppercase" :class="muted">Más MVP</p>
                            <p class="mt-1 truncate font-black" :class="strong">{{ report.highlights.mvp_leader?.name || 'Sin datos' }}</p>
                            <p class="text-sm" :class="accent">{{ report.highlights.mvp_leader ? `${report.highlights.mvp_leader.value} premios · ${report.highlights.mvp_leader.team_name || 'Sin equipo'}` : '—' }}</p>
                        </div>
                    </div>
                </section>
            </template>
        </main>

        <!-- Selector de secciones para el PDF -->
        <div v-if="pdfOpen" class="fixed inset-0 z-50 flex items-end justify-center bg-black/60 p-3 sm:items-center" role="dialog" aria-modal="true" aria-labelledby="pdf-modal-title" @click.self="pdfOpen = false">
            <div class="w-full max-w-lg rounded-2xl border p-4 shadow-2xl sm:p-5" :class="surface">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 id="pdf-modal-title" class="text-lg font-black" :class="strong">Secciones para imprimir</h2>
                        <p class="text-sm" :class="muted">Solo las secciones marcadas aparecerán en el PDF.</p>
                    </div>
                    <button type="button" class="rounded-lg px-2 py-1 text-xl leading-none" :class="muted" aria-label="Cerrar" @click="pdfOpen = false">×</button>
                </div>

                <div class="mt-4 flex items-center justify-between gap-2">
                    <button type="button" class="text-xs font-bold uppercase tracking-wide" :class="accent" @click="toggleAll">
                        {{ allChosen ? 'Quitar todas' : 'Marcar todas' }}
                    </button>
                    <span class="text-xs font-semibold" :class="muted">{{ chosenKeys.length }} de {{ sectionList.length }} seleccionadas</span>
                </div>

                <div class="mt-2 max-h-[55vh] space-y-2 overflow-y-auto pr-1">
                    <label v-for="section in sectionList" :key="section.key" class="flex cursor-pointer items-center gap-3 rounded-xl border p-3" :class="soft">
                        <input v-model="chosen[section.key]" type="checkbox" class="h-4 w-4 rounded" :class="sportsMode ? 'border-[#2b4b5d] text-lime-400 focus:ring-lime-300' : 'text-emerald-600 focus:ring-emerald-500'" />
                        <span class="text-sm font-semibold" :class="strong">{{ section.label }}</span>
                    </label>
                </div>

                <div class="mt-4 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button type="button" class="inline-flex min-h-11 items-center justify-center rounded-xl border px-4 py-2 text-sm font-bold" :class="sportsMode ? 'border-[#2b4b5d] text-slate-200 hover:bg-[#17394a]' : 'border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800'" @click="pdfOpen = false">Cancelar</button>
                    <button type="button" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl px-5 py-2 text-sm font-black disabled:cursor-not-allowed disabled:opacity-50" :class="sportsMode ? 'bg-[#c8f169] text-[#08251a] hover:bg-[#d9fb8d]' : 'bg-emerald-700 text-white hover:bg-emerald-800'" :disabled="exporting || chosenKeys.length === 0" @click="downloadPdf">
                        <span v-if="exporting">Generando…</span>
                        <span v-else>Descargar PDF</span>
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Estilos propios de las tablas del reporte: aislados para no depender de
   las reglas globales de table/td/th de otros módulos o del tema. */
.se-season-table {
    background-color: #ffffff;
    color: #1f2937;
}
.se-season-table thead tr {
    background-color: #f3f4f6;
    color: #374151;
}
.se-season-table tbody tr {
    background-color: #ffffff;
    color: #1f2937;
    border-color: #e5e7eb;
}
.se-season-table th,
.se-season-table td {
    background-color: transparent;
    color: inherit;
}

/* Tema deportivo (PW00001): fondo oscuro explícito. */
.se-season-table.se-season-table--sports {
    background-color: #0f2533;
    color: #f1f5f9;
}
.se-season-table--sports thead tr {
    background-color: #122c3a;
    color: #cbd5e1;
}
.se-season-table--sports tbody tr {
    background-color: #0f2533;
    color: #f1f5f9;
    border-color: #234052;
}
</style>
