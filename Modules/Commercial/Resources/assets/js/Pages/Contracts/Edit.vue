<script setup>
import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
import Navigation from "@/Components/vristo/layout/Navigation.vue";
import ContractForm from "./Partials/ContractForm.vue";
import { Head } from "@inertiajs/vue3";

defineProps({
    contract: Object,
    clients: Object,
    identityDocumentTypes: Object,
    contractTypes: Array,
    currencyTypes: { type: Array, default: () => [] },
});
</script>

<template>
    <AppLayout title="Editar contrato">
        <!-- Vista nueva: con Aracodito activo, los avisos de guardado salen con el
             asistente en lugar de SweetAlert2 (ver resources/js/alerta-sistema.js). -->
        <Head>
            <meta name="aracodito-view" content="new" />
        </Head>

        <Navigation :routeModule="route('comm_dashboard')" titleModule="Comercial"
            :data="[
                {route: route('comm_contracts'), title: 'Contratos'},
                {title: 'Editar contrato'}
            ]"
        />

        <div class="mt-5">
            <ContractForm
                :contract="contract"
                :clients="clients"
                :identity-document-types="identityDocumentTypes"
                :contract-types="contractTypes"
                :currency-types="currencyTypes"
            />
        </div>
    </AppLayout>
</template>
