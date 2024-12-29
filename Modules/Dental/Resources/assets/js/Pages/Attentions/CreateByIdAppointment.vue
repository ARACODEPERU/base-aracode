<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import CreateFormAppointment from './Partials/CreateFormAppointment.vue';
import { Link } from '@inertiajs/vue3';
import Navigation from '@/Components/vristo/layout/Navigation.vue';

const props = defineProps({
    doctors: {
        type: Object,
        default: () => ({}),
    },
    appointment: {
        type: Object,
        default: () => ({}),
    }
});

</script>

<template>
    <AppLayout title="Crear Atencion">
        <Navigation :routeModule="route('dental_dashboard')" :titleModule="'Salud'">
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Odontología</span>
            </li>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <Link :href="route('odontology_attention_list')" class="text-primary hover:underline">Atencion</Link>
            </li>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Desde Cita</span>
            </li>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Nuevo</span>
            </li>
        </Navigation>
        <div class="pt-5">
            <CreateFormAppointment :doctors="doctors" :appointment="appointment" /> 
        </div>
    </AppLayout>
</template>
