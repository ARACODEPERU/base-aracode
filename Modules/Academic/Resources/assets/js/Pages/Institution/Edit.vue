<script setup>
import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
import EditForm from './Partials/EditForm.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';

const props = defineProps({
    institution: {
        type: Object,
        default: () => ({}),
    }
});

</script>

<template>
    <AppLayout title="Editar Institución">
        <Navigation :routeModule="route('aca_dashboard')" :titleModule="'Académico'"
            :data="[
                {route: route('aca_institutions_list'), title: 'Institución'},
                {title: 'Editar'}
            ]"
        />
        <div class="pt-5">
            <EditForm :institution="institution" />
        </div>
    </AppLayout>
</template>
