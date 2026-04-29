<script setup>
import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
import EditForm from './Partials/EditForm.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';

const props = defineProps({
    integration: {
        type: Object,
        default: () => ({}),
    },
    errors: {
        type: Object,
        default: () => ({}),
    },
});

</script>

<template>
    <AppLayout :title="'Editar: ' + integration.name">
        <Navigation :routeModule="route('integrationhub_listado')" :titleModule="'Centro de Integraciones'"
            :data="[
                {route: route('integrationhub_listado'), title: 'Integraciones'},
                {title: integration.name}
            ]"
        />
        <div class="pt-5">
            <EditForm :integration="integration" />
        </div>
    </AppLayout>
</template>