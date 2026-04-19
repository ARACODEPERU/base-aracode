<script setup>

import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import IconLoader from '@/Components/vristo/icon/icon-loader.vue';
import InputError from '@/Components/InputError.vue';
import IconSave from "@/Components/vristo/icon/icon-save.vue";
import ModalLarge from '@/Components/ModalLarge.vue';
import IconPlus from '@/Components/vristo/icon/icon-plus.vue';
import IconEdit from '@/Components/vristo/icon/icon-edit.vue';
import IconTrash from '@/Components/vristo/icon/icon-trash.vue';

const props = defineProps({
    course: {
        type: Object,
        default: () => ({}),
    },
    landing: {
        type: Object,
        default: () => ({}),
    },
});

const getInitialStudyPlanData = () => {
    const data = props.landing.study_plan_section;
    if (data && typeof data === 'string') {
        try {
            return JSON.parse(data);
        } catch (e) {
            return null;
        }
    }
    return data || null;
};

const initialData = getInitialStudyPlanData();

const formStudyPlan = useForm({
    course_id: props.course.id,
    name: initialData?.name || 'Plan de Estudios',
    title: initialData?.title || '',
    description: initialData?.description || '',
    image: null,
    image_preview: initialData?.image ? '/storage/' + initialData.image : null,
    items: initialData?.items || []
});

const showModal = ref(false);
const editingIndex = ref(-1);
const modalItem = ref({ number: 1, title: '', description: '' });

