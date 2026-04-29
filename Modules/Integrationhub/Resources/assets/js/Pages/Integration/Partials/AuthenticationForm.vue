<script setup>
import { ref } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';
import Swal2 from 'sweetalert2';
import axios from 'axios';
import ModalSmall from '@/Components/ModalSmall.vue';
import IconLoader from '@/Components/vristo/icon/icon-loader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputError from '@/Components/InputError.vue';
import { useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    integrationId: {
        type: Number,
        required: true
    },
    auths: {
        type: Array,
        default: () => []
    }
});

const authFieldTypes = [
    { value: 'text', label: 'Texto' },
    { value: 'password', label: 'Password' },
    { value: 'token', label: 'Token' },
    { value: 'api_key', label: 'API Key' },
    { value: 'oauth', label: 'OAuth' },
];

const authLocations = [
    { value: 'header', label: 'Header' },
    { value: 'body', label: 'Body' },
    { value: 'query', label: 'Query Param' },
];

const newAuthField = useForm({
    field_id: null,
    field_name: '',
    field_type: 'token',
    field_value: '',
    auth_location: 'header',
    is_enabled: true
});

const showAuthModal = ref(false);
const saving = ref(false);
const togglingId = ref(null);

const addAuthField = () => {
    showAuthModal.value = true;
};

const editAuthField = (auth) => {
    newAuthField.field_id = auth.id;
    newAuthField.field_name = auth.field_name;
    newAuthField.field_type = auth.field_type;
    newAuthField.field_value = auth.field_value;
    newAuthField.auth_location = auth.auth_location;
    newAuthField.is_enabled = auth.is_enabled;
    showAuthModal.value = true;
};

const confirmDelete = (auth) => {
    const fieldName = auth.field_name;

    Swal2.fire({
        title: '¿Eliminar campo?',
        text: `¿Estás seguro de eliminar el campo "${fieldName}"?`,
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
            destroyServer(auth.id);
        }
    });
};

