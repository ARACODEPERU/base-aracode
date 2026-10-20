<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import { Link, router } from '@inertiajs/vue3';
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

    const roundRobinRef = ref(null);

    const openModalTeams = () => {
        if (roundRobinRef.value && typeof roundRobinRef.value.openModalTeams === 'function') {
            roundRobinRef.value.openModalTeams();
        } else if (roundRobinRef.value?.displayModalTeams) {
            roundRobinRef.value.displayModalTeams = true;
        }
    };

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
                showConfirmButton: Boolean(data.can_write),
                confirmButtonText: 'Recargar saldo',
                cancelButtonText: 'Cerrar',
                customClass: 'sweet-alerts',
            });
            if (result.isConfirmed && data.can_write) {
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
        <Navigation :routeModule="route('even_dashboard')" :titleModule="'Eventos sociales'"
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
        <div class="pt-5">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div class="w-full">
                    <div class="flex flex-col sm:flex-row items-center justify-end gap-4">

                        <button @click="openModalTeams" class="btn btn-success uppercase text-xs">
                            Generar todos contra todos
                        </button>
                        <button v-can="'even_ediciones_fixtures_nuevo'" type="button" :disabled="checkingNewMatch" class="btn btn-primary uppercase text-xs" @click="openNewMatch">
                            {{ checkingNewMatch ? 'Verificando...' : 'Nuevo' }}
                        </button>
                        <Link :href="route('even_ediciones_listado')" :preserveState="true" class="btn btn-warning uppercase text-xs">
                            Ir atras
                        </Link>
                    </div>
                </div>
            </div>
            <div class="mt-5">
                <template v-if="edition.competition_format == 'single_elimination' || edition.competition_format == 'relampago'">
                   <SingleElimination
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
