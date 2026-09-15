<script setup>
import { ref, computed, watch } from 'vue';
import Swal2 from 'sweetalert2';
import axios from 'axios';
import IconLoader from '@/Components/vristo/icon/icon-loader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { router } from '@inertiajs/vue3';
import IconInfoCircle from '@/Components/vristo/icon/icon-info-circle.vue';

const props = defineProps({
    integrationId: {
        type: Number,
        required: true
    },
    integration: {
        type: Object,
        default: () => ({})
    },
    endpoints: {
        type: Array,
        default: () => []
    }
});

const selectedEndpointId = ref(null);
const executing = ref(false);
const executionResult = ref(null);
const executionError = ref(null);
const showResponse = ref(false);
const showRequest = ref(true);
const showFullDetails = ref(true);
const testFieldValues = ref({});

// --- Extra params state ---
const showExtraParams = ref(false);
const activeExtraTab = ref('body');
const extraParams = ref({
    path: [],
    query: [],
    body: [],
    headers: []
});

// --- JSON body editor state ---
const jsonBodyMode = ref(false);
const jsonBodyText = ref('');
const jsonBodyError = ref('');

const selectedEndpoint = computed(() => {
    return props.endpoints.find(ep => ep.id === selectedEndpointId.value);
});

const selectedFieldMaps = computed(() => {
    return (selectedEndpoint.value?.field_maps || selectedEndpoint.value?.fieldMaps || [])
        .filter(field => field.is_enabled !== false)
        .sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0));
});

const mappedTestFields = computed(() => {
    return selectedFieldMaps.value
        .map(field => ({
            ...field,
            test_value: testFieldValues.value[field.id] ?? getDefaultFieldValue(field)
        }));
});

const pathTestFields = computed(() => mappedTestFields.value.filter(field => field.field_location === 'path'));

const requiredFields = computed(() => mappedTestFields.value.filter(field => field.is_required));
const optionalFields = computed(() => mappedTestFields.value.filter(field => !field.is_required));

const testVariablesByKey = computed(() => {
    return selectedFieldMaps.value.reduce((values, field) => {
        if (field.field_key) {
            values[field.field_key] = testFieldValues.value[field.id] ?? '';
        }

        return values;
    }, {});
});

const fullEndpointUrl = computed(() => {
    if (!selectedEndpoint.value) {
        return '';
    }

    let path = selectedEndpoint.value.endpoint_path || '';

    pathTestFields.value.forEach(field => {
        const manualValue = testFieldValues.value[field.id];
        const hasManualValue = manualValue !== null
            && manualValue !== undefined
            && String(manualValue).trim() !== '';
        const value = hasManualValue
            ? encodeURIComponent(manualValue)
            : `{${field.field_key}}`;
        path = path.replaceAll(`{${field.field_key}}`, value);
    });

    return `${String(props.integration.url_base || '').replace(/\/$/, '')}/${String(path).replace(/^\//, '')}`;
});

const locationLabels = {
    path: 'Ruta URL',
    query: 'Parametro URL',
    body: 'Body',
    header: 'Header'
};

const statusColors = {
    success: 'text-green-600 dark:text-green-400',
    error: 'text-red-600 dark:text-red-400',
    pending: 'text-yellow-600 dark:text-yellow-400'
};

const extraTabs = [
    { key: 'body', label: 'Body' },
    { key: 'query', label: 'Query' },
    { key: 'path', label: 'Path' },
    { key: 'headers', label: 'Headers' }
];

const extraTabDescriptions = {
    body: 'Campos adicionales para el body de la solicitud.',
    query: 'Parámetros adicionales de la URL (?key=value).',
    path: 'Parámetros de ruta adicionales para reemplazar placeholders {key}.',
    headers: 'Headers HTTP adicionales para la solicitud.'
};

const getDefaultFieldValue = (field) => {
    const fieldValue = field.field_value;
    const defaultValue = field.default_value;

    if (fieldValue !== null && fieldValue !== undefined && String(fieldValue).trim() !== '') {
        return fieldValue;
    }

    if (defaultValue !== null && defaultValue !== undefined && String(defaultValue).trim() !== '') {
        return defaultValue;
    }

    return '';
};

