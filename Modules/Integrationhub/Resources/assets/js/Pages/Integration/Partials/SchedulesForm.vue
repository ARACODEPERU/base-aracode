<script setup>
import { ref, computed } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';
import Swal2 from 'sweetalert2';
import axios from 'axios';
import ModalLarge from '@/Components/ModalLarge.vue';
import IconLoader from '@/Components/vristo/icon/icon-loader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    integrationId: {
        type: Number,
        required: true
    },
    schedules: {
        type: Array,
        default: () => []
    },
    endpoints: {
        type: Array,
        default: () => []
    },
    apiRoutes: {
        type: Array,
        default: () => []
    }
});

const cronPresets = [
    { value: '0 * * * *', label: 'Cada hora', description: 'minuto 0 de cada hora' },
    { value: '0 */6 * * *', label: 'Cada 6 horas', description: 'a las 0, 6, 12, 18 horas' },
    { value: '0 */12 * * *', label: 'Cada 12 horas', description: 'a las 0 y 12 horas' },
    { value: '0 0 * * *', label: 'Diario (medianoche)', description: 'todos los días a las 00:00' },
    { value: '0 6 * * *', label: 'Diario (6 AM)', description: 'todos los días a las 06:00' },
    { value: '0 0 * * 0', label: 'Semanal (domingo)', description: 'todos los domingos a medianoche' },
    { value: '0 0 1 * *', label: 'Mensual (día 1)', description: 'el primer día de cada mes' },
    { value: '0 0 1 1 *', label: 'Anual (1 enero)', description: 'el 1 de enero cada año' },
];

const newSchedule = ref({
    schedule_id: null,
    target_type: 'integration_endpoint',
    endpoint_id: null,
    api_route_name: null,
    cron_expression: '0 0 * * *',
    payload: {},
    is_active: true
});

const showModal = ref(false);
const saving = ref(false);
const togglingId = ref(null);
const minutesInterval = ref(5);
const apiPayloadJson = ref('{\n  "variables": {}\n}');

const selectedScheduleEndpoint = computed(() => {
    return props.endpoints.find(endpoint => endpoint.id === newSchedule.value.endpoint_id) || null;
});

const selectedEndpointFields = computed(() => {
    return (selectedScheduleEndpoint.value?.field_maps || selectedScheduleEndpoint.value?.fieldMaps || [])
        .filter(field => field.is_enabled !== false)
        .sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0));
});

const requiredEndpointFields = computed(() => selectedEndpointFields.value.filter(field => field.is_required));

const selectedApiRoute = computed(() => {
    return props.apiRoutes.find(apiRoute => apiRoute.name === newSchedule.value.api_route_name) || null;
});

const getEndpointName = (endpointId) => {
    return props.endpoints.find(endpoint => endpoint.id === endpointId)?.name || 'Todos los endpoints activos';
};

const getScheduleTargetName = (schedule) => {
    if ((schedule.target_type || 'integration_endpoint') === 'module_api') {
        const apiRoute = props.apiRoutes.find(route => route.name === schedule.api_route_name);
        return apiRoute ? `${apiRoute.method} ${apiRoute.uri}` : (schedule.api_route_name || 'API REST no encontrada');
    }

    return getEndpointName(schedule.endpoint_id);
};

const normalizePayload = (payload) => {
    return payload && !Array.isArray(payload) && typeof payload === 'object' ? { ...payload } : {};
};

const resetPayloadForEndpoint = (endpointId, currentPayload = {}) => {
    const endpoint = props.endpoints.find(item => item.id === endpointId);
    const fields = endpoint?.field_maps || endpoint?.fieldMaps || [];
    const payload = {};

    fields
        .filter(field => field.is_enabled !== false && field.field_key)
        .forEach(field => {
            payload[field.field_key] = currentPayload[field.field_key] ?? '';
        });

    newSchedule.value.payload = payload;
};

