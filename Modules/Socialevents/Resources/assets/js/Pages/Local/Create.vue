<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import CreateForm from './Partials/CreateForm.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import { Link, usePage } from '@inertiajs/vue3';
    import { computed } from 'vue';
    // En modo deportivo (torneos) el local se llama complejo deportivo.
    const etiquetaLocal = computed(() => usePage().props.sportsOrganization?.mode ? 'Complejo deportivo' : 'Locales');
    const props = defineProps({
        ubigeo: {
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
                {title: 'Nuevo'}
            ]"
        />
        <div class="mt-6 w-full">
            <CreateForm :ubigeo="ubigeo" />
        </div>
    </AppLayout>
</template>
