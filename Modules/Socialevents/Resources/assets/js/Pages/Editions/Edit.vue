<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import EditForm from './Partials/EditForm.vue';
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
                {route: route('even_ediciones_listado'), title: sportsMode ? 'Torneos' : 'Ediciones',
                    children: [
                        { route: route('even_ediciones_equipos', edicion.id), title: 'Equipos', permissions: 'even_ediciones_equipos'},
                        { route: route('even_ediciones_fixtures', edicion.id), title: 'Partidos', permissions: 'even_ediciones_fixtures'},
                        { route: route('even_ediciones_pago_sanciones', edicion.id), title: 'Sanciones', permissions: 'even_ediciones_sanciones'},
                        { route: route('even_ediciones_exclusiones', edicion.id), title: 'Exclusiones', permissions: 'even_ediciones_exclusiones'},
                        { route: route('even_ediciones_suspensiones', edicion.id), title: 'Suspensiones', permissions: 'even_ediciones_suspensiones'},
                        { route: route('even_ediciones_actas_listado', edicion.id), title: 'Actas', permissions: 'even_ediciones_actas'},
                        { route: route('even_ediciones_diplomas', edicion.id), title: 'Diplomas', permissions: 'even_ediciones_diplomas'},
                    ]
                },
                {title: 'Editar'}
            ]"
        />
        <div class="mt-6 w-full">
            <EditForm
                :sportsMode="sportsMode"
                :canManageRates="canManageRates"
                :eventos="eventos"
                :formats="formats"
                :edicion="edicion"
            />
        </div>
    </AppLayout>
</template>
