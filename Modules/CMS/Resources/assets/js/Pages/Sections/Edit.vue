<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import EditPageForm from './Partials/EditPageForm.vue';

    const props = defineProps({
        section: {
            type: Object,
            default: () => ({}),
        }
    });
</script>

<template>
    <AppLayout title="Editar Sección">
        <Navigation
            :routeModule="route('cms_dashboard')"
            :titleModule="'CMS'"
            :data="[
                { title: 'Secciones', route: route('cms_section_list') },
                { title: 'Editar' }
            ]"
        />
        <div class="mt-5">
            <EditPageForm :section="section" />
        </div>
    </AppLayout>
</template>
