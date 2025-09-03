<script setup>
    import { useForm, Link, router } from "@inertiajs/vue3"
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import CreateForm from "./Partials/CreateForm.vue";

    const props = defineProps({
        sedes: {
            type: Object,
            default: () => ({})
        },
        identityDocumentTypes: {
            type: Object,
            default: () => ({}),
        },
        ubigeo: {
            type: Object,
            default: () => ({})
        },
        schedules: {
            type: Object,
            default: () => ({})
        },
        evangelizations: {
            type: Object,
            default: () => ({})
        }
    });
</script>
<template>
    <AppLayout title="Comunidad de la iglesia">
        <Navigation :routeModule="route('cigle_dashboard')" :titleModule="'Comunidad de la iglesia'">
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Miembros</span>
            </li>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Ficha de nuevo creyente</span>
            </li>
        </Navigation>
        <div class="mt-5">
            <CreateForm
                :sedes="sedes"
                :identityDocumentTypes="identityDocumentTypes"
                :ubigeo="ubigeo"
                :schedules="schedules"
                :evangelizations="evangelizations"
            />
        </div>
    </AppLayout>
</template>
