<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import SubscriptionForm from './Partials/Form.vue';

defineProps({
    plans: { type: Array, default: () => [] },
    organizations: { type: Array, default: () => [] },
    lectorUsers: { type: Array, default: () => [] },
});
</script>

<template>
    <AppLayout title="Nueva suscripción">
        <Navigation
            :routeModule="route('bib_dashboard')"
            :titleModule="'Biblio Data'"
            :data="[
                { title: 'Suscripciones', route: route('bib_subscriptions') },
                { title: 'Nueva' },
            ]"
        />
        <div class="pt-5">
            <SubscriptionForm :plans="plans" :organizations="organizations" :lector-users="lectorUsers" />
        </div>
    </AppLayout>
</template>
