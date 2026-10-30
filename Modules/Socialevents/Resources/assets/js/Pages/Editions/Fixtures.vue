<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import { Link, router, usePage } from '@inertiajs/vue3';
    import axios from 'axios';
    import Swal2 from 'sweetalert2';
    import { computed, ref } from 'vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';

    import RoundRobinPlayoff from './Partials/RoundRobinPlayoff.vue';
    import SingleElimination from './Partials/SingleElimination.vue';

    const props = defineProps({
        edition: {
            type: Object,
            default: () => ({}),
        },
        teams: {
            type: Object,
            default: () => ({}),
        },
        filters: {
            type: Object,
            default: () => ({}),
        },
        fixture: {
            type: Object,
            default: () => ({}),
        },
        locales: {
            type: Object,
            default: () => ({}),
        },
        ubigeo: {
            type: Object,
            default: () => ({}),
        },
        documentTypes: {
            type: Object,
            default: () => ({}),
        },
        // Catálogo único de fases y formatos de cruce que envía el servidor.
        phaseLabels: {
            type: Object,
            default: () => ({}),
        },
        phaseOptions: {
            type: Array,
            default: () => [],
        },
        tieFormats: {
            type: Array,
            default: () => [],
        },
    });

    const page = usePage();
    const sportsMode = computed(() => page.props.sportsOrganization?.mode === true);

    const roundRobinRef = ref(null);

    const openModalTeams = () => {
        if (roundRobinRef.value && typeof roundRobinRef.value.openModalTeams === 'function') {
            roundRobinRef.value.openModalTeams();
        } else if (roundRobinRef.value?.displayModalTeams) {
            roundRobinRef.value.displayModalTeams = true;
        }
    };

    // El botón de todos contra todos solo tiene parcial que abrir en los
    // formatos de liguilla; en eliminación directa o relámpago no se pinta.
    const usesRoundRobinPartial = computed(() => {
        const formato = props.edition.competition_format;

        return formato !== 'single_elimination' && formato !== 'relampago';
    });

    const checkingNewMatch = ref(false);

    const openNewMatch = async () => {
        if (checkingNewMatch.value) return;
        checkingNewMatch.value = true;
        try {
            const { data } = await axios.get(route('even_ediciones_fixtures_create_availability', props.edition.id));
            if (data.can_create) {
                router.visit(route('even_ediciones_fixtures_create', props.edition.id));
                return;
            }

            const result = await Swal2.fire({
                title: data.can_write ? 'Saldo insuficiente' : 'Acceso vencido',
                text: data.reason || 'No se puede crear un partido con el acceso actual de la organización.',
                icon: 'warning',
                showCancelButton: true,
                showConfirmButton: true,
                confirmButtonText: data.can_write ? 'Recargar saldo' : 'Ir a pagar',
                cancelButtonText: 'Cerrar',
                customClass: 'sweet-alerts',
            });
            if (result.isConfirmed) {
                router.visit(route('even_organizations.topup.index'));
            }
        } catch (error) {
            await Swal2.fire({
                title: 'No se pudo validar',
                text: error.response?.data?.message || 'No se pudo verificar el saldo y el acceso de la organización. Inténtalo nuevamente.',
                icon: 'error',
                confirmButtonText: 'Entendido',
                customClass: 'sweet-alerts',
            });
        } finally {
            checkingNewMatch.value = false;
        }
    };

