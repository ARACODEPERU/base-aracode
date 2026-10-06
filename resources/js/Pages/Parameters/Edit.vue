<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import EditForm from './Partials/EditForm.vue';
import { Link } from '@inertiajs/vue3';
import Navigation from '@/Components/vristo/layout/Navigation.vue';

const props = defineProps({
    parameter: {
        type: Object,
        default: () => ({}),
    },
    // Parámetro confidencial: el valor no viene del servidor.
    is_secret: {
        type: Boolean,
        default: false,
    },
    // Ya hay un valor guardado (solo aplica a los confidenciales).
    has_value: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <AppLayout title="Editar Parametro">
        <Navigation >
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Configuraciones</span>
            </li>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <Link :href="route('parameters')" class="text-primary hover:underline">Parametros</Link>
            </li>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Editar</span>
            </li>
        </Navigation>
        <div class="mt-5">
            <EditForm :parameter="parameter" :is_secret="is_secret" :has_value="has_value" /> 
        </div>
    </AppLayout>
</template>
