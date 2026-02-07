<script setup>
import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
import CreateForm from './Partials/CreateForm.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';

</script>

<template>
    <AppLayout title="Crear Institución">
        <Navigation :routeModule="route('aca_dashboard')" :titleModule="'Académico'"
            :data="[
                {route: route('aca_institutions_list'), title: 'Institución'},
                {title: 'Nuevo'}
            ]"
        />
        <div class="pt-5">
            <CreateForm />
        </div>
    </AppLayout>
</template>
