<script setup>
import { computed } from 'vue';
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import Keypad from '@/Components/Keypad.vue';
import Pagination from '@/Components/Pagination.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import Swal2 from 'sweetalert2';
import { etiquetaPuestoPremio, obtenerPremios } from '../../utils/editionPrizes.js';

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
const estadoClase = (value) => ({
    pending: 'border-amber-200 bg-amber-50 text-amber-800',
    in_progress: 'border-emerald-200 bg-emerald-50 text-emerald-800',
    finished: 'border-slate-200 bg-slate-100 text-slate-700',
}[value] || 'border-white/20 bg-white/10 text-white');
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
const searchId = 'editions-search';
const submitSearch = () => buscar();
const landingSlug = (edition) => (edition.public_slug || '').trim() || String(edition.id);

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
            <section class="overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-950 via-emerald-800 to-green-700 p-4 text-white shadow-xl sm:p-6 lg:p-8">
                <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
                    <div class="min-w-0 xl:flex-1">
                        <div class="mb-3 inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-bold uppercase tracking-[0.18em] text-emerald-100">
                            <span class="h-2 w-2 rounded-full bg-lime-300"></span> Zona de campeonato
                        </div>
                        <h1 class="text-2xl font-black tracking-tight sm:text-4xl">Ediciones <span class="text-lime-300">de torneo</span></h1>
                        <p class="mt-2 max-w-xl text-sm text-emerald-100 sm:text-base">Organiza cada temporada, consulta los equipos y entra directo a la acción.</p>
                    </div>
                    <div class="flex w-full min-w-0 flex-col gap-3 xl:w-[min(100%,34rem)] xl:flex-none">
                        <form @submit.prevent="submitSearch" class="relative flex min-w-0 w-full items-center gap-2">
                            <label :for="searchId" class="sr-only">Buscar ediciones</label>
                            <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-emerald-800" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><circle cx="11" cy="11" r="7" stroke-width="2"/><path d="m16 16 4 4" stroke-width="2" stroke-linecap="round"/></svg>
                            <input :id="searchId" v-model="form.search" type="search" class="form-input h-12 min-w-0 flex-1 rounded-xl border-2 bg-white py-3 pl-10 pr-3 text-sm text-gray-900 shadow-lg placeholder:text-gray-700 focus:ring-2" :class="sportsMode ? 'border-lime-300 placeholder:text-gray-700 focus:border-lime-200 focus:ring-lime-300' : 'border-gray-300 placeholder:text-gray-600 focus:border-sky-500 focus:ring-sky-500'" placeholder="Buscar torneo o temporada" />
                            <button type="submit" class="inline-flex h-12 shrink-0 items-center justify-center rounded-xl bg-sky-600 px-4 text-sm font-bold text-white shadow-lg transition hover:bg-sky-500 focus:outline-none focus:ring-2 focus:ring-white">Buscar</button>
                        </form>
                        <div class="w-full min-w-0">
                            <Keypad>
                                <template #botones>
                                    <Link v-can="'even_ediciones_nuevo'" :href="route('even_ediciones_nuevo')" class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-xl bg-lime-300 px-4 py-3 text-sm font-extrabold text-emerald-950 shadow-lg transition hover:bg-lime-200 focus:outline-none focus:ring-2 focus:ring-white">
                                        <span class="text-xl leading-none" aria-hidden="true">＋</span><span>Nueva edición</span>
                                    </Link>
                                </template>
                            </Keypad>
                        </div>
                    </div>
                </div>
                <div class="mt-6 flex items-center gap-2 text-xs font-semibold text-white sm:text-sm">
                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-white/10" aria-hidden="true">⚽</span>
                    <span>{{ editions.total || 0 }} {{ (editions.total || 0) === 1 ? 'edición registrada' : 'ediciones registradas' }}</span>
                </div>
            </section>

            <section v-if="hasEditions" aria-label="Ediciones disponibles" class="grid grid-cols-1 gap-4 md:grid-cols-2 2xl:grid-cols-3">
                <article v-for="edition in editions.data" :key="edition.id" class="edition-card group overflow-hidden rounded-2xl border bg-white transition duration-200 hover:-translate-y-1 hover:shadow-xl dark:border-gray-700 dark:bg-gray-900" :class="sportsMode ? 'border-emerald-300 shadow-md hover:border-emerald-500' : 'border-gray-200 shadow-sm'">
                    <header class="relative isolate overflow-hidden bg-gradient-to-br from-emerald-950 to-emerald-700 p-5 text-white">
                        <div class="pitch-lines" aria-hidden="true"><span></span></div>
                        <div class="relative flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="mb-1 truncate text-xs font-semibold uppercase tracking-wider text-emerald-200">{{ edition.evento?.title || 'Torneo' }}</p>
                                <h2 class="break-words text-xl font-extrabold leading-tight sm:text-2xl">{{ edition.name }}</h2>
                            </div>
                            <span class="shrink-0 rounded-lg bg-lime-300 px-3 py-1.5 text-sm font-black text-emerald-950">{{ edition.year }}</span>
                        </div>
                        <div class="relative mt-4 flex flex-wrap items-center gap-2">
                            <span class="rounded-full border px-3 py-1 text-xs font-bold" :class="estadoClase(edition.status)">{{ estado(edition.status) }}</span>
                            <span v-if="edition.landing_published" class="rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-semibold">Landing pública</span>
                            <span v-if="edition.mobile_enabled !== false" class="rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-semibold">App móvil</span>
                        </div>
                    </header>

                    <div class="space-y-4 p-4 sm:p-5">
                        <div class="grid grid-cols-2 gap-3">
                            <div class="edition-meta rounded-xl p-3 dark:bg-gray-800" :class="sportsMode ? 'bg-emerald-50' : 'bg-gray-50'">
                                <p class="text-[11px] font-bold uppercase tracking-wide text-gray-700 dark:text-gray-400">Temporada</p>
                                <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ fecha(edition.start_date) }}</p>
                                <p class="text-xs text-gray-700 dark:text-gray-400">al {{ fecha(edition.end_date) }}</p>
                            </div>
                            <div class="edition-meta rounded-xl p-3 dark:bg-gray-800" :class="sportsMode ? 'bg-emerald-50' : 'bg-gray-50'">
                                <p class="text-[11px] font-bold uppercase tracking-wide text-gray-700 dark:text-gray-400">Equipos</p>
                                <p class="mt-1 text-2xl font-black text-emerald-700 dark:text-emerald-300">{{ edition.equipos?.length || 0 }}<span class="ml-1 text-xs font-semibold text-gray-700 dark:text-gray-400">inscritos</span></p>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="inline-flex max-w-full items-center gap-2 rounded-full px-3 py-1.5 text-xs font-bold dark:bg-emerald-900/40 dark:text-emerald-200" :class="sportsMode ? 'bg-emerald-100 text-emerald-950' : 'bg-emerald-50 text-emerald-800'">
                                <span aria-hidden="true">🏆</span><span class="truncate">{{ formato(edition.competition_format) }}</span>
                            </span>
                            <span class="text-sm font-bold text-gray-800 dark:text-gray-100">Inscripción: S/ {{ edition.inscription_fee ?? '0.00' }}</span>
                        </div>
                        <div v-if="premios(edition.prize_details).length" class="prizes-panel rounded-xl border border-amber-300 bg-amber-50 p-3 dark:border-amber-900 dark:bg-amber-950/20">
                            <p class="mb-2 text-xs font-extrabold uppercase tracking-wide text-amber-800 dark:text-amber-300">🥇 Premios</p>
                            <div class="flex flex-wrap gap-2">
                                <div v-for="(prize, index) in buscaPremios(edition)" :key="posicionKey(prize, index)" class="min-w-0 flex-1 rounded-lg border bg-white px-3 py-2 dark:border-amber-900 dark:bg-gray-900" :class="sportsMode ? 'border-amber-300' : 'border-amber-100'">
                                    <p class="text-xs font-bold text-amber-800 dark:text-amber-300">{{ premioLabel(prize.position) }}</p>
                                    <p v-if="prize.money" class="truncate text-sm font-extrabold text-gray-900 dark:text-white">S/ {{ prize.money }}</p>
                                    <p v-if="prize.gift" class="truncate text-xs font-medium text-gray-700 dark:text-gray-300">{{ prize.gift }}</p>
                                </div>
                            </div>
                        </div>
                        <p v-if="edition.details" class="line-clamp-3 text-sm leading-relaxed dark:text-gray-300" :class="sportsMode ? 'text-gray-800' : 'text-gray-600'" v-html="edition.details"></p>
                        <a v-if="edition.path_database_file" :href="assetBase + 'storage/' + edition.path_database_file" :download="edition.name_database_file" class="inline-flex max-w-full items-center gap-2 text-sm font-semibold text-emerald-700 hover:underline dark:text-emerald-300">
                            <span aria-hidden="true">📄</span><span class="truncate">Bases: {{ edition.name_database_file || 'Descargar documento' }}</span>
                        </a>

                        <div class="edition-actions grid min-w-0 grid-cols-2 gap-2 border-t border-gray-200 pt-4 dark:border-gray-700 sm:grid-cols-3">
                            <Link v-can="'even_ediciones_equipos'" :href="route('even_ediciones_equipos', edition.id)" :class="[actionLinkClass, 'bg-emerald-700 hover:bg-emerald-800']">⚽ Equipos</Link>
                            <Link v-can="'even_ediciones_fixtures'" :href="route('even_ediciones_fixtures', edition.id)" :class="[actionLinkClass, 'bg-sky-700 hover:bg-sky-800']">📅 Partidos</Link>
                            <Link v-can="'even_ediciones_sanciones'" :href="route('even_ediciones_pago_sanciones', edition.id)" :class="[actionLinkClass, 'bg-violet-700 hover:bg-violet-800']">📊 Sanciones</Link>
                            <Link v-can="'even_ediciones_actas'" :href="route('even_ediciones_actas_listado', edition.id)" :class="[actionLinkClass, 'bg-slate-700 hover:bg-slate-800']">📝 Actas</Link>
                            <Link v-can="'even_ediciones_galeria'" :href="route('even_ediciones_galeria', edition.id)" :class="[actionLinkClass, 'bg-indigo-700 hover:bg-indigo-800']">📸 Galería</Link>
                            <Link v-can="'even_ediciones_editar'" :href="route('even_ediciones_editar', edition.id)" :class="[actionLinkClass, 'bg-gray-700 hover:bg-gray-800']">✏️ Editar</Link>
                            <a v-if="edition.landing_published" :href="route('socialevents_torneos_landing', { slug: landingSlug(edition) })" target="_blank" rel="noopener" :class="[actionLinkClass, 'bg-blue-700 hover:bg-blue-800']">🌐 Ver torneo</a>
                            <button v-if="edition.landing_published" type="button" :class="[actionLinkClass, 'bg-cyan-700 hover:bg-cyan-800']" @click="copyLandingUrl(edition)">🔗 Copiar enlace</button>
                            <button v-can="'even_ediciones_eliminar'" type="button" :class="[actionLinkClass, 'bg-rose-700 hover:bg-rose-800']" @click="destroyEdition(edition.id)">🗑️ Eliminar</button>
                        </div>
                        <div v-if="edition.status === 'in_progress'" class="flex items-center justify-between rounded-xl bg-emerald-50 px-3 py-2 dark:bg-emerald-950/30">
                            <span class="text-sm font-semibold text-emerald-900 dark:text-emerald-200">Finalizar campeonato</span>
                            <label class="relative inline-flex cursor-pointer items-center" aria-label="Finalizar edición">
                                <input type="checkbox" class="peer sr-only" @change="finishEdition(edition.id, $event)" />
                                <span class="h-6 w-11 rounded-full bg-gray-300 transition peer-checked:bg-emerald-600 peer-focus:ring-4 peer-focus:ring-emerald-200 after:absolute after:left-0.5 after:top-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:transition peer-checked:after:translate-x-5"></span>
                            </label>
                        </div>
                    </div>
                </article>
            </section>

            <section v-else class="rounded-2xl border border-dashed border-emerald-500 bg-white px-5 py-12 text-center shadow-sm dark:border-emerald-900 dark:bg-gray-900">
                <div class="mx-auto grid h-16 w-16 place-items-center rounded-full bg-emerald-100 text-3xl dark:bg-emerald-900/50" aria-hidden="true">⚽</div>
                <h2 class="mt-4 text-lg font-extrabold text-gray-900 dark:text-white">{{ filteredSearch ? 'No encontramos ese torneo' : 'Todavía no hay ediciones' }}</h2>
                <p class="mx-auto mt-1 max-w-md text-sm text-gray-700 dark:text-gray-400">{{ filteredSearch ? 'Prueba con otro nombre o temporada.' : 'Cuando crees una edición, aquí tendrás a mano todos los datos del campeonato.' }}</p>
                <Link v-if="!filteredSearch" v-can="'even_ediciones_nuevo'" :href="route('even_ediciones_nuevo')" class="mt-5 inline-flex min-h-11 items-center justify-center rounded-xl bg-emerald-700 px-5 py-2.5 font-bold text-white hover:bg-emerald-800">＋ Nueva edición</Link>
            </section>

            <div v-if="editions?.links?.length" class="overflow-x-auto"><Pagination :data="editions" /></div>
        </main>
    </AppLayout>
