<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import GroupItemsForm from './Partials/GroupItemsForm.vue';

    const props = defineProps({
        section: {
            type: Object,
            default: () => ({}),
        },
        types: {
            type: Object,
            default: () => ({}),
        },
        groups: {
            type: Object,
            default: () => ({}),
        }
    });
</script>

<template>
    <AppLayout title="Grupos de Items">
        <Navigation
            :routeModule="route('cms_dashboard')"
            :titleModule="'CMS'"
            :data="[
                { title: 'Secciones', route: route('cms_section_list') },
                { title: section.description || 'Grupos de items' }
            ]"
        />
        <div class="mt-5">
            <GroupItemsForm :section="section" :types="types" :groups="groups" />
        </div>
    </AppLayout>
</template>
