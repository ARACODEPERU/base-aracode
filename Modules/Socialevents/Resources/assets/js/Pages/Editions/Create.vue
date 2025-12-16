<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import CreateForm from './Partials/CreateForm.vue';
    import { Link } from '@inertiajs/vue3';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';

    const props = defineProps({
        eventos: {
            type: Object,
            default: () => ({}),
        },
        formats: {
            type: Object,
            default: () => ({}),
        },
        tinyApiKey: {
            type: String,
            default: () => ({}),
        },
        edicion: {
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
                <span>Nuevo</span>
            </li>
        </Navigation>
        <div class="mt-6 w-full">
            <CreateForm
                :eventos="eventos"
                :formats="formats"
                :tinyApiKey="tinyApiKey"
            />
        </div>
    </AppLayout>
</template>
