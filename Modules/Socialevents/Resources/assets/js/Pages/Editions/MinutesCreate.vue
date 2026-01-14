<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import { useForm } from '@inertiajs/vue3';
    import Swal from "sweetalert2";
    import { Link, router } from '@inertiajs/vue3';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import { ref } from "vue";
    import MinutesCreateForm from './Partials/MinutesCreateForm.vue';


    const props = defineProps({
        edicion: {
            type: Object,
            default: () => ({}),
        },
        typeSessions: {
            type: Array,
            default: () => ([]),
        },
        tinyApiKey: {
            type: String,
            default: null,
        },
        ubigeo: {
            type: Object,
            default: () => ({}),
        },
        documentTypes: {
            type: Object,
            default: () => ({}),
        }
    });
</script>
<template>
    <AppLayout title="Ediciones">
        <Navigation :routeModule="route('even_dashboard')" :titleModule="'Eventos sociales'">
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <Link :href="route('even_ediciones_listado')" class="text-primary hover:underline">Ediciones</Link>
            </li>
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <Link :href="route('even_ediciones_editar', edicion.id)" class="text-primary hover:underline">{{ edicion.name }}</Link>
            </li>
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <Link :href="route('even_ediciones_actas_listado', edicion.id)" class="text-primary hover:underline">Libro de Actas y Acuerdos</Link>
            </li>
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <span>Nuevo</span>
            </li>
        </Navigation>
        <div class="mt-6 w-full">
            <MinutesCreateForm
                :edicion="edicion"
                :typeSessions="typeSessions"
                :tinyApiKey="tinyApiKey"
                :ubigeo="ubigeo"
                :documentTypes="documentTypes"
            />
        </div>
    </AppLayout>
</template>
