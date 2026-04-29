<script setup>
import { ref } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';
import Swal2 from 'sweetalert2';
import axios from 'axios';
import ModalSmall from '@/Components/ModalSmall.vue';
import IconLoader from '@/Components/vristo/icon/icon-loader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { useForm, router } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    integrationId: {
        type: Number,
        required: true
    },
    endpoints: {
        type: Array,
        default: () => []
    }
});


const httpMethods = [
    { value: 'GET', label: 'GET' },
    { value: 'POST', label: 'POST' },
    { value: 'PUT', label: 'PUT' },
    { value: 'DELETE', label: 'DELETE' },
    { value: 'PATCH', label: 'PATCH' },
];

const bodyTypes = [
    { value: 'json', label: 'JSON' },
    { value: 'xml', label: 'XML' },
    { value: 'form', label: 'Form Data' },
    { value: 'raw', label: 'Raw' },
    { value: 'none', label: 'Ninguno' },
];

const newEndpoint = useForm({
    endpoint_id: null,
    name: '',
    endpoint_path: '',
    http_method: 'POST',
    body_type: 'json',
    is_active: true
});

const showModal = ref(false);
const editingIndex = ref(null);
const saving = ref(false);
const togglingId = ref(null);

const methodColors = {
    GET: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
    POST: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
    PUT: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
    DELETE: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
    PATCH: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
};

const addEndpoint = () => {
    showModal.value = true;
};

const editEndpoint = (endpoint) => {
    newEndpoint.endpoint_id = endpoint.id;
    newEndpoint.name = endpoint.name;
    newEndpoint.endpoint_path = endpoint.endpoint_path;
    newEndpoint.http_method = endpoint.http_method;
    newEndpoint.body_type = endpoint.body_type;
    newEndpoint.is_active = endpoint.is_active;
    showModal.value = true;
};


const confirmDelete = (endpoint) => {
    const endpointName = endpoint.name;

    Swal2.fire({
        title: '¿Eliminar endpoint?',
        text: `¿Estás seguro de eliminar el endpoint "${endpointName}"?`,
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
            destroyServer(endpoint.id);
        }
    });
};


