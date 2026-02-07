<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import CreateForm from './Partials/CreateForm.vue';
        import Navigation from '@/Components/vristo/layout/Navigation.vue';

    const props = defineProps({
        establishments: {
            type: Object,
            default: () => ({}),
        }
    });
</script>

<template>
    <AppLayout title="Crear Producto">
        <Navigation :routeModule="route('sales_dashboard')" :titleModule="'Ventas'"
            :data="[
                {route: route('sales_services'), title: 'Servicios'},
                {title: 'Nuevo'}
            ]"
        />
        <div class="max-w-screen-2xl  mx-auto p-4 md:p-6 2xl:p-10">

            <CreateForm :establishments="props.establishments" />
        </div>
    </AppLayout>
</template>
