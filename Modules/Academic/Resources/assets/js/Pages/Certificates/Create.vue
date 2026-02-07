<script setup>
    import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
    import CreateForm from './Partials/CreateForm.vue';

    import Navigation from '@/Components/vristo/layout/Navigation.vue';


    defineProps({
        courses:{
            type: Object,
            default : () => ({})
        }
    });

</script>

<template>
    <AppLayout title="Certificados">
        <Navigation :routeModule="route('aca_dashboard')" :titleModule="'Académico'"
            :data="[
                {
                    route: route('aca_certificate_list'),
                    title: 'Certificados',
                },
                {title: 'Nuevo'}
            ]"
        />
        <div class="pt-5">
            <CreateForm :courses="courses" />
        </div>
    </AppLayout>
</template>
