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

const selectedEndpoint = computed(() => {
    return props.endpoints.find(ep => ep.id === selectedEndpointId.value);
});

const statusColors = {
    success: 'text-green-600 dark:text-green-400',
    error: 'text-red-600 dark:text-red-400',
    pending: 'text-yellow-600 dark:text-yellow-400'
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

const formatResponse = (data) => {
    if (!data) return '';
    try {
        return JSON.stringify(data, null, 2);
    } catch {
        return String(data);
    }
};

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

    executing.value = true;
    executionResult.value = null;
    executionError.value = null;

    try {
        const response = await axios.post(route('integrationhub_execute', props.integrationId), {
            endpoint_id: selectedEndpointId.value
        });

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
                            {{ ep.method }} - {{ ep.name }} ({{ ep.url }})
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

                <!-- ToggleResponse -->
                <div v-if="executionResult?.response || executionError?.response">
                    <button
                        @click="showResponse = !showResponse"
                        class="flex items-center gap-2 text-sm text-primary hover:text-primary/80"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" :class="showResponse ? 'rotate-180' : ''" class="w-4 h-4 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                        {{ showResponse ? 'Ocultar' : 'Ver' }} respuesta completa
                    </button>

                    <div v-if="showResponse" class="mt-3">
                        <pre class="bg-gray-100 dark:bg-zinc-900 p-4 rounded-lg text-xs font-mono overflow-x-auto max-h-96 overflow-y-auto text-gray-700 dark:text-gray-300">{{ formatResponse(executionResult?.response || executionError?.response) }}</pre>
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
