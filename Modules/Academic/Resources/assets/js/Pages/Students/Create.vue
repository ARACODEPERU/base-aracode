<script setup>
import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
import CreateForm from './Partials/CreateForm.vue';
import { Link } from '@inertiajs/vue3';
import Navigation from "@/Components/vristo/layout/Navigation.vue";

const props = defineProps({
    identityDocumentTypes: {
        type: Object,
        default: () => ({}),
    },
    ubigeo: {
        type: Object,
        default: () => ({})
    },
    industrias: {
        type: Object,
        default: () => ({})
    },
    professions: {
        type: Object,
        default: () => ({})
    },
    occupations: {
        type: Object,
        default: () => ({})
    },
    countries: {
        type: Object,
        default: () => ({})
    }
});

</script>

<template>
    <AppLayout title="Crear Estudiante">
        <Navigation :routeModule="route('aca_dashboard')" :titleModule="'Académico'"
            :data="[
                {route: route('aca_students_list'), title: 'Estudiantes'},
                {title: 'Nuevo'}
            ]"
        />
        <div class="pt-5">
            <CreateForm
                :identityDocumentTypes="identityDocumentTypes"
                :ubigeo="ubigeo"
                :industrias="industrias"
                :professions="professions"
                :occupations="occupations"
                :countries="countries"
            />
        </div>
    </AppLayout>
</template>
