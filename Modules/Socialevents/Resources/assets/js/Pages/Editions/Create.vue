<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import CreateForm from './Partials/CreateForm.vue';
    import { Link } from '@inertiajs/vue3';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';

    const props = defineProps({
        sportsMode: { type: Boolean, default: false },
        canManageRates: { type: Boolean, default: false },
        eventos: {
            type: Object,
            default: () => ({}),
        },
        formats: {
            type: Object,
            default: () => ({}),
        },
        edicion: {
            type: Object,
            default: () => ({}),
        }
    });
</script>

<template>
    <AppLayout :title="sportsMode ? 'Gestión Deportiva' : 'Ediciones'">
        <Navigation :routeModule="route('even_dashboard')" :titleModule="sportsMode ? 'GESTIÓN DEPORTIVA' : 'Eventos sociales'"
            :data="[
                {
                    route: route('even_ediciones_listado'), title: sportsMode ? 'Torneos' : 'Ediciones'
                },
                {title: 'Nuevo'}
            ]"
        />
        <div class="mt-6 w-full">
            <CreateForm
                :sportsMode="sportsMode"
                :canManageRates="canManageRates"
                :eventos="eventos"
                :formats="formats"
            />
        </div>
    </AppLayout>
</template>
