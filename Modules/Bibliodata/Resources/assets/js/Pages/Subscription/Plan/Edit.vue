<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import PlanForm from './Partials/Form.vue';

defineProps({
    plan: { type: Object, required: true },
    books: { type: Array, default: () => [] },
});
</script>

<template>
    <AppLayout title="Editar plan">
        <Navigation
            :routeModule="route('bib_dashboard')"
            :titleModule="'Biblio Data'"
            :data="[
                { title: 'Planes', route: route('bib_subscription_plans') },
                { title: 'Editar' },
            ]"
        />
        <div class="pt-5">
            <PlanForm :plan="plan" :books="books" />
        </div>
    </AppLayout>
</template>