const resetTestFieldValues = () => {
    const values = {};

    selectedFieldMaps.value.forEach(field => {
        values[field.id] = field.source_type === 'query' || field.field_location === 'path'
            ? ''
            : getDefaultFieldValue(field);
    });

    testFieldValues.value = values;
};

const resetExtraParams = () => {
    extraParams.value = {
        path: [],
        query: [],
        body: [],
        headers: []
    };
};

const resetJsonBody = () => {
    jsonBodyMode.value = false;
    jsonBodyText.value = '';
    jsonBodyError.value = '';
};

watch(selectedEndpointId, () => {
    resetTestFieldValues();
    resetExtraParams();
    resetJsonBody();
    executionResult.value = null;
    executionError.value = null;
    showResponse.value = false;
    showRequest.value = true;
    showFullDetails.value = true;
    showExtraParams.value = false;
});

// --- Extra params helpers ---
const addExtraParam = (tab) => {
    extraParams.value[tab].push({ key: '', value: '' });
};

const removeExtraParam = (tab, index) => {
    extraParams.value[tab].splice(index, 1);
};

const parseJsonBody = (text) => {
    jsonBodyError.value = '';

    if (!text || text.trim() === '') {
        return null;
    }

    try {
        return JSON.parse(text);
    } catch (e) {
        jsonBodyError.value = 'JSON inválido: ' + e.message;
        return undefined;
    }
};

const buildExtraParams = () => {
    const result = {};

    for (const tab of ['path', 'query', 'body', 'headers']) {
        const entries = extraParams.value[tab]
            .filter(p => p.key && p.key.trim() !== '')
            .reduce((obj, p) => {
                obj[p.key.trim()] = p.value;
                return obj;
            }, {});

        if (Object.keys(entries).length > 0) {
            result[tab] = entries;
        }
    }

    // Si el modo JSON body está activo y hay JSON válido, usarlo como body
    if (jsonBodyMode.value) {
        const parsed = parseJsonBody(jsonBodyText.value);

        if (parsed === undefined) {
            // JSON inválido — no enviar
            return null;
        }

        if (parsed !== null) {
            result.body = parsed;
        }
    }

    return Object.keys(result).length > 0 ? result : {};
};

const extraParamsHasData = computed(() => {
    for (const tab of ['path', 'query', 'body', 'headers']) {
        if (extraParams.value[tab].some(p => p.key && p.key.trim() !== '')) {
            return true;
        }
    }

    if (jsonBodyMode.value && jsonBodyText.value.trim() !== '') {
        return true;
    }

    return false;
});

const extraParamsCount = computed(() => {
    let count = 0;

    for (const tab of ['path', 'query', 'body', 'headers']) {
        count += extraParams.value[tab].filter(p => p.key && p.key.trim() !== '').length;
    }

    if (jsonBodyMode.value && jsonBodyText.value.trim() !== '') {
        count += 1;
    }

    return count;
});

// --- Formatting helpers ---
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

const formatResponse = (data) => {
    if (!data) return '';
    try {
        return JSON.stringify(data, null, 2);
    } catch {
        return String(data);
    }
};

const getCurrentRequest = () => {
    return executionResult.value?.request || executionError.value?.request || null;
};

const getCurrentReceived = () => {
    return executionResult.value?.received || executionError.value?.received || executionResult.value?.response || executionError.value?.response || null;
};

const summarizeRequest = (request) => {
    if (!request) return null;

    return {
        method: request.method,
        url: request.url,
        body_type: request.body_type,
        body: request.body
    };
};

const summarizeReceived = (received) => {
    if (!received) return null;

    if (received.body !== undefined || received.status_code !== undefined) {
        return {
            status_code: received.status_code,
            body: received.body
        };
    }

    return {
        body: received
    };
};

const displayRequestData = () => {
    const request = getCurrentRequest();
    return showFullDetails.value ? request : summarizeRequest(request);
};

const displayReceivedData = () => {
    const received = getCurrentReceived();
    return showFullDetails.value ? received : summarizeReceived(received);
};

