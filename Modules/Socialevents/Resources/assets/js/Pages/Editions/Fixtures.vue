<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import { Link, router } from '@inertiajs/vue3';
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
        }
    });


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
                            Generar Automático
                        </button>
                        <Link v-can="'even_ediciones_fixtures_nuevo'" :href="route('even_ediciones_fixtures_create', edition.id)" class="btn btn-primary uppercase text-xs">
                            Nuevo
                        </Link>
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
                   />
                </template>
                <template v-else>
                    <RoundRobinPlayoff
                        :fixture="fixture"
                        :teams="teams"
                        :locales="locales"
                        :edition="edition"
                        :ubigeo="ubigeo"
                        :document-types="documentTypes"
                    />
                </template>
            </div>
        </div>
    </AppLayout>
</template>
