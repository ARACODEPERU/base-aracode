<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import CreateForm from './Partials/CreateForm.vue';
import { Link } from '@inertiajs/vue3';
import Navigation from '@/Components/vristo/layout/Navigation.vue';

</script>

<template>
    <AppLayout title="Crear Marca">
        <Navigation :routeModule="route('sales_dashboard')" :titleModule="'Ventas'"
            :data="[
                {route:route('sale_brands_product_list'), title: 'Marcas'},
                {title: 'Nuevo'}
            ]"
        />
        <div class="mt-5">

            <CreateForm />
        </div>
    </AppLayout>
</template>