</template>

<style scoped>
.sports-editions-page .edition-meta {
    border-color: #6ee7b7;
}
.sports-editions-page .prizes-panel {
    border-color: #d97706;
}
.sports-editions-page .prizes-panel > div > div {
    border-color: #fcd34d;
}
:global(.dark .sports-editions-page .edition-meta),
:global(.dark .sports-editions-page .prizes-panel),
:global(.dark .sports-editions-page .prizes-panel > div > div) {
    border-color: revert-layer;
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
.edition-action {
    display: inline-flex;
    min-height: 2.75rem;
    min-width: 0;
    align-items: center;
    justify-content: center;
    overflow-wrap: anywhere;
    border-radius: 0.75rem;
    padding: 0.5rem 0.65rem;
    color: white;
    font-size: 0.75rem;
    font-weight: 700;
    line-height: 1rem;
    text-align: center;
    transition: background-color 150ms ease;
}
.sports-editions-page .edition-meta {
    border: 1px solid #6ee7b7;
}
.sports-editions-page .prizes-panel {
    border-color: #d97706;
}
.sports-editions-page .prizes-panel > div > div {
    border-color: #fcd34d;
}
.sports-editions-page .edition-actions {
    border-color: #a7f3d0;
}
:global(.dark .sports-editions-page .edition-meta),
:global(.dark .sports-editions-page .prizes-panel),
:global(.dark .sports-editions-page .prizes-panel > div > div),
:global(.dark .sports-editions-page .edition-actions) {
    border-color: revert-layer;
}
:global(body:not(.dark) .sports-editions-page .edition-card) {
    border-color: #047857;
}
:global(body:not(.dark) .sports-editions-page .edition-card:hover) {
    border-color: #065f46;
}
:global(body:not(.dark) .sports-editions-page .edition-meta) {
    border-color: #047857;
    background-color: #f0fdf4;
}
:global(body:not(.dark) .sports-editions-page .edition-meta > p:first-child) {
    color: #166534;
}
:global(body:not(.dark) .sports-editions-page .prizes-panel) {
    border-color: #b45309;
    background-color: #fffbeb;
}
:global(body:not(.dark) .sports-editions-page .prizes-panel > div > div) {
    border-color: #d97706;
}
:global(body:not(.dark) .sports-editions-page .edition-actions) {
    border-color: #047857;
}
</style>
