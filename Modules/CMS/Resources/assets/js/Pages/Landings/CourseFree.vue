<script setup>
    import { ref } from 'vue';
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import { useForm } from '@inertiajs/vue3';
    import Swal2 from "sweetalert2";
    import FormConfCourses from './Partials/FormConfCourses.vue';

    defineProps({
        courses: {
            type: Object,
            default: () => ({})
        },
        tinykey: {
            type: String,
            default: null,
        },
        landingPage: {
            type: Object,
            default: () => ({})
        }
    });
</script>
<template>
    <AppLayout title="Resumen">
        <Navigation :routeModule="route('cms_dashboard')" :titleModule="'CMS'">
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Configuracion Landing curso gratis</span>
            </li>
        </Navigation>
        <div class="mt-5">
            <div class="panel">
                <FormConfCourses :courses="courses" :tinykey="tinykey" :landingPage="landingPage" />
            </div>
        </div>
    </AppLayout>
</template>
