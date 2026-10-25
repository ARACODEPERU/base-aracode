<script setup>
import { computed, ref } from 'vue';
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import Keypad from '@/Components/Keypad.vue';
import Pagination from '@/Components/Pagination.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import Swal2 from 'sweetalert2';
import { etiquetaPuestoPremio, obtenerPremios } from '../../utils/editionPrizes.js';
import LandingVisitsModal from './Partials/LandingVisitsModal.vue';

const props = defineProps({
    editions: { type: Object, required: true, default: () => ({}) },
    sportsMode: { type: Boolean, default: false },
    filters: { type: Object, default: () => ({}) },
});

const form = useForm({ search: props.filters.search || '' });
const hasEditions = computed(() => (props.editions?.data || []).length > 0);
const filteredSearch = computed(() => String(props.filters.search || '').trim());
const assetBase = assetUrl;
const buscar = () => form.get(route('even_ediciones_listado'), { preserveState: true, preserveScroll: true });
const formato = (value) => ({
    round_robin: 'Todos contra todos',
    group_stage_and_playoffs: 'Grupos y eliminatorias',
    single_elimination: 'Eliminación simple',
    round_robin_playoff: 'Liga y eliminatorias',
    relampago: 'Relámpago',
}[String(value || '').toLowerCase()] || value || 'Formato por definir');
const estado = (value) => ({
    pending: 'Próximamente', in_progress: 'En juego', finished: 'Finalizado',
}[value] || value || 'Sin estado');
const estadoClase = (value, sports = false) => {
    if (sports) {
        return {
            pending: 'border-amber-300/40 bg-amber-400/10 text-amber-200',
            in_progress: 'border-lime-300/40 bg-lime-300/10 text-lime-200',
            finished: 'border-slate-400/30 bg-white/5 text-slate-300',
        }[value] || 'border-white/20 bg-white/10 text-white';
    }

    return {
        pending: 'border-amber-200 bg-amber-50 text-amber-800',
        in_progress: 'border-emerald-200 bg-emerald-50 text-emerald-800',
        finished: 'border-slate-200 bg-slate-100 text-slate-700',
    }[value] || 'border-white/20 bg-white/10 text-white';
};
const fecha = (value) => {
    if (!value) return 'Por confirmar';
    const date = new Date(`${String(value).slice(0, 10)}T00:00:00`);
    return Number.isNaN(date.getTime()) ? value : new Intl.DateTimeFormat('es-PE', { day: '2-digit', month: 'short', year: 'numeric' }).format(date);
};
const premios = (details) => obtenerPremios(details);
const premioLabel = (position) => Number.isInteger(position) && position > 0 ? `Premio ${etiquetaPuestoPremio(position)}` : 'Premio';
const buscaPremios = (edition) => premios(edition.prize_details);
const posicionKey = (prize, index) => `${prize.position ?? 'extra'}-${index}`;
const actionLinkClass = 'inline-flex min-h-11 w-full min-w-0 items-center justify-center gap-1.5 rounded-xl px-2 py-2 text-center text-xs font-bold leading-tight text-white transition hover:brightness-110 focus:outline-none focus:ring-2 focus:ring-emerald-400 sm:gap-2 sm:px-3 sm:text-sm';
// En Torneos todas las acciones comparten el mismo botón oscuro con borde
// acero; así la tarjeta no se convierte en un arcoíris de colores.
const accionClase = (color) => [actionLinkClass, props.sportsMode ? 'edition-action--sports' : color];
const accionPeligroClase = () => [
    actionLinkClass,
    props.sportsMode ? 'edition-action--sports edition-action--danger' : 'bg-rose-700 hover:bg-rose-800',
];
const searchId = 'editions-search';
const submitSearch = () => buscar();
const landingSlug = (edition) => (edition.public_slug || '').trim() || String(edition.id);
const visitsEdition = ref(null);
const openVisits = (edition) => { visitsEdition.value = edition; };
const closeVisits = () => { visitsEdition.value = null; };
const visitas = (edition) => Number(edition?.landing_views || 0).toLocaleString('es-PE');

