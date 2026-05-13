<script setup>
import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import { Link, router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import axios from 'axios';
import { useForm } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue';
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

const deleteBook = (id) => {
    Swal.fire({
        title: '¿Estás seguro?',
        text: 'Esta acción eliminará el libro y todo su contenido.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: '¡Sí, Eliminar!',
        cancelButtonText: 'Cancelar',
        showLoaderOnConfirm: true,
        padding: '2em',
        customClass: 'sweet-alerts',
        preConfirm: () => {
            return axios.delete(route('bib_books_destroy', id)).then((res) => {
                if (res.data && !res.data.success) {
                    Swal.showValidationMessage(res.data.message || 'Error al eliminar')
                }
                return res
            }).catch((error) => {
                Swal.showValidationMessage(error.response?.data?.message || 'Error de conexión')
            });
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Enhorabuena',
                text: 'Se eliminó correctamente',
                icon: 'success',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
            router.visit(route('bib_books'), {
                replace: false,
                method: 'get',
                preserveState: true,
                preserveScroll: true,
                only: ['books', 'categories'],
            });
        }
    });
};

const search = () => {
    router.visit(route('bib_books', {
        search: form.search,
        category_id: form.category_id,
    }));
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
</script>

<template>
    <AppLayout title="Libros">
        <Navigation :routeModule="route('bib_dashboard')" :titleModule="'Biblio Data'"
            :data="[
                {title: 'Libros'}
            ]"
        />

        <div class="pt-5 space-y-8">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <h2 class="text-xl font-bold">Libros</h2>
                <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                    <div class="flex gap-2 w-full sm:w-auto">
                        <div class="relative flex-1 sm:flex-initial">
                            <input v-model="form.search" type="text" placeholder="Buscar por título o autor..."
                                class="form-input pl-10 pr-4 w-full sm:w-64"
                                @keyup.enter="search" />
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <select v-model="form.category_id" @change="search" class="form-select w-auto">
                            <option value="">Todas las categorías</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                        </select>
                    </div>
                    <Link :href="route('bib_books_create')" class="btn btn-primary whitespace-nowrap">
                        + Nuevo Libro
                    </Link>
                </div>
            </div>

            <!-- Grid de libros -->
            <div class="panel">
                <div v-if="books?.data?.length > 0">
                    <div class="grid sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4">
                        <div v-for="book in books.data" :key="book.id"
                            class="group flex flex-col bg-white dark:bg-neutral-900 border border-gray-200 dark:border-neutral-800 rounded-xl hover:shadow-lg transition-shadow overflow-hidden">
                            <!-- Cover -->
                            <div class="h-40 bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-blue-900/30 dark:to-indigo-900/30 flex items-center justify-center relative overflow-hidden">
                                <img v-if="book.cover_image" :src="'/storage/' + book.cover_image"
                                    class="w-full h-full object-cover" />
                                <svg v-else class="w-16 h-16 text-blue-300 dark:text-blue-600"
                                    xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
                                    <path fill="currentColor" d="M96 0C43 0 0 43 0 96L0 416c0 53 43 96 96 96l288 0 32 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-32 0 0-64 32 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-32 0 0-64 32 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-32 0 0-64c0-53-43-96-96-96L96 0zM64 96c0-17.7 14.3-32 32-32l192 0c17.7 0 32 14.3 32 32l0 224-32 0L96 320l0-32 192 0 0-32L96 256l0-32 192 0 0-32L96 192l0-32 192 0 0-32L96 128 96 96z"/>
                                </svg>
                                <span class="absolute top-2 right-2 px-2 py-0.5 text-xs rounded-full font-medium"
                                    :class="statusBadge(book.status)">
                                    {{ statusLabel(book.status) }}
                                </span>
                            </div>
                            <!-- Info -->
                            <div class="p-4 flex-1 flex flex-col">
                                <h3 class="font-semibold text-gray-800 dark:text-neutral-200 line-clamp-2 mb-1">
                                    {{ book.title }}
                                </h3>
                                <p class="text-xs text-gray-500 dark:text-neutral-400 mb-2">
                                    {{ book.author?.person?.full_name || 'Sin autor' }}
                                </p>
                                <div class="flex items-center gap-2 mb-3">
                                    <span class="px-2 py-0.5 text-xs rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300">
                                        {{ book.category?.name }}
                                    </span>
                                    <span v-if="book.pages" class="text-xs text-gray-400">{{ book.pages }} pág.</span>
                                </div>
                                <div v-if="book.tags?.length" class="flex flex-wrap gap-1 mb-3">
                                    <span v-for="tag in book.tags" :key="tag.id"
                                        class="px-1.5 py-0.5 text-[10px] rounded-full bg-purple-50 text-purple-600 dark:bg-purple-900/40 dark:text-purple-300">
                                        {{ tag.name }}
                                    </span>
                                </div>
                                <div class="mt-auto flex gap-2 pt-3 border-t border-gray-100 dark:border-neutral-700">
                                    <Link :href="route('bib_books_edit', book.id)"
                                        class="text-xs font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400 flex items-center gap-1 transition">
                                        <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M441 58.9L453.1 71c9.4 9.4 9.4 24.6 0 33.9L424 134.1 377.9 88 407 58.9c9.4-9.4 24.6-9.4 33.9 0zM209.8 256.2L344 121.9 390.1 168 255.8 302.2c-2.9 2.9-6.5 5-10.4 6.1l-58.5 16.7 16.7-58.5c1.1-3.9 3.2-7.5 6.1-10.4zM373.1 25L175.8 222.2c-8.7 8.7-15 19.4-18.3 31.1l-28.6 100c-2.4 8.4-.1 17.4 6.1 23.6s15.2 8.5 23.6 6.1l100-28.6c11.8-3.4 22.5-9.7 31.1-18.3L487 138.9c28.1-28.1 28.1-73.7 0-101.8L474.9 25C446.8-3.1 401.2-3.1 373.1 25zM88 64C39.4 64 0 103.4 0 152L0 424c0 48.6 39.4 88 88 88l272 0c48.6 0 88-39.4 88-88l0-112c0-13.3-10.7-24-24-24s-24 10.7-24 24l0 112c0 22.1-17.9 40-40 40L88 464c-22.1 0-40-17.9-40-40l0-272c0-22.1 17.9-40 40-40l112 0c13.3 0 24-10.7 24-24s-10.7-24-24-24L88 64z"/></svg>
                                        Editar
                                    </Link>
                                    <button @click="deleteBook(book.id)"
                                        class="text-xs font-medium text-red-600 hover:text-red-800 dark:text-red-400 flex items-center gap-1 transition">
                                        <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M135.2 17.7C140.6 6.8 151.7 0 163.8 0L284.2 0c12.1 0 23.2 6.8 28.6 17.7L320 32l96 0c17.7 0 32 14.3 32 32s-14.3 32-32 32L32 96C14.3 96 0 81.7 0 64S14.3 32 32 32l96 0 7.2-14.3zM32 128l384 0v320c0 35.3-28.7 64-64 64H96c-35.3 0-64-28.7-64-64V128zm96 64c-8.8 0-16 7.2-16 16v224c0 8.8 7.2 16 16 16s16-7.2 16-16V208c0-8.8-7.2-16-16-16zm96 0c-8.8 0-16 7.2-16 16v224c0 8.8 7.2 16 16 16s16-7.2 16-16V208c0-8.8-7.2-16-16-16zm96 0c-8.8 0-16 7.2-16 16v224c0 8.8 7.2 16 16 16s16-7.2 16-16V208c0-8.8-7.2-16-16-16z"/></svg>
                                        Eliminar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="flex flex-col items-center justify-center py-16">
                    <Empty image="/img/empty-box.png" :image-style="{ height: '60px' }" />
                    <p class="mt-3 text-gray-500 dark:text-gray-400">No hay libros registrados</p>
                </div>
            </div>

            <div v-if="books?.data?.length" class="mt-4">
                <Pagination :data="books" />
            </div>
        </div>
    </AppLayout>
</template>