// --- Execute ---
const executeIntegration = async () => {
    if (!selectedEndpointId.value) {
        Swal2.fire({
            title: 'Error',
            text: 'Por favor selecciona un endpoint',
            icon: 'error',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
        return;
    }

    // Validar JSON body si está activo
    if (jsonBodyMode.value && jsonBodyText.value.trim() !== '') {
        const parsed = parseJsonBody(jsonBodyText.value);

        if (parsed === undefined) {
            Swal2.fire({
                title: 'Error',
                text: 'El JSON del body no es válido. Corrígelo antes de ejecutar.',
                icon: 'error',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
            return;
        }
    }

    executing.value = true;
    executionResult.value = null;
    executionError.value = null;

    try {
        const payload = {
            endpoint_id: selectedEndpointId.value,
            field_values: testFieldValues.value,
            variables: testVariablesByKey.value
        };

        const extra = buildExtraParams();

        if (extra && Object.keys(extra).length > 0) {
            payload.extra_params = extra;
        }

        const response = await axios.post(route('integrationhub_execute', props.integrationId), payload);

        executing.value = false;
        executionResult.value = response.data;
        showResponse.value = true;

        Swal2.fire({
            title: 'Ejecución completada',
            text: response.data.message || 'La integración se ejecutó correctamente',
            icon: 'success',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    } catch (error) {
        executing.value = false;
        executionError.value = error.response?.data || error.message;

        Swal2.fire({
            title: 'Error en la ejecución',
            text: error.response?.data?.message || 'Hubo un problema al ejecutar la integración',
            icon: 'error',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    }
};

const resetExecution = () => {
    selectedEndpointId.value = null;
    executionResult.value = null;
    executionError.value = null;
    showResponse.value = false;
    showRequest.value = true;
    showFullDetails.value = true;
    testFieldValues.value = {};
    resetExtraParams();
    resetJsonBody();
    showExtraParams.value = false;
};
</script>

<template>
    <div class="space-y-6">
        <!-- Sección de selección -->
        <div class="">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                Ejecutar Integración
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                Selecciona un endpoint y ejecuta la integración para probar la conexión con la API externa.
            </p>

            <div class="bg-blue-100 border border-blue-200 text-foreground rounded-lg p-4 dark:bg-blue-500/20 dark:border-blue-900 mb-6">
                <div class="flex">
                    <div class="shrink-0">
                        <IconInfoCircle class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div class="ms-3">
                        <h2 id="text-blue-600" class="font-semibold">
                            API
                        </h2>
                        <div class="mt-2 text-lg text-muted-foreground-2">
                           {{ integration.name }}
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            {{ integration.url_base }}
                        </p>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                <!-- Selección de Endpoint -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Endpoint *
                    </label>
                    <select
                        v-model="selectedEndpointId"
                        class="form-select"
                        :disabled="executing"
                    >
                        <option :value="null">Seleccionar endpoint...</option>
                        <option v-for="ep in endpoints" :key="ep.id" :value="ep.id">
                            {{ ep.http_method }} - {{ ep.name }} ({{ ep.endpoint_path }})
                        </option>
                    </select>
                </div>

                <!-- Botón ejecutar -->
                <div>
                    <PrimaryButton
                        @click="executeIntegration"
                        :disabled="executing || !selectedEndpointId"
                        class="justify-center"
                    >
                        <template v-if="executing">
                            <IconLoader class="w-5 h-5 mr-2 animate-spin" />
                            Ejecutando...
                        </template>
                        <template v-else>
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.133a1 1 0 000-1.664z M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Ejecutar Ahora
                        </template>
                    </PrimaryButton>
                </div>
            </div>

            <div v-if="selectedEndpoint" class="mt-4 space-y-4">
                <div class="rounded-lg border border-gray-200 dark:border-zinc-700 bg-gray-50 dark:bg-zinc-900 p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="px-2 py-1 text-xs font-semibold rounded bg-primary/10 text-primary">
                            {{ selectedEndpoint.http_method }}
                        </span>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Ruta completa</span>
                    </div>
                    <code class="block text-sm break-all text-gray-800 dark:text-gray-200">
                        {{ fullEndpointUrl }}
                    </code>
                </div>

                <!-- Variables de prueba - Requeridos -->
                <div v-if="requiredFields.length" class="rounded-lg border border-red-200 dark:border-red-800/40 p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-200">
                            Campos requeridos
                            <span class="ml-1 px-1.5 py-0.5 rounded bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 text-[11px] font-medium">
                                {{ requiredFields.length }}
                            </span>
                        </h4>
                        <button type="button" @click="resetTestFieldValues" class="text-xs text-primary hover:text-primary/80">
                            Restaurar valores por defecto
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div v-for="field in requiredFields" :key="field.id">
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                                {{ field.field_key }}
                                <span class="ml-1 text-red-500 dark:text-red-400">*</span>
                                <span class="ml-1 px-1.5 py-0.5 rounded bg-gray-100 dark:bg-zinc-700 text-[11px]">
                                    {{ locationLabels[field.field_location] || field.field_location }}
                                </span>
                            </label>
                            <input
                                v-model="testFieldValues[field.id]"
                                type="text"
                                class="form-input"
                                :placeholder="field.source_type === 'query' ? 'Consulta SQL: si escribe algo se enviará eso y no se hará la consulta' : String(getDefaultFieldValue(field) || 'Valor de prueba')"
                                :disabled="executing"
                            />
                            <p v-if="field.source_type === 'query'" class="mt-1 text-xs text-gray-500">
                                Vacío: ejecuta la consulta SQL configurada. Con valor: envía este dato manual.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Variables de prueba - Opcionales -->
                <div v-if="optionalFields.length" class="rounded-lg border border-gray-200 dark:border-zinc-700 p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-200">
                            Campos opcionales
                            <span class="ml-1 px-1.5 py-0.5 rounded bg-gray-100 dark:bg-zinc-700 text-gray-500 dark:text-gray-400 text-[11px] font-medium">
                                {{ optionalFields.length }}
                            </span>
                        </h4>
                        <button v-if="!requiredFields.length" type="button" @click="resetTestFieldValues" class="text-xs text-primary hover:text-primary/80">
                            Restaurar valores por defecto
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div v-for="field in optionalFields" :key="field.id">
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                                {{ field.field_key }}
                                <span class="ml-1 px-1.5 py-0.5 rounded bg-gray-100 dark:bg-zinc-700 text-[11px] text-gray-400">
                                    {{ locationLabels[field.field_location] || field.field_location }}
                                </span>
                                <span class="ml-1 text-[11px] text-gray-400 italic">(Opcional)</span>
                            </label>
                            <input
                                v-model="testFieldValues[field.id]"
                                type="text"
                                class="form-input"
                                :placeholder="field.source_type === 'query' ? 'Consulta SQL: si escribe algo se enviará eso y no se hará la consulta' : String(getDefaultFieldValue(field) || 'Valor de prueba')"
                                :disabled="executing"
                            />
                            <p v-if="field.source_type === 'query'" class="mt-1 text-xs text-gray-500">
                                Vacío: ejecuta la consulta SQL configurada. Con valor: envía este dato manual.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Parámetros Extra (colapsable) -->
                <div class="rounded-lg border border-gray-200 dark:border-zinc-700 overflow-hidden">
                    <button
                        type="button"
                        @click="showExtraParams = !showExtraParams"
                        class="w-full flex items-center justify-between p-4 text-left hover:bg-gray-50 dark:hover:bg-zinc-800/50 transition-colors"
                    >
                        <div class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                            </svg>
                            <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">
                                Parámetros Extra
                            </span>
                            <span
                                v-if="extraParamsCount > 0"
                                class="px-1.5 py-0.5 rounded-full bg-primary/10 text-primary text-[11px] font-medium"
                            >
                                {{ extraParamsCount }}
                            </span>
                        </div>
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            :class="showExtraParams ? 'rotate-180' : ''"
                            class="w-4 h-4 text-gray-400 transition-transform"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div v-if="showExtraParams" class="px-4 pb-4 border-t border-gray-200 dark:border-zinc-700">
                        <!-- Tabs -->
                        <div class="flex gap-1 mt-3 mb-3 bg-gray-100 dark:bg-zinc-800 rounded-lg p-1">
                            <button
                                v-for="tab in extraTabs"
                                :key="tab.key"
                                type="button"
                                @click="activeExtraTab = tab.key"
                                :class="[
                                    activeExtraTab === tab.key
                                        ? 'bg-white dark:bg-zinc-700 text-primary shadow-sm'
                                        : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'
                                ]"
                                class="flex-1 px-3 py-1.5 text-xs font-medium rounded-md transition-all"
                            >
                                {{ tab.label }}
                                <span
                                    v-if="extraParams[tab.key].filter(p => p.key && p.key.trim() !== '').length > 0"
                                    class="ml-1 inline-flex items-center justify-center w-4 h-4 text-[10px] rounded-full bg-primary/10 text-primary"
                                >
                                    {{ extraParams[tab.key].filter(p => p.key && p.key.trim() !== '').length }}
                                </span>
                            </button>
                        </div>

                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                            {{ extraTabDescriptions[activeExtraTab] }}
                        </p>

                        <!-- JSON Body Mode Toggle (only on body tab) -->
                        <div v-if="activeExtraTab === 'body'" class="mb-3">
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                                <input
                                    v-model="jsonBodyMode"
                                    type="checkbox"
                                    class="form-checkbox"
                                    :disabled="executing"
                                />
                                Modo JSON (enviar JSON crudo como body)
                            </label>
                        </div>

                        <!-- JSON Body Editor -->
                        <div v-if="activeExtraTab === 'body' && jsonBodyMode" class="mb-3">
                            <textarea
                                v-model="jsonBodyText"
                                class="w-full font-mono text-xs p-3 rounded-lg border border-gray-200 dark:border-zinc-600 bg-gray-50 dark:bg-zinc-900 text-gray-800 dark:text-gray-200 focus:border-primary focus:ring-1 focus:ring-primary resize-y"
                                :rows="8"
                                placeholder='Pega tu JSON aquí, por ejemplo:
{
    "nombre": "Juan",
    "email": "juan@ejemplo.com",
    "datos_extra": {
        "telefono": "5551234"
    }
}'
                                :disabled="executing"
                                spellcheck="false"
                            />
                            <p v-if="jsonBodyError" class="mt-1 text-xs text-red-500">
                                {{ jsonBodyError }}
                            </p>
                            <p v-else-if="jsonBodyText.trim() !== ''" class="mt-1 text-xs text-green-600 dark:text-green-400">
                                ✓ JSON válido
                            </p>
                        </div>

                        <!-- Key-Value pairs (shown when NOT in JSON body mode for body tab, always for other tabs) -->
                        <div v-if="!(activeExtraTab === 'body' && jsonBodyMode)">
                            <div
                                v-for="(param, index) in extraParams[activeExtraTab]"
                                :key="index"
                                class="flex items-center gap-2 mb-2"
                            >
                                <input
                                    v-model="param.key"
                                    type="text"
                                    class="flex-1 form-input text-xs"
                                    placeholder="Clave"
                                    :disabled="executing"
                                />
                                <input
                                    v-model="param.value"
                                    type="text"
                                    class="flex-1 form-input text-xs"
                                    placeholder="Valor"
                                    :disabled="executing"
                                />
                                <button
                                    type="button"
                                    @click="removeExtraParam(activeExtraTab, index)"
                                    class="shrink-0 p-1.5 text-red-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded transition-colors"
                                    :disabled="executing"
                                    title="Eliminar"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>

                            <button
                                type="button"
                                @click="addExtraParam(activeExtraTab)"
                                class="inline-flex items-center gap-1 text-xs text-primary hover:text-primary/80 font-medium"
                                :disabled="executing"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Agregar campo
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Animación de carga -->
        <div v-if="executing" class="bg-white dark:bg-zinc-800 rounded-lg border border-gray-200 dark:border-zinc-700 p-8 text-center">
            <div class="flex flex-col items-center">
                <div class="relative w-24 h-24 mb-4">
                    <div class="absolute inset-0 rounded-full border-4 border-gray-200 dark:border-zinc-700"></div>
                    <div class="absolute inset-0 rounded-full border-4 border-t-primary border-b-transparent animate-spin"></div>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-primary animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 3v2m6-2v2M3 9h2m-2 6h2m14-6h2m-2 6h2M3 11h18m-9-9v2m0 16v2M9 21h6" />
                        </svg>
                    </div>
                </div>
                <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                    Ejecutando integración...
                </h4>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ selectedEndpoint?.name || 'Preparando solicitud' }}
                </p>
            </div>
        </div>

        <!-- Resultado de la ejecución -->
        <div v-if="!executing && (executionResult || executionError)" class="bg-white dark:bg-zinc-800 rounded-lg border border-gray-200 dark:border-zinc-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-zinc-700 flex items-center justify-between">
                <h4 class="text-lg font-medium text-gray-900 dark:text-white">
                    Resultado de la Ejecución
                </h4>
                <button
                    @click="resetExecution"
                    class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"
                >
                    Nueva ejecución
                </button>
            </div>

            <div class="p-6 space-y-4">
                <!-- Estado -->
                <div class="flex items-center gap-3">
                    <div v-if="executionError" class="flex items-center gap-2 text-red-600 dark:text-red-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="font-medium">Error en la ejecución</span>
                    </div>
                    <div v-else class="flex items-center gap-2 text-green-600 dark:text-green-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="font-medium">Ejecución exitosa</span>
                    </div>
                </div>

                <!-- Código de estado -->
                <div v-if="executionResult?.status_code || executionError?.status_code" class="flex items-center gap-2">
                    <span class="text-sm text-gray-500 dark:text-gray-400">Código de estado:</span>
                    <span
                        :class="[
                            (executionResult?.status_code || executionError?.status_code) >= 200 && (executionResult?.status_code || executionError?.status_code) < 300
                                ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
                                : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'
                        ]"
                        class="px-3 py-1 rounded-full text-sm font-medium"
                    >
                        {{ executionResult?.status_code || executionError?.status_code }}
                    </span>
                </div>

                <!-- Mensaje -->
                <div v-if="executionResult?.message || executionError?.message">
                    <span class="text-sm text-gray-500 dark:text-gray-400">Mensaje:</span>
                    <p class="text-gray-900 dark:text-white mt-1">
                        {{ executionResult?.message || executionError?.message }}
                    </p>
                </div>

                <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <input
                        v-model="showFullDetails"
                        type="checkbox"
                        class="form-checkbox"
                    />
                    Mostrar detalle completo
                </label>

                <!-- Request enviado -->
                <div v-if="executionResult?.request || executionError?.request">
                    <button
                        @click="showRequest = !showRequest"
                        class="flex items-center gap-2 text-sm text-primary hover:text-primary/80"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" :class="showRequest ? 'rotate-180' : ''" class="w-4 h-4 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                        {{ showRequest ? 'Ocultar' : 'Ver' }} datos enviados
                    </button>

                    <div v-if="showRequest" class="mt-3">
                        <pre class="bg-gray-100 dark:bg-zinc-900 p-4 rounded-lg text-xs font-mono overflow-x-auto max-h-96 overflow-y-auto text-gray-700 dark:text-gray-300">{{ formatResponse(displayRequestData()) }}</pre>
                    </div>
                </div>

                <!-- ToggleResponse -->
                <div v-if="executionResult?.received || executionError?.received || executionResult?.response || executionError?.response">
                    <button
                        @click="showResponse = !showResponse"
                        class="flex items-center gap-2 text-sm text-primary hover:text-primary/80"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" :class="showResponse ? 'rotate-180' : ''" class="w-4 h-4 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                        {{ showResponse ? 'Ocultar' : 'Ver' }} datos recibidos
                    </button>

                    <div v-if="showResponse" class="mt-3">
                        <pre class="bg-gray-100 dark:bg-zinc-900 p-4 rounded-lg text-xs font-mono overflow-x-auto max-h-96 overflow-y-auto text-gray-700 dark:text-gray-300">{{ formatResponse(displayReceivedData()) }}</pre>
                    </div>
                </div>

                <!-- Timestamp -->
                <div v-if="executionResult?.executed_at || executionError?.executed_at" class="text-sm text-gray-500 dark:text-gray-400">
                    Ejecutado el: {{ formatDate(executionResult?.executed_at || executionError?.executed_at) }}
                </div>
            </div>
        </div>

        <!-- Estado inicial -->
        <div v-if="!executing && !executionResult && !executionError" class="bg-gray-50 dark:bg-zinc-800 rounded-lg border border-gray-200 dark:border-zinc-700 p-8 text-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.133a1 1 0 000-1.664z M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                Listo para ejecutar
            </h4>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Selecciona un endpoint y haz click en "Ejecutar Ahora" para probar la integración
            </p>
        </div>
    </div>
</template>