const copyLandingUrl = async (edition) => {
    try {
        await navigator.clipboard.writeText(route('socialevents_torneos_landing', { slug: landingSlug(edition) }));
        await Swal2.fire({ title: 'Enlace copiado', icon: 'success', timer: 1600, showConfirmButton: false });
    } catch {
        await Swal2.fire({ title: 'No se pudo copiar', text: 'Inténtalo de nuevo.', icon: 'error' });
    }
};

const destroyEdition = async (id) => {
    const result = await Swal2.fire({
        title: '¿Eliminar esta edición?',
        text: 'Se eliminarán también los datos relacionados. Esta acción no se puede deshacer.',
        icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc2626',
        confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar',
    });
    if (!result.isConfirmed) return;

    try {
        await axios.delete(route('even_ediciones_eliminar', id));
        await Swal2.fire({ title: 'Edición eliminada', icon: 'success', timer: 1600, showConfirmButton: false });
        router.reload({ only: ['editions'] });
    } catch {
        await Swal2.fire({ title: 'No se pudo eliminar', text: 'Inténtalo de nuevo.', icon: 'error' });
    }
};

const finishEdition = async (editionId, event) => {
    const result = await Swal2.fire({
        title: '¿Finalizar edición?',
        text: 'Al finalizar ya no se podrán registrar cambios.',
        icon: 'question', showCancelButton: true,
        confirmButtonText: 'Sí, finalizar', cancelButtonText: 'Cancelar',
    });
    if (!result.isConfirmed) {
        event.target.checked = false;
        return;
    }

    try {
        await axios.put(route('even_ediciones_update_status', editionId), { status: 'finished' });
        await Swal2.fire({ title: 'Edición finalizada', icon: 'success', timer: 1600, showConfirmButton: false });
        router.reload({ only: ['editions'] });
    } catch {
        event.target.checked = false;
        await Swal2.fire({ title: 'No se pudo finalizar', text: 'Inténtalo de nuevo.', icon: 'error' });
    }
};
</script>

