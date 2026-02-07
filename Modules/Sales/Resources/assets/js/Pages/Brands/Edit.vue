<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import EditForm from './Partials/EditForm.vue';
import { Link } from '@inertiajs/vue3';
import Navigation from '@/Components/vristo/layout/Navigation.vue';

const props = defineProps({
    brand: {
        type: Object,
        default: () => ({}),
    }
});
</script>

<template>
    <AppLayout title="Crear Categoría">
        <Navigation :routeModule="route('sales_dashboard')" :titleModule="'Ventas'"
            :data="[
                {route:route('sale_brands_product_list'), title: 'Marcas'},
                {title: 'Editar'}
            ]"
        />
        <div class="mt-5">
            <EditForm :brand="brand" />
        </div>
    </AppLayout>
</template>
