<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import CreateForm from './Partials/CreateForm.vue';
import { Link } from '@inertiajs/vue3';
import Navigation from '@/Components/vristo/layout/Navigation.vue';

const props = defineProps({
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
    tiny_api_key: {
        type: String,
        default: null,
    }
});
</script>

<template>
    <AppLayout title="Eventos">
        <Navigation :routeModule="route('even_dashboard')" :titleModule="'Eventos sociales'"
            :data="[
                {route: route('even_eventos_list'), title: 'Eventos'},
                {title: 'Nuevo'}
            ]"
        />
        <div class="max-w-screen-2xl  mx-auto p-4 md:p-6 2xl:p-10">
            <CreateForm
                :tiny_api_key="tiny_api_key"
                :categories="categories"
                :instructors="instructors"
                :locales="locales"
            />
        </div>
    </AppLayout>
</template>
