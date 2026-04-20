<script setup>

import { ref, computed, watch } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import IconLoader from '@/Components/vristo/icon/icon-loader.vue';
import InputError from '@/Components/InputError.vue';
import IconSave from "@/Components/vristo/icon/icon-save.vue";
import ModalLarge from '@/Components/ModalLarge.vue';
import * as fasIcons from '@fortawesome/free-solid-svg-icons';
import IconTrash from '@/Components/vristo/icon/icon-trash.vue';
import IconEdit from '@/Components/vristo/icon/icon-edit.vue';

const props = defineProps({
    course: {
        type: Object,
        default: () => ({}),
    },
    landing: {
        type: Object,
        default: () => ({}),
    },
    languageOptions: {
        type: Object,
        default: () => ({}),
    },
});

const iconCategories = {
    marketing: [
        { value: 'fa-bullhorn', label: 'Megáfono' },
        { value: 'fa-bullseye', label: 'Objetivo' },
        { value: 'fa-rocket', label: 'Cohete' },
        { value: 'fa-chart-line', label: 'Gráfico Línea' },
        { value: 'fa-chart-pie', label: 'Gráfico Circular' },
        { value: 'fa-chart-bar', label: 'Gráfico Barras' },
        { value: 'fa-megaphone', label: 'Megáfono Alt' },
        { value: 'fa-search-dollar', label: 'Buscar Dinero' },
        { value: 'fa-funnel-dollar', label: 'Embudo Ventas' },
        { value: 'fa-poll', label: 'Encuesta' },
        { value: 'fa-mail-bulk', label: 'Correo Masivo' },
        { value: 'fa-layer-group', label: 'Capas' },
    ],
    resultados: [
        { value: 'fa-trophy', label: 'Trofeo' },
        { value: 'fa-medal', label: 'Medalla' },
        { value: 'fa-award', label: 'Premio' },
        { value: 'fa-certificate', label: 'Certificado' },
        { value: 'fa-star', label: 'Estrella' },
        { value: 'fa-check-circle', label: 'Check' },
        { value: 'fa-check-double', label: 'Doble Check' },
        { value: 'fa-list-check', label: 'Lista Check' },
    ],
    crecimiento: [
        { value: 'fa-arrow-trend-up', label: 'Crecimiento' },
        { value: 'fa-arrow-trend-down', label: 'Decrecimiento' },
        { value: 'fa-chart-simple', label: 'Gráfico' },
        { value: 'fa-bolt', label: 'Rayo' },
        { value: 'fa-fire', label: 'Fuego' },
        { value: 'fa-rocket', label: 'Cohete' },
    ],
    academico: [
        { value: 'fa-graduation-cap', label: 'Graduación' },
        { value: 'fa-book', label: 'Libro' },
        { value: 'fa-school', label: 'Escuela' },
        { value: 'fa-book-reader', label: 'Lectura' },
        { value: 'fa-chalkboard-teacher', label: 'Profesor' },
        { value: 'fa-university', label: 'Universidad' },
        { value: 'fa-brain', label: 'Cerebro' },
    ],
    basicos: [
        { value: 'fa-users', label: 'Usuarios' },
        { value: 'fa-briefcase', label: 'Maletín' },
        { value: 'fa-globe', label: 'Globo' },
        { value: 'fa-clock', label: 'Reloj' },
        { value: 'fa-calendar', label: 'Calendario' },
        { value: 'fa-envelope', label: 'Correo' },
        { value: 'fa-phone', label: 'Teléfono' },
        { value: 'fa-laptop', label: 'Computadora' },
        { value: 'fa-video', label: 'Video' },
        { value: 'fa-lightbulb', label: 'Idea' },
    ],
};

const categoryLabels = {
    marketing: 'Marketing',
    resultados: 'Resultados',
    crecimiento: 'Crecimiento',
    academico: 'Académico',
    basicos: 'Básicos',
};

const getInitialResultsData = () => {
    const data = props.landing.results_section;
    if (data && typeof data === 'string') {
        try {
            return JSON.parse(data);
        } catch (e) {
            return null;
        }
    }
    return data || null;
};

const initialData = getInitialResultsData();

const formResults = useForm({
    name: initialData?.name || 'Resultados del Programa',
    title: initialData?.title || '',
    description: initialData?.description || '',
    items: initialData?.items || [
        { icon: '', title: '', description: '' },
        { icon: '', title: '', description: '' }
    ]
});

const showModal = ref(false);
const editingIndex = ref(-1);
const modalItem = ref({ icon: '', title: '', description: '' });

const openAddModal = () => {
    modalItem.value = { icon: '', title: '', description: '' };
    editingIndex.value = -1;
    showModal.value = true;
};

