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
        <Navigation :routeModule="route('even_dashboard')" :titleModule="'Eventos sociales'">
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <Link :href="route('even_ediciones_listado')" class="text-primary hover:underline">Ediciones</Link>
            </li>
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <span>{{ edition.name }}</span>
            </li>
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <span>Control de Partidos</span>
            </li>
        </Navigation>
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
