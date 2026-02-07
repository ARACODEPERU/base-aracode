<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import CreateForm from './Partials/CreateForm.vue';
    import { Link } from '@inertiajs/vue3';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';

    const props = defineProps({
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
    <AppLayout title="Equipos">
        <Navigation :routeModule="route('even_dashboard')" :titleModule="'Eventos sociales'"
            :data="[
                {route: route('even_equipos_listado'), title: 'Equipos'},
                {title: 'Nuevo'}
            ]"
        />
        <div class="mt-6 w-full">
            <CreateForm
                :ubigeo="ubigeo"
                :documentTypes="documentTypes"
            />
        </div>
    </AppLayout>
</template>
