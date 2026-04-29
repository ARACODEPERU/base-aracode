<script setup>
import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
import CreateForm from './Partials/CreateForm.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';

const props = defineProps({
    errors: {
        type: Object,
        default: () => ({}),
    },
});

</script>

<template>
    <AppLayout title="Nueva Integración">
        <Navigation :routeModule="route('integrationhub_listado')" :titleModule="'Centro de Integraciones'"
            :data="[
                {route: route('integrationhub_listado'), title: 'Integraciones'},
                {title: 'Nueva'}
            ]"
        />
        <div class="pt-5">
            <CreateForm />
        </div>
    </AppLayout>
</template>