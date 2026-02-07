<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import CreateProviderForm from './Partials/CreateProviderForm.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';

</script>

<template>
    <AppLayout title="Create Team">
        <Navigation :routeModule="route('purchases_dashboard')" :titleModule="'Compras'"
            :data="[
                {route: route('providers.index'), title: 'Proveedores'},
                {title: 'Nuevo'}
            ]"
        />
        <div class="mt-5">
            <CreateProviderForm />
        </div>
    </AppLayout>
</template>
