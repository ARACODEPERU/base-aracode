<script setup>
import { ref, computed } from 'vue';
import Swal2 from 'sweetalert2';
import axios from 'axios';
import ModalLarge from '@/Components/ModalLarge.vue';

const props = defineProps({
    integrationId: {
        type: Number,
        required: true
    },
    logs: {
        type: Array,
        default: () => []
    },
    endpoints: {
        type: Array,
        default: () => []
    }
});

const statusFilter = ref('all');
const selectedLog = ref(null);
const showLogModal = ref(false);

const filteredLogs = computed(() => {
    let logs = [...props.logs];
    
    if (statusFilter.value !== 'all') {
        logs = logs.filter(log => log.status === statusFilter.value);
    }
    
    return logs.sort((a, b) => new Date(b.executed_at) - new Date(a.executed_at));
});

const statusColors = {
    success: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
    failed: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
    partial: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300'
};

const statusLabels = {
    success: 'Éxito',
    failed: 'Error',
    partial: 'Parcial'
};

const getEndpointName = (endpointId) => {
    const endpoint = props.endpoints.find(ep => ep.id === endpointId);
    return endpoint ? endpoint.name : `Endpoint #${endpointId}`;
};

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

const formatJson = (data) => {
    if (!data) return '-';
    try {
        return JSON.stringify(data, null, 2);
    } catch {
        return String(data);
    }
};

const viewLogDetails = (log) => {
    selectedLog.value = log;
    showLogModal.value = true;
};

const closeLogModal = () => {
    showLogModal.value = false;
    selectedLog.value = null;
};
</script>

<template>
    <div class="space-y-4">
        <!-- Filtros -->
        <div class="flex items-center gap-4">
            <div>
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Estado:</label>
                <select v-model="statusFilter" class="form-select ml-2">
                    <option value="all">Todos</option>
                    <option value="success">Éxito</option>
                    <option value="failed">Error</option>
                    <option value="partial">Parcial</option>
                </select>
            </div>
            <div class="text-sm text-gray-500 dark:text-gray-400">
                {{ filteredLogs.length }} registro(s)
            </div>
        </div>

        <!-- Tabla de logs -->
        <div v-if="filteredLogs.length === 0" class="text-center py-12">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            <p class="text-gray-500 dark:text-gray-400">No hay registros de ejecución.</p>
            <p class="text-sm text-gray-400 mt-1">Ejecuta la integración para ver el historial aquí.</p>
        </div>

        <div v-else class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-zinc-700 dark:text-gray-400">
                    <tr>
                        <th class="px-4 py-3">Fecha/Hora</th>
                        <th class="px-4 py-3">Endpoint</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3">Código</th>
                        <th class="px-4 py-3">Tiempo</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(log, index) in filteredLogs" :key="index" class="border-b dark:border-zinc-600 hover:bg-gray-50 dark:hover:bg-zinc-700/50">
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                            {{ formatDate(log.executed_at) }}
                        </td>
                        <td class="px-4 py-3 text-gray-900 dark:text-white">
                            {{ getEndpointName(log.endpoint_id) }}
                        </td>
                        <td class="px-4 py-3">
                            <span :class="statusColors[log.status]" class="px-2 py-1 text-xs rounded-full">
                                {{ statusLabels[log.status] || log.status }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span v-if="log.response_status_code" 
                                :class="log.response_status_code >= 200 && log.response_status_code < 300 ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'"
                                class="px-2 py-1 text-xs rounded-full">
                                {{ log.response_status_code }}
                            </span>
                            <span v-else class="text-gray-400">-</span>
                        </td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                            {{ log.execution_time_ms ? log.execution_time_ms + 'ms' : '-' }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button @click="viewLogDetails(log)" class="text-blue-600 hover:text-blue-800 dark:text-blue-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Modal de detalles del log -->
        <ModalLarge :show="showLogModal" :onClose="closeLogModal" :icon="'/img/base-de-datos.png'">
            <template #title>
                Detalles de Ejecución
            </template>
            <template #message>
                {{ selectedLog ? formatDate(selectedLog.executed_at) : '' }}
            </template>
            <template #content>
                <div v-if="selectedLog" class="space-y-4">
                    <!-- Estado -->
                    <div class="flex items-center gap-3">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Estado:</span>
                        <span :class="statusColors[selectedLog.status]" class="px-3 py-1 text-sm rounded-full">
                            {{ statusLabels[selectedLog.status] || selectedLog.status }}
                        </span>
                    </div>

                    <!-- Endpoint -->
                    <div class="flex items-center gap-3">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Endpoint:</span>
                        <span class="text-gray-900 dark:text-white">{{ getEndpointName(selectedLog.endpoint_id) }}</span>
                    </div>

                    <!-- Código de respuesta -->
                    <div v-if="selectedLog.response_status_code" class="flex items-center gap-3">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Código HTTP:</span>
                        <span :class="selectedLog.response_status_code >= 200 && selectedLog.response_status_code < 300 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" 
                            class="px-3 py-1 text-sm rounded-full">
                            {{ selectedLog.response_status_code }}
                        </span>
                    </div>

                    <!-- Tiempo de ejecución -->
                    <div v-if="selectedLog.execution_time_ms" class="flex items-center gap-3">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Tiempo:</span>
                        <span class="text-gray-900 dark:text-white">{{ selectedLog.execution_time_ms }}ms</span>
                    </div>

                    <!-- Error message -->
                    <div v-if="selectedLog.error_message" class="p-3 bg-red-50 dark:bg-red-900/20 rounded-lg">
                        <span class="text-sm text-red-700 dark:text-red-400 font-medium">Error:</span>
                        <p class="text-sm text-red-600 dark:text-red-300 mt-1">{{ selectedLog.error_message }}</p>
                    </div>

                    <!-- Request payload -->
                    <div v-if="selectedLog.request_payload">
                        <button @click="selectedLog.showRequest = !selectedLog.showRequest" class="flex items-center gap-2 text-sm text-primary hover:text-primary/80">
                            <svg xmlns="http://www.w3.org/2000/svg" :class="selectedLog.showRequest ? 'rotate-180' : ''" class="w-4 h-4 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                            Request Payload
                        </button>
                        <pre v-if="selectedLog.showRequest" class="mt-2 bg-gray-100 dark:bg-zinc-900 p-4 rounded-lg text-xs font-mono overflow-x-auto max-h-48 text-gray-700 dark:text-gray-300">{{ formatJson(selectedLog.request_payload) }}</pre>
                    </div>

                    <!-- Response body -->
                    <div v-if="selectedLog.response_body">
                        <button @click="selectedLog.showResponse = !selectedLog.showResponse" class="flex items-center gap-2 text-sm text-primary hover:text-primary/80">
                            <svg xmlns="http://www.w3.org/2000/svg" :class="selectedLog.showResponse ? 'rotate-180' : ''" class="w-4 h-4 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                            Response Body
                        </button>
                        <pre v-if="selectedLog.showResponse" class="mt-2 bg-gray-100 dark:bg-zinc-900 p-4 rounded-lg text-xs font-mono overflow-x-auto max-h-48 text-gray-700 dark:text-gray-300">{{ formatJson(selectedLog.response_body) }}</pre>
                    </div>
                </div>
            </template>
            <template #buttons>
                <button @click="closeLogModal" class="px-4 py-2 bg-gray-200 dark:bg-zinc-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-zinc-600 transition">
                    Cerrar
                </button>
            </template>
        </ModalLarge>
    </div>
</template>