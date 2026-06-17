<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import CreateForm from './Partials/CreateForm.vue';

defineProps({
    units: { type: Array, default: () => [] },
});
</script>

<template>
    <AppLayout title="Nuevo insumo">
        <Navigation
            :routeModule="route('res_dashboard')"
            titleModule="Restaurante"
            :data="[{ title: 'Insumos', route: route('res_supplies_list') }, { title: 'Nuevo' }]"
        />
        <div class="pt-5">
            <CreateForm :units="units" />
        </div>
    </AppLayout>
</template>