const toggleActive = (endpoint) => {
    togglingId.value = endpoint.id;
    let lisActive = !endpoint.is_active;

    axios.put(route('integrationhub_update_status_endpoints', props.integrationId), {
        endpoint_id: endpoint.id,
        is_active: lisActive
    }).then(() => {
        endpoint.is_active = lisActive;
        togglingId.value = null;
        Swal2.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: lisActive ? 'Endpoint activado' : 'Endpoint desactivado',
            showConfirmButton: false,
            timer: 2000,
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    }).catch(() => {
        togglingId.value = null;
        Swal2.fire({
            title: 'Error',
            text: 'No se pudo actualizar el estado del endpoint.',
            icon: 'error',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    });
};

const destroyServer = async (id) => {
    try {
        await axios.delete(route('integrationhub_destroy_endpoints', id)).then(() => {
            Swal2.fire({
                title: 'Enhorabuena',
                text: 'Se eliminó correctamente',
                icon: 'success',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
            refreshEndpoints();
        });
    } catch (error) {
        Swal2.fire({
            title: 'Error',
            text: 'No se pudieron guardar los endpoints.',
            icon: 'error',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    }
};

const storeToServer = async () => {
    newEndpoint.put(route('integrationhub_update_endpoints', props.integrationId), {
        errorBag: 'storeToServer',
        preserveScroll: true,
        onSuccess: () => {
            Swal2.fire({
                title: 'Enhorabuena',
                text: 'Se registró correctamente',
                icon: 'success',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
            showModal.value = false;
            newEndpoint.reset();
            refreshEndpoints();
        },
    });
};

const refreshEndpoints = async () => {
    router.visit(route('integrationhub_editar', props.integrationId), {
        method: "get",
        replace: true,
        preserveState: true,
        preserveScroll: true,
        only: ['integration'],
    });
};
</script>

<template>
    <div class="mb-4">
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Define las rutas de la API externa que usarás para enviar y recibir datos.
        </p>
    </div>

    <div class="mb-4 flex justify-end">
        <button @click="addEndpoint" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Agregar Endpoint
        </button>
    </div>

    <div v-if="endpoints.length === 0" class="text-center py-8">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
        </svg>
        <p class="text-gray-500 dark:text-gray-400">No hay endpoints configurados.</p>
        <p class="text-sm text-gray-400 mt-1">Haz clic en "Agregar Endpoint" para comenzar.</p>
    </div>

    <div v-else class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-zinc-700 dark:text-gray-400">
                <tr>
                    <th class="px-4 py-3 w-32">Activo</th>
                    <th class="px-4 py-3">Nombre</th>
                    <th class="px-4 py-3">Ruta</th>
                    <th class="px-4 py-3">Método</th>
                    <th class="px-4 py-3">Body</th>
                    <th class="px-4 py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(endpoint, index) in endpoints" :key="index" class="border-b dark:border-zinc-600 hover:bg-gray-50 dark:hover:bg-zinc-700/50">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <!-- Loader cuando está togglesando -->
                            <div v-if="togglingId === endpoint.id" class="flex items-center justify-center h-6 w-11">
                                <svg class="animate-spin h-5 w-5 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                            <!-- Toggle normal -->
                            <button v-else @click="toggleActive(endpoint)"
                                :class="endpoint.is_active ? 'bg-green-500' : 'bg-gray-300 dark:bg-zinc-600'"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-200">
                                <span :class="endpoint.is_active ? 'translate-x-6' : 'translate-x-1'"
                                    class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform duration-200 shadow-sm"></span>
                            </button>
                            <span :class="endpoint.is_active ? 'text-green-600 dark:text-green-400 font-medium' : 'text-gray-400'"
                                class="text-xs font-medium">
                                {{ endpoint.is_active ? 'Activo' : 'Inactivo' }}
                            </span>
                        </div>
                    </td>
                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ endpoint.name }}</td>
                    <td class="px-4 py-3">
                        <code class="text-sm text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-zinc-700 px-2 py-1 rounded">
                            {{ endpoint.endpoint_path }}
                        </code>
                    </td>
                    <td class="px-4 py-3">
                        <span :class="methodColors[endpoint.http_method] || 'bg-gray-100 text-gray-800'"
                            class="px-2 py-1 text-xs font-semibold rounded uppercase">
                            {{ endpoint.http_method }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-700 dark:bg-zinc-700 dark:text-gray-300">
                            {{ endpoint.body_type?.toUpperCase() || 'Ninguno' }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <button @click="editEndpoint(endpoint)" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 mr-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </button>
                        <button @click="confirmDelete(endpoint)" class="text-red-600 hover:text-red-800 dark:text-red-400">
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
        <h4 class="font-medium text-blue-900 dark:text-blue-300 mb-2">Métodos HTTP:</h4>
        <ul class="text-sm text-blue-700 dark:text-blue-400 space-y-1">
            <li><strong>GET:</strong> Obtener datos (solo lectura)</li>
            <li><strong>POST:</strong> Crear nuevos registros</li>
            <li><strong>PUT:</strong> Actualizar registros existentes</li>
            <li><strong>DELETE:</strong> Eliminar registros</li>
            <li><strong>PATCH:</strong> Actualización parcial</li>
        </ul>
    </div>

    <!-- Modal Endpoint -->
    <ModalSmall :show="showModal" :onClose="() => showModal = false" :icon="'/img/punto-final.png'">
        <template #title>
            {{ editingIndex !== null ? 'Editar Endpoint' : 'Agregar Endpoint' }}
        </template>
        <template #message>
            Configura los datos del endpoint de la API
        </template>
        <template #content>
            <div class="space-y-4">
                <div>
                    <InputLabel for="name" value="Nombre *" />
                    <input id="name" v-model="newEndpoint.name" type="text" class="form-input" placeholder="Ej: Obtener Usuarios" />
                    <p class="mt-1 text-xs text-gray-500">Nombre descriptivo para identificar el endpoint</p>
                    <InputError :message="newEndpoint.errors.name" class="mt-2" />
                </div>
                <div>
                    <InputLabel for="endpoint_path" value="Ruta del Endpoint *" />
                    <input id="endpoint_path" v-model="newEndpoint.endpoint_path" type="text" class="form-input" placeholder="Ej: /api/v1/users" />
                    <p class="mt-1 text-xs text-gray-500">Ruta relativa a la URL base (ej: /api/users, /customers/list)</p>
                    <InputError :message="newEndpoint.errors.endpoint_path" class="mt-2" />
                </div>
                <div>
                    <InputLabel for="http_method" value="Método HTTP *" />
                    <select id="http_method" v-model="newEndpoint.http_method" class="form-select">
                        <option v-for="method in httpMethods" :key="method.value" :value="method.value">{{ method.label }}</option>
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Tipo de solicitud: GET (obtener), POST (crear), PUT (actualizar), DELETE (eliminar)</p>
                    <InputError :message="newEndpoint.errors.http_method" class="mt-2" />
                </div>
                <div>
                    <InputLabel for="body_type" value="Tipo de Body *" />
                    <select id="body_type" v-model="newEndpoint.body_type" class="form-select">
                        <option v-for="type in bodyTypes" :key="type.value" :value="type.value">{{ type.label }}</option>
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Formato del cuerpo: JSON (más común), XML, Form Data, Raw o Ninguno</p>
                    <InputError :message="newEndpoint.errors.body_type" class="mt-2" />
                </div>
            </div>
        </template>
        <template #buttons>
            <PrimaryButton @click="storeToServer" :disabled="newEndpoint.processing" class="mr-3">
                <IconLoader v-if="newEndpoint.processing" class="w-4 h-4 mr-2 animate-spin" />
                {{ saving ? 'Guardando...' : 'Guardar' }}
            </PrimaryButton>
        </template>
    </ModalSmall>
</template>
