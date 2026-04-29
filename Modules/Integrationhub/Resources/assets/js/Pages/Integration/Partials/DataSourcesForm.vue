<script setup>
import { ref, computed } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';
import Swal2 from 'sweetalert2';
import axios from 'axios';
import ModalLarge from '@/Components/ModalLarge.vue';
import IconLoader from '@/Components/vristo/icon/icon-loader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputError from '@/Components/InputError.vue';
import { useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    integrationId: {
        type: Number,
        required: true
    },
    queries: {
        type: Array,
        default: () => []
    }
});

const queryTypes = [
    { value: 'select', label: 'Consulta SELECT' },
    { value: 'raw_sql', label: 'SQL Raw' },
];

const newQuery = useForm({
    query_id: null,
    query_name: '',
    query_sql: '',
    query_type: 'select',
    parameters: '',
    is_active: true
});

const showModal = ref(false);
const saving = ref(false);
const togglingId = ref(null);

const addQuery = () => {
    newQuery.query_id = null;
    newQuery.query_name = '';
    newQuery.query_sql = '';
    newQuery.query_type = 'select';
    newQuery.parameters = '';
    newQuery.is_enabled = true;
    showModal.value = true;
};

const editQuery = (query) => {
    newQuery.query_id = query.id;
    newQuery.query_name = query.query_name;
    newQuery.query_sql = query.query_sql;
    newQuery.query_type = query.query_type;
    newQuery.parameters = query.parameters ? JSON.stringify(query.parameters, null, 2) : '';
    newQuery.is_active = query.is_active;
    showModal.value = true;
};

const confirmDelete = (query) => {
    const queryName = query.query_name;

    Swal2.fire({
        title: '¿Eliminar Query?',
        text: `¿Estás seguro de eliminar la query "${queryName}"?`,
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
            destroyServer(query.id);
        }
    });
};

