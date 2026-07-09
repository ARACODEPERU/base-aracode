<script setup>
import { ref, computed, onMounted } from 'vue';
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
const batchFilter = ref('all');
const searchText = ref('');
const batchSearch = ref('');
const dateFrom = ref('');
const dateTo = ref('');
const loadingLogs = ref(false);
const currentLogs = ref([...props.logs]);
const selectedLog = ref(null);
const showLogModal = ref(false);

// Paginación
const currentPage = ref(1);
const perPage = ref(25);
const totalLogs = ref(0);
const lastPage = ref(1);

const filteredLogs = computed(() => {
    let logs = [...currentLogs.value];
    
    if (statusFilter.value !== 'all') {
        logs = logs.filter(log => log.status === statusFilter.value);
    }

    if (batchFilter.value !== 'all') {
        logs = logs.filter(log => log.batch_id === batchFilter.value);
    }
    
    return logs.sort((a, b) => new Date(b.executed_at) - new Date(a.executed_at));
});

const batchOptions = computed(() => {
    return [...new Set(currentLogs.value.map(log => log.batch_id).filter(Boolean))];
});

const fromRecord = computed(() => {
    if (totalLogs.value === 0) return 0;
    return (currentPage.value - 1) * perPage.value + 1;
});

const toRecord = computed(() => {
    return Math.min(currentPage.value * perPage.value, totalLogs.value);
});

