<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import CreateItemForm from './Partials/CreateItemForm.vue';

    const props = defineProps({
        types: {
            type: Object,
            default: () => ({}),
        }
    });
</script>

<template>
    <AppLayout title="Crear Item">
        <Navigation
            :routeModule="route('cms_dashboard')"
            :titleModule="'CMS'"
            :data="[
                { title: 'Items', route: route('cms_items_list') },
                { title: 'Nuevo' }
            ]"
        />
        <div class="mt-5">
            <CreateItemForm :types="types" />
        </div>
    </AppLayout>
</template>
