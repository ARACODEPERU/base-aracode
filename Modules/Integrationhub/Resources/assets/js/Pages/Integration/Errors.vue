<script setup>
import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import ErrorLogs from './Partials/ErrorLogs.vue';
</script>

<template>
    <AppLayout title="Errores de Integración">
        <Navigation :routeModule="route('integrationhub_listado')" :titleModule="'Centro de Integraciones'"
            :data="[
                {route: route('integrationhub_listado'), title: 'Integraciones'},
                {title: 'Errores'}
            ]"
        />
        <div class="pt-5">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Errores de Integración</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Registro de errores generados por las integraciones del sistema.</p>
            </div>
            <div class="panel">
                <ErrorLogs :integration-id="0" />
            </div>
        </div>
    </AppLayout>
</template>
