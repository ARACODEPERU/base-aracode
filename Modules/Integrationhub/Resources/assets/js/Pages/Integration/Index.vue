<script setup>
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import { useForm } from '@inertiajs/vue3';
import Keypad from '@/Components/Keypad.vue';
import Pagination from '@/Components/Pagination.vue';
import Swal2 from 'sweetalert2';
import { Link, router } from '@inertiajs/vue3';
import Navigation from '@/Components/vristo/layout/Navigation.vue';

const props = defineProps({
    integrations: {
        type: Object,
        default: () => ({}),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const form = useForm({
    search: props.filters.search || '',
});

const xhttp = assetUrl;

const destroyIntegration = async (id) => {
    const result = await Swal2.fire({
        title: '¿Estás seguro?',
        text: 'Esta acción eliminará permanentemente la integración y todos sus datos.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        customClass: {
            popup: 'rounded-lg shadow-xl',
            confirmButton: 'rounded-md font-medium',
            cancelButton: 'rounded-md font-medium',
        },
    });

    if (!result.isConfirmed) return;

    try {
        await axios.delete(route('integrationhub_destroy', id));
        Swal2.fire({
            title: 'Eliminado',
            text: 'La integración ha sido eliminada correctamente.',
            icon: 'success',
            timer: 2000,
            showConfirmButton: false,
            customClass: {
                popup: 'rounded-lg',
            },
        });
        router.visit(route('integrationhub_listado'), {
            preserveState: true,
            preserveScroll: true,
            only: ['integrations'],
        });
    } catch (error) {
        Swal2.fire({
            title: 'Error',
            text: 'No se pudo eliminar la integración. Inténtalo de nuevo.',
            icon: 'error',
            customClass: {
                popup: 'rounded-lg',
            },
        });
    }
};

const hasIntegrations = computed(() => props.integrations?.data?.length > 0);

const statusClass = (status) => {
    return status ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300' : 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300';
};

const statusLabel = (status) => {
    return status ? 'Activo' : 'Inactivo';
};

const executionTypeLabel = (type) => {
    const types = {
        'manual': 'Manual',
        'scheduled': 'Programada',
        'webhook': 'Webhook'
    };
    return types[type] || type;
};

const executeIntegration = async (id) => {
    const result = await Swal2.fire({
        title: 'Ejecutar Integración',
        text: '¿Deseas ejecutar esta integración ahora?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#22c55e',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ejecutar',
        cancelButtonText: 'Cancelar',
        customClass: {
            popup: 'rounded-lg shadow-xl',
            confirmButton: 'rounded-md font-medium',
            cancelButton: 'rounded-md font-medium',
        },
    });

    if (!result.isConfirmed) return;

    try {
        const response = await axios.post(route('integrationhub_execute', id));
        Swal2.fire({
            title: 'Ejecutada',
            text: response.data.message || 'La integración se ejecutó correctamente.',
            icon: 'success',
            customClass: {
                popup: 'rounded-lg',
            },
        });
        router.visit(route('integrationhub_listado'), {
            preserveState: true,
            preserveScroll: true,
            only: ['integrations'],
        });
    } catch (error) {
        Swal2.fire({
            title: 'Error',
            text: error.response?.data?.message || 'No se pudo ejecutar la integración.',
            icon: 'error',
            customClass: {
                popup: 'rounded-lg',
            },
        });
    }
};
</script>

<template>
    <AppLayout title="Centro de Integraciones">
        <Navigation :routeModule="null" :titleModule="'Centro de Integraciones'"
            :data="[
                {title: 'Integraciones'}
            ]"
        />
        <div class="pt-5">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Integraciones API</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Gestiona tus conexiones a sistemas externos</p>
                </div>
                 <div class="flex items-center gap-3 w-full sm:w-auto">
                    <form @submit.prevent="router.visit(route('integrationhub_listado', { search: form.search }))">
                        <div class="relative">
                            <input
                                v-model="form.search"
                                type="text"
                                placeholder="Buscar integraciones..."
                                class="form-input pl-10 pr-4 w-full"
                            />
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </form>
                    <Link :href="route('integrationhub_create')" class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition font-medium text-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Nueva Integración
                    </Link>
                </div>
            </div>



            <div class="bg-white dark:bg-zinc-800 rounded-lg shadow overflow-hidden dark:shadow-zinc-900">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-gray-500 uppercase bg-gray-50 dark:bg-zinc-700 dark:text-gray-400">
                        <tr>
                            <th class="px-6 py-3">Nombre</th>
                            <th class="px-6 py-3">URL Base</th>
                            <th class="px-6 py-3">Tipo</th>
                            <th class="px-6 py-3">Estado</th>
                            <th class="px-6 py-3">Última Ejecución</th>
                            <th class="px-6 py-3 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="!hasIntegrations" class="border-b border-gray-200 dark:border-zinc-700">
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                <div class="flex flex-col items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mb-2 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                    </svg>
                                    <p>No hay integraciones configuradas</p>
                                    <Link :href="route('integrationhub_create')" class="mt-2 text-primary hover:underline">
                                        Crear primera integración
                                    </Link>
                                </div>
                            </td>
                        </tr>
                        <tr v-for="integration in integrations.data" :key="integration.id" class="border-b border-gray-200 dark:border-zinc-700 hover:bg-gray-50 dark:hover:bg-zinc-700/50 transition">
                            <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                                {{ integration.name }}
                            </td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-300">
                                <div class="max-w-xs truncate" :title="integration.url_base">
                                    {{ integration.url_base }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-300">
                                <span class="px-2 py-1 bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300 rounded text-xs font-medium">
                                    {{ executionTypeLabel(integration.execution_type) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span :class="statusClass(integration.is_active)" class="px-2 py-1 rounded text-xs font-medium">
                                    {{ statusLabel(integration.is_active) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-300">
                                <template v-if="integration.last_executed_at">
                                    {{ new Date(integration.last_executed_at).toLocaleString('es-ES') }}
                                </template>
                                <template v-else>
                                    <span class="text-gray-400">Nunca</span>
                                </template>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-2">
                                    <Link
                                        :href="route('integrationhub_editar', integration.id)"
                                        class="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition"
                                        title="Editar"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </Link>
                                    <button
                                        @click="executeIntegration(integration.id)"
                                        class="p-2 text-green-600 hover:bg-green-50 dark:hover:bg-green-900/30 rounded-lg transition"
                                        title="Ejecutar"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.133a1 1 0 000-1.664z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </button>
                                    <button
                                        @click="destroyIntegration(integration.id)"
                                        class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition"
                                        title="Eliminar"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="hasIntegrations" class="mt-4">
                <Pagination :data="integrations" />
            </div>
        </div>
    </AppLayout>
</template>