const handleImageChange = (event) => {
    const file = event.target.files[0];
    if (!file) {
        formStudyPlan.image = null;
        formStudyPlan.image_preview = null;
        return;
    }

    if (!file.type.startsWith('image/')) {
        Swal.fire({
            icon: 'warning',
            title: 'Tipo de archivo no válido',
            text: 'Por favor selecciona una imagen',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
        event.target.value = null;
        return;
    }

    if (file.size > 5 * 1024 * 1024) {
        Swal.fire({
            icon: 'warning',
            title: 'Archivo muy grande',
            text: 'La imagen no debe superar 5MB',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
        event.target.value = null;
        return;
    }

    formStudyPlan.image = file;
    formStudyPlan.image_preview = URL.createObjectURL(file);
};

const removeImage = () => {
    formStudyPlan.image = null;
    formStudyPlan.image_preview = null;
};

const openAddModal = () => {
    const nextNumber = formStudyPlan.items.length > 0
        ? Math.max(...formStudyPlan.items.map(i => i.number)) + 1
        : 1;
    modalItem.value = { number: nextNumber, title: '', description: '' };
    editingIndex.value = -1;
    showModal.value = true;
};

const openEditModal = (index) => {
    modalItem.value = { ...formStudyPlan.items[index] };
    editingIndex.value = index;
    showModal.value = true;
};

const saveItemFromModal = () => {
    if (!modalItem.value.title) {
        Swal.fire({
            icon: 'warning',
            title: 'Error',
            text: 'El título es obligatorio',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
        return;
    }

    if (editingIndex.value >= 0) {
        formStudyPlan.items[editingIndex.value] = { ...modalItem.value };
    } else {
        formStudyPlan.items.push({ ...modalItem.value });
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
            formStudyPlan.items.splice(index, 1);
            Swal.fire({
                icon: 'success',
                title: 'Eliminado',
                text: 'El módulo ha sido eliminado',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
        }
    });
};

const saveStudyPlanSettings = () => {
    formStudyPlan.post(route('aca_courses_landing_update_study_plan'),{
        forceFormData: true,
        errorBag: 'saveStudyPlanSettings',
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: 'Éxito',
                text: 'Sección Plan de Estudios guardada correctamente',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
        },
    });
};

</script>
<template>
    <div class="mb-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
            <i class="fa fa-book-open mr-2 text-blue-600"></i>
            Sección Plan de Estudios
        </h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Configura el temario o plan de estudios del curso.
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
                        v-model="formStudyPlan.name"
                        class="form-input"
                        placeholder="Plan de Estudios"
                    >
                    <InputError :message="formStudyPlan.errors.name" class="mt-2" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Título
                    </label>
                    <input
                        type="text"
                        v-model="formStudyPlan.title"
                        class="form-input"
                        placeholder="Título de la sección"
                    >
                    <InputError :message="formStudyPlan.errors.title" class="mt-2" />
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Descripción
                    </label>
                    <textarea
                        v-model="formStudyPlan.description"
                        rows="3"
                        class="form-textarea"
                        placeholder="Descripción de la sección..."
                    ></textarea>
                    <InputError :message="formStudyPlan.errors.description" class="mt-2" />
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Imagen del Temario
                    </label>

<!-- Drop Zone -->
                    <div
                        class="relative border-2 border-dashed rounded-lg p-4 mt-1 transition-colors border-gray-300 dark:border-gray-600"
                    >
                        <input
                            type="file"
                            accept="image/*"
                            @change="handleImageChange"
                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                        >

                        <!-- Preview -->
                        <div v-if="formStudyPlan.image_preview" class="relative">
                            <img
                                :src="formStudyPlan.image_preview"
                                alt="Preview"
                                class="max-h-48 mx-auto rounded-lg"
                            >
                            <button
                                type="button"
                                @click.stop="removeImage"
                                class="absolute top-2 right-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600"
                            >
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                    <path fill="currentColor" d="M256 48C141.31 48 48 141.31 48 256s93.31 208 208 208 208-93.31 208-208S370.69 48 256 48zm75.31 260.69a16 16 0 1 1-22.62 22.62L256 278.63l-52.69 52.68a16 16 0 1 1-22.62-22.62L233.37 256l-52.68-52.69a16 16 0 1 1 22.62-22.62L256 233.37l52.69-52.68a16 16 0 1 1 22.62 22.62L278.63 256z"/>
                                </svg>
                            </button>
                        </div>

                        <!-- Empty State -->
                        <div v-else class="flex flex-col items-center py-4">
                            <svg class="w-10 h-10 text-gray-400 mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <p class="text-sm text-gray-500 dark:text-gray-400 text-center">
                                Arrastra una imagen aquí o <span class="text-blue-600">haz clic para seleccionar</span>
                            </p>
                            <p class="text-xs text-gray-400 mt-1">
                                PNG, JPG, JPEG (máx 5MB)
                            </p>
                        </div>
                    </div>

                    <InputError :message="formStudyPlan.errors.image" class="mt-2" />
                </div>
            </div>
        </div>

        <!-- Items Lista -->
        <div class="">
            <div class="flex items-center justify-between mb-4">
                <h4 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider flex items-center">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-green-200 text-green-600 dark:bg-green-900/50 dark:text-green-400 mr-2">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                            <path fill="currentColor" d="M64 64C28.7 64 0 92.7 0 128L0 384c0 35.3 28.7 64 64 64l384 0c35.3 0 64-28.7 64-64l0-256c0-35.3-28.7-64-64-64L64 64zm96 256a32 32 0 1 1 -64 0 32 32 0 1 1 64 0zm-32-96a32 32 0 1 1 0-64 32 32 0 1 1 0 64zm120-56l144 0c13.3 0 24 10.7 24 24s-10.7 24-24 24l-144 0c-13.3 0-24-10.7-24-24s10.7-24 24-24zm0 128l144 0c13.3 0 24 10.7 24 24s-10.7 24-24 24l-144 0c-13.3 0-24-10.7-24-24s10.7-24 24-24z"/>
                        </svg>
                    </span>
                    Módulos / Temas
                </h4>
                <button
                    type="button"
                    @click="openAddModal"
                    class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                    <i class="fa fa-plus mr-2"></i>
                    Agregar Módulo
                </button>
            </div>

            <!-- Lista de Módulos -->
            <div v-if="formStudyPlan.items.length === 0" class="p-8 text-center bg-gray-50 dark:bg-gray-700/50 rounded-lg border-2 border-dashed border-gray-200 dark:border-gray-600">
                <i class="fa fa-inbox text-4xl text-gray-300 dark:text-gray-500 mb-2"></i>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    No hay módulos agregados. Haz clic en "Agregar Módulo" para crear el primero.
                </p>
            </div>

            <div v-else class="space-y-2">
                <div
                    v-for="(item, index) in formStudyPlan.items"
                    :key="index"
                    class="flex items-start gap-4 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-600"
                >
                    <!-- Número -->
                    <div class="shrink-0 flex items-center justify-center w-10 h-10 rounded-full bg-blue-100 text-blue-600 dark:bg-blue-900/50 dark:text-blue-400 font-bold">
                        {{ item.number }}
                    </div>

                    <!-- Título y Descripción -->
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ item.title || 'Sin título' }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 line-clamp-2">
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
            <InputError :message="formStudyPlan.errors.items" class="mt-2" />
        </div>

        <!-- Save Button -->
        <div class="flex justify-end">
            <button
                @click="saveStudyPlanSettings"
                :disabled="formStudyPlan.processing"
                :class="{ 'opacity-25': formStudyPlan.processing }"
                class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <IconLoader v-if="formStudyPlan.processing" class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" />
                <IconSave v-else class="mr-2" />
                {{ formStudyPlan.processing ? 'Guardando...' : 'Guardar Sección Plan de Estudios' }}
            </button>
        </div>
    </div>

    <!-- Modal -->
    <ModalLarge :show="showModal" :onClose="closeModal" :icon="null">
        <template #title>
            {{ editingIndex >= 0 ? 'Editar Módulo' : 'Agregar Módulo' }}
        </template>
        <template #message>
            Completa los datos del módulo
        </template>
        <template #content>
            <div class="space-y-4">
                <!-- Número -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Número / Orden
                    </label>
                    <input
                        type="number"
                        v-model="modalItem.number"
                        class="form-input mt-1"
                        placeholder="1"
                    >
                </div>

                <!-- Título -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Título del Módulo
                    </label>
                    <input
                        type="text"
                        v-model="modalItem.title"
                        class="form-input mt-1"
                        placeholder="Título del módulo..."
                    >
                </div>

                <!-- Descripción -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Descripción
                    </label>
                    <textarea
                        v-model="modalItem.description"
                        rows="4"
                        class="form-textarea mt-1"
                        placeholder="Descripción del módulo..."
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
