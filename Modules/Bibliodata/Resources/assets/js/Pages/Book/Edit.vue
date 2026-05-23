<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import MetadataForm from './Partials/MetadataForm.vue';

defineProps({
    book: { type: Object, required: true },
    authors: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    tags: { type: Array, default: () => [] },
});
</script>

<template>
    <AppLayout :title="'Editar: ' + book.title">
        <Navigation
            :routeModule="route('bib_dashboard')"
            :titleModule="'Biblio Data'"
            :data="[
                { title: 'Libros', route: route('bib_books') },
                { title: 'Editar' },
            ]"
        />
        <div class="pt-5">
            <MetadataForm :book="book" :authors="authors" :categories="categories" :tags="tags" />
        </div>
    </AppLayout>
</template>
