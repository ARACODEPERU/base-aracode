<script setup>
import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
import Navigation from "@/Components/vristo/layout/Navigation.vue";
import NegotiationForm from "./Partials/NegotiationForm.vue";

defineProps({
    negotiation: Object,
    courses: Object,
    subscriptions: Object,
    identityDocumentTypes: Object,
    currencyTypes: { type: Array, default: () => [] },
    paymentMethods: Array,
    contactChannels: Array,
    companyBilleteras: { type: Array, default: () => [] },
});
</script>

<template>
    <AppLayout title="Editar negociacion">
        <Navigation :routeModule="route('comm_dashboard')" titleModule="Comercial"
            :data="[
                {route: route('comm_negotiations'), title: 'Negociaciones'},
                {title: 'Editar negociacion'}
            ]"
        />

        <div class="mt-5">
            <NegotiationForm
                :negotiation="negotiation"
                :courses="courses"
                :subscriptions="subscriptions"
                :identity-document-types="identityDocumentTypes"
                :currency-types="currencyTypes"
                :payment-methods="paymentMethods"
                :contact-channels="contactChannels"
                :company-billeteras="companyBilleteras"
            />
        </div>
    </AppLayout>
</template>
