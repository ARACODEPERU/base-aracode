<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
import BookForm from './Partials/BookForm.vue';
import ContentBuilder from './Partials/ContentBuilder.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';

const props = defineProps({
    book: { type: Object, required: true },
    authors: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    tags: { type: Array, default: () => [] },
});

const formRef = ref(null);

const mapSection = (sec) => ({
    temp_id: 'sec_' + sec.id,
    title: sec.title,
    parent_id: sec.parent_id,
    order: sec.order || 0,
    expanded: true,
    children: (sec.children || []).map(mapSection),
    pages: (sec.pages || []).map(p => ({
        temp_id: 'page_' + p.id,
        page_number: p.page_number || 1,
        content: p.content || '',
    })),
});

const sections = ref((props.book.sections || []).map(mapSection));

const handleSave = () => {
    const form = formRef.value?.form;
    if (form) {
        form.sections = sections.value;
        form.post(route('bib_books_update'), {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    }
};
</script>

<template>
    <AppLayout :title="'Editar: ' + book.title">
        <Navigation :routeModule="route('bib_dashboard')" :titleModule="'Biblio Data'"
            :data="[
                {title: 'Libros', route: route('bib_books')},
                {title: 'Editar'}
            ]"
        />
        <div class="pt-5 space-y-6">
            <BookForm
                ref="formRef"
                :book="book"
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
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M48 96V416c0 8.8 7.2 16 16 16H384c8.8 0 16-7.2 16-16V170.5c0-4.2-1.7-8.3-4.7-11.3l33.9-33.9c12 12 18.7 28.3 18.7 45.3V416c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V96C0 60.7 28.7 32 64 32H309.5c17 0 33.3 6.7 45.3 18.7l74.5 74.5-33.9 33.9L320 93.3V160c0 17.7-14.3 32-32 32H128c-17.7 0-32-14.3-32-32V64H64C55.3 64 48 71.3 48 80z"/></svg>
                    Guardar Cambios
                </button>
            </div>
        </div>
    </AppLayout>
</template>
