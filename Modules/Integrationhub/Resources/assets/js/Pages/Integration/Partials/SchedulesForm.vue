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
    cron_expression: '0 0 * * *',
    is_active: true
});

const showModal = ref(false);
const saving = ref(false);
const togglingId = ref(null);

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
        cron_expression: '0 0 * * *',
        is_active: true
    };
    showModal.value = true;
};

const editSchedule = (schedule) => {
    newSchedule.value = {
        schedule_id: schedule.id,
        cron_expression: schedule.cron_expression,
        is_active: schedule.is_active
    };
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
        cron_expression: schedule.cron_expression,
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
</script>

<template>
    <div class="mb-4">
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">
            Configura la ejecución automática de la integración usando expresiones cron.
        </p>
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

                <!-- Expresión cron personalizada -->
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