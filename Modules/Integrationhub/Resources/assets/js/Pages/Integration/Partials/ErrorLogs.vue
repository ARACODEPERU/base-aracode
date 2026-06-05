<script setup>
import { ref, computed, onMounted } from 'vue';
import Swal2 from 'sweetalert2';
import axios from 'axios';
import ModalLarge from '@/Components/ModalLarge.vue';

const props = defineProps({
    integrationId: {
        type: Number,
        default: 0
    }
});

const loadingErrors = ref(false);
const errors = ref([]);
const pagination = ref(null);
const searchText = ref('');
const dateFrom = ref('');
const dateTo = ref('');
const selectedError = ref(null);
const showErrorModal = ref(false);
const currentPage = ref(1);
const perPage = ref(50);

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleString('es-PE', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
};

const fetchErrors = async (page = 1) => {
    loadingErrors.value = true;
    currentPage.value = page;

    try {
        const params = {
            per_page: perPage.value,
            page: page,
        };

        if (searchText.value) params.search = searchText.value;
        if (dateFrom.value) params.date_from = dateFrom.value;
        if (dateTo.value) params.date_to = dateTo.value;

        const response = await axios.get(route('integrationhub_errors'), { params });
        const data = response.data;
        errors.value = data.errors.data || [];
        pagination.value = {
            current_page: data.errors.current_page,
            last_page: data.errors.last_page,
            total: data.errors.total,
            per_page: data.errors.per_page,
            from: data.errors.from,
            to: data.errors.to,
        };
    } catch (error) {
        Swal2.fire({
            title: 'Error',
            text: 'No se pudieron cargar los errores de integración.',
            icon: 'error',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    } finally {
        loadingErrors.value = false;
    }
};

const goToPage = (page) => {
    if (page >= 1 && page <= (pagination.value?.last_page || 1)) {
        fetchErrors(page);
    }
};

const paginationPages = computed(() => {
    if (!pagination.value) return [];
    const pages = [];
    const current = pagination.value.current_page;
    const last = pagination.value.last_page;
    const start = Math.max(1, current - 2);
    const end = Math.min(last, current + 2);

    if (start > 1) {
        pages.push(1);
        if (start > 2) pages.push('...');
    }
    for (let i = start; i <= end; i++) {
        pages.push(i);
    }
    if (end < last) {
        if (end < last - 1) pages.push('...');
        pages.push(last);
    }
    return pages;
});

const viewErrorDetails = (error) => {
    selectedError.value = error;
    showErrorModal.value = true;
};

const closeErrorModal = () => {
    showErrorModal.value = false;
    selectedError.value = null;
};

const clearFilters = () => {
    searchText.value = '';
    dateFrom.value = '';
    dateTo.value = '';
    fetchErrors(1);
};

const confirmClearErrors = async () => {
    const result = await Swal2.fire({
        title: '¿Limpiar errores?',
        text: dateFrom.value || dateTo.value
            ? 'Se eliminarán los errores según los filtros aplicados.'
            : 'Se eliminarán TODOS los errores de integración registrados.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: '¡Sí, eliminar!',
        cancelButtonText: 'Cancelar',
        padding: '2em',
        customClass: 'sweet-alerts',
    });

    if (!result.isConfirmed) return;

    try {
        const params = {};

        if (dateTo.value) {
            params.before_date = dateTo.value;
        }

        const response = await axios.delete(route('integrationhub_errors_clear'), { params });

        Swal2.fire({
            title: 'Eliminado',
            text: response.data.message || 'Errores eliminados correctamente.',
            icon: 'success',
            padding: '2em',
            customClass: 'sweet-alerts',
        });

        fetchErrors(1);
    } catch (error) {
        Swal2.fire({
            title: 'Error',
            text: error.response?.data?.message || 'No se pudieron eliminar los errores.',
            icon: 'error',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    }
};

const confirmDeleteError = async (error) => {
    const result = await Swal2.fire({
        title: '¿Eliminar este error?',
        text: 'Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: '¡Sí, eliminar!',
        cancelButtonText: 'Cancelar',
        padding: '2em',
        customClass: 'sweet-alerts',
    });

    if (!result.isConfirmed) return;

    try {
        await axios.delete(route('integrationhub_errors_destroy', error.id));

        Swal2.fire({
            title: 'Eliminado',
            text: 'Error eliminado correctamente.',
            icon: 'success',
            timer: 1500,
            showConfirmButton: false,
            padding: '2em',
            customClass: 'sweet-alerts',
        });

        // Cerrar modal si está abierto y coincide
        if (selectedError.value?.id === error.id) {
            closeErrorModal();
        }

        fetchErrors(currentPage.value);
    } catch (error) {
        Swal2.fire({
            title: 'Error',
            text: error.response?.data?.message || 'No se pudo eliminar el error.',
            icon: 'error',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    }
};

onMounted(() => {
    fetchErrors();
});
</script>

<template>
    <div class="space-y-4">
        <!-- Filtros -->
        <div class="flex flex-wrap items-end gap-4">
            <div>
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Buscar:</label>
                <input v-model="searchText" type="text" class="form-input ml-2 w-56" placeholder="Texto del error..." @keyup.enter="fetchErrors(1)" />
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Desde:</label>
                <input v-model="dateFrom" type="date" class="form-input ml-2" />
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Hasta:</label>
                <input v-model="dateTo" type="date" class="form-input ml-2" />
            </div>
            <button
                type="button"
                @click="fetchErrors(1)"
                :disabled="loadingErrors"
                class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition disabled:opacity-60"
            >
                {{ loadingErrors ? 'Buscando...' : 'Buscar' }}
            </button>
            <button
                type="button"
                @click="clearFilters"
                class="px-4 py-2 bg-gray-200 dark:bg-zinc-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-zinc-600 transition"
            >
                Limpiar filtros
            </button>
            <button
                type="button"
                @click="confirmClearErrors"
                class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition ml-auto"
                :title="dateTo ? 'Eliminar errores hasta la fecha seleccionada' : 'Eliminar todos los errores'"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 inline mr-1 -mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                {{ dateTo ? 'Limpiar hasta fecha' : 'Limpiar todo' }}
            </button>
        </div>

        <!-- Información -->
        <div v-if="pagination" class="text-sm text-gray-500 dark:text-gray-400">
            {{ pagination.total }} error(es) registrado(s) — Mostrando {{ pagination.from }}-{{ pagination.to }}
        </div>

        <!-- Estado vacío -->
        <div v-if="!loadingErrors && errors.length === 0" class="text-center py-12">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="text-gray-500 dark:text-gray-400">No hay errores registrados.</p>
            <p class="text-sm text-gray-400 mt-1">Los errores de integración aparecerán aquí automáticamente.</p>
        </div>

        <!-- Loader -->
        <div v-if="loadingErrors" class="flex justify-center py-12">
            <svg class="animate-spin h-8 w-8 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>

        <!-- Tabla de errores -->
        <div v-else-if="errors.length > 0" class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-zinc-700 dark:text-gray-400">
                    <tr>
                        <th class="px-4 py-3">Fecha/Hora</th>
                        <th class="px-4 py-3">Origen</th>
                        <th class="px-4 py-3">Mensaje de Error</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(error, index) in errors" :key="error.id || index" class="border-b dark:border-zinc-600 hover:bg-gray-50 dark:hover:bg-zinc-700/50">
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-300 whitespace-nowrap">
                            {{ formatDate(error.created_at) }}
                        </td>
                        <td class="px-4 py-3">
                            <span v-if="error.source" class="px-2 py-1 bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300 rounded text-xs font-medium max-w-32 truncate inline-block" :title="error.source">
                                {{ error.source }}
                            </span>
                            <span v-else class="text-gray-400 text-xs">-</span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="max-w-md truncate text-gray-900 dark:text-white font-mono text-xs">
                                {{ error.message }}
                            </div>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button @click="viewErrorDetails(error)" class="text-blue-600 hover:text-blue-800 dark:text-blue-400" title="Ver detalle">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>
                                <button @click="confirmDeleteError(error)" class="text-red-500 hover:text-red-700 dark:text-red-400" title="Eliminar">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Paginación -->
            <div v-if="pagination && pagination.last_page > 1" class="flex items-center justify-between px-4 py-3 border-t dark:border-zinc-600">
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    Página {{ pagination.current_page }} de {{ pagination.last_page }}
                </div>
                <div class="flex items-center gap-1">
                    <button
                        @click="goToPage(1)"
                        :disabled="pagination.current_page === 1"
                        class="px-2 py-1 text-xs rounded border dark:border-zinc-600 hover:bg-gray-100 dark:hover:bg-zinc-700 disabled:opacity-40 disabled:cursor-not-allowed"
                    >
                        &laquo;
                    </button>
                    <button
                        @click="goToPage(pagination.current_page - 1)"
                        :disabled="pagination.current_page === 1"
                        class="px-2 py-1 text-xs rounded border dark:border-zinc-600 hover:bg-gray-100 dark:hover:bg-zinc-700 disabled:opacity-40 disabled:cursor-not-allowed"
                    >
                        &lsaquo;
                    </button>
                    <template v-for="(page, idx) in paginationPages" :key="idx">
                        <span v-if="page === '...'" class="px-2 py-1 text-xs text-gray-400">...</span>
                        <button
                            v-else
                            @click="goToPage(page)"
                            :class="page === pagination.current_page
                                ? 'bg-primary text-white border-primary'
                                : 'border dark:border-zinc-600 hover:bg-gray-100 dark:hover:bg-zinc-700'"
                            class="px-3 py-1 text-xs rounded border"
                        >
                            {{ page }}
                        </button>
                    </template>
                    <button
                        @click="goToPage(pagination.current_page + 1)"
                        :disabled="pagination.current_page === pagination.last_page"
                        class="px-2 py-1 text-xs rounded border dark:border-zinc-600 hover:bg-gray-100 dark:hover:bg-zinc-700 disabled:opacity-40 disabled:cursor-not-allowed"
                    >
                        &rsaquo;
                    </button>
                    <button
                        @click="goToPage(pagination.last_page)"
                        :disabled="pagination.current_page === pagination.last_page"
                        class="px-2 py-1 text-xs rounded border dark:border-zinc-600 hover:bg-gray-100 dark:hover:bg-zinc-700 disabled:opacity-40 disabled:cursor-not-allowed"
                    >
                        &raquo;
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal de detalle del error -->
        <ModalLarge :show="showErrorModal" :onClose="closeErrorModal" :icon="'/img/base-de-datos.png'">
            <template #title>
                Detalle del Error
            </template>
            <template #message>
                {{ selectedError ? formatDate(selectedError.created_at) : '' }}
            </template>
            <template #content>
                <div v-if="selectedError" class="space-y-4">
                    <!-- Fecha -->
                    <div class="flex items-center gap-3">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Registrado el:</span>
                        <span class="text-gray-900 dark:text-white font-medium">{{ formatDate(selectedError.created_at) }}</span>
                    </div>

                    <!-- ID -->
                    <div class="flex items-center gap-3">
                        <span class="text-sm text-gray-500 dark:text-gray-400">ID:</span>
                        <code class="text-xs bg-gray-100 dark:bg-zinc-700 px-2 py-1 rounded">{{ selectedError.id }}</code>
                    </div>

                    <!-- Origen -->
                    <div v-if="selectedError.source" class="flex items-center gap-3">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Origen:</span>
                        <span class="px-2 py-1 bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300 rounded text-xs font-medium">{{ selectedError.source }}</span>
                    </div>

                    <!-- Mensaje de error completo -->
                    <div>
                        <span class="text-sm text-gray-500 dark:text-gray-400 block mb-2">Mensaje completo:</span>
                        <pre class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-900 p-4 rounded-lg text-xs font-mono overflow-x-auto max-h-96 overflow-y-auto text-red-700 dark:text-red-300 whitespace-pre-wrap break-words">{{ selectedError.message }}</pre>
                    </div>
                </div>
            </template>
            <template #buttons>
                <div class="flex items-center justify-between w-full">
                    <button
                        @click="confirmDeleteError(selectedError)"
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 inline mr-1 -mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Eliminar este error
                    </button>
                    <button @click="closeErrorModal" class="px-4 py-2 bg-gray-200 dark:bg-zinc-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-zinc-600 transition">
                        Cerrar
                    </button>
                </div>
            </template>
        </ModalLarge>
    </div>
</template>