const destroyServer = async (id) => {
    try {
        await axios.delete(route('integrationhub_destroy_query', id));
        Swal2.fire({
            title: 'Enhorabuena',
            text: 'Se eliminó correctamente',
            icon: 'success',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
        refreshQueries();
    } catch (error) {
        Swal2.fire({
            title: 'Error',
            text: 'No se pudo eliminar la query.',
            icon: 'error',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    }
};

const toggleEnabled = (query) => {
    togglingId.value = query.id;
    let isActive = !query.is_active;

    axios.put(route('integrationhub_update_status_query', props.integrationId), {
        query_id: query.id,
        is_active: isActive
    }).then(() => {
        query.is_active = isActive;
        togglingId.value = null;
        Swal2.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: isActive ? 'Query activada' : 'Query desactivada',
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
        await axios.put(route('integrationhub_update_query', props.integrationId), {
            ...newQuery,
            parameters: newQuery.parameters ? newQuery.parameters : null
        });
        
        saving.value = false;
        Swal2.fire({
            title: 'Enhorabuena',
            text: 'Se registró correctamente',
            icon: 'success',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
        showModal.value = false;
        newQuery.reset();
        refreshQueries();
    } catch (error) {
        saving.value = false;
        Swal2.fire({
            title: 'Error',
            text: error.response?.data?.message || 'No se pudo guardar la query.',
            icon: 'error',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    }
};

const refreshQueries = async () => {
    router.visit(route('integrationhub_editar', props.integrationId), {
        method: "get",
        replace: true,
        preserveState: true,
        preserveScroll: true,
        only: ['integration'],
    });
};

const getQueryTypeLabel = (type) => {
    const found = queryTypes.find(q => q.value === type);
    return found ? found.label : type;
};
</script>

<template>
    <div class="mb-4">
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">
            Gestiona las consultas SQL personalizadas que se usarán como origen de datos para la integración.
        </p>
    </div>

    <div class="mb-4 flex justify-end">
        <button @click="addQuery" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Agregar Query
        </button>
    </div>

    <div v-if="queries.length === 0" class="text-center py-8">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
        </svg>
        <p class="text-gray-500 dark:text-gray-400">No hay queries configuradas.</p>
        <p class="text-sm text-gray-400 mt-1">Haz clic en "Agregar Query" para comenzar.</p>
    </div>

    <div v-else class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-zinc-700 dark:text-gray-400">
                <tr>
                    <th class="px-4 py-3 w-24">Activo</th>
                    <th class="px-4 py-3">Nombre</th>
                    <th class="px-4 py-3">Tipo</th>
                    <th class="px-4 py-3">SQL</th>
                    <th class="px-4 py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(query, index) in queries" :key="index" class="border-b dark:border-zinc-600 hover:bg-gray-50 dark:hover:bg-zinc-700/50">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <div v-if="togglingId === query.id" class="flex items-center justify-center h-6 w-11">
                                <svg class="animate-spin h-5 w-5 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                            <button v-else @click="toggleEnabled(query)"
                                :class="query.is_active ? 'bg-green-500' : 'bg-gray-300 dark:bg-zinc-600'"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-200">
                                <span :class="query.is_active ? 'translate-x-6' : 'translate-x-1'"
                                    class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform duration-200 shadow-sm"></span>
                            </button>
                        </div>
                    </td>
                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ query.query_name }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                            {{ getQueryTypeLabel(query.query_type) }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <code class="text-xs text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-zinc-700 px-2 py-1 rounded block max-w-xs truncate">
                            {{ query.query_sql }}
                        </code>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <button @click="editQuery(query)" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 mr-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </button>
                        <button @click="confirmDelete(query)" class="text-red-600 hover:text-red-800 dark:text-red-400">
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
        <h4 class="font-medium text-blue-900 dark:text-blue-300 mb-2">Tipos de Query:</h4>
        <ul class="text-sm text-blue-700 dark:text-blue-400 space-y-1">
            <li><strong>Consulta SELECT:</strong> Consulta SQL estándar con SELECT</li>
            <li><strong>SQL Raw:</strong> SQL avanzado que puede incluir múltiples sentencias</li>
        </ul>
    </div>

    <!-- Modal Query -->
    <ModalLarge :show="showModal" :onClose="() => showModal = false" :icon="'/img/base-de-datos.png'">
        <template #title>
            {{ newQuery.query_id ? 'Editar Query' : 'Agregar Query' }}
        </template>
        <template #message>
            Configura una consulta SQL personalizada como origen de datos
        </template>
        <template #content>
            <div class="space-y-4">
                <!-- Nombre -->
                <div>
                    <InputLabel for="query_name" value="Nombre de la Query *" />
                    <input id="query_name" v-model="newQuery.query_name" type="text" class="form-input" placeholder="Ej: Obtener productos del cliente" />
                    <p class="mt-1 text-xs text-gray-500">Nombre descriptivo para identificar la consulta</p>
                </div>

                <!-- Tipo de Query -->
                <div>
                    <InputLabel for="query_type" value="Tipo de Query" />
                    <select id="query_type" v-model="newQuery.query_type" class="form-select">
                        <option v-for="type in queryTypes" :key="type.value" :value="type.value">{{ type.label }}</option>
                    </select>
                </div>

                <!-- SQL -->
                <div>
                    <InputLabel for="query_sql" value="Consulta SQL *" />
                    <textarea id="query_sql" v-model="newQuery.query_sql" rows="6" class="form-textarea" 
                        placeholder="SELECT campo1, campo2 FROM tabla WHERE..."></textarea>
                    <p class="mt-1 text-xs text-gray-500">Escribe tu consulta SQL. Puedes usar {{variable}} para parámetros.</p>
                </div>

                <!-- Parámetros JSON -->
                <div>
                    <InputLabel for="parameters" value="Parámetros (JSON)" />
                    <textarea id="parameters" v-model="newQuery.parameters" rows="3" class="form-textarea font-mono text-sm"
                        placeholder='{"cliente_id": 1, "estado": "activo"}'></textarea>
                    <p class="mt-1 text-xs text-gray-500">Parámetros opcionales en formato JSON</p>
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