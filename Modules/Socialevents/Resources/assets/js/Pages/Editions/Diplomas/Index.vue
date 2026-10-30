<script setup>
import { computed, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import Swal2 from 'sweetalert2';
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import AwardFormModal from './Partials/AwardFormModal.vue';
import DiplomaPreviewModal from './Partials/DiplomaPreviewModal.vue';
import { downloadBatchPdf } from '../../../utils/diplomaRender';

/**
 * Panel de diplomas de una edición.
 *
 * Dos secciones: los premiados (equipos y jugadores), donde se elige el modelo
 * del torneo y se emite el diploma; y la biblioteca de modelos reutilizables.
 */
const props = defineProps({
    sportsMode: { type: Boolean, default: false },
    edition: { type: Object, required: true },
    eventId: { type: [Number, String], default: null },
    awards: { type: Array, default: () => [] },
    templates: { type: Array, default: () => [] },
    availableTemplates: { type: Array, default: () => [] },
    awardCatalog: { type: Object, default: () => ({}) },
    teams: { type: Array, default: () => [] },
    players: { type: Array, default: () => [] },
    suggestions: { type: Object, default: () => ({}) },
});

const tab = ref('awards');

const showAwardModal = ref(false);
const editingAward = ref(null);
const savingAward = ref(false);
const preselectedKey = ref(null);
const preselectedCandidate = ref(null);

const showCreateTemplate = ref(false);
const creatingTemplate = ref(false);
const templateForm = ref({ diploma_type: 'team', name: '', file: null });

const previewOpen = ref(false);
const previewPayload = ref(null);
const previewBusy = ref(false);

const busyAwardId = ref(null);
const printingLabel = ref(null);

const suggestionsOpen = ref(false);
const suggestionsLoading = ref(false);
const candidates = ref(props.suggestions || {});
const suggestionKey = ref('champion');

const hasAwards = computed(() => props.awards.length > 0);
const readyAwards = computed(() => props.awards.filter((award) => award.has_template));
const teamAwards = computed(() => props.awards.filter((award) => award.recipient_type === 'team'));
const playerAwards = computed(() => props.awards.filter((award) => award.recipient_type !== 'team'));

/** Modelos vigentes que puede usar un premiado según su tipo. */
const templatesFor = (award) =>
    props.availableTemplates.filter(
        (template) => template.is_active && template.diploma_type === award.diploma_type
    );

/** El catálogo no trae cuarto puesto: se usa una clave propia del torneo. */
const placeKey = (place) =>
    ({ 1: 'champion', 2: 'runner_up', 3: 'third_place', 4: 'fourth_place' }[place] || 'champion');

/** Filas sugeridas de la clasificación que aún no tienen premio. */
const suggestedTeams = computed(() => {
    const rows = candidates.value.champion || [];
    const taken = new Set(teamAwards.value.map((award) => award.team_id));

    return rows
        .filter((row) => row.team_id && row.place >= 1 && row.place <= 4 && !taken.has(row.team_id))
        .slice(0, 4)
        .map((row) => ({ ...row, award_key: placeKey(row.place) }));
});

const suggestedPlayers = computed(() => {
    const keys = ['mvp', 'top_scorer', 'best_goalkeeper'];
    const taken = new Set(playerAwards.value.map((award) => award.award_key));

    return keys
        .filter((key) => !taken.has(key) && (candidates.value[key] || []).length)
        .map((key) => ({ key, label: props.awardCatalog[key]?.label || key, row: candidates.value[key][0] }));
});
const suggestionKeys = computed(() =>
    Object.entries(candidates.value)
        .filter(([, rows]) => rows?.length)
        .map(([key, rows]) => ({
            key,
            label: props.awardCatalog[key]?.label || key,
            rows,
        }))
);

const firstError = (error, fallback) => {
    const errors = error?.response?.data?.errors;

    if (errors) {
        const first = Object.values(errors)[0];

        if (Array.isArray(first) && first.length) return first[0];
    }

    return error?.response?.data?.message || fallback;
};

// ── Premiados ──────────────────────────────────────────────────────────────
const openAwardModal = (award = null) => {
    editingAward.value = award;
    preselectedKey.value = award ? award.award_key : null;
    preselectedCandidate.value = null;
    showAwardModal.value = true;
};

const saveAward = async (payload) => {
    savingAward.value = true;

    try {
        if (editingAward.value) {
            await axios.put(
                route('even_ediciones_premiados_update', [props.edition.id, editingAward.value.id]),
                payload
            );
        } else {
            await axios.post(route('even_ediciones_premiados_store', props.edition.id), payload);
        }

        showAwardModal.value = false;
        router.reload({ only: ['awards', 'templates'] });
    } catch (error) {
        Swal2.fire({ icon: 'error', title: 'No se pudo guardar', text: firstError(error, 'Revisa los datos.') });
    } finally {
        savingAward.value = false;
    }
};

const deleteAward = async (award) => {
    const result = await Swal2.fire({
        icon: 'warning',
        title: '¿Quitar este premiado?',
        text: 'También se eliminarán sus diplomas emitidos.',
        showCancelButton: true,
        confirmButtonText: 'Sí, quitar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc2626',
    });

    if (!result.isConfirmed) return;

    try {
        await axios.delete(route('even_ediciones_premiados_destroy', [props.edition.id, award.id]));
        router.reload({ only: ['awards', 'templates'] });
    } catch (error) {
        Swal2.fire({ icon: 'error', title: 'No se pudo quitar', text: firstError(error, 'Inténtalo de nuevo.') });
    }
};

const loadSuggestions = async () => {
    suggestionsOpen.value = true;
    suggestionsLoading.value = true;

    try {
        const { data } = await axios.get(route('even_ediciones_premiados_sugerencias', props.edition.id));
        candidates.value = data.candidates || {};

        if (!suggestionKeys.value.some((option) => option.key === suggestionKey.value)) {
            suggestionKey.value = suggestionKeys.value[0]?.key || 'champion';
        }
    } catch (error) {
        suggestionsOpen.value = false;
        Swal2.fire({ icon: 'error', title: 'No se pudieron cargar los candidatos' });
    } finally {
        suggestionsLoading.value = false;
    }
};

const pickCandidate = (candidate) => {
    suggestionsOpen.value = false;
    editingAward.value = null;
    preselectedKey.value = suggestionKey.value;
    preselectedCandidate.value = candidate;
    showAwardModal.value = true;
};

// ── Modelos ────────────────────────────────────────────────────────────────
const openTemplateModal = () => {
    templateForm.value = { diploma_type: 'team', name: '', file: null };
    showCreateTemplate.value = true;
};

const submitTemplate = () => {
    if (!templateForm.value.file) {
        Swal2.fire({ icon: 'info', title: 'Falta información', text: 'Sube la imagen de fondo del modelo.' });
        return;
    }

    creatingTemplate.value = true;

    router.post(
        route('even_ediciones_diplomas_store', props.edition.id),
        {
            diploma_type: templateForm.value.diploma_type,
            year: props.edition.year,
            name: templateForm.value.name || 'Modelo de diploma',
            background: templateForm.value.file,
        },
        {
            forceFormData: true,
            onFinish: () => {
                creatingTemplate.value = false;
            },
        }
    );
};

const activateTemplate = async (template) => {
    try {
        await axios.post(route('even_ediciones_diplomas_activar', [props.edition.id, template.id]));
        router.reload({ only: ['awards', 'templates'] });
    } catch (error) {
        Swal2.fire({ icon: 'error', title: 'No se pudo activar', text: firstError(error, 'Inténtalo de nuevo.') });
    }
};

const assignTemplate = async (award, templateId) => {
    try {
        await axios.put(
            route('even_ediciones_premiados_template', [props.edition.id, award.id]),
            { template_id: templateId ? Number(templateId) : null }
        );

        router.reload({ only: ['awards'] });
    } catch (error) {
        Swal2.fire({ icon: 'error', title: 'No se pudo asignar el modelo', text: firstError(error, 'Inténtalo de nuevo.') });
    }
};

const pickSuggestedTeam = (row) => {
    editingAward.value = null;
    preselectedKey.value = row.award_key;
    preselectedCandidate.value = row;
    showAwardModal.value = true;
};

const pickSuggestedPlayer = (option) => {
    editingAward.value = null;
    preselectedKey.value = option.key;
    preselectedCandidate.value = option.row;
    showAwardModal.value = true;
};

const deleteTemplate = async (template) => {
    const result = await Swal2.fire({
        icon: 'warning',
        title: '¿Eliminar este diseño?',
        text: 'Los diplomas ya emitidos conservan su copia, pero dejarán de reimprimirse con este modelo.',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc2626',
    });

    if (!result.isConfirmed) return;

    try {
        const { data } = await axios.delete(route('even_ediciones_diplomas_destroy', [props.edition.id, template.id]));
        router.reload({ only: ['awards', 'templates'] });

        if (data.deactivated) {
            Swal2.fire({ icon: 'info', title: 'Modelo desactivado', text: data.message });
        }
    } catch (error) {
        Swal2.fire({ icon: 'error', title: 'No se pudo eliminar', text: firstError(error, 'Inténtalo de nuevo.') });
    }
};

// ── Emisión e impresión ────────────────────────────────────────────────────
/**
 * Vista previa sin emitir: no crea ni actualiza el registro del diploma.
 */
const previewDiploma = async (award) => {
    if (!award.has_template) {
        Swal2.fire({
            icon: 'info',
            title: 'Falta el modelo',
            text: 'Asigna un modelo de diploma a este premiado antes de previsualizar.',
        });
        return;
    }

    previewOpen.value = true;
    previewBusy.value = true;
    previewPayload.value = null;

    try {
        const { data } = await axios.get(
            route('even_ediciones_diploma_payload', [props.edition.id, award.id])
        );

        previewPayload.value = data.payload;
    } catch (error) {
        previewOpen.value = false;
        Swal2.fire({ icon: 'error', title: 'No se pudo previsualizar', text: firstError(error, 'Inténtalo de nuevo.') });
    } finally {
        previewBusy.value = false;
    }
};

const issueDiploma = async (award) => {
    if (!award.has_template) {
        Swal2.fire({
            icon: 'info',
            title: 'Falta el diseño',
            text: 'Crea un diseño de diploma para este premio antes de imprimir.',
        });
        return;
    }

    busyAwardId.value = award.id;
    previewBusy.value = true;
    previewOpen.value = true;
    previewPayload.value = null;

    try {
        const { data } = await axios.post(
            route('even_ediciones_diploma_emitir', [props.edition.id, award.id])
        );

        previewPayload.value = data.payload;
        router.reload({ only: ['awards'] });
    } catch (error) {
        previewOpen.value = false;
        Swal2.fire({ icon: 'error', title: 'No se pudo emitir el diploma', text: firstError(error, 'Inténtalo de nuevo.') });
    } finally {
        previewBusy.value = false;
        busyAwardId.value = null;
    }
};

const issueAll = async () => {
    if (!readyAwards.value.length) {
        Swal2.fire({
            icon: 'info',
            title: 'Nada que imprimir',
            text: 'Ningún premiado tiene un diseño de diploma vigente.',
        });
        return;
    }

    printingLabel.value = 'Preparando diplomas…';

    try {
        const { data } = await axios.post(
            route('even_ediciones_diploma_emitir_todos', props.edition.id)
        );

        if (!data.payloads?.length) {
            printingLabel.value = null;
            Swal2.fire({ icon: 'info', title: 'Nada que imprimir' });
            return;
        }

        await downloadBatchPdf(
            data.payloads,
            `diplomas_${props.edition.year || props.edition.id}`,
            (current, total) => {
                printingLabel.value = `Generando diploma ${current} de ${total}…`;
            }
        );

        router.reload({ only: ['awards'] });

        if (data.skipped?.length) {
            Swal2.fire({
                icon: 'warning',
                title: 'Algunos premios quedaron fuera',
                html: `Sin diseño vigente: <strong>${data.skipped.join(', ')}</strong>`,
            });
        }
    } catch (error) {
        Swal2.fire({ icon: 'error', title: 'No se pudieron generar', text: firstError(error, 'Inténtalo de nuevo.') });
    } finally {
        printingLabel.value = null;
    }
};

const awardSummary = (award) => {
    const meta = award.meta || {};
    const parts = [];

    if (meta.goles) parts.push(`${meta.goles} goles`);
    if (meta.asistencias) parts.push(`${meta.asistencias} asistencias`);
    if (meta.mvp) parts.push(`${meta.mvp} MVP`);
    if (meta.puntos) parts.push(`${meta.puntos} pts`);

    return parts.join(' · ');
};

</script>

<template>
    <AppLayout :title="`Diplomas · ${edition.name}`">
        <Navigation
            :routeModule="route('even_dashboard')"
            :titleModule="sportsMode ? 'GESTIÓN DEPORTIVA' : 'Eventos sociales'"
            :data="[{ route: route('even_ediciones_listado'), title: 'Torneos' }, { title: 'Diplomas' }]"
        />

        <main class="mx-auto mt-5 max-w-7xl space-y-5 px-3 pb-10 sm:px-5">
            <section class="diploma-header">
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-lime-300/80">Diplomas y certificados</p>
                    <h1 class="mt-1 text-2xl font-black tracking-tight text-white sm:text-3xl">
                        {{ edition.evento }} <span class="text-lime-300">· {{ edition.name }}</span>
                    </h1>
                    <p class="mt-2 max-w-2xl text-sm text-slate-300">
                        Elige al equipo o jugador premiado y el modelo reutilizable del torneo. Los datos se completan solos y el QR lleva a la landing.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <Link :href="route('even_ediciones_listado')" class="diploma-action">← Torneos</Link>
                    <Link
                        v-if="eventId"
                        :href="route('even_eventos_diplomas', eventId)"
                        class="diploma-action"
                    >
                        Modelos del torneo
                    </Link>
                    <button type="button" class="diploma-action" @click="loadSuggestions">Sugerir premiados</button>
                    <button type="button" class="diploma-action diploma-action--primary" @click="openAwardModal()">
                        + Premiado
                    </button>
                </div>
            </section>

            <div class="flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    class="diploma-tab"
                    :class="{ 'diploma-tab--active': tab === 'awards' }"
                    @click="tab = 'awards'"
                >
                    Premiados ({{ awards.length }})
                </button>
                <button
                    type="button"
                    class="diploma-tab"
                    :class="{ 'diploma-tab--active': tab === 'templates' }"
                    @click="tab = 'templates'"
                >
Modelos ({{ templates.length }})
                </button>

                <div class="ml-auto flex items-center gap-2">
                    <span v-if="printingLabel" class="text-xs font-bold text-amber-600">{{ printingLabel }}</span>
                    <button
                        v-if="tab === 'awards'"
                        type="button"
                        class="diploma-action diploma-action--primary"
                        :disabled="!readyAwards.length || printingLabel"
                        @click="issueAll"
                    >
                        Imprimir todos
                    </button>
                    <button
                        v-else
                        type="button"
                        class="diploma-action diploma-action--primary"
                        :disabled="!hasAwards"
                        @click="openTemplateModal"
                    >
                        + Modelo
                    </button>
                </div>
            </div>

            <!-- Premiados: equipos y jugadores -->
            <section v-if="tab === 'awards'" class="space-y-6">
                <div v-if="!hasAwards" class="diploma-empty">
                    <p class="text-lg font-black text-slate-800">Todavía no hay premiados</p>
                    <p class="mt-1 text-sm text-slate-500">
                        Elige un equipo de la clasificación o un jugador de los rankings para emitir su diploma.
                    </p>
                </div>

                <!-- Por equipo -->
                <div class="space-y-3">
                    <header class="diploma-section-head">
                        <div>
                            <h2>Diplomas por equipo</h2>
                            <p>Un solo diploma por equipo, con la lista de su plantel y el puesto real obtenido.</p>
                        </div>
                        <button type="button" class="diploma-action" @click="openAwardModal()">+ Premiado de equipo</button>
                    </header>

                    <article v-for="award in teamAwards" :key="award.id" class="diploma-card">
                        <div class="flex min-w-0 flex-1 flex-col gap-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span v-if="award.place" class="diploma-place">{{ award.place }}°</span>
                                <h3 class="truncate text-lg font-black text-slate-900">{{ award.recipient_name }}</h3>
                                <span v-if="award.source === 'auto'" class="diploma-chip diploma-chip--auto">Sugerido</span>
                                <span v-if="award.diploma" class="diploma-chip diploma-chip--done">Emitido {{ award.diploma.issued_at }}</span>
                            </div>
                            <p class="text-xs text-slate-500">
                                {{ award.label }} · {{ award.squad_count }} jugadores en el plantel
                                <template v-if="awardSummary(award)"> · {{ awardSummary(award) }}</template>
                            </p>
                            <label class="diploma-modelo">
                                <span>Modelo de diploma</span>
                                <select
                                    :value="award.selected_template_id || ''"
                                    @change="assignTemplate(award, $event.target.value)"
                                >
                                    <option value="">Automático (vigente del tipo)</option>
                                    <option v-for="template in templatesFor(award)" :key="template.id" :value="template.id">
                                        {{ template.name }} · {{ template.year || 'sin año' }}
                                    </option>
                                </select>
                            </label>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <button
                                type="button"
                                class="diploma-action"
                                :disabled="!award.has_template"
                                @click="previewDiploma(award)"
                            >
                                Vista previa
                            </button>
                            <button
                                type="button"
                                class="diploma-action diploma-action--primary"
                                :disabled="!award.has_template || busyAwardId === award.id"
                                @click="issueDiploma(award)"
                            >
                                {{ busyAwardId === award.id ? 'Emitiendo…' : 'Emitir' }}
                            </button>
                            <button type="button" class="diploma-action" @click="openAwardModal(award)">Editar</button>
                            <button type="button" class="diploma-action diploma-action--danger" @click="deleteAward(award)">Quitar</button>
                        </div>
                    </article>

                    <p v-if="!teamAwards.length" class="diploma-empty diploma-empty--soft">
                        Aún no hay premios de equipo. Usa la clasificación real para agregar al campeón o subcampeón.
                    </p>

                    <div v-if="suggestedTeams.length" class="diploma-suggest">
                        <p class="diploma-suggest__title">Clasificación de la edición · añadir premiado</p>
                        <button
                            v-for="row in suggestedTeams"
                            :key="`team-${row.team_id}`"
                            type="button"
                            class="diploma-suggest__row"
                            @click="pickSuggestedTeam(row)"
                        >
                            <span class="diploma-place">{{ row.place }}°</span>
                            <span class="min-w-0 flex-1 truncate text-left font-bold text-slate-800">{{ row.recipient_name }}</span>
                            <span class="text-xs font-bold text-lime-700">+ Añadir</span>
                        </button>
                    </div>
                </div>

                <!-- Por jugador -->
                <div class="space-y-3">
                    <header class="diploma-section-head">
                        <div>
                            <h2>Diplomas por jugador</h2>
                            <p>Goleador, mejor jugador, mejor arquero y premios individuales de la edición.</p>
                        </div>
                        <button type="button" class="diploma-action" @click="openAwardModal()">+ Premiado individual</button>
                    </header>

                    <article v-for="award in playerAwards" :key="award.id" class="diploma-card">
                        <div class="flex min-w-0 flex-1 flex-col gap-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="truncate text-lg font-black text-slate-900">{{ award.recipient_name }}</h3>
                                <span class="diploma-chip diploma-chip--auto">{{ award.label }}</span>
                                <span v-if="award.diploma" class="diploma-chip diploma-chip--done">Emitido {{ award.diploma.issued_at }}</span>
                            </div>
                            <p class="text-xs text-slate-500">
                                <template v-if="award.team_name">{{ award.team_name }} · </template>
                                Reconocimiento individual
                                <template v-if="awardSummary(award)"> · {{ awardSummary(award) }}</template>
                            </p>
                            <label class="diploma-modelo">
                                <span>Modelo de diploma</span>
                                <select
                                    :value="award.selected_template_id || ''"
                                    @change="assignTemplate(award, $event.target.value)"
                                >
                                    <option value="">Automático (vigente del tipo)</option>
                                    <option v-for="template in templatesFor(award)" :key="template.id" :value="template.id">
                                        {{ template.name }} · {{ template.year || 'sin año' }}
                                    </option>
                                </select>
                            </label>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <button
                                type="button"
                                class="diploma-action"
                                :disabled="!award.has_template"
                                @click="previewDiploma(award)"
                            >
                                Vista previa
                            </button>
                            <button
                                type="button"
                                class="diploma-action diploma-action--primary"
                                :disabled="!award.has_template || busyAwardId === award.id"
                                @click="issueDiploma(award)"
                            >
                                {{ busyAwardId === award.id ? 'Emitiendo…' : 'Emitir' }}
                            </button>
                            <button type="button" class="diploma-action" @click="openAwardModal(award)">Editar</button>
                            <button type="button" class="diploma-action diploma-action--danger" @click="deleteAward(award)">Quitar</button>
                        </div>
                    </article>

                    <p v-if="!playerAwards.length" class="diploma-empty diploma-empty--soft">
                        Aún no hay premios individuales. Los rankings de la edición aparecen abajo como sugerencia.
                    </p>

                    <div v-if="suggestedPlayers.length" class="diploma-suggest">
                        <p class="diploma-suggest__title">Rankings de la edición · añadir premiado</p>
                        <button
                            v-for="option in suggestedPlayers"
                            :key="`player-${option.key}`"
                            type="button"
                            class="diploma-suggest__row"
                            @click="pickSuggestedPlayer(option)"
                        >
                            <span class="diploma-chip">{{ option.label }}</span>
                            <span class="min-w-0 flex-1 truncate text-left font-bold text-slate-800">
                                {{ option.row.recipient_name }}
                                <span v-if="option.row.team_name" class="font-medium text-slate-500"> · {{ option.row.team_name }}</span>
                            </span>
                            <span class="text-xs font-bold text-lime-700">+ Añadir</span>
                        </button>
                    </div>
                </div>
            </section>

            <!-- Modelos reutilizables -->
            <section v-else class="space-y-3">
                <div v-if="!templates.length" class="diploma-empty">
                    <p class="text-lg font-black text-slate-800">Todavía no hay modelos</p>
                    <p class="mt-1 text-sm text-slate-500">
                        Sube la imagen de fondo y coloca los textos. El modelo queda disponible para todas las ediciones del torneo.
                    </p>
                    <button type="button" class="diploma-action diploma-action--primary mt-4" @click="openTemplateModal">
                        + Crear modelo
                    </button>
                </div>

                <article v-for="template in templates" :key="template.id" class="diploma-card">
                    <div class="h-24 w-40 shrink-0 overflow-hidden rounded-lg border border-slate-200 bg-slate-100">
                        <img v-if="template.background_url" :src="template.background_url" :alt="template.name" class="h-full w-full object-cover" />
                    </div>

                    <div class="flex min-w-0 flex-1 flex-col gap-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="truncate text-lg font-black text-slate-900">{{ template.name }}</h2>
                            <span v-if="template.is_active" class="diploma-chip diploma-chip--done">Vigente</span>
                            <span class="diploma-chip">{{ template.type_label }}</span>
                            <span v-if="template.year" class="diploma-chip">{{ template.year }}</span>
                        </div>
                        <p class="text-xs text-slate-500">
                            Reutilizable en todas las ediciones · {{ template.width }} × {{ template.height }} px · {{ template.elements_count }} elementos · actualizado {{ template.updated_at }}
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <Link :href="route('even_ediciones_diplomas_editar', [edition.id, template.id])" class="diploma-action">
                            Editar
                        </Link>
                        <button v-if="!template.is_active" type="button" class="diploma-action" @click="activateTemplate(template)">
                            Activar
                        </button>
                        <button type="button" class="diploma-action diploma-action--danger" @click="deleteTemplate(template)">
                            Eliminar
                        </button>
                    </div>
                </article>
            </section>
        </main>

        <!-- Modal de premiado -->
        <AwardFormModal
            :open="showAwardModal"
            :award="editingAward"
            :is-new="!editingAward"
            :catalog="awardCatalog"
            :teams="teams"
            :players="players"
            :preselected-key="preselectedKey"
            :preselected-candidate="preselectedCandidate"
            :saving="savingAward"
            @close="showAwardModal = false"
            @save="saveAward"
        />

        <!-- Modal de sugerencias -->
        <div v-if="suggestionsOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 p-4" @click.self="suggestionsOpen = false">
            <div class="flex max-h-[88vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
                <header class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                    <div>
                        <h2 class="text-lg font-black text-slate-900">Sugerencias de premiados</h2>
                        <p class="text-sm text-slate-500">Salen de los rankings y la tabla de posiciones del torneo.</p>
                    </div>
                    <button type="button" class="rounded-full p-2 text-slate-500 hover:bg-slate-100" @click="suggestionsOpen = false">✕</button>
                </header>

                <div class="flex flex-wrap gap-1 border-b border-slate-200 px-5 py-3">
                    <button
                        v-for="option in suggestionKeys"
                        :key="option.key"
                        type="button"
                        class="diploma-chip"
                        :class="{ 'diploma-chip--done': suggestionKey === option.key }"
                        @click="suggestionKey = option.key"
                    >
                        {{ option.label }}
                    </button>
                </div>

                <div class="flex-1 overflow-auto px-5 py-3">
                    <p v-if="suggestionsLoading" class="py-8 text-center text-sm font-semibold text-slate-500">Cargando candidatos…</p>
                    <p v-else-if="!suggestionKeys.length" class="py-8 text-center text-sm font-semibold text-slate-500">
                        Aún no hay estadísticas suficientes en esta edición.
                    </p>
                    <template v-else>
                        <button
                            v-for="candidate in suggestionKeys.find((option) => option.key === suggestionKey)?.rows || []"
                            :key="`${candidate.recipient_type}-${candidate.person_id || candidate.team_id}`"
                            type="button"
                            class="diploma-candidate"
                            @click="pickCandidate(candidate)"
                        >
                            <span class="min-w-0 flex-1 truncate text-left font-bold text-slate-800">
                                {{ candidate.recipient_name }}
                                <span v-if="candidate.team_name && candidate.team_name !== candidate.recipient_name" class="font-medium text-slate-500">
                                    · {{ candidate.team_name }}
                                </span>
                            </span>
                            <span class="shrink-0 text-xs font-semibold text-slate-500">
                                <template v-if="candidate.meta?.goles">{{ candidate.meta.goles }} goles</template>
                                <template v-else-if="candidate.meta?.puntos">{{ candidate.meta.puntos }} pts</template>
                            </span>
                        </button>
                    </template>
                </div>
            </div>
        </div>

        <!-- Modal de nuevo diseño -->
        <div v-if="showCreateTemplate" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 p-4" @click.self="showCreateTemplate = false">
            <div class="w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl">
                <header class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                    <h2 class="text-lg font-black text-slate-900">Nuevo modelo de diploma</h2>
                    <button type="button" class="rounded-full p-2 text-slate-500 hover:bg-slate-100" @click="showCreateTemplate = false">✕</button>
                </header>

                <div class="space-y-3 px-5 py-4">
                    <label class="diploma-field">
                        <span>Tipo de diploma</span>
                        <select v-model="templateForm.diploma_type">
                            <option value="team">Por equipo (incluye plantel y puesto)</option>
                            <option value="player">Por jugador (incluye nombre y premio)</option>
                        </select>
                    </label>

                    <label class="diploma-field">
                        <span>Nombre del modelo</span>
                        <input v-model="templateForm.name" type="text" maxlength="120" placeholder="Diploma campeón 2026" />
                    </label>

                    <label class="diploma-field">
                        <span>Imagen de fondo (define el tamaño)</span>
                        <input type="file" accept="image/png,image/jpeg,image/webp" @change="templateForm.file = $event.target.files?.[0] || null" />
                    </label>
                </div>

                <footer class="flex items-center justify-end gap-2 border-t border-slate-200 px-5 py-4">
                    <button type="button" class="diploma-action" @click="showCreateTemplate = false">Cancelar</button>
                    <button type="button" class="diploma-action diploma-action--primary" :disabled="creatingTemplate" @click="submitTemplate">
                        {{ creatingTemplate ? 'Creando…' : 'Crear y diseñar' }}
                    </button>
                </footer>
            </div>
        </div>

        <DiplomaPreviewModal
            :open="previewOpen"
            :payload="previewPayload"
            :busy="previewBusy"
            @close="previewOpen = false"
        />
    </AppLayout>
</template>

<style scoped>
.diploma-header {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    justify-content: space-between;
    gap: 1rem;
    border-radius: 1rem;
    border: 1px solid #234052;
    background: linear-gradient(135deg, #0b1f2b 0%, #0e2532 55%, #122c3a 100%);
    padding: 1.25rem 1.5rem;
    box-shadow: 0 20px 50px rgb(0 0 0 / 25%);
}

.diploma-tab {
    border-radius: 999px;
    border: 1px solid #cbd5e1;
    background: #fff;
    padding: 0.45rem 0.95rem;
    font-size: 0.8rem;
    font-weight: 800;
    color: #475569;
}

.diploma-tab--active {
    border-color: #84cc16;
    background: #ecfccb;
    color: #3f6212;
}

.diploma-action {
    display: inline-flex;
    min-height: 2.4rem;
    align-items: center;
    justify-content: center;
    gap: 0.3rem;
    border-radius: 0.7rem;
    border: 1px solid #2b4b5d;
    background: #122c3a;
    padding: 0 0.85rem;
    font-size: 0.78rem;
    font-weight: 800;
    color: #e9f4f2;
    transition: all 0.15s ease;
}

.diploma-action:hover:not(:disabled) {
    border-color: rgb(132 204 22 / 60%);
    color: #d9f99d;
}

.diploma-action:disabled {
    cursor: not-allowed;
    opacity: 0.45;
}

.diploma-action--primary {
    border-color: #84cc16;
    background: #c8f169;
    color: #08251a;
}

.diploma-action--primary:hover:not(:disabled) {
    background: #d9fb8d;
    color: #08251a;
}

.diploma-action--danger {
    border-color: rgb(248 113 113 / 40%);
    background: rgb(69 10 10 / 45%);
    color: #fecaca;
}

.diploma-card {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 1rem;
    border-radius: 1rem;
    border: 1px solid #e2e8f0;
    background: #fff;
    padding: 1rem 1.15rem;
    box-shadow: 0 12px 30px rgb(15 23 42 / 6%);
}

.diploma-chip {
    display: inline-flex;
    align-items: center;
    border-radius: 999px;
    border: 1px solid #cbd5e1;
    background: #f8fafc;
    padding: 0.12rem 0.55rem;
    font-size: 0.65rem;
    font-weight: 800;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    color: #475569;
}

.diploma-chip--auto {
    border-color: #bfdbfe;
    background: #eff6ff;
    color: #1d4ed8;
}

.diploma-chip--done {
    border-color: #bbf7d0;
    background: #f0fdf4;
    color: #15803d;
}

.diploma-empty {
    border-radius: 1rem;
    border: 1px dashed #cbd5e1;
    background: #fff;
    padding: 2.5rem 1.5rem;
    text-align: center;
}

.diploma-candidate {
    display: flex;
    width: 100%;
    align-items: center;
    gap: 0.5rem;
    border-radius: 0.6rem;
    border: 1px solid transparent;
    padding: 0.55rem 0.65rem;
    font-size: 0.9rem;
}

.diploma-candidate:hover {
    border-color: #d9f99d;
    background: #f7fee7;
}

.diploma-field {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    font-size: 0.7rem;
    font-weight: 800;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    color: #475569;
}

.diploma-field select,
.diploma-field input {
    border: 1px solid #cbd5e1;
    border-radius: 0.6rem;
    padding: 0.5rem 0.65rem;
    font-size: 0.9rem;
    font-weight: 600;
    color: #0f172a;
    text-transform: none;
    letter-spacing: normal;
}

.diploma-section-head {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    justify-content: space-between;
    gap: 0.75rem;
    border-bottom: 2px solid #e2e8f0;
    padding-bottom: 0.5rem;
}

.diploma-section-head h2 {
    font-size: 1.15rem;
    font-weight: 900;
    color: #0f172a;
}

.diploma-section-head p {
    margin-top: 0.15rem;
    font-size: 0.8rem;
    color: #64748b;
}

.diploma-place {
    display: inline-flex;
    min-width: 2.1rem;
    align-items: center;
    justify-content: center;
    border-radius: 0.5rem;
    background: #0b1f2b;
    padding: 0.15rem 0.4rem;
    font-size: 0.75rem;
    font-weight: 900;
    color: #f5c451;
}

.diploma-modelo {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.5rem;
    margin-top: 0.35rem;
    font-size: 0.65rem;
    font-weight: 800;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    color: #64748b;
}

.diploma-modelo select {
    max-width: 22rem;
    border: 1px solid #cbd5e1;
    border-radius: 0.5rem;
    padding: 0.3rem 0.5rem;
    font-size: 0.8rem;
    font-weight: 600;
    color: #0f172a;
    text-transform: none;
    letter-spacing: normal;
}

.diploma-suggest {
    border-radius: 0.85rem;
    border: 1px dashed #cbd5e1;
    background: #f8fafc;
    padding: 0.6rem;
}

.diploma-suggest__title {
    padding: 0.15rem 0.35rem 0.4rem;
    font-size: 0.62rem;
    font-weight: 900;
    letter-spacing: 0.09em;
    text-transform: uppercase;
    color: #64748b;
}

.diploma-suggest__row {
    display: flex;
    width: 100%;
    align-items: center;
    gap: 0.6rem;
    border-radius: 0.6rem;
    border: 1px solid transparent;
    padding: 0.45rem 0.5rem;
    font-size: 0.85rem;
}

.diploma-suggest__row:hover {
    border-color: #bef264;
    background: #f7fee7;
}

.diploma-empty--soft {
    border-style: dashed;
    padding: 1.2rem;
    font-size: 0.85rem;
    color: #64748b;
}
</style>
