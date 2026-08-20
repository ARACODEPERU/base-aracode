<script setup>
import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
import Navigation from "@/Components/vristo/layout/Navigation.vue";
import NegotiationForm from "./Partials/NegotiationForm.vue";

defineProps({
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
    <AppLayout title="Nueva negociacion">
        <Navigation :routeModule="route('comm_dashboard')" titleModule="Comercial"
            :data="[
                {route: route('comm_negotiations'), title: 'Negociaciones'},
                {title: 'Nueva negociacion'}
            ]"
        />

        <div class="mt-5">
            <NegotiationForm
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
