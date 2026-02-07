<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import CreateForm from './Partials/CreateForm.vue';
import { Link } from '@inertiajs/vue3';
import Navigation from '@/Components/vristo/layout/Navigation.vue';

const props = defineProps({
    categories: {
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
    <AppLayout title="Crear Categoría">
        <Navigation :routeModule="route('sales_dashboard')" :titleModule="'Ventas'"
            :data="[
                {route: route('sale_category_product_list'), title: 'Categorías'},
                {title: 'Nuevo'}
            ]"
        />
        <div class="mt-5">
            <CreateForm :categories="categories" :P000014="P000014" />
        </div>
    </AppLayout>
</template>
