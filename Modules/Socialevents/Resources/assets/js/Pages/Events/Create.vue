<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import CreateForm from './Partials/CreateForm.vue';
import { Link } from '@inertiajs/vue3';
import Navigation from '@/Components/vristo/layout/Navigation.vue';

const props = defineProps({
    sportsMode: {
        type: Boolean,
        default: false,
    },
    categories: {
        type: Object,
        default: () => ({}),
    },
    instructors: {
        type: Object,
        default: () => ({}),
    },
    locales: {
        type: Object,
        default: () => ({}),
    },
    organizers: {
        type: Object,
        default: () => ({}),
    },
});
</script>

<template>
    <AppLayout :title="sportsMode ? 'Gestión Deportiva' : 'Eventos'">
        <Navigation :routeModule="route('even_dashboard')" :titleModule="sportsMode ? 'GESTIÓN DEPORTIVA' : 'Eventos sociales'"
            :data="[
                {route: route('even_eventos_list'), title: 'Eventos'},
                {title: 'Nuevo'}
            ]"
        />
        <div class="max-w-screen-2xl  mx-auto p-4 md:p-6 2xl:p-10">
            <CreateForm
                :sportsMode="sportsMode"
                :categories="categories"
                :instructors="instructors"
                :organizers="organizers"
                :locales="locales"
            />
        </div>
    </AppLayout>
</template>
