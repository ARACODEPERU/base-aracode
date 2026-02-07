<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import CreateProductForm from './Partials/CreateProductForm.vue';
import { Link } from '@inertiajs/vue3';
import Navigation from '@/Components/vristo/layout/Navigation.vue';

const props = defineProps({
    establishments: {
        type: Object,
        default: () => ({}),
    },
    categories: {
        type: Object,
        default: () => ({}),
    },
    brands: {
        type: Object,
        default: () => ({}),
    },
    P000014: {
        type: String,
        default: "1",
    }
});
</script>

<template>
    <AppLayout title="Crear Producto">
        <Navigation :routeModule="route('sales_dashboard')" :titleModule="'Ventas'"
            :data="[
                {route: route('products.index'), title: 'Productos'},
                {title: 'Nuevo'}
            ]"
        />
        <div class="mt-5">
            <CreateProductForm
                :categories="categories"
                :establishments="establishments"
                :brands="brands"
                :P000014="P000014"
            />
        </div>
    </AppLayout>
</template>