const openEditModal = (index) => {
    modalItem.value = { ...formResults.items[index] };
    editingIndex.value = index;
    showModal.value = true;
};

const saveItemFromModal = () => {
    if (editingIndex.value >= 0) {
        formResults.items[editingIndex.value] = { ...modalItem.value };
    } else {
        formResults.items.push({ ...modalItem.value });
    }
    showModal.value = false;
};

const closeModal = () => {
    showModal.value = false;
};

const confirmDelete = (index) => {
    Swal.fire({
        title: '¿Estás seguro?',
        text: 'No podrás revertir esta acción',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        padding: '2em',
        customClass: 'sweet-alerts',
    }).then((result) => {
        if (result.isConfirmed) {
            formResults.items.splice(index, 1);
            Swal.fire({
                icon: 'success',
                title: 'Eliminado',
                text: 'El item ha sido eliminado',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
        }
    });
};

const saveResultsSettings = () => {
    formResults.put(route('aca_courses_landing_update_results', props.course.id),{
        errorBag: 'saveResultsSettings',
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: 'Éxito',
                text: 'Sección Resultados del Programa guardada correctamente',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
        },
    });
};

const formatIconForVue = (iconName) => {
    if (!iconName) return fasIcons.faIcons;
    const cleanName = iconName.replace(/^fa-/, '');
    const pascalName = 'fa' + cleanName
        .split('-')
        .map(part => part.charAt(0).toUpperCase() + part.slice(1))
        .join('');
    return fasIcons[pascalName] || fasIcons.faIcons;
};

</script>
<template>
    <div class="mb-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
            <svg class="w-5 h-5 mr-2 text-blue-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                <path fill="currentColor" d="M64 64c0-17.7-14.3-32-32-32S0 46.3 0 64L0 400c0 44.2 35.8 80 80 80l400 0c17.7 0 32-14.3 32-32s-14.3-32-32-32L80 416c-8.8 0-16-7.2-16-16L64 64zm406.6 86.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L320 210.7 262.6 153.4c-12.5-12.5-32.8-12.5-45.3 0l-96 96c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0l73.4-73.4 57.4 57.4c12.5 12.5 32.8 12.5 45.3 0l128-128z"/>
            </svg>
            Sección Resultados del Programa
        </h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Configura los resultados que obtendrán los estudiantes al completar el programa.
        </p>
    </div>

    <div class="space-y-6">
        <!-- Datos Principales -->
        <div class="">
            <h4 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-4 flex items-center">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-100 text-blue-600 dark:bg-blue-900/50 dark:text-blue-400 mr-2">
                    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 192 512">
                        <path fill="currentColor" d="M48 48a48 48 0 1 1 96 0 48 48 0 1 1 -96 0zM0 192c0-17.7 14.3-32 32-32l64 0c17.7 0 32 14.3 32 32l0 256 32 0c17.7 0 32 14.3 32 32s-14.3 32-32 32L32 512c-17.7 0-32-14.3-32-32s14.3-32 32-32l32 0 0-224-32 0c-17.7 0-32-14.3-32-32z"/>
                    </svg>
                </span>
                Datos Principales
            </h4>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Nombre
                    </label>
                    <input
                        type="text"
                        v-model="formResults.name"
                        class="form-input"
                        placeholder="Resultados del Programa"
                    >
                    <InputError :message="formResults.errors.name" class="mt-2" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Título
                    </label>
                    <input
                        type="text"
                        v-model="formResults.title"
                        class="form-input"
                        placeholder="Título de la sección"
                    >
                    <InputError :message="formResults.errors.title" class="mt-2" />
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Descripción
                    </label>
                    <textarea
                        v-model="formResults.description"
                        rows="3"
                        class="form-textarea"
                        placeholder="Descripción de la sección..."
                    ></textarea>
                    <InputError :message="formResults.errors.description" class="mt-2" />
                </div>
            </div>
        </div>

        <!-- Items Lista Compacta -->
        <div class="">
            <div class="flex items-center justify-between mb-4">
                <h4 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider flex items-center">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-green-200 text-green-600 dark:bg-green-900/50 dark:text-green-400 mr-2">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                            <path fill="currentColor" d="M64 64C28.7 64 0 92.7 0 128L0 384c0 35.3 28.7 64 64 64l384 0c35.3 0 64-28.7 64-64l0-256c0-35.3-28.7-64-64-64L64 64zm96 256a32 32 0 1 1 -64 0 32 32 0 1 1 64 0zm-32-96a32 32 0 1 1 0-64 32 32 0 1 1 0 64zm120-56l144 0c13.3 0 24 10.7 24 24s-10.7 24-24 24l-144 0c-13.3 0-24-10.7-24-24s10.7-24 24-24zm0 128l144 0c13.3 0 24 10.7 24 24s-10.7 24-24 24l-144 0c-13.3 0-24-10.7-24-24s10.7-24 24-24z"/>
                        </svg>
                    </span>
                    Items de Resultados
                </h4>
                <button
                    type="button"
                    @click="openAddModal"
                    class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                    <i class="fa fa-plus mr-2"></i>
                    Agregar Item
                </button>
            </div>

            <!-- Lista de Items -->
            <div v-if="formResults.items.length === 0" class="p-8 text-center bg-gray-50 dark:bg-gray-700/50 rounded-lg border-2 border-dashed border-gray-200 dark:border-gray-600">
                <i class="fa fa-inbox text-4xl text-gray-300 dark:text-gray-500 mb-2"></i>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    No hay items agregados. Haz clic en "Agregar Item" para crear el primero.
                </p>
            </div>

            <div v-else class="space-y-2">
                <div
                    v-for="(item, index) in formResults.items"
                    :key="index"
                    class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-600"
                >
                    <!-- Icono -->
                    <div class="shrink-0">
                        <div class="flex items-center justify-center w-12 h-12 rounded-full bg-blue-100 text-blue-600 dark:bg-blue-900/50 dark:text-blue-400">
                            <font-awesome-icon v-if="item.icon" :icon="formatIconForVue(item.icon)" class="w-5 h-5" />
                            <font-awesome-icon v-else :icon="['fas', 'icons']" class="w-5 h-5" />
                        </div>
                    </div>

                    <!-- Título y Descripción -->
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                            {{ item.title || 'Sin título' }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                            {{ item.description || 'Sin descripción' }}
                        </p>
                    </div>

                    <!-- Botones -->
                    <div class="shrink-0 flex items-center gap-2">
                        <button
                            type="button"
                            @click="openEditModal(index)"
                            class="inline-flex items-center p-2 text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                            title="Editar"
                        >
                            <IconEdit class="w-4 h-4" />
                        </button>
                        <button
                            type="button"
                            @click="confirmDelete(index)"
                            class="inline-flex items-center p-2 text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                            title="Eliminar"
                        >
                            <IconTrash class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Save Button -->
        <div class="flex justify-end">
            <button
                @click="saveResultsSettings"
                :disabled="formResults.processing"
                :class="{ 'opacity-25': formResults.processing }"
                class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <IconLoader v-if="formResults.processing" class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" />
                <IconSave v-else class="mr-2" />
                {{ formResults.processing ? 'Guardando...' : 'Guardar Sección Resultados del Programa' }}
            </button>
        </div>
    </div>

    <!-- Modal -->
    <ModalLarge :show="showModal" :onClose="closeModal" :icon="'/img/editar-codigo.png'">
        <template #title>
            {{ editingIndex >= 0 ? 'Editar Resultado' : 'Agregar Resultado' }}
        </template>
        <template #message>
            Completa los datos del resultado del programa
        </template>
        <template #content>
            <div class="space-y-4">
                <!-- Icono -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Icono
                    </label>
                    <div class="flex items-center gap-2 mt-1">
                        <div class="flex items-center justify-center w-12 h-12 rounded-full bg-blue-100 text-blue-600 dark:bg-blue-900/50 dark:text-blue-400 shrink-0">
                            <font-awesome-icon v-if="modalItem.icon" :icon="formatIconForVue(modalItem.icon)" class="w-5 h-5" />
                            <font-awesome-icon v-else :icon="['fas', 'icons']" class="w-5 h-5" />
                        </div>
                        <select
                            v-model="modalItem.icon"
                            class="form-select flex-1"
                        >
                            <option value="">Seleccionar icono</option>
                            <optgroup v-for="(icons, category) in iconCategories" :key="category" :label="categoryLabels[category]">
                                <option v-for="icon in icons" :key="icon.value" :value="icon.value">
                                    {{ icon.label }}
                                </option>
                            </optgroup>
                        </select>
                    </div>
                </div>

                <!-- Título -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Título
                    </label>
                    <input
                        type="text"
                        v-model="modalItem.title"
                        class="form-input mt-1"
                        placeholder="Título del resultado"
                    >
                </div>

                <!-- Descripción -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Descripción
                    </label>
                    <textarea
                        v-model="modalItem.description"
                        rows="3"
                        class="form-textarea mt-1"
                        placeholder="Descripción del resultado"
                    ></textarea>
                </div>
            </div>
        </template>
        <template #buttons>
            <button
                type="button"
                @click="saveItemFromModal"
                class="btn btn-primary text-xs uppercase"
            >
                Hecho
            </button>
        </template>
    </ModalLarge>
</template>
