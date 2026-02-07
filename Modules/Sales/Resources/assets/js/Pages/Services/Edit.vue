<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import EditForm from './Partials/EditForm.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';

const props = defineProps({
    establishments: {
        type: Object,
        default: () => ({}),
    },
    product: {
        type: Object,
        default: () => ({}),
    }
});
</script>

<template>
    <AppLayout title="Crear Servicio">
        <Navigation :routeModule="route('sales_dashboard')" :titleModule="'Ventas'"
            :data="[
                {route: route('sales_services'), title: 'Servicios'},
                {title: 'Editar'}
            ]"
        />
        <div class="mt-5">
            <EditForm :establishments="props.establishments" :product="product" />
        </div>
    </AppLayout>
</template>
