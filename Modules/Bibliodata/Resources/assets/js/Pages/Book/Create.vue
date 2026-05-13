<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
import BookForm from './Partials/BookForm.vue';
import ContentBuilder from './Partials/ContentBuilder.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';

const props = defineProps({
    authors: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    tags: { type: Array, default: () => [] },
});

const formRef = ref(null);
const sections = ref([]);

const handleSave = () => {
    const form = formRef.value?.form;
    if (form) {
        form.sections = sections.value;
        form.post(route('bib_books_store'), {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    }
};
</script>

<template>
    <AppLayout title="Nuevo Libro">
        <Navigation :routeModule="route('bib_dashboard')" :titleModule="'Biblio Data'"
            :data="[
                {title: 'Libros', route: route('bib_books')},
                {title: 'Nuevo'}
            ]"
        />
        <div class="pt-5 space-y-6">
            <BookForm
                ref="formRef"
                :authors="authors"
                :categories="categories"
                :tags="tags"
                @save="handleSave"
            />

            <ContentBuilder v-model="sections" />

            <div class="flex justify-end gap-3">
                <Link :href="route('bib_books')" class="btn btn-danger px-6 py-2">Cancelar</Link>
                <button @click="handleSave" type="button"
                    class="btn btn-primary px-6 py-2 flex items-center gap-2">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M48 96V416c0 8.8 7.2 16 16 16H384c8.8 0 16-7.2 16-16V170.5c0-4.2-1.7-8.3-4.7-11.3l33.9-33.9c12 12 18.7 28.3 18.7 45.3V416c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V96C0 60.7 28.7 32 64 32H309.5c17 0 33.3 6.7 45.3 18.7l74.5 74.5-33.9 33.9L320 93.3V160c0 17.7-14.3 32-32 32H128c-17.7 0-32-14.3-32-32V64H64C55.3 64 48 71.3 48 80zM117.5 64v96h128V64H117.5zM256 304A64 64 0 1 0 128 304a64 64 0 1 0 128 0z"/></svg>
                    Guardar Libro
                </button>
            </div>
        </div>
    </AppLayout>
</template>