<template>
    <AppLayout title="Ediciones de Torneos">
        <Navigation :routeModule="route('even_dashboard')" :titleModule="sportsMode ? 'GESTIÓN DEPORTIVA' : 'Eventos sociales'" :data="[{ title: 'Ediciones' }]" />

        <main class="mx-auto mt-5 max-w-7xl space-y-5 px-3 pb-8 sm:px-5 lg:px-6" :class="sportsMode ? 'sports-editions-page' : ''">
            <section
                class="relative isolate overflow-hidden rounded-2xl border p-4 text-white sm:p-6 lg:p-8"
                :class="sportsMode
                    ? 'border-white/10 bg-[#0b1f2b] shadow-[0_26px_70px_rgba(0,0,0,0.38)]'
                    : 'border-transparent bg-gradient-to-br from-emerald-950 via-emerald-800 to-green-700 shadow-xl'"
            >
                <div v-if="sportsMode" class="hero-glow" aria-hidden="true"></div>
                <div class="relative flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
                    <div class="min-w-0 xl:flex-1">
                        <div
                            class="mb-3 inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-bold uppercase tracking-[0.18em]"
                            :class="sportsMode ? 'border-lime-300/25 bg-lime-300/10 text-lime-200' : 'border-white/20 bg-white/10 text-emerald-100'"
                        >
                            <span class="h-2 w-2 rounded-full bg-lime-300"></span> Zona de campeonato
                        </div>
                        <h1 class="text-2xl font-black tracking-tight sm:text-4xl">Ediciones <span class="text-lime-300">de torneo</span></h1>
                        <p class="mt-2 max-w-xl text-sm sm:text-base" :class="sportsMode ? 'text-slate-300' : 'text-emerald-100'">Organiza cada temporada, consulta los equipos y entra directo a la acción.</p>
                    </div>
                    <div class="flex w-full min-w-0 flex-col gap-3 xl:w-[min(100%,34rem)] xl:flex-none">
                        <form @submit.prevent="submitSearch" class="relative flex min-w-0 w-full items-center gap-2">
                            <label :for="searchId" class="sr-only">Buscar ediciones</label>
                            <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2" :class="sportsMode ? 'text-lime-300' : 'text-emerald-800'" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><circle cx="11" cy="11" r="7" stroke-width="2"/><path d="m16 16 4 4" stroke-width="2" stroke-linecap="round"/></svg>
                            <input :id="searchId" v-model="form.search" type="search" class="form-input h-12 min-w-0 flex-1 rounded-xl py-3 pl-10 pr-3 text-sm shadow-lg focus:ring-2" :class="sportsMode ? 'border border-[#2b4b5d] bg-[#122c3a] text-slate-100 placeholder:text-slate-400 focus:border-lime-300 focus:ring-lime-300/30' : 'border-2 border-gray-300 bg-white text-gray-900 placeholder:text-gray-600 focus:border-sky-500 focus:ring-sky-500'" placeholder="Buscar torneo o temporada" />
                            <button type="submit" class="inline-flex h-12 shrink-0 items-center justify-center rounded-xl px-4 text-sm font-bold shadow-lg transition focus:outline-none focus:ring-2" :class="sportsMode ? 'bg-[#c8f169] text-[#08251a] hover:bg-[#d9fb8d] focus:ring-lime-300' : 'bg-sky-600 text-white hover:bg-sky-500 focus:ring-white'">Buscar</button>
                        </form>
                        <div class="w-full min-w-0">
                            <Keypad>
                                <template #botones>
                                    <Link v-can="'even_ediciones_nuevo'" :href="route('even_ediciones_nuevo')" class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-xl px-4 py-3 text-sm font-extrabold shadow-lg transition focus:outline-none focus:ring-2" :class="sportsMode ? 'bg-[#c8f169] text-[#08251a] hover:bg-[#d9fb8d] focus:ring-lime-300' : 'bg-lime-300 text-emerald-950 hover:bg-lime-200 focus:ring-white'">
                                        <span class="text-xl leading-none" aria-hidden="true">＋</span><span>Nueva edición</span>
                                    </Link>
                                </template>
                            </Keypad>
                        </div>
                    </div>
                </div>
                <div class="relative mt-6 flex flex-wrap items-center gap-2 text-xs font-semibold text-white sm:text-sm">
                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full" :class="sportsMode ? 'border border-[#2b4b5d] bg-[#122c3a] text-lime-300' : 'bg-white/10'" aria-hidden="true">⚽</span>
                    <span>{{ editions.total || 0 }} {{ (editions.total || 0) === 1 ? 'edición registrada' : 'ediciones registradas' }}</span>
                    <Link v-if="filteredSearch" :href="route('even_ediciones_listado')" class="ml-auto inline-flex items-center gap-1 rounded-full border px-3 py-1 text-xs font-semibold transition" :class="sportsMode ? 'border-[#2b4b5d] text-slate-300 hover:border-lime-300/60 hover:text-lime-200' : 'border-white/25 text-emerald-100 hover:bg-white/10'">
                        Limpiar búsqueda
                        <span aria-hidden="true">×</span>
                    </Link>
                </div>
            </section>

            <section v-if="hasEditions" aria-label="Ediciones disponibles" class="grid grid-cols-1 gap-4 md:grid-cols-2 2xl:grid-cols-3">
                <article v-for="edition in editions.data" :key="edition.id" class="edition-card group overflow-hidden rounded-2xl border transition duration-200 hover:-translate-y-1" :class="sportsMode ? 'border-[#234052] bg-[#0b1f2b] text-slate-100 shadow-[0_18px_50px_rgba(0,0,0,0.28)] hover:border-lime-300/50 hover:shadow-[0_24px_60px_rgba(0,0,0,0.4)]' : 'border-gray-200 bg-white shadow-sm hover:shadow-xl dark:border-gray-700 dark:bg-gray-900'">
                    <header class="relative isolate overflow-hidden p-5 text-white" :class="sportsMode ? 'bg-gradient-to-br from-[#0b1f2b] via-[#0e2532] to-[#122c3a]' : 'bg-gradient-to-br from-emerald-950 to-emerald-700'">
                        <div class="pitch-lines" aria-hidden="true"><span></span></div>
                        <div class="relative flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="mb-1 truncate text-xs font-semibold uppercase tracking-wider" :class="sportsMode ? 'text-slate-400' : 'text-emerald-200'">{{ edition.evento?.title || 'Torneo' }}</p>
                                <h2 class="break-words text-xl font-extrabold leading-tight sm:text-2xl">{{ edition.name }}</h2>
                            </div>
                            <span class="shrink-0 rounded-lg bg-lime-300 px-3 py-1.5 text-sm font-black text-emerald-950">{{ edition.year }}</span>
                        </div>
                        <div class="relative mt-4 flex flex-wrap items-center gap-2">
                            <span class="rounded-full border px-3 py-1 text-xs font-bold" :class="estadoClase(edition.status, sportsMode)">{{ estado(edition.status) }}</span>
                            <span v-if="edition.landing_published" class="rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-semibold">Landing pública</span>
                            <span v-if="edition.landing_published" class="inline-flex items-center gap-1.5 rounded-full border border-sky-200/50 bg-sky-300/15 px-3 py-1 text-xs font-bold text-sky-50" :title="`${visitas(edition)} visitas registradas en la landing`">
                                <span aria-hidden="true">👁️</span>{{ visitas(edition) }} visitas
                            </span>
                            <span v-if="edition.mobile_enabled !== false" class="rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-semibold">App móvil</span>
                        </div>
                    </header>

                    <div class="space-y-4 p-4 sm:p-5">
                        <div class="grid grid-cols-2 gap-3">
                            <div class="edition-meta rounded-xl p-3" :class="sportsMode ? 'border border-[#2b4b5d] bg-[#122c3a]' : 'bg-gray-50 dark:bg-gray-800'">
                                <p class="text-[11px] font-bold uppercase tracking-wide" :class="sportsMode ? 'text-slate-400' : 'text-gray-700 dark:text-gray-400'">Temporada</p>
                                <p class="mt-1 text-sm font-semibold" :class="sportsMode ? 'text-white' : 'text-gray-900 dark:text-white'">{{ fecha(edition.start_date) }}</p>
                                <p class="text-xs" :class="sportsMode ? 'text-slate-400' : 'text-gray-700 dark:text-gray-400'">al {{ fecha(edition.end_date) }}</p>
                            </div>
                            <div class="edition-meta rounded-xl p-3" :class="sportsMode ? 'border border-[#2b4b5d] bg-[#122c3a]' : 'bg-gray-50 dark:bg-gray-800'">
                                <p class="text-[11px] font-bold uppercase tracking-wide" :class="sportsMode ? 'text-slate-400' : 'text-gray-700 dark:text-gray-400'">Equipos</p>
                                <p class="mt-1 text-2xl font-black" :class="sportsMode ? 'text-lime-300' : 'text-emerald-700 dark:text-emerald-300'">{{ edition.equipos?.length || 0 }}<span class="ml-1 text-xs font-semibold" :class="sportsMode ? 'text-slate-400' : 'text-gray-700 dark:text-gray-400'">inscritos</span></p>
                            </div>
                        </div>
                        <div v-if="edition.landing_published" class="flex flex-wrap items-center justify-between gap-3 rounded-xl border p-3" :class="sportsMode ? 'border-[#2b4b5d] bg-[#122c3a]' : 'border-sky-100 bg-sky-50/70 dark:border-sky-900 dark:bg-sky-950/30'">
                            <div class="flex min-w-0 items-center gap-2.5">
                                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full text-base" :class="sportsMode ? 'border border-[#2b4b5d] bg-[#17394a]' : 'bg-white text-sky-700 shadow-sm dark:bg-sky-900/60 dark:text-sky-200'" aria-hidden="true">👁️</span>
                                <div class="min-w-0">
                                    <p class="text-[11px] font-bold uppercase tracking-wide" :class="sportsMode ? 'text-slate-400' : 'text-sky-800 dark:text-sky-300'">Visitas a la landing</p>
                                    <p class="text-lg font-black leading-tight" :class="sportsMode ? 'text-white' : 'text-gray-900 dark:text-white'">{{ visitas(edition) }} <span class="text-xs font-semibold" :class="sportsMode ? 'text-slate-400' : 'text-gray-600 dark:text-gray-300'">visualizaciones</span></p>
                                </div>
                            </div>
                            <button type="button" class="inline-flex min-h-10 items-center justify-center rounded-xl px-3 py-2 text-xs font-bold transition focus:outline-none focus:ring-2" :class="sportsMode ? 'border border-[#2b4b5d] bg-[#17394a] text-lime-200 hover:border-lime-300/60 hover:text-lime-100 focus:ring-lime-300/40' : 'border border-sky-200 bg-white text-sky-800 hover:bg-sky-100 focus:ring-sky-400 dark:border-sky-800 dark:bg-sky-900/50 dark:text-sky-100'" @click="openVisits(edition)">
                                Ver detalle
                            </button>
                        </div>
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="inline-flex max-w-full items-center gap-2 rounded-full px-3 py-1.5 text-xs font-bold" :class="sportsMode ? 'bg-[#17394a] text-lime-200' : 'bg-emerald-50 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200'">
                                <span aria-hidden="true">🏆</span><span class="truncate">{{ formato(edition.competition_format) }}</span>
                            </span>
                            <span class="text-sm font-bold" :class="sportsMode ? 'text-slate-200' : 'text-gray-800 dark:text-gray-100'">Inscripción: S/ {{ edition.inscription_fee ?? '0.00' }}</span>
                        </div>
                        <div v-if="premios(edition.prize_details).length" class="prizes-panel rounded-xl border p-3" :class="sportsMode ? 'border-amber-300/30 bg-amber-300/5' : 'border-amber-300 bg-amber-50 dark:border-amber-900 dark:bg-amber-950/20'">
                            <p class="mb-2 text-xs font-extrabold uppercase tracking-wide" :class="sportsMode ? 'text-amber-200' : 'text-amber-800 dark:text-amber-300'">🥇 Premios</p>
                            <div class="flex flex-wrap gap-2">
                                <div v-for="(prize, index) in buscaPremios(edition)" :key="posicionKey(prize, index)" class="min-w-0 flex-1 rounded-lg border px-3 py-2" :class="sportsMode ? 'border-amber-300/25 bg-[#122c3a]' : 'border-amber-100 bg-white dark:border-amber-900 dark:bg-gray-900'">
                                    <p class="text-xs font-bold" :class="sportsMode ? 'text-amber-200' : 'text-amber-800 dark:text-amber-300'">{{ premioLabel(prize.position) }}</p>
                                    <p v-if="prize.money" class="truncate text-sm font-extrabold" :class="sportsMode ? 'text-white' : 'text-gray-900 dark:text-white'">S/ {{ prize.money }}</p>
                                    <p v-if="prize.gift" class="truncate text-xs font-medium" :class="sportsMode ? 'text-slate-300' : 'text-gray-700 dark:text-gray-300'">{{ prize.gift }}</p>
                                </div>
                            </div>
                        </div>
                        <p v-if="edition.details" class="line-clamp-3 text-sm leading-relaxed" :class="sportsMode ? 'text-slate-300' : 'text-gray-600 dark:text-gray-300'" v-html="edition.details"></p>
                        <a v-if="edition.path_database_file" :href="assetBase + 'storage/' + edition.path_database_file" :download="edition.name_database_file" class="inline-flex max-w-full items-center gap-2 text-sm font-semibold hover:underline" :class="sportsMode ? 'text-lime-300 hover:text-lime-200' : 'text-emerald-700 dark:text-emerald-300'">
                            <span aria-hidden="true">📄</span><span class="truncate">Bases: {{ edition.name_database_file || 'Descargar documento' }}</span>
                        </a>

                        <div class="edition-actions grid min-w-0 grid-cols-2 gap-2 border-t pt-4 sm:grid-cols-3" :class="sportsMode ? 'border-[#234052]' : 'border-gray-200 dark:border-gray-700'">
                            <Link v-can="'even_ediciones_equipos'" :href="route('even_ediciones_equipos', edition.id)" :class="accionClase('bg-emerald-700 hover:bg-emerald-800')">⚽ Equipos</Link>
                            <Link v-can="'even_ediciones_fixtures'" :href="route('even_ediciones_fixtures', edition.id)" :class="accionClase('bg-sky-700 hover:bg-sky-800')">📅 Partidos</Link>
                            <Link v-can="'even_ediciones_sanciones'" :href="route('even_ediciones_pago_sanciones', edition.id)" :class="accionClase('bg-violet-700 hover:bg-violet-800')">📊 Sanciones</Link>
                            <Link v-can="'even_ediciones_actas'" :href="route('even_ediciones_actas_listado', edition.id)" :class="accionClase('bg-slate-700 hover:bg-slate-800')">📝 Actas</Link>
                            <Link v-can="'even_ediciones_galeria'" :href="route('even_ediciones_galeria', edition.id)" :class="accionClase('bg-indigo-700 hover:bg-indigo-800')">📸 Galería</Link>
                            <Link v-can="'even_ediciones_editar'" :href="route('even_ediciones_editar', edition.id)" :class="accionClase('bg-gray-700 hover:bg-gray-800')">✏️ Editar</Link>
                            <a v-if="edition.landing_published" :href="route('socialevents_torneos_landing', { slug: landingSlug(edition) })" target="_blank" rel="noopener" :class="accionClase('bg-blue-700 hover:bg-blue-800')">🌐 Ver torneo</a>
                            <button v-if="edition.landing_published" type="button" :class="accionClase('bg-cyan-700 hover:bg-cyan-800')" @click="copyLandingUrl(edition)">🔗 Copiar enlace</button>
                            <button v-can="'even_ediciones_eliminar'" type="button" :class="accionPeligroClase()" @click="destroyEdition(edition.id)">🗑️ Eliminar</button>
                        </div>
                        <div v-if="edition.status === 'in_progress'" class="flex items-center justify-between rounded-xl px-3 py-2" :class="sportsMode ? 'border border-[#2b4b5d] bg-[#122c3a]' : 'bg-emerald-50 dark:bg-emerald-950/30'">
                            <span class="text-sm font-semibold" :class="sportsMode ? 'text-slate-200' : 'text-emerald-900 dark:text-emerald-200'">Finalizar campeonato</span>
                            <label class="relative inline-flex cursor-pointer items-center" aria-label="Finalizar edición">
                                <input type="checkbox" class="peer sr-only" @change="finishEdition(edition.id, $event)" />
                                <span class="h-6 w-11 rounded-full bg-gray-300 transition peer-checked:bg-emerald-600 peer-focus:ring-4 peer-focus:ring-emerald-200 after:absolute after:left-0.5 after:top-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:transition peer-checked:after:translate-x-5"></span>
                            </label>
                        </div>
                    </div>
                </article>
            </section>

            <section v-else class="rounded-2xl border border-dashed px-5 py-12 text-center" :class="sportsMode ? 'border-[#2b4b5d] bg-[#0b1f2b] shadow-[0_22px_60px_rgba(0,0,0,0.32)]' : 'border-emerald-500 bg-white shadow-sm dark:border-emerald-900 dark:bg-gray-900'">
                <div class="mx-auto grid h-16 w-16 place-items-center rounded-full text-3xl" :class="sportsMode ? 'border border-[#2b4b5d] bg-[#122c3a] text-lime-300' : 'bg-emerald-100 dark:bg-emerald-900/50'" aria-hidden="true">⚽</div>
                <h2 class="mt-4 text-lg font-extrabold" :class="sportsMode ? 'text-white' : 'text-gray-900 dark:text-white'">{{ filteredSearch ? 'No encontramos ese torneo' : 'Todavía no hay ediciones' }}</h2>
                <p class="mx-auto mt-1 max-w-md text-sm" :class="sportsMode ? 'text-slate-300' : 'text-gray-700 dark:text-gray-400'">{{ filteredSearch ? 'Prueba con otro nombre o temporada, o consulta todas las ediciones disponibles.' : 'Cuando crees una edición, aquí tendrás a mano todos los datos del campeonato.' }}</p>
                <div class="mt-5 flex flex-wrap items-center justify-center gap-3">
                    <Link v-if="filteredSearch" :href="route('even_ediciones_listado')" class="inline-flex min-h-11 items-center justify-center rounded-xl border px-5 py-2.5 text-sm font-semibold transition" :class="sportsMode ? 'border-[#2b4b5d] text-slate-200 hover:border-lime-300/60 hover:text-lime-200' : 'border-emerald-600 text-emerald-800 hover:bg-emerald-50 dark:border-emerald-700 dark:text-emerald-200'">Ver todas las ediciones</Link>
                    <Link v-can="'even_ediciones_nuevo'" :href="route('even_ediciones_nuevo')" class="inline-flex min-h-11 items-center justify-center rounded-xl px-5 py-2.5 font-bold transition" :class="sportsMode ? 'bg-[#c8f169] text-[#08251a] hover:bg-[#d9fb8d]' : 'bg-emerald-700 text-white hover:bg-emerald-800'">＋ Nueva edición</Link>
                </div>
            </section>

            <!-- Sin ediciones el paginador solo añade ruido; aparece cuando hay resultados que navegar. -->
            <div v-if="editions?.links?.length && hasEditions" class="overflow-x-auto"><Pagination :data="editions" /></div>
        </main>

        <LandingVisitsModal
            :open="!!visitsEdition"
            :edition="visitsEdition"
            :sports-mode="sportsMode"
            @close="closeVisits"
        />
    </AppLayout>
