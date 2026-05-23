<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import PlanForm from './Partials/Form.vue';

defineProps({
    books: { type: Array, default: () => [] },
});
</script>

<template>
    <AppLayout title="Nuevo plan">
        <Navigation
            :routeModule="route('bib_dashboard')"
            :titleModule="'Biblio Data'"
            :data="[
                { title: 'Planes', route: route('bib_subscription_plans') },
                { title: 'Nuevo' },
            ]"
        />
        <div class="pt-5">
            <PlanForm :books="books" />
        </div>
    </AppLayout>
</template>