const toggleEnabled = (auth) => {
    togglingId.value = auth.id;
    let lisActive = !auth.is_enabled;

    axios.put(route('integrationhub_update_status_auth', props.integrationId), {
        field_id: auth.id,
        is_active: lisActive
    }).then(() => {
        auth.is_enabled = lisActive;
        togglingId.value = null;
        Swal2.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: lisActive ? 'Campo activado' : 'Campo desactivado',
            showConfirmButton: false,
            timer: 2000,
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    }).catch(() => {
        togglingId.value = null;
        Swal2.fire({
            title: 'Error',
            text: 'No se pudo actualizar el estado del campo.',
            icon: 'error',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    });
};

const destroyServer = async (id) => {
    try {
        await axios.delete(route('integrationhub_destroy_auth', id)).then(() => {
            Swal2.fire({
                title: 'Enhorabuena',
                text: 'Se eliminó correctamente',
                icon: 'success',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
            refreshAuths();
        });
    } catch (error) {
        Swal2.fire({
            title: 'Error',
            text: 'No se pudo eliminar el campo.',
            icon: 'error',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    }
};

const storeToServer = async () => {
    newAuthField.put(route('integrationhub_update_auth', props.integrationId), {
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
            showAuthModal.value = false;
            newAuthField.reset();
            refreshAuths();
        },
    });
};

const refreshAuths = async () => {
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
            Agrega los campos de autenticación requeridos por la API (token, API key, etc.).
        </p>
    </div>

    <div class="mb-4 flex justify-end">
        <button @click="addAuthField" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Agregar Campo
        </button>
    </div>

    <div v-if="auths.length === 0" class="text-center py-8">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
        </svg>
        <p class="text-gray-500 dark:text-gray-400">No hay campos de autenticación configurados.</p>
        <p class="text-sm text-gray-400 mt-1">Haz clic en "Agregar Campo" para comenzar.</p>
    </div>

    <div v-else class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-zinc-700 dark:text-gray-400">
                <tr>
                    <th class="px-4 py-3 w-32">Habilitado</th>
                    <th class="px-4 py-3">Nombre del Campo</th>
                    <th class="px-4 py-3">Tipo</th>
                    <th class="px-4 py-3">Ubicación</th>
                    <th class="px-4 py-3">Valor</th>
                    <th class="px-4 py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(auth, index) in auths" :key="index" class="border-b dark:border-zinc-600 hover:bg-gray-50 dark:hover:bg-zinc-700/50">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <div v-if="togglingId === auth.id" class="flex items-center justify-center h-6 w-11">
                                <svg class="animate-spin h-5 w-5 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                            <button v-else @click="toggleEnabled(auth)"
                                :class="auth.is_enabled ? 'bg-green-500' : 'bg-gray-300 dark:bg-zinc-600'"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-200">
                                <span :class="auth.is_enabled ? 'translate-x-6' : 'translate-x-1'"
                                    class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform duration-200 shadow-sm"></span>
                            </button>
                            <span :class="auth.is_enabled ? 'text-green-600 dark:text-green-400 font-medium' : 'text-gray-400'"
                                class="text-xs font-medium">
                                {{ auth.is_enabled ? 'Activo' : 'Inactivo' }}
                            </span>
                        </div>
                    </td>
                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ auth.field_name }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                            {{ auth.field_type }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 text-xs rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300">
                            {{ auth.auth_location === 'header' ? 'Header' : auth.auth_location === 'body' ? 'Body' : 'Query' }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <span v-if="auth.field_value" class="text-gray-500 dark:text-gray-400">••••••••</span>
                        <span v-else class="text-gray-400">-</span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <button @click="editAuthField(auth)" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 mr-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </button>
                        <button @click="confirmDelete(auth)" class="text-red-600 hover:text-red-800 dark:text-red-400">
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
        <h4 class="font-medium text-blue-900 dark:text-blue-300 mb-2">Ubicaciones de envío:</h4>
        <ul class="text-sm text-blue-700 dark:text-blue-400 space-y-1">
            <li><strong>Header:</strong> Se envía en las cabeceras HTTP (ej. Authorization)</li>
            <li><strong>Body:</strong> Se envía dentro del cuerpo de la petición JSON</li>
            <li><strong>Query:</strong> Se envía como parámetro en la URL</li>
        </ul>
    </div>

    <!-- Modal Auth -->
    <ModalSmall :show="showAuthModal" :onClose="() => showAuthModal = false" :icon="'/img/autenticacion-de-dos-factores.png'">
        <template #title>
            {{ newAuthField.field_id ? 'Editar Campo de Autenticación' : 'Agregar Campo de Autenticación' }}
        </template>
        <template #message>
            Completa los datos del campo de autenticación
        </template>
        <template #content>
            <div class="space-y-4">
                <div>
                    <InputLabel for="field_name" value="Nombre del Campo *" />
                    <input id="field_name" v-model="newAuthField.field_name" type="text" class="form-input" placeholder="Ej: Authorization, api_key" />
                    <p class="mt-1 text-xs text-gray-500">Nombre descriptivo para identificar el campo (ej: Authorization, api_key)</p>
                    <InputError :message="newAuthField.errors.field_name" class="mt-2" />
                </div>
                <div>
                    <InputLabel for="field_type" value="Tipo de Campo *" />
                    <select id="field_type" v-model="newAuthField.field_type" class="form-select">
                        <option v-for="type in authFieldTypes" :key="type.value" :value="type.value">{{ type.label }}</option>
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Tipo de valor: Texto, Password, Token, API Key, OAuth</p>
                    <InputError :message="newAuthField.errors.field_type" class="mt-2" />
                </div>
                <div>
                    <InputLabel for="auth_location" value="Ubicación *" />
                    <select id="auth_location" v-model="newAuthField.auth_location" class="form-select">
                        <option v-for="loc in authLocations" :key="loc.value" :value="loc.value">{{ loc.label }}</option>
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Dónde se envía: Header (cabeceras), Body (cuerpo JSON), Query (URL)</p>
                    <InputError :message="newAuthField.errors.auth_location" class="mt-2" />
                </div>
                <div>
                    <InputLabel for="field_value" value="Valor del Campo" />
                    <input id="field_value" v-model="newAuthField.field_value" :type="newAuthField.field_type === 'password' ? 'password' : 'text'" class="form-input" placeholder="Token o clave de API" />
                    <p class="mt-1 text-xs text-gray-500">Este valor se guardará de forma segura en la base de datos.</p>
                    <InputError :message="newAuthField.errors.field_value" class="mt-2" />
                </div>
            </div>
        </template>
        <template #buttons>
            <PrimaryButton @click="storeToServer" :disabled="newAuthField.processing" class="mr-3">
                <IconLoader v-if="newAuthField.processing" class="w-4 h-4 mr-2 animate-spin" />
                {{ saving ? 'Guardando...' : 'Guardar' }}
            </PrimaryButton>
        </template>
    </ModalSmall>
</template>