</template>

<style scoped>
/* La página hereda los controles del tema con la superficie azul noche del
   prototipo, incluido el buscador y los selects de las tarjetas. */
.sports-editions-page {
    --torneos-control-bg: #122c3a;
    --torneos-control-border: #2b4b5d;
    --torneos-control-hover-border: #3f6678;
    --torneos-control-focus-border: #b8f34a;
    --torneos-control-focus-ring: rgb(184 243 74 / 22%);
    --torneos-control-text: #e9f4f2;
    --torneos-control-placeholder: #7d9ba4;
    --torneos-control-icon: #b8f34a;
}

/* Halo lima del prototipo sobre la cabecera azul noche. */
.sports-editions-page .hero-glow {
    position: absolute;
    inset: 0;
    pointer-events: none;
    background: radial-gradient(70% 130% at 88% -10%, rgb(184 243 74 / 12%), transparent 62%);
}

/* Acciones uniformes de la tarjeta en el tema Torneos. */
.sports-editions-page .edition-action--sports {
    border: 1px solid #2b4b5d;
    background-color: #122c3a;
    color: #e9f4f2;
}

.sports-editions-page .edition-action--sports:hover {
    border-color: rgb(184 243 74 / 60%);
    background-color: #17394a;
    color: #d9f99d;
}

