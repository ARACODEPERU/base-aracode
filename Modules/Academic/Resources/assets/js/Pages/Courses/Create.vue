<script setup>
import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
import CreateForm from './Partials/CreateForm.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';

const props = defineProps({
    modalities: {
        type: Object,
        default: () => ({}),
    },
    categories: {
        type: Object,
        default: () => ({}),
    },
    types: {
        type: Object,
        default: () => ({}),
    },
    sectors: {
        type: Object,
        default: () => ({}),
    },
    P000018: {
        type: Boolean,
        default: false,
    }
});

</script>

<template>
    <AppLayout title="Crear Curso">
        <Navigation :routeModule="route('aca_dashboard')" :titleModule="'Académico'"
            :data="[
                {route: route('aca_courses_list'), title: 'Cursos'},
                {title: 'Nuevo'}
            ]"
        />
        <div class="pt-5">
            <CreateForm
                :modalities="modalities"
                :categories="categories"
                :types="types"
                :sectors="sectors"
                :P000018="P000018"
            />
        </div>
    </AppLayout>
</template>
