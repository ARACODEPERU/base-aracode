<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import EditProviderForm from './Partials/EditProviderForm.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';

    defineProps({
        provider: {
            type: Object,
            default: () => ({}),
        }
    });
</script>

<template>
    <AppLayout title="Edit Provider">
        <Navigation :routeModule="route('purchases_dashboard')" :titleModule="'Compras'"
            :data="[
                {route: route('providers.index'), title: 'Proveedores'},
                {title: 'Nuevo'}
            ]"
        />
        <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
            <EditProviderForm
                :provider="provider"
            />
        </div>
    </AppLayout>
</template>
