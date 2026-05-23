<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import SubscriptionForm from './Partials/Form.vue';

defineProps({
    subscription: { type: Object, required: true },
    plans: { type: Array, default: () => [] },
    organizations: { type: Array, default: () => [] },
    lectorUsers: { type: Array, default: () => [] },
});
</script>

<template>
    <AppLayout title="Editar suscripción">
        <Navigation
            :routeModule="route('bib_dashboard')"
            :titleModule="'Biblio Data'"
            :data="[
                { title: 'Suscripciones', route: route('bib_subscriptions') },
                { title: 'Editar' },
            ]"
        />
        <div class="pt-5">
            <SubscriptionForm
                :subscription="subscription"
                :plans="plans"
                :organizations="organizations"
                :lector-users="lectorUsers"
            />
        </div>
    </AppLayout>
</template>
