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
    }
});

</script>

<template>
    <AppLayout title="Crear Estudiante">
        <Navigation :routeModule="route('crm_dashboard')" :titleModule="'Academico'">
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <Link :href="route('aca_students_list')" class="text-primary hover:underline">Estudiantes</Link>
            </li>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Nuevo</span>
            </li>
        </Navigation>
        <div class="pt-5">
            <CreateForm
                :identityDocumentTypes="identityDocumentTypes"
                :ubigeo="ubigeo"
                :industrias="industrias"
                :professions="professions"
                :occupations="occupations"
            />
        </div>
    </AppLayout>
</template>
