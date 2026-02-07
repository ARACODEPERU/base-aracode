<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import CreateForm from './Partials/CreateForm.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import { Link } from '@inertiajs/vue3';
    const props = defineProps({
        ubigeo: {
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
                {title: 'Nuevo'}
            ]"
        />
        <div class="mt-6 w-full">
            <CreateForm :ubigeo="ubigeo" />
        </div>
    </AppLayout>
</template>
