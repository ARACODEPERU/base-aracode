<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import EditForm from './Partials/EditForm.vue';
    import { Link } from '@inertiajs/vue3';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';

    const props = defineProps({
        ubigeo: {
            type: Object,
            default: () => ({}),
        },
        documentTypes: {
            type: Object,
            default: () => ({}),
        },
        team: {
            type: Object,
            default: () => ({}),
        }
    });
</script>

<template>
    <AppLayout title="Equipos">
        <Navigation :routeModule="route('even_dashboard')" :titleModule="'Eventos sociales'">
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <Link :href="route('even_equipos_listado')" class="text-primary hover:underline">Equipos</Link>
            </li>
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <span>Editar</span>
            </li>
        </Navigation>
        <div class="mt-6 w-full">
            <EditForm
                :ubigeo="ubigeo"
                :documentTypes="documentTypes"
                :team="team"
            />
        </div>
    </AppLayout>
</template>