const formatJson = (payload = {}) => {
    return JSON.stringify(payload && typeof payload === 'object' && !Array.isArray(payload) ? payload : {}, null, 2);
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleString('es-PE', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const getCronDescription = (expression) => {
    const preset = cronPresets.find(p => p.value === expression);
    return preset ? preset.description : expression;
};

const addSchedule = () => {
    newSchedule.value = {
        schedule_id: null,
        target_type: 'integration_endpoint',
        endpoint_id: props.endpoints[0]?.id || null,
        api_route_name: props.apiRoutes[0]?.name || null,
        cron_expression: '0 0 * * *',
        payload: {},
        is_active: true
    };
    resetPayloadForEndpoint(newSchedule.value.endpoint_id);
    apiPayloadJson.value = '{\n  "variables": {}\n}';
    showModal.value = true;
};

const editSchedule = (schedule) => {
    const targetType = schedule.target_type || 'integration_endpoint';
    newSchedule.value = {
        schedule_id: schedule.id,
        target_type: targetType,
        endpoint_id: schedule.endpoint_id || null,
        api_route_name: schedule.api_route_name || props.apiRoutes[0]?.name || null,
        cron_expression: schedule.cron_expression,
        payload: normalizePayload(schedule.payload),
        is_active: schedule.is_active
    };

    if (targetType === 'module_api') {
        apiPayloadJson.value = formatJson(newSchedule.value.payload);
    } else {
        resetPayloadForEndpoint(newSchedule.value.endpoint_id, newSchedule.value.payload);
        apiPayloadJson.value = formatJson({});
    }

    showModal.value = true;
};

const confirmDelete = (schedule) => {
    Swal2.fire({
        title: '¿Eliminar Programación?',
        text: `¿Estás seguro de eliminar esta programación?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Eliminar',
        cancelButtonText: 'Cancelar',
        padding: '2em',
        customClass: 'sweet-alerts',
    }).then((result) => {
        if (result.isConfirmed) {
            destroyServer(schedule.id);
        }
    });
};

const destroyServer = async (id) => {
    try {
        await axios.delete(route('integrationhub_destroy_schedule', id));
        Swal2.fire({
            title: 'Enhorabuena',
            text: 'Se eliminó correctamente',
            icon: 'success',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
        refreshSchedules();
    } catch (error) {
        Swal2.fire({
            title: 'Error',
            text: 'No se pudo eliminar la programación.',
            icon: 'error',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    }
};

const toggleEnabled = (schedule) => {
    togglingId.value = schedule.id;
    let isActive = !schedule.is_active;

    axios.put(route('integrationhub_update_schedule', props.integrationId), {
        schedule_id: schedule.id,
        target_type: schedule.target_type || 'integration_endpoint',
        endpoint_id: schedule.endpoint_id || null,
        api_route_name: schedule.api_route_name || null,
        cron_expression: schedule.cron_expression,
        payload: schedule.payload || {},
        is_active: isActive
    }).then(() => {
        schedule.is_active = isActive;
        togglingId.value = null;
        Swal2.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: isActive ? 'Programación activada' : 'Programación desactivada',
            showConfirmButton: false,
            timer: 2000,
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    }).catch(() => {
        togglingId.value = null;
        Swal2.fire({
            title: 'Error',
            text: 'No se pudo actualizar el estado.',
            icon: 'error',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    });
};

const storeToServer = async () => {
    saving.value = true;
    
    try {
        if (newSchedule.value.target_type === 'module_api') {
            try {
                const parsedPayload = JSON.parse(apiPayloadJson.value || '{}');
                if (!parsedPayload || Array.isArray(parsedPayload) || typeof parsedPayload !== 'object') {
                    throw new Error();
                }
                newSchedule.value.payload = parsedPayload;
                newSchedule.value.endpoint_id = null;
            } catch (jsonError) {
                saving.value = false;
                Swal2.fire({
                    title: 'JSON inválido',
                    text: 'Revisa el cuerpo JSON que se enviará a la API REST.',
                    icon: 'warning',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
                return;
            }
        } else {
            newSchedule.value.api_route_name = null;
        }

        await axios.put(route('integrationhub_update_schedule', props.integrationId), newSchedule.value);
        
        saving.value = false;
        Swal2.fire({
            title: 'Enhorabuena',
            text: 'Programación guardada correctamente',
            icon: 'success',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
        showModal.value = false;
        refreshSchedules();
    } catch (error) {
        saving.value = false;
        Swal2.fire({
            title: 'Error',
            text: error.response?.data?.message || 'No se pudo guardar la programación.',
            icon: 'error',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    }
};

const refreshSchedules = async () => {
    router.visit(route('integrationhub_editar', props.integrationId), {
        method: "get",
        replace: true,
        preserveState: true,
        preserveScroll: true,
        only: ['integration'],
    });
};

const selectPreset = (value) => {
    newSchedule.value.cron_expression = value;
};

const selectEveryMinutes = () => {
    const minutes = Math.min(59, Math.max(1, Number(minutesInterval.value) || 1));
    minutesInterval.value = minutes;
    newSchedule.value.cron_expression = `*/${minutes} * * * *`;
};

const onEndpointChange = () => {
    resetPayloadForEndpoint(newSchedule.value.endpoint_id, newSchedule.value.payload);
};

const onTargetTypeChange = () => {
    if (newSchedule.value.target_type === 'module_api') {
        newSchedule.value.api_route_name = newSchedule.value.api_route_name || props.apiRoutes[0]?.name || null;
        newSchedule.value.endpoint_id = null;
        newSchedule.value.payload = {};
        apiPayloadJson.value = '{\n  "variables": {}\n}';
        return;
    }

    newSchedule.value.endpoint_id = newSchedule.value.endpoint_id || props.endpoints[0]?.id || null;
    newSchedule.value.api_route_name = null;
    resetPayloadForEndpoint(newSchedule.value.endpoint_id, {});
};

const middlewareLabel = (middleware = []) => {
    return middleware.length ? middleware.join(', ') : '-';
};
</script>

<template>
    <div class="mb-4">
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">
            Configura la ejecución automática de la integración usando expresiones cron.
        </p>
    </div>

    <div class="mb-6 rounded-lg border border-gray-200 dark:border-zinc-700 overflow-hidden">
        <div class="px-4 py-3 bg-gray-50 dark:bg-zinc-800 border-b border-gray-200 dark:border-zinc-700">
            <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-200">APIs REST disponibles del módulo</h4>
        </div>
        <div v-if="apiRoutes.length === 0" class="px-4 py-5 text-sm text-gray-500 dark:text-gray-400">
            No hay rutas API REST registradas para Integrationhub.
        </div>
        <div v-else class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-zinc-700 dark:text-gray-400">
                    <tr>
                        <th class="px-4 py-3">Método</th>
                        <th class="px-4 py-3">Ruta</th>
                        <th class="px-4 py-3">Nombre</th>
                        <th class="px-4 py-3">Middleware</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="apiRoute in apiRoutes" :key="`${apiRoute.method}-${apiRoute.uri}`" class="border-t dark:border-zinc-700">
                        <td class="px-4 py-3">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                                {{ apiRoute.method }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <code class="text-xs bg-gray-100 dark:bg-zinc-700 px-2 py-1 rounded">{{ apiRoute.uri }}</code>
                        </td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ apiRoute.name || '-' }}</td>
                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ middlewareLabel(apiRoute.middleware) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="mb-4 flex justify-end">
        <button @click="addSchedule" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Agregar Programación
        </button>
    </div>

    <div v-if="schedules.length === 0" class="text-center py-8">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <p class="text-gray-500 dark:text-gray-400">No hay programaciones configuradas.</p>
        <p class="text-sm text-gray-400 mt-1">Haz clic en "Agregar Programación" para configurar la ejecución automática.</p>
    </div>

    <div v-else class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-zinc-700 dark:text-gray-400">
                <tr>
                    <th class="px-4 py-3 w-24">Activo</th>
                    <th class="px-4 py-3">Destino</th>
                    <th class="px-4 py-3">Expresión Cron</th>
                    <th class="px-4 py-3">Próxima Ejecución</th>
                    <th class="px-4 py-3">Última Ejecución</th>
                    <th class="px-4 py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(schedule, index) in schedules" :key="index" class="border-b dark:border-zinc-600 hover:bg-gray-50 dark:hover:bg-zinc-700/50">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <div v-if="togglingId === schedule.id" class="flex items-center justify-center h-6 w-11">
                                <svg class="animate-spin h-5 w-5 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                            <button v-else @click="toggleEnabled(schedule)"
                                :class="schedule.is_active ? 'bg-green-500' : 'bg-gray-300 dark:bg-zinc-600'"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-200">
                                <span :class="schedule.is_active ? 'translate-x-6' : 'translate-x-1'"
                                    class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform duration-200 shadow-sm"></span>
                            </button>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                        <div class="font-medium">{{ getScheduleTargetName(schedule) }}</div>
                        <div class="text-xs text-gray-500">
                            {{ (schedule.target_type || 'integration_endpoint') === 'module_api' ? 'API REST del módulo' : 'Endpoint de conexión' }}
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <div>
                            <code class="text-sm font-mono bg-gray-100 dark:bg-zinc-700 px-2 py-1 rounded">
                                {{ schedule.cron_expression }}
                            </code>
                            <p class="text-xs text-gray-500 mt-1">{{ getCronDescription(schedule.cron_expression) }}</p>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                        {{ formatDate(schedule.next_execution_at) }}
                    </td>
                    <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                        {{ formatDate(schedule.last_executed_at) }}
                    </td>
                    <td class="px-4 py-3 text-right">
                        <button @click="editSchedule(schedule)" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 mr-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </button>
                        <button @click="confirmDelete(schedule)" class="text-red-600 hover:text-red-800 dark:text-red-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
        <h4 class="font-medium text-blue-900 dark:text-blue-300 mb-2">Formato de expresión cron:</h4>
        <p class="text-sm text-blue-700 dark:text-blue-400 mb-2">
            <code class="bg-blue-100 dark:bg-blue-800 px-1 rounded">minuto hora día mes día_semana</code>
        </p>
        <ul class="text-sm text-blue-700 dark:text-blue-400 space-y-1">
            <li><strong>minuto:</strong> 0-59</li>
            <li><strong>hora:</strong> 0-23</li>
            <li><strong>día:</strong> 1-31</li>
            <li><strong>mes:</strong> 1-12</li>
            <li><strong>día_semana:</strong> 0-7 (0 y 7 son domingo)</li>
        </ul>
    </div>

    <!-- Modal Schedule -->
    <ModalLarge :show="showModal" :onClose="() => showModal = false" :icon="'/img/base-de-datos.png'">
        <template #title>
            {{ newSchedule.schedule_id ? 'Editar Programación' : 'Agregar Programación' }}
        </template>
        <template #message>
            Configura la frecuencia de ejecución automática
        </template>
        <template #content>
            <div class="space-y-4">
                <!-- Frecuencias predefinidas -->
                <div>
                    <InputLabel value="Frecuencia sugerida" />
                    <div class="grid grid-cols-2 gap-2 mt-2">
                        <button 
                            v-for="preset in cronPresets" 
                            :key="preset.value"
                            @click="selectPreset(preset.value)"
                            :class="newSchedule.cron_expression === preset.value ? 'bg-primary text-white' : 'bg-gray-100 dark:bg-zinc-700 hover:bg-gray-200 dark:hover:bg-zinc-600'"
                            class="p-2 rounded-lg text-left transition"
                        >
                            <div class="font-medium text-sm">{{ preset.label }}</div>
                            <div class="text-xs opacity-75">{{ preset.description }}</div>
                        </button>
                    </div>
                </div>

                <div class="rounded-lg border border-gray-200 dark:border-zinc-700 p-4">
                    <InputLabel for="minutes_interval" value="Activar cada cierta cantidad de minutos" />
                    <div class="mt-2 flex flex-col sm:flex-row gap-2">
                        <input
                            id="minutes_interval"
                            v-model.number="minutesInterval"
                            type="number"
                            min="1"
                            max="59"
                            class="form-input sm:w-36"
                            placeholder="5"
                        />
                        <button
                            type="button"
                            @click="selectEveryMinutes"
                            class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition"
                        >
                            Usar intervalo
                        </button>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Genera una expresion como <code>*/5 * * * *</code> para ejecutar cada 5 minutos.</p>
                </div>

                <div>
                    <InputLabel value="Qué debe ejecutar esta programación" />
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-2">
                        <label
                            class="cursor-pointer rounded-lg border p-4 transition"
                            :class="newSchedule.target_type === 'integration_endpoint' ? 'border-primary bg-primary/5 dark:bg-primary/10' : 'border-gray-200 dark:border-zinc-700 hover:bg-gray-50 dark:hover:bg-zinc-800'"
                        >
                            <div class="flex items-start gap-3">
                                <input
                                    v-model="newSchedule.target_type"
                                    value="integration_endpoint"
                                    type="radio"
                                    class="form-radio mt-1"
                                    @change="onTargetTypeChange"
                                />
                                <div>
                                    <div class="font-semibold text-gray-800 dark:text-gray-200">Endpoint de conexión</div>
                                    <p class="text-xs text-gray-500 mt-1">Usa los endpoints configurados en esta integración.</p>
                                </div>
                            </div>
                        </label>
                        <label
                            class="cursor-pointer rounded-lg border p-4 transition"
                            :class="newSchedule.target_type === 'module_api' ? 'border-primary bg-primary/5 dark:bg-primary/10' : 'border-gray-200 dark:border-zinc-700 hover:bg-gray-50 dark:hover:bg-zinc-800'"
                        >
                            <div class="flex items-start gap-3">
                                <input
                                    v-model="newSchedule.target_type"
                                    value="module_api"
                                    type="radio"
                                    class="form-radio mt-1"
                                    @change="onTargetTypeChange"
                                />
                                <div>
                                    <div class="font-semibold text-gray-800 dark:text-gray-200">API REST del módulo</div>
                                    <p class="text-xs text-gray-500 mt-1">Ejecuta una ruta API interna de Integrationhub.</p>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

                <div v-if="newSchedule.target_type === 'integration_endpoint'">
                    <InputLabel for="endpoint_id" value="Endpoint a ejecutar *" />
                    <select
                        id="endpoint_id"
                        v-model="newSchedule.endpoint_id"
                        class="form-select"
                        @change="onEndpointChange"
                    >
                        <option v-for="endpoint in endpoints" :key="endpoint.id" :value="endpoint.id">
                            {{ endpoint.http_method }} - {{ endpoint.name }}
                        </option>
                    </select>
                    <p class="mt-1 text-xs text-gray-500">La programacion enviara estos valores al endpoint seleccionado.</p>
                </div>

                <div v-if="newSchedule.target_type === 'module_api'" class="space-y-3">
                    <div>
                        <InputLabel for="api_route_name" value="API REST a ejecutar *" />
                        <select
                            id="api_route_name"
                            v-model="newSchedule.api_route_name"
                            class="form-select"
                        >
                            <option v-for="apiRoute in apiRoutes" :key="apiRoute.name || apiRoute.uri" :value="apiRoute.name">
                                {{ apiRoute.method }} - {{ apiRoute.uri }}
                            </option>
                        </select>
                        <p v-if="selectedApiRoute" class="mt-1 text-xs text-gray-500">
                            Ruta interna: <code>{{ selectedApiRoute.name }}</code>
                        </p>
                    </div>

                    <div class="rounded-lg border border-gray-200 dark:border-zinc-700 p-4">
                        <InputLabel for="api_payload_json" value="JSON que se enviará a la API" />
                        <textarea
                            id="api_payload_json"
                            v-model="apiPayloadJson"
                            rows="8"
                            class="form-textarea font-mono text-sm mt-2"
                            placeholder='{"variables": {}}'
                        />
                        <p class="mt-1 text-xs text-gray-500">
                            Este JSON se enviará como body de la petición interna. Para birthday_benefits puedes dejar variables vacío.
                        </p>
                    </div>
                </div>

                <div v-if="newSchedule.target_type === 'integration_endpoint' && selectedScheduleEndpoint && selectedEndpointFields.length" class="rounded-lg border border-gray-200 dark:border-zinc-700 p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Valores para la ejecucion</h4>
                        <span v-if="requiredEndpointFields.length" class="text-xs text-red-500">
                            {{ requiredEndpointFields.length }} requerido(s)
                        </span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div v-for="field in selectedEndpointFields" :key="field.id">
                            <InputLabel :for="`payload_${field.id}`" :value="`${field.field_key}${field.is_required ? ' *' : ''}`" />
                            <input
                                :id="`payload_${field.id}`"
                                v-model="newSchedule.payload[field.field_key]"
                                type="text"
                                class="form-input"
                                :placeholder="field.source_type === 'query' ? 'Vacio: ejecuta la consulta SQL configurada' : 'Valor a enviar'"
                            />
                            <p class="mt-1 text-xs text-gray-500">
                                {{ field.field_location === 'path' ? 'Ruta URL' : (field.field_location === 'query' ? 'Parametro URL' : (field.field_location === 'body' ? 'Body' : 'Header')) }}
                            </p>
                        </div>
                    </div>
                </div>

                <div>
                    <InputLabel for="cron_expression" value="Expresión Cron *" />
                    <input 
                        id="cron_expression" 
                        v-model="newSchedule.cron_expression" 
                        type="text" 
                        class="form-input font-mono" 
                        placeholder="0 0 * * *"
                    />
                    <p class="mt-1 text-xs text-gray-500">Usa una de las frecuencias sugeridas o ingresa tu propia expresión cron</p>
                </div>

                <!-- Descripción de la expresión -->
                <div class="p-3 bg-gray-50 dark:bg-zinc-800 rounded-lg">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        <strong>Descripción:</strong> {{ getCronDescription(newSchedule.cron_expression) }}
                    </p>
                </div>

                <!-- Ayuda del formato cron -->
                <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <h4 class="font-medium text-blue-900 dark:text-blue-300 mb-2">
                        ¿Qué significa la expresión cron?
                    </h4>
                    <p class="text-sm text-blue-700 dark:text-blue-400 mb-3">
                        Cada asterisco representa una unidad de tiempo:
                    </p>
                    <ul class="text-sm text-blue-700 dark:text-blue-400 space-y-1 mb-3">
                        <li><strong>Minutos</strong> (0 - 59)</li>
                        <li><strong>Horas</strong> (0 - 23)</li>
                        <li><strong>Día del mes</strong> (1 - 31)</li>
                        <li><strong>Mes</strong> (1 - 12)</li>
                        <li><strong>Día de la semana</strong> (0 - 6) - (0 es domingo)</li>
                    </ul>
                    <p class="text-sm text-blue-700 dark:text-blue-400 mb-2">
                        <strong>Ejemplos:</strong>
                    </p>
                    <ul class="text-sm text-blue-700 dark:text-blue-400 space-y-1">
                        <li><code class="bg-blue-100 dark:bg-blue-800 px-1 rounded">* * * * *</code> - Cada minuto</li>
                        <li><code class="bg-blue-100 dark:bg-blue-800 px-1 rounded">0 * * * *</code> - Al inicio de cada hora</li>
                        <li><code class="bg-blue-100 dark:bg-blue-800 px-1 rounded">0 0 * * *</code> - A medianoche todos los días</li>
                        <li><code class="bg-blue-100 dark:bg-blue-800 px-1 rounded">30 8 * * 1</code> - A las 8:30 AM todos los lunes</li>
                        <li><code class="bg-blue-100 dark:bg-blue-800 px-1 rounded">0 0 1 * *</code> - El día 1 de cada mes</li>
                    </ul>
                </div>
            </div>
        </template>
        <template #buttons>
            <PrimaryButton @click="storeToServer" :disabled="saving" class="mr-3">
                <IconLoader v-if="saving" class="w-4 h-4 mr-2 animate-spin" />
                {{ saving ? 'Guardando...' : 'Guardar' }}
            </PrimaryButton>
        </template>
    </ModalLarge>
</template>
