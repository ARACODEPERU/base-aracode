<script setup>
import { ref } from 'vue';
import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import { Link, router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import axios from 'axios';
import PrimaryButton from "@/Components/PrimaryButton.vue";
import Keypad from '@/Components/Keypad.vue';
import Pagination from '@/Components/Pagination.vue';
import { useForm } from '@inertiajs/vue3';
import IconLoader from '@/Components/vristo/icon/icon-loader.vue';

const props = defineProps({
    authors: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
});

const form = useForm({ search: props.filters.search || '' });

const deleteAuthor = (id) => {
    Swal.fire({
        title: '¿Estás seguro?',
        text: 'Esta acción eliminará permanentemente el autor.',
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
            return axios.delete(route('bib_authors_destroy', id)).then((res) => {
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
            router.visit(route('bib_authors'), {
                replace: false,
                method: 'get',
                preserveState: true,
                preserveScroll: true,
                only: ['authors'],
            });
        }
    });
};
</script>

<template>
    <AppLayout title="Autores">
        <Navigation :routeModule="route('bib_dashboard')" :titleModule="'Biblio Data'"
            :data="[
                {title: 'Autores'}
            ]"
        />
        <div class="pt-5 space-y-8">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <h2 class="text-xl">Autores</h2>
                <div class="flex sm:flex-row flex-col sm:items-center sm:gap-3 gap-4 w-full sm:w-auto">
                    <form @submit.prevent="router.visit(route('bib_authors', { search: form.search }))">
                        <div class="relative">
                            <input v-model="form.search" type="text" placeholder="Buscar autores..."
                                class="form-input pl-10 pr-4 w-full" />
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </form>
                    <Keypad>
                        <template #botones>
                            <Link :href="route('bib_authors_create')" class="btn btn-primary">
                                Nuevo Autor
                            </Link>
                        </template>
                    </Keypad>
                </div>
            </div>

            <div class="panel">
                <div v-if="authors?.data?.length > 0">
                    <div class="grid sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-6">
                        <div v-for="author in authors.data" :key="author.id"
                            class="flex flex-col bg-white border border-gray-200 shadow-2xs rounded-xl hover:shadow-md transition dark:bg-neutral-900 dark:border-neutral-800">
                            <div class="p-4 md:p-5">
                                <div class="flex items-center gap-4 mb-3">
                                    <div class="w-14 h-14 rounded-full bg-blue-100 dark:bg-blue-800 flex items-center justify-center overflow-hidden shrink-0">
                                        <img v-if="author.person?.image" :src="'/storage/' + author.person.image" class="w-full h-full object-cover" />
                                        <span v-else class="text-xl font-bold text-blue-600 dark:text-blue-300">
                                            {{ author.person?.names?.charAt(0) }}{{ author.person?.father_lastname?.charAt(0) }}
                                        </span>
                                    </div>
                                    <div class="min-w-0">
                                        <h3 class="font-semibold text-gray-800 dark:text-neutral-200 truncate">
                                            {{ author.person?.full_name }}
                                        </h3>
                                        <p class="text-xs text-gray-500 dark:text-neutral-500">
                                            {{ author.person?.number }}
                                        </p>
                                    </div>
                                </div>
                                <p v-if="author.biography" class="text-sm text-gray-600 dark:text-neutral-400 line-clamp-2 mb-2">
                                    {{ author.biography }}
                                </p>
                                <div v-if="author.specialty" class="mb-3">
                                    <span class="px-2 py-1 text-xs rounded-full bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300">
                                        {{ author.specialty }}
                                    </span>
                                </div>
                                <div class="flex gap-2 pt-3 border-t border-gray-100 dark:border-neutral-700">
                                    <Link :href="route('bib_authors_edit', author.id)"
                                        class="text-xs font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400 flex items-center gap-1 transition">
                                        <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M441 58.9L453.1 71c9.4 9.4 9.4 24.6 0 33.9L424 134.1 377.9 88 407 58.9c9.4-9.4 24.6-9.4 33.9 0zM209.8 256.2L344 121.9 390.1 168 255.8 302.2c-2.9 2.9-6.5 5-10.4 6.1l-58.5 16.7 16.7-58.5c1.1-3.9 3.2-7.5 6.1-10.4zM373.1 25L175.8 222.2c-8.7 8.7-15 19.4-18.3 31.1l-28.6 100c-2.4 8.4-.1 17.4 6.1 23.6s15.2 8.5 23.6 6.1l100-28.6c11.8-3.4 22.5-9.7 31.1-18.3L487 138.9c28.1-28.1 28.1-73.7 0-101.8L474.9 25C446.8-3.1 401.2-3.1 373.1 25zM88 64C39.4 64 0 103.4 0 152L0 424c0 48.6 39.4 88 88 88l272 0c48.6 0 88-39.4 88-88l0-112c0-13.3-10.7-24-24-24s-24 10.7-24 24l0 112c0 22.1-17.9 40-40 40L88 464c-22.1 0-40-17.9-40-40l0-272c0-22.1 17.9-40 40-40l112 0c13.3 0 24-10.7 24-24s-10.7-24-24-24L88 64z"/></svg>
                                        Editar
                                    </Link>
                                    <button @click="deleteAuthor(author.id)"
                                        class="text-xs font-medium text-red-600 hover:text-red-800 dark:text-red-400 flex items-center gap-1 transition">
                                        <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M135.2 17.7C140.6 6.8 151.7 0 163.8 0L284.2 0c12.1 0 23.2 6.8 28.6 17.7L320 32l96 0c17.7 0 32 14.3 32 32s-14.3 32-32 32L32 96C14.3 96 0 81.7 0 64S14.3 32 32 32l96 0 7.2-14.3zM32 128l384 0 0 320c0 35.3-28.7 64-64 64L96 512c-35.3 0-64-28.7-64-64l0-320zm96 64c-8.8 0-16 7.2-16 16l0 224c0 8.8 7.2 16 16 16s16-7.2 16-16l0-224c0-8.8-7.2-16-16-16zm96 0c-8.8 0-16 7.2-16 16l0 224c0 8.8 7.2 16 16 16s16-7.2 16-16l0-224c0-8.8-7.2-16-16-16zm96 0c-8.8 0-16 7.2-16 16l0 224c0 8.8 7.2 16 16 16s16-7.2 16-16l0-224c0-8.8-7.2-16-16-16z"/></svg>
                                        Eliminar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="flex justify-center py-12">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-16 h-16 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <p class="mt-2 text-gray-500">No hay autores registrados</p>
                </div>
            </div>

            <div v-if="authors?.data?.length > 0" class="mt-4">
                <Pagination :data="authors" />
            </div>
        </div>
    </AppLayout>
</template>
