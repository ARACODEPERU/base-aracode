<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import { useForm } from '@inertiajs/vue3';
    import Swal from "sweetalert2";
    import { Link, router } from '@inertiajs/vue3';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import { ref } from "vue";
    import MinutesEditForm from './Partials/MinutesEditForm.vue';


    const props = defineProps({
        accordance: {
            type: Object,
            default: () => ({}),
        },
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
        <Navigation :routeModule="route('even_dashboard')" :titleModule="'Eventos sociales'"
            :data="[
                {
                    route: route('even_ediciones_listado'),
                    title: 'Ediciones',
                    children: [
                        { route: route('even_ediciones_equipos', edicion.id), title: 'Equipos', permissions: 'even_ediciones_equipos'},
                        { route: route('even_ediciones_fixtures', edicion.id), title: 'Partidos', permissions: 'even_ediciones_fixtures'},
                        { route: route('even_ediciones_pago_sanciones', edicion.id), title: 'Sanciones', permissions: 'even_ediciones_sanciones'}
                    ]
                },
                {route:route('even_ediciones_editar', edicion.id), title: edicion.name},
                {route:route('even_ediciones_actas_listado', edicion.id), title: 'Libro de Actas y Acuerdos'},
                {title: 'Editar'}
            ]"
        />
        <div class="mt-6 w-full">
            <MinutesEditForm
                :edicion="edicion"
                :typeSessions="typeSessions"
                :tinyApiKey="tinyApiKey"
                :ubigeo="ubigeo"
                :documentTypes="documentTypes"
                :accordance="accordance"
            />
        </div>
    </AppLayout>
</template>
