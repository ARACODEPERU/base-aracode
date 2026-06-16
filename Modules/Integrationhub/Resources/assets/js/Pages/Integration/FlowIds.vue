<script setup>
import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import { ref, onMounted } from 'vue';
import Swal2 from 'sweetalert2';
import axios from 'axios';

const flowIds = ref([]);
const loading = ref(false);
const saving = ref({});

const loadFlowIds = async () => {
    loading.value = true;
    try {
        const response = await axios.get(route('integrationhub_flow_ids'));
        flowIds.value = response.data.flow_ids || [];
    } catch (error) {
        Swal2.fire({
            title: 'Error',
            text: 'No se pudieron cargar los IDs de flujo.',
            icon: 'error',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    } finally {
        loading.value = false;
    }
};

const saveFlowId = async (flowId) => {
    saving.value[flowId.key] = true;
    try {
        await axios.put(route('integrationhub_flow_ids_update', flowId.key), {
            flow_id: flowId.flow_id,
            label: flowId.label,
        });
        Swal2.fire({
            title: 'Guardado',
            text: 'ID de flujo actualizado correctamente.',
            icon: 'success',
            timer: 1500,
            showConfirmButton: false,
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    } catch (error) {
        Swal2.fire({
            title: 'Error',
            text: error.response?.data?.message || 'No se pudo guardar el ID de flujo.',
            icon: 'error',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    } finally {
        saving.value[flowId.key] = false;
    }
};

onMounted(() => {
    loadFlowIds();
});
</script>

<template>
    <AppLayout title="Plantillas / Flujos">
        <Navigation :routeModule="route('integrationhub_listado')" :titleModule="'Centro de Integraciones'"
            :data="[
                {route: route('integrationhub_listado'), title: 'Integraciones'},
                {title: 'Plantillas / Flujos'}
            ]"
        />
        <div class="pt-5">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Plantillas / Flujos</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Configura los IDs de flujo o plantilla utilizados por las integraciones del sistema.</p>
            </div>

            <!-- Loader -->
            <div v-if="loading" class="flex justify-center py-12">
                <svg class="animate-spin h-8 w-8 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>

            <!-- Empty state -->
            <div v-else-if="flowIds.length === 0" class="text-center py-12">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <p class="text-gray-500 dark:text-gray-400">No hay flujos configurados.</p>
                <p class="text-sm text-gray-400 mt-1">Los flujos se crearán automáticamente con valores por defecto.</p>
            </div>

            <!-- Flow IDs Cards -->
            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="flowId in flowIds" :key="flowId.key" class="panel p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ flowId.label || flowId.key }}</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                <code class="bg-gray-100 dark:bg-zinc-700 px-1.5 py-0.5 rounded text-xs">{{ flowId.key }}</code>
                            </p>
                        </div>
                        <span class="px-2 py-1 bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300 rounded text-xs font-medium">
                            ID de flujo
                        </span>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            ID de flujo o plantilla
                        </label>
                        <input
                            v-model="flowId.flow_id"
                            type="text"
                            class="form-input w-full"
                            placeholder="Ingresa el ID del flujo..."
                        />
                    </div>

                    <div class="flex justify-end">
                        <button
                            @click="saveFlowId(flowId)"
                            :disabled="saving[flowId.key]"
                            class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition disabled:opacity-60 disabled:cursor-not-allowed text-sm"
                        >
                            <svg v-if="saving[flowId.key]" class="animate-spin h-4 w-4 inline mr-1 -mt-0.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ saving[flowId.key] ? 'Guardando...' : 'Guardar' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
