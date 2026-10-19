<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import EditForm from './Partials/EditForm.vue';
    import { Link, usePage } from '@inertiajs/vue3';
    import { computed } from 'vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    // En modo deportivo (torneos) el local se llama complejo deportivo.
    const etiquetaLocal = computed(() => usePage().props.sportsOrganization?.mode ? 'Complejo deportivo' : 'Locales');
    const props = defineProps({
        ubigeo: {
            type: Object,
            default: () => ({}),
        },
        local: {
            type: Object,
            default: () => ({}),
        }
    });
</script>

<template>
    <AppLayout :title="etiquetaLocal">
        <Navigation :routeModule="route('even_dashboard')" :titleModule="'Eventos sociales'"
            :data="[
                {route: route('even_local_list'), title: etiquetaLocal},
                {title: 'Editar'}
            ]"
        />
        <div class="mt-6 w-full">
            <EditForm :ubigeo="ubigeo" :local="local" />
        </div>
    </AppLayout>
</template>
