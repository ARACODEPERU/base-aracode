<script setup>
import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
import Navigation from '@/Components/vristo/layout/Navigation.vue';

const props = defineProps({
    course: {
        type: Object,
        default: () => ({}),
    }
});
</script>

<template>
    <AppLayout title="Configuración de Landing">
        <Navigation :routeModule="route('aca_dashboard')" :titleModule="'Académico'"
            :data="[
                {route: route('aca_courses_list'), title: 'Cursos'},
                {route: route('aca_courses_edit',course.id), title: course.description},
                {title: 'Landing'}
            ]"
        />
        <div class="pt-5">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                    Configuración de Landing - {{ course.description }}
                </h2>
                <p class="text-gray-600 dark:text-gray-400">
                    Aquí podrás configurar la información que se mostrará en la landing page del curso.
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-500 mt-2">
                   尽快告诉我 los campos que necesitas agregar al formulario.
                </p>
            </div>
        </div>
    </AppLayout>
</template>