const paginationRange = computed(() => {
    const range = [];
    const delta = 2;
    const left = Math.max(1, currentPage.value - delta);
    const right = Math.min(lastPage.value, currentPage.value + delta);

    if (left > 1) {
        range.push(1);
        if (left > 2) range.push('...');
    }

    for (let i = left; i <= right; i++) {
        range.push(i);
    }

    if (right < lastPage.value) {
        if (right < lastPage.value - 1) range.push('...');
        range.push(lastPage.value);
    }

    return range;
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
    const selected = selectedLog.value;
    if (selected?.endpoint?.name && selected.endpoint_id === endpointId) {
        return selected.endpoint.name;
    }

    const endpoint = props.endpoints.find(ep => ep.id === endpointId);
    return endpoint ? endpoint.name : `Endpoint #${endpointId}`;
};

const endpointName = (log) => {
    return log.endpoint?.name || getEndpointName(log.endpoint_id);
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

const sensitivePatterns = [
    'password', 'passwd', 'pwd',
    'token', 'access_token', 'refresh_token',
    'api_key', 'apikey', 'api-key',
    'secret', 'client_secret',
    'authorization',
    'credential', 'bearer',
    'private_key', 'privatekey',
];

const isSensitiveKey = (key) => {
    const k = String(key || '').toLowerCase().trim();
    return sensitivePatterns.some(p => k === p || k.includes(p));
};

const maskSensitiveData = (data) => {
    if (!data || typeof data !== 'object') return data;
    if (Array.isArray(data)) return data.map(maskSensitiveData);
    const masked = {};
    for (const [key, value] of Object.entries(data)) {
        if (isSensitiveKey(key) && typeof value === 'string' && value.length > 0) {
            masked[key] = 'x'.repeat(value.length);
        } else if (typeof value === 'object' && value !== null) {
            masked[key] = maskSensitiveData(value);
        } else {
            masked[key] = value;
        }
    }
    return masked;
};

const formatJson = (data) => {
    if (!data) return '-';
    try {
        const masked = maskSensitiveData(data);
        return JSON.stringify(masked, null, 2);
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

const fetchLogs = async (page = 1) => {
    loadingLogs.value = true;
    currentPage.value = page;

    try {
        const response = await axios.get(route('integrationhub_logs', props.integrationId), {
            params: {
                status: statusFilter.value,
                batch_id: batchSearch.value || (batchFilter.value !== 'all' ? batchFilter.value : null),
                search: searchText.value || null,
                date_from: dateFrom.value || null,
                date_to: dateTo.value || null,
                page: page,
                per_page: perPage.value,
            },
        });

        const paginated = response.data.logs;
        currentLogs.value = paginated.data || [];
        totalLogs.value = paginated.total || 0;
        lastPage.value = paginated.last_page || 1;
        currentPage.value = paginated.current_page || page;
    } catch (error) {
        Swal2.fire({
            title: 'Error',
            text: 'No se pudo cargar el historial.',
            icon: 'error',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    } finally {
        loadingLogs.value = false;
    }
};

const goToPage = (page) => {
    if (page < 1 || page > lastPage.value || loadingLogs.value) return;
    fetchLogs(page);
};

const clearFilters = () => {
    statusFilter.value = 'all';
    batchFilter.value = 'all';
    searchText.value = '';
    batchSearch.value = '';
    dateFrom.value = '';
    dateTo.value = '';
    fetchLogs(1);
};

// Cargar la primera página al montar el componente
onMounted(() => {
    fetchLogs(1);
});
</script>

<template>
    <div class="space-y-4">
        <!-- Filtros -->
        <div class="flex flex-wrap items-end gap-4">
            <div>
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Estado:</label>
                <select v-model="statusFilter" class="form-select ml-2">
                    <option value="all">Todos</option>
                    <option value="success">Éxito</option>
                    <option value="failed">Error</option>
                    <option value="partial">Parcial</option>
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Lote:</label>
                <select v-model="batchFilter" class="form-select ml-2">
                    <option value="all">Todos</option>
                    <option v-for="batchId in batchOptions" :key="batchId" :value="batchId">{{ batchId }}</option>
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Buscar lote:</label>
                <input v-model="batchSearch" type="text" class="form-input ml-2 w-72" placeholder="Pega el batch_id" />
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Buscar:</label>
                <input v-model="searchText" type="text" class="form-input ml-2 w-56" placeholder="Endpoint, error o lote" />
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
                @click="fetchLogs"
                :disabled="loadingLogs"
                class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition disabled:opacity-60"
            >
                {{ loadingLogs ? 'Buscando...' : 'Buscar' }}
            </button>
            <button
                type="button"
                @click="clearFilters"
                class="px-4 py-2 bg-gray-200 dark:bg-zinc-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-zinc-600 transition"
            >
                Limpiar
            </button>
            <div class="text-sm text-gray-500 dark:text-gray-400">
                <template v-if="totalLogs > 0">
                    Mostrando {{ fromRecord }}-{{ toRecord }} de {{ totalLogs }} registro(s)
                </template>
                <template v-else>
                    {{ filteredLogs.length }} registro(s)
                </template>
            </div>
        </div>

        <div class="text-sm text-gray-500 dark:text-gray-400">
            Para ejecuciones del API REST pega el batch_id devuelto por Postman en "Buscar lote".
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
                        <th class="px-4 py-3">Lote</th>
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
                        <td class="px-4 py-3">
                            <code v-if="log.batch_id" class="text-xs bg-gray-100 dark:bg-zinc-700 px-2 py-1 rounded">{{ log.batch_id }}</code>
                            <span v-else class="text-gray-400">-</span>
                        </td>
                        <td class="px-4 py-3 text-gray-900 dark:text-white">
                            {{ endpointName(log) }}
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

            <!-- Paginación -->
            <div v-if="lastPage > 1" class="flex flex-wrap items-center justify-between gap-4 px-4 py-3 border-t border-gray-200 dark:border-zinc-600">
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    Página {{ currentPage }} de {{ lastPage }}
                </div>
                <div class="flex items-center gap-1">
                    <button
                        @click="goToPage(1)"
                        :disabled="currentPage === 1 || loadingLogs"
                        class="px-2 py-1.5 text-xs rounded-md border border-gray-300 dark:border-zinc-600 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-zinc-700 disabled:opacity-40 disabled:cursor-not-allowed transition"
                        title="Primera página"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                        </svg>
                    </button>
                    <button
                        @click="goToPage(currentPage - 1)"
                        :disabled="currentPage === 1 || loadingLogs"
                        class="px-2 py-1.5 text-xs rounded-md border border-gray-300 dark:border-zinc-600 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-zinc-700 disabled:opacity-40 disabled:cursor-not-allowed transition"
                        title="Página anterior"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>

                    <template v-for="(item, idx) in paginationRange" :key="idx">
                        <span v-if="item === '...'" class="px-2 text-gray-400 dark:text-gray-500 text-xs">…</span>
                        <button
                            v-else
                            @click="goToPage(item)"
                            :class="[
                                item === currentPage
                                    ? 'bg-primary text-white border-primary'
                                    : 'border-gray-300 dark:border-zinc-600 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-zinc-700',
                            ]"
                            class="px-3 py-1.5 text-xs font-medium rounded-md border transition disabled:opacity-40"
                            :disabled="loadingLogs"
                        >
                            {{ item }}
                        </button>
                    </template>

                    <button
                        @click="goToPage(currentPage + 1)"
                        :disabled="currentPage === lastPage || loadingLogs"
                        class="px-2 py-1.5 text-xs rounded-md border border-gray-300 dark:border-zinc-600 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-zinc-700 disabled:opacity-40 disabled:cursor-not-allowed transition"
                        title="Página siguiente"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                    <button
                        @click="goToPage(lastPage)"
                        :disabled="currentPage === lastPage || loadingLogs"
                        class="px-2 py-1.5 text-xs rounded-md border border-gray-300 dark:border-zinc-600 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-zinc-700 disabled:opacity-40 disabled:cursor-not-allowed transition"
                        title="Última página"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>
                <select v-model.number="perPage" @change="fetchLogs(1)" class="form-select text-xs py-1.5 w-20" :disabled="loadingLogs">
                    <option :value="10">10/pág</option>
                    <option :value="25">25/pág</option>
                    <option :value="50">50/pág</option>
                    <option :value="100">100/pág</option>
                </select>
            </div>
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
                        <span class="text-gray-900 dark:text-white">{{ endpointName(selectedLog) }}</span>
                    </div>

                    <div v-if="selectedLog.batch_id" class="flex items-center gap-3">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Lote:</span>
                        <code class="text-xs bg-gray-100 dark:bg-zinc-700 px-2 py-1 rounded">{{ selectedLog.batch_id }}</code>
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
