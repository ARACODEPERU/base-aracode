<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import EditPageForm from './Partials/EditPageForm.vue';

    const props = defineProps({
        types: {
            type: Object,
            default: () => ({}),
        },
        item: {
            type: Object,
            default: () => ({}),
        }
    });
</script>

<template>
    <AppLayout title="Editar Item">
        <Navigation
            :routeModule="route('cms_dashboard')"
            :titleModule="'CMS'"
            :data="[
                { title: 'Items', route: route('cms_items_list') },
                { title: 'Editar' }
            ]"
        />
        <div class="mt-5">
            <EditPageForm :types="types" :item="item" />
        </div>
    </AppLayout>
</template>