.sports-editions-page .edition-action--danger {
    border-color: rgb(248 113 113 / 35%);
    background-color: rgb(69 10 10 / 45%);
    color: #fecaca;
}

.sports-editions-page .edition-action--danger:hover {
    border-color: rgb(248 113 113 / 70%);
    background-color: rgb(127 29 29 / 55%);
    color: #fee2e2;
}

/* El botón «Panel de inicio» del pie sigue el estilo fantasma del formulario. */
:global(.sports-editions-page .btn-info) {
    background-color: transparent;
    background-image: none;
    border: 1px solid rgb(148 178 190 / 34%);
    border-radius: 0.75rem;
    color: #cfe2e4;
    font-weight: 600;
    letter-spacing: 0.08em;
    box-shadow: none;
}

:global(.sports-editions-page .btn-info:hover) {
    background-color: rgb(184 243 74 / 10%);
    border-color: rgb(184 243 74 / 45%);
    color: #d9f99d;
}

.pitch-lines {
    position: absolute;
    z-index: -1;
    right: -1.5rem;
    top: -3.5rem;
    width: 12rem;
    height: 12rem;
    border: 1px solid rgb(255 255 255 / 12%);
    border-radius: 9999px;
}
.pitch-lines::before,
.pitch-lines::after,
.pitch-lines span {
    position: absolute;
    border: 1px solid rgb(255 255 255 / 12%);
    border-radius: 9999px;
    content: '';
}
.pitch-lines::before { inset: 1.5rem; }
.pitch-lines::after { inset: 3rem; }
.pitch-lines span { inset: 4.5rem; }
</style>
