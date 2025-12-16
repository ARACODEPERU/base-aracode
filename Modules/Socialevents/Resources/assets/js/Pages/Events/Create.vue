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
        <Navigation :routeModule="route('even_dashboard')" :titleModule="'Eventos sociales'">
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <Link :href="route('even_eventos_list')" class="text-primary hover:underline">Eventos</Link>
            </li>
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <span>Nuevo</span>
            </li>
        </Navigation>
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
