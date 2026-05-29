<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import RentalForm from './Partials/RentalForm.vue';

defineProps({
    rental: { type: Object, required: true },
    locals: { type: Array, default: () => [] },
    activeRates: { type: Array, default: () => [] },
    eventTypes: { type: Array, default: () => [] },
    ubigeo: { type: Object, default: () => ({}) },
    documentTypes: { type: Object, default: () => ({}) },
});
</script>

<template>
    <AppLayout :title="`Editar reserva #${rental.id}`">
        <Navigation
            :routeModule="route('even_dashboard')"
            :titleModule="'Eventos sociales'"
            :data="[
                { route: route('even_alquiler_local_index'), title: 'Alquiler de local' },
                { title: `Editar reserva #${rental.id}` },
            ]"
        />
        <div class="mt-6 w-full">
            <RentalForm
                mode="edit"
                :rental="rental"
                :locals="locals"
                :active-rates="activeRates"
                :event-types="eventTypes"
                :ubigeo="ubigeo"
                :document-types="documentTypes"
            />
        </div>
    </AppLayout>
</template>
