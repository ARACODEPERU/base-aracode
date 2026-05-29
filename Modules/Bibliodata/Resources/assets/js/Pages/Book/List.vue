<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import Pagination from '@/Components/Pagination.vue';
import Keypad from '@/Components/Keypad.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import axios from 'axios';
import { Empty } from 'ant-design-vue';

const props = defineProps({
    books: { type: Object, default: () => ({}) },
    categories: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const form = useForm({
    search: props.filters.search || '',
    category_id: props.filters.category_id || '',
});

const search = () => {
    router.visit(
        route('bib_books', {
            search: form.search,
            category_id: form.category_id,
        })
    );
};

const deleteBook = (id) => {
    Swal.fire({
        title: '¿Estás seguro?',
        text: 'Se eliminará el libro y todo su contenido.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Eliminar',
        cancelButtonText: 'Cancelar',
        showLoaderOnConfirm: true,
        padding: '2em',
        customClass: 'sweet-alerts',
        preConfirm: () =>
            axios.delete(route('bib_books_destroy', id)).then((res) => {
                if (res.data && !res.data.success) {
                    Swal.showValidationMessage(res.data.message || 'Error al eliminar');
                }
                return res;
            }),
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                icon: 'success',
                title: 'Eliminado',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
            router.visit(route('bib_books'), { preserveScroll: true });
        }
    });
};

const statusBadge = (status) => {
    const map = {
        available: 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300',
        restricted: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300',
        archived: 'bg-gray-100 text-gray-500 dark:bg-zinc-700 dark:text-gray-400',
    };
    return map[status] || 'bg-gray-100 text-gray-500';
};

const statusLabel = (status) => {
    const map = { available: 'Disponible', restricted: 'Restringido', archived: 'Archivado' };
    return map[status] || status;
};

const structureLabel = (structure) => {
    const map = {
        chapter_subchapter: 'Capítulo → Sub-capítulo',
        level_content: 'Nivel → Contenido',
    };
    return map[structure] || structure;
};
</script>

<template>
    <AppLayout title="Libros">
        <Navigation
            :routeModule="route('bib_dashboard')"
            :titleModule="'Biblio Data'"
            :data="[{ title: 'Libros' }]"
        />

        <div class="pt-5 space-y-5">
            <div class="panel">
                <div class="flex flex-col lg:flex-row lg:items-end gap-4 justify-between">
                    <div class="flex flex-col sm:flex-row gap-3 flex-1">
                        <div class="flex-1">
                            <TextInput
                                v-model="form.search"
                                type="search"
                                class="w-full"
                                placeholder="Buscar por título o autor..."
                                @keyup.enter="search"
                            />
                        </div>
                        <select v-model="form.category_id" class="form-select sm:w-48" @change="search">
                            <option value="">Todas las categorías</option>
                            <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                        <button type="button" class="btn btn-primary" @click="search">Buscar</button>
                    </div>
                    <Keypad>
                        <template #botones>
                            <Link
                                v-if="$page.props.auth?.user"
                                :href="route('bib_books_create')"
                                class="btn btn-success text-xs uppercase"
                            >
                                Nuevo libro
                            </Link>
                        </template>
                    </Keypad>
                </div>
            </div>

            <div v-if="books.data?.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                <div
                    v-for="book in books.data"
                    :key="book.id"
                    class="panel p-0 overflow-hidden flex flex-col hover:shadow-lg transition"
                >
                    <div class="aspect-[3/4] bg-gray-100 dark:bg-zinc-800 relative">
                        <img
                            v-if="book.cover_image"
                            :src="'/storage/' + book.cover_image"
                            :alt="book.title"
                            class="w-full h-full object-cover"
                        />
                        <div v-else class="w-full h-full flex items-center justify-center text-gray-300">
                            <svg class="w-16 h-16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
                                <path
                                    fill="currentColor"
                                    d="M96 0C43 0 0 43 0 96V416c0 53 43 96 96 96H352c53 0 96-43 96-96V96c0-53-43-96-96-96H96zM128 224v32H320V224H128zm0 96v32H320V320H128zM96 64H352c8.8 0 16 7.2 16 16V160H80V80c0-8.8 7.2-16 16-16z"
                                />
                            </svg>
                        </div>
                        <span
                            class="absolute top-2 right-2 text-xs px-2 py-0.5 rounded-full"
                            :class="statusBadge(book.status)"
                        >
                            {{ statusLabel(book.status) }}
                        </span>
                    </div>
                    <div class="p-4 flex-1 flex flex-col">
                        <h3 class="font-semibold text-gray-800 dark:text-neutral-200 line-clamp-2">{{ book.title }}</h3>
                        <p class="text-sm text-gray-500 mt-1 line-clamp-1">
                            {{ book.author?.person?.formatted_name || book.author?.person?.full_name || 'Sin autor' }}
                        </p>
                        <p class="text-xs text-gray-400 mt-1">{{ book.category?.name }}</p>
                        <span
                            v-if="book.content_structure"
                            class="inline-block mt-1 text-[10px] px-1.5 py-0.5 rounded bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300"
                        >
                            {{ structureLabel(book.content_structure) }}
                        </span>
                        <p v-if="book.pages" class="text-xs text-gray-400">{{ book.pages }} pág. (ref.)</p>
                        <div class="flex flex-wrap gap-2 mt-4 pt-3 border-t border-gray-100 dark:border-zinc-700">
                            <Link
                                :href="route('bib_books_content', book.id)"
                                class="btn btn-primary btn-sm text-xs flex-1 text-center"
                            >
                                Contenido
                            </Link>
                            <Link
                                :href="route('bib_books_edit', book.id)"
                                class="btn btn-outline-primary btn-sm text-xs"
                            >
                                Editar
                            </Link>
                            <button
                                type="button"
                                class="btn btn-outline-danger btn-sm text-xs"
                                @click="deleteBook(book.id)"
                            >
                                Eliminar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="panel py-12">
                <Empty description="No hay libros registrados" />
            </div>

            <Pagination v-if="books?.data?.length" :data="books" class="mt-4" />
        </div>
    </AppLayout>
</template>