</script>
<template>
    <AppLayout title="Ediciones">
        <Navigation :routeModule="route('even_dashboard')" :titleModule="sportsMode ? 'GESTIÓN DEPORTIVA' : 'Eventos sociales'"
            :data="[
                {
                    route: route('even_ediciones_listado'), title: 'Ediciones',
                    children: [
                        { route: route('even_ediciones_equipos', edition.id), title: 'Equipos', permissions: 'even_ediciones_equipos'},
                        { route: route('even_ediciones_fixtures', edition.id), title: 'Partidos', permissions: 'even_ediciones_fixtures'},
                        { route: route('even_ediciones_pago_sanciones', edition.id), title: 'Sanciones', permissions: 'even_ediciones_sanciones'},
                        { route: route('even_ediciones_exclusiones', edition.id), title: 'Exclusiones', permissions: 'even_ediciones_exclusiones'},
                        { route: route('even_ediciones_suspensiones', edition.id), title: 'Suspensiones', permissions: 'even_ediciones_suspensiones'},
                        { route: route('even_ediciones_actas_listado', edition.id), title: 'Actas', permissions: 'even_ediciones_actas'},
                    ]
                },
                {route: route('even_ediciones_editar', edition.id), title: edition.name},
                {title: 'Control de Partidos'}
            ]"
        />
        <div class="sports-edition-page pt-5" :class="sportsMode ? 'sports-fixtures-page' : ''">
            <section
                class="rounded-2xl border p-5 sm:p-6"
                :class="sportsMode
                    ? 'border-white/10 bg-[#0b1f2b] text-white shadow-[0_26px_70px_rgba(0,0,0,0.38)]'
                    : 'border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900'"
            >
                <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                    <div class="min-w-0">
                        <div
                            class="mb-2 inline-flex items-center gap-2 rounded-full border px-3 py-1 text-[0.65rem] font-bold uppercase tracking-[0.2em]"
                            :class="sportsMode ? 'border-lime-300/25 bg-lime-300/10 text-lime-200' : 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200'"
                        >
                            <span class="h-1.5 w-1.5 rounded-full bg-lime-400"></span> Partidos
                        </div>
                        <h1 class="text-2xl font-black tracking-tight sm:text-3xl" :class="sportsMode ? 'text-white' : 'text-gray-900 dark:text-white'">
                            Control de partidos
                        </h1>
                        <p class="mt-1 text-sm" :class="sportsMode ? 'text-slate-300' : 'text-gray-600 dark:text-gray-300'">
                            {{ edition.name }}<span v-if="edition.year"> · {{ edition.year }}</span> — programa jornadas, registra marcadores y cierra cada acta.
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 lg:justify-end">
                        <button
                            v-if="usesRoundRobinPartial"
                            @click="openModalTeams"
                            class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-xs font-bold uppercase tracking-wider transition"
                            :class="sportsMode
                                ? 'border border-[#2b4b5d] text-slate-200 hover:border-lime-300/60 hover:text-lime-200'
                                : 'btn btn-success text-xs'"
                        >
                            Generar todos contra todos
                        </button>
                        <button
                            v-can="'even_ediciones_fixtures_nuevo'"
                            type="button"
                            :disabled="checkingNewMatch"
                            class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-xs font-bold uppercase tracking-wider transition disabled:cursor-not-allowed disabled:opacity-50"
                            :class="sportsMode
                                ? 'bg-[#c8f169] text-[#08251a] hover:bg-[#d9fb8d]'
                                : 'btn btn-primary text-xs'"
                            @click="openNewMatch"
                        >
                            {{ checkingNewMatch ? 'Verificando...' : 'Nuevo' }}
                        </button>
                        <Link
                            :href="route('even_ediciones_listado')"
                            :preserveState="true"
                            class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-xs font-bold uppercase tracking-wider transition"
                            :class="sportsMode
                                ? 'border border-[#2b4b5d] text-slate-200 hover:border-lime-300/60 hover:text-lime-200'
                                : 'btn btn-warning text-xs'"
                        >
                            Ir atrás
                        </Link>
                    </div>
                </div>
            </section>
            <div class="mt-5">
                <template v-if="! usesRoundRobinPartial">
                   <SingleElimination
                        :sports-mode="sportsMode"
                        :knockout="fixture"
                        :teams="teams"
                        :locales="locales"
                        :edition="edition"
                        :ubigeo="ubigeo"
                        :document-types="documentTypes"
                        :phase-labels="phaseLabels"
                        :phase-options="phaseOptions"
                        :tie-formats="tieFormats"
                   />
                </template>
                <template v-else>
                    <RoundRobinPlayoff
                        :sports-mode="sportsMode"
                        ref="roundRobinRef"
                        :fixture="fixture"
                        :teams="teams"
                        :locales="locales"
                        :edition="edition"
                        :ubigeo="ubigeo"
                        :document-types="documentTypes"
                        :phase-labels="phaseLabels"
                        :phase-options="phaseOptions"
                        :tie-formats="tieFormats"
                    />
                </template>
            </div>
        </div>
    </AppLayout>
</template>
