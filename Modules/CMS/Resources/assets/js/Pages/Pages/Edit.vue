<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import EditPageForm from './Partials/EditPageForm.vue';

    const props = defineProps({
        hey: {
            type: Object,
            default: () => ({}),
        },
        countries: {
            type: Object,
            default: () => ({}),
        }
    });
</script>

<template>
    <AppLayout title="Editar Página">
        <Navigation
            :routeModule="route('cms_dashboard')"
            :titleModule="'CMS'"
            :data="[
                { title: 'Páginas', route: route('cms_pages_list') },
                { title: 'Editar' }
            ]"
        />
        <div class="mt-5">
            <EditPageForm :hey="hey" :countries="countries" />
        </div>
    </AppLayout>
</template>
