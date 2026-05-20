<script setup>
import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
import Navigation from "@/Components/vristo/layout/Navigation.vue";
import ClientForm from "./Partials/ClientForm.vue";

defineProps({
    client: Object,
    identityDocumentTypes: Object,
    countries: Object,
    ubigeo: Object,
});
</script>

<template>
    <AppLayout title="Editar cliente">
        <Navigation :routeModule="route('comm_dashboard')" titleModule="Comercial"
            :data="[
                {route: route('comm_clients'), title: 'Clientes'},
                {title: 'Editar cliente'}
            ]"
        />

        <div class="mt-5">
            <ClientForm
                :client="client"
                :identity-document-types="identityDocumentTypes"
                :countries="countries"
                :ubigeo="ubigeo"
            />
        </div>
    </AppLayout>
</template>
