<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import CreateForm from './Partials/CreateForm.vue';
    import { Link } from '@inertiajs/vue3';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
</script>

<template>
    <AppLayout title="Crear Categoría">
        <Navigation :routeModule="route('even_dashboard')" :titleModule="'Eventos sociales'"
            :data="[
                {route: route('even_categories_list'), title: 'Categorías'},
                {title: 'Nuevo'}
            ]"
        />
        <div class="mt-6">
            <CreateForm />
        </div>
    </AppLayout>
</template>
