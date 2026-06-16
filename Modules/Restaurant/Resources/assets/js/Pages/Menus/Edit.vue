<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import EditForm from './Partials/EditForm.vue';

defineProps({
    resmenu: {
        type: Object,
        default: () => ({}),
    },
});
</script>

<template>
    <AppLayout title="Editar carta del día">
        <Navigation
            :routeModule="route('res_dashboard')"
            titleModule="Restaurante"
            :data="[{ title: 'Carta del día', route: route('res_menu_list') }, { title: 'Editar' }]"
        />
        <div class="pt-5">
            <EditForm :resmenu="resmenu" />
        </div>
    </AppLayout>
</template>
