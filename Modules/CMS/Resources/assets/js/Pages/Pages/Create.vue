<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import CreatePageForm from './Partials/CreatePageForm.vue';

    const props = defineProps({
        countries: {
            type: Object,
            default: () => ({}),
        }
    });
</script>

<template>
    <AppLayout title="Crear Página">
        <Navigation
            :routeModule="route('cms_dashboard')"
            :titleModule="'CMS'"
            :data="[
                { title: 'Páginas', route: route('cms_pages_list') },
                { title: 'Nuevo' }
            ]"
        />
        <div class="mt-5">
            <CreatePageForm :countries="countries" />
        </div>
    </AppLayout>
</template>
