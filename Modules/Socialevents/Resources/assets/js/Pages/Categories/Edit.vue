<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import EditForm from './Partials/EditForm.vue';
    import { Link } from '@inertiajs/vue3';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
const props = defineProps({
    category: {
        type: Object,
        default: () => ({}),
    }
});
</script>

<template>
    <AppLayout title="Crear Institución">
        <Navigation :routeModule="route('even_dashboard')" :titleModule="'Eventos sociales'"
            :data="[
                {route: route('even_categories_list'), title: 'Categorías'},
                {title: 'Editar'}
            ]"
        />
        <div class="mt-6">
            <EditForm :category="category" />
        </div>
    </AppLayout>
</template>
