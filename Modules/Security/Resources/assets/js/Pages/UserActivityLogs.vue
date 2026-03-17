<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import iconSearch from '@/Components/vristo/icon/icon-search.vue';
    import iconLoader from '@/Components/vristo/icon/icon-loader.vue';
    import Multiselect from "@suadelabs/vue3-multiselect";
    import "@suadelabs/vue3-multiselect/dist/vue3-multiselect.css";
    import { ref } from 'vue';
    import axios from 'axios';

    const props = defineProps({
        users: {
            type: Array,
            default: () => []
        }
    });

    const form = ref({
        user_id: null,
        date_start: '',
        date_end: ''
    });

    const activities = ref([]);
    const loaderSearch = ref(false);
    const searched = ref(false);

    const formatDate = (date) => {
        if (!date) return '-';
        return new Date(date).toLocaleString('es-ES', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    const searchActivities = () => {
        loaderSearch.value = true;
        searched.value = true;

        axios.get(route('user_activity_logs_data'), {
            params: {
                user_id: form.value.user_id?.id || null,
                date_start: form.value.date_start || null,
                date_end: form.value.date_end || null
            }
        })
        .then(function (response) {
            activities.value = response.data.data || [];
        })
        .finally(() => {
            loaderSearch.value = false;
        });
    };

    const getMethodClass = (method) => {
        const classes = {
            'GET': 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
            'POST': 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
            'PUT': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
            'PATCH': 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
            'DELETE': 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        };
        return classes[method] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';
    };

    const getStatusClass = (status) => {
        if (!status) {
            return 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300';
        }
        if (status >= 200 && status < 300) {
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
        } else if (status >= 400) {
            return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
        }
        return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';
    };

    const formatField = (value, maxLength = 50) => {
        if (value === null || value === undefined || value === '') {
            return { text: 'Sin datos', isEmpty: true };
        }

        let str = typeof value === 'object' ? JSON.stringify(value) : String(value);

        if (str.length > maxLength) {
            return { text: str.substring(0, maxLength) + '...', full: str, isEmpty: false };
        }

        return { text: str, isEmpty: false };
    };

    const clearFilters = () => {
        form.value.user_id = null;
        form.value.date_start = '';
        form.value.date_end = '';
        activities.value = [];
        searched.value = false;
    };
</script>

<template>
    <AppLayout title="Historial de Actividades">
        <Navigation
            :routeModule="route('security_dashboard')"
            :titleModule="'Seguridad'"
            :data="[
                {title: 'Historial de Actividades'}
            ]"
        />
        <div class="mt-5">
            <div class="panel p-0 rounded-2xl overflow-hidden">
                <!-- Header -->
                <div class="p-5 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        Filtros de Búsqueda
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <!-- Usuario -->
                        <div class="sm:col-span-2">
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                Usuario
                            </label>
                            <multiselect
                                v-model="form.user_id"
                                :options="users"
                                :searchable="true"
                                placeholder="Buscar usuario..."
                                selected-label="seleccionado"
                                select-label="Elegir"
                                deselect-label="Quitar"
                                label="name"
                                track-by="id"
                                class="custom-multiselect"
                            ></multiselect>
                        </div>

                        <!-- Fecha inicio -->
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                Fecha inicio
                            </label>
                            <input v-model="form.date_start" type="date" class="form-input" />
                        </div>

                        <!-- Fecha fin -->
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                Fecha fin
                            </label>
                            <input v-model="form.date_end" type="date" class="form-input" />
                        </div>

                        <!-- Botones -->
                        <div class="flex items-end gap-2">
                            <button
                                @click="searchActivities"
                                :class="{ 'opacity-25': loaderSearch }"
                                :disabled="loaderSearch"
                                type="button"
                                class="btn btn-primary flex-1"
                            >
                                <icon-loader v-if="loaderSearch" class="w-4 h-4 mr-2 animate-spin" />
                                <icon-search v-else class="w-4 h-4 mr-2" />
                                Consultar
                            </button>
                            <button
                                v-if="searched"
                                @click="clearFilters"
                                type="button"
                                class="btn btn-outline-secondary"
                            >
                                Limpiar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Resultados -->
                <div>
                    <!-- Loading -->
                    <div v-if="loaderSearch" class="text-center py-12">
                        <icon-loader class="w-8 h-8 mx-auto text-primary animate-spin" />
                        <p class="mt-2 text-gray-500 dark:text-gray-400">Buscando actividades...</p>
                    </div>

                    <!-- Sin buscar -->
                    <div v-else-if="!searched" class="text-center py-12">
                        <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Historial de Actividades</h3>
                        <p class="text-gray-500 dark:text-gray-400">
                            Seleccione un usuario y/o rango de fechas y haga clic en "Consultar"
                        </p>
                    </div>

                    <!-- Sin resultados -->
                    <div v-else-if="activities.length === 0" class="text-center py-12">
                        <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">No se encontraron registros</h3>
                        <p class="text-gray-500 dark:text-gray-400">
                            No hay actividades para los filtros seleccionados
                        </p>
                    </div>

                    <!-- Tabla de resultados -->
                    <div v-else class="table-responsive">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="bg-gray-50 dark:bg-gray-800">
                                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Usuario</th>
                                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Método</th>
                                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">URL</th>
                                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">IP</th>
                                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Payload</th>
                                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Details</th>
                                        <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Status</th>
                                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Error</th>
                                        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Fecha/Hora</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="activity in activities"
                                        :key="activity.id"
                                        class="border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800"
                                    >
                                        <!-- Usuario -->
                                        <td class="py-3 px-4 text-gray-900 dark:text-white">
                                            <div v-if="activity.user">
                                                <span class="font-medium">{{ activity.user.name }}</span>
                                                <span class="block text-xs text-gray-500 dark:text-gray-400">{{ activity.user.email }}</span>
                                            </div>
                                            <span v-else class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                                Sin usuario
                                            </span>
                                        </td>

                                        <!-- Método -->
                                        <td class="py-3 px-4">
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                                :class="getMethodClass(activity.method)"
                                            >
                                                {{ activity.method || 'Sin método' }}
                                            </span>
                                        </td>

                                        <!-- URL -->
                                        <td class="py-3 px-4 text-gray-600 dark:text-gray-300 text-sm">
                                            <div v-if="activity.url" class="relative group">
                                                <span class="block truncate max-w-xs cursor-help">
                                                    {{ activity.url.length > 40 ? activity.url.substring(0, 40) + '...' : activity.url }}
                                                </span>
                                                <div class="absolute z-50 hidden group-hover:block bottom-full left-0 mb-2 p-2 bg-gray-900 text-white text-xs rounded max-w-lg whitespace-pre-wrap break-all">
                                                    {{ activity.url }}
                                                </div>
                                            </div>
                                            <span v-else class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400">
                                                Sin URL
                                            </span>
                                        </td>

                                        <!-- IP -->
                                        <td class="py-3 px-4 text-gray-600 dark:text-gray-300 text-sm">
                                            <span v-if="activity.ip">{{ activity.ip }}</span>
                                            <span v-else class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400">
                                                -
                                            </span>
                                        </td>

                                        <!-- Payload -->
                                        <td class="py-3 px-4 text-gray-600 dark:text-gray-300 text-sm">
                                            <div v-if="activity.request_payload && Object.keys(activity.request_payload).length > 0" class="relative group">
                                                <span class="block truncate max-w-xs cursor-help">
                                                    {{ formatField(activity.request_payload, 30).text }}
                                                </span>
                                                <div class="absolute z-50 hidden group-hover:block bottom-full left-0 mb-2 p-2 bg-gray-900 text-white text-xs rounded max-w-lg whitespace-pre-wrap break-all">
                                                    {{ JSON.stringify(activity.request_payload, null, 2) }}
                                                </div>
                                            </div>
                                            <span v-else class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400">
                                                Vacío
                                            </span>
                                        </td>

                                        <!-- Details Data -->
                                        <td class="py-3 px-4 text-gray-600 dark:text-gray-300 text-sm">
                                            <div v-if="activity.details_data && Object.keys(activity.details_data).length > 0" class="relative group">
                                                <span class="block truncate max-w-xs cursor-help">
                                                    {{ formatField(activity.details_data, 30).text }}
                                                </span>
                                                <div class="absolute z-50 hidden group-hover:block bottom-full left-0 mb-2 p-2 bg-gray-900 text-white text-xs rounded max-w-lg whitespace-pre-wrap break-all">
                                                    {{ JSON.stringify(activity.details_data, null, 2) }}
                                                </div>
                                            </div>
                                            <span v-else class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400">
                                                Sin details
                                            </span>
                                        </td>

                                        <!-- Status -->
                                        <td class="py-3 px-4 text-center">
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                                :class="getStatusClass(activity.status_code)"
                                            >
                                                {{ activity.status_code || '-' }}
                                            </span>
                                        </td>

                                        <!-- Error -->
                                        <td class="py-3 px-4 text-gray-600 dark:text-gray-300 text-sm">
                                            <div v-if="activity.error_message" class="relative group">
                                                <span class="block truncate max-w-xs cursor-help text-red-600 dark:text-red-400">
                                                    {{ activity.error_message.length > 25 ? activity.error_message.substring(0, 25) + '...' : activity.error_message }}
                                                </span>
                                                <div class="absolute z-50 hidden group-hover:block bottom-full left-0 mb-2 p-2 bg-red-900 text-white text-xs rounded max-w-lg whitespace-pre-wrap">
                                                    {{ activity.error_message }}
                                                </div>
                                            </div>
                                            <span v-else class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300">
                                                Sin error
                                            </span>
                                        </td>

                                        <!-- Fecha/Hora -->
                                        <td class="py-3 px-4 text-gray-600 dark:text-gray-300 text-sm">
                                            {{ formatDate(activity.created_at) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Info de resultados -->
                        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                Total de registros: <strong>{{ activities.length }}</strong>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
