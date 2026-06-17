<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import PurchaseForm from './Partials/PurchaseForm.vue';

defineProps({
    supplies: { type: Array, default: () => [] },
});
</script>

<template>
    <AppLayout title="Registrar compra">
        <Navigation
            :routeModule="route('res_dashboard')"
            titleModule="Restaurante"
            :data="[{ title: 'Insumos', route: route('res_supplies_list') }, { title: 'Registrar compra' }]"
        />
        <div class="pt-5">
            <PurchaseForm :supplies="supplies" />
        </div>
    </AppLayout>
</template>
