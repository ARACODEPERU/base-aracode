<script setup>
import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
import Navigation from "@/Components/vristo/layout/Navigation.vue";
import ContractForm from "./Partials/ContractForm.vue";

defineProps({
    clients: Object,
    identityDocumentTypes: Object,
    contractTypes: Array,
    currencyTypes: { type: Array, default: () => [] },
});
</script>

<template>
    <AppLayout title="Nuevo contrato">
        <Navigation :routeModule="route('comm_dashboard')" titleModule="Comercial"
            :data="[
                {route: route('comm_contracts'), title: 'Contratos'},
                {title: 'Nuevo contrato'}
            ]"
        />

        <div class="mt-5">
            <ContractForm
                :clients="clients"
                :identity-document-types="identityDocumentTypes"
                :contract-types="contractTypes"
                :currency-types="currencyTypes"
            />
        </div>
    </AppLayout>
</template>
