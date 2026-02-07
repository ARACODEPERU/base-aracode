<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import EditForm from './Partials/EditForm.vue';
    import { Link } from '@inertiajs/vue3';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
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
    <AppLayout title="Locales">
        <Navigation :routeModule="route('even_dashboard')" :titleModule="'Eventos sociales'"
            :data="[
                {route: route('even_local_list'), title: 'Locales'},
                {title: 'Editar'}
            ]"
        />
        <div class="mt-6 w-full">
            <EditForm :ubigeo="ubigeo" :local="local" />
        </div>
    </AppLayout>
</template>
