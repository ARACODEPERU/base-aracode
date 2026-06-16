<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import CreateForm from './Partials/CreateForm.vue';

defineProps({
    categories: {
        type: Object,
        default: () => ({}),
    }
});
</script>

<template>
    <AppLayout title="Nueva comanda">
        <Navigation
            :routeModule="route('res_dashboard')"
            titleModule="Restaurante"
            :data="[{ title: 'Comandas', route: route('res_comandas_list') }, { title: 'Nuevo' }]"
        />
        <div class="pt-5">
            <CreateForm :categories="categories" />
        </div>
    </AppLayout>
</template>
