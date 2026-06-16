<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import CreateForm from './Partials/CreateForm.vue';
</script>

<template>
    <AppLayout title="Nueva carta del día">
        <Navigation
            :routeModule="route('res_dashboard')"
            titleModule="Restaurante"
            :data="[{ title: 'Carta del día', route: route('res_menu_list') }, { title: 'Nuevo' }]"
        />
        <div class="pt-5">
            <CreateForm />
        </div>
    </AppLayout>
</template>
