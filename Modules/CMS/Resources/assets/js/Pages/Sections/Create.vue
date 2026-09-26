<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import CreatePageForm from './Partials/CreatePageForm.vue';
</script>

<template>
    <AppLayout title="Crear Sección">
        <Navigation
            :routeModule="route('cms_dashboard')"
            :titleModule="'CMS'"
            :data="[
                { title: 'Secciones', route: route('cms_section_list') },
                { title: 'Nuevo' }
            ]"
        />
        <div class="mt-5">
            <CreatePageForm />
        </div>
    </AppLayout>
</template>
