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
import IconMessage2 from '@/Components/vristo/icon/icon-message-2.vue';
import IconInbox from '@/Components/vristo/icon/icon-inbox.vue';
import IconUser from '@/Components/vristo/icon/icon-user.vue';
import IconX from '@/Components/vristo/icon/icon-x.vue';

const props = defineProps({
    course: {
        type: Object,
        default: () => ({}),
    },
    landing: {
        type: Object,
        default: () => ({}),
    },
    people: {
        type: Array,
        default: () => ([]),
    },
});

const getInitialTestimonialsData = () => {
    const data = props.landing.testimonials_section;
    if (data && typeof data === 'string') {
        try {
            return JSON.parse(data);
        } catch (e) {
            return null;
        }
    }
    return data || null;
};

const initialData = getInitialTestimonialsData();

const formTestimonials = useForm({
    course_id: props.course.id,
    name: initialData?.name || 'Testimonios',
    title: initialData?.title || '',
    description: initialData?.description || '',
    items: initialData?.items || []
});

const showModal = ref(false);
const editingIndex = ref(-1);

const modalItem = ref({
    name: '',
    presentation: '',
    description: '',
    image: null,
    image_preview: null,
    existing_image: ''
});

const handleImageChange = (event) => {
    const file = event.target.files[0];
    if (!file) {
        modalItem.value.image = null;
        modalItem.value.image_preview = null;
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

    modalItem.value.image = file;
    modalItem.value.image_preview = URL.createObjectURL(file);
};

const removeImage = () => {
    modalItem.value.image = null;
    modalItem.value.image_preview = null;
    modalItem.value.existing_image = '';
};

const openAddModal = () => {
    modalItem.value = {
        name: '',
        presentation: '',
        description: '',
        image: null,
        image_preview: null,
        existing_image: ''
    };
    editingIndex.value = -1;
    showModal.value = true;
};

const openEditModal = (index) => {
    const item = formTestimonials.items[index];
    const isFile = item.image && typeof item.image === 'object';

    modalItem.value = {
        name: item.name || '',
        presentation: item.presentation || '',
        description: item.description || '',
        image: isFile ? item.image : null,
        image_preview: !isFile && item.image ? '/storage/' + item.image : null,
        existing_image: !isFile && item.image ? item.image : ''
    };
    editingIndex.value = index;
    showModal.value = true;
};

const saveItemFromModal = () => {
    if (!modalItem.value.name) {
        Swal.fire({
            icon: 'warning',
            title: 'Error',
            text: 'El nombre es obligatorio',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
        return;
    }

    const itemData = {
        name: modalItem.value.name,
        presentation: modalItem.value.presentation,
        description: modalItem.value.description,
    };

    // Si hay una nueva imagen, agregarla al item para procesar en el backend
    if (modalItem.value.image) {
        itemData.image = modalItem.value.image;
        itemData.image_preview = modalItem.value.image_preview;
    }
    // Si hay una imagen existente y no se cambió, mantenerla
    if (!modalItem.value.image && modalItem.value.existing_image) {
        itemData.image = modalItem.value.existing_image;
        itemData.image_preview = '/storage/' + modalItem.value.existing_image;
    }

    if (editingIndex.value >= 0) {
        formTestimonials.items[editingIndex.value] = itemData;
    } else {
        formTestimonials.items.push(itemData);
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
            formTestimonials.items.splice(index, 1);
            Swal.fire({
                icon: 'success',
                title: 'Eliminado',
                text: 'El testimonio ha sido eliminado',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
        }
    });
};

const saveTestimonialsSettings = () => {
    formTestimonials.post(route('aca_courses_landing_update_testimonials'), {
        forceFormData: true,
        errorBag: 'saveTestimonialsSettings',
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: 'Éxito',
                text: 'Sección Testimonios guardada correctamente',
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
            <IconMessage2 class="w-5 h-5 text-blue-600 dark:text-blue-400 mr-2" />
            Sección Testimonios
        </h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Agrega los testimonios de tus estudiantes sobre el curso.
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
                        v-model="formTestimonials.name"
                        class="form-input"
                        placeholder="Testimonios"
                    >
                    <InputError :message="formTestimonials.errors.name" class="mt-2" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Título
                    </label>
                    <input
                        type="text"
                        v-model="formTestimonials.title"
                        class="form-input"
                        placeholder="Título de la sección"
                    >
                    <InputError :message="formTestimonials.errors.title" class="mt-2" />
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Descripción
                    </label>
                    <textarea
                        v-model="formTestimonials.description"
                        rows="3"
                        class="form-textarea"
                        placeholder="Descripción de la sección..."
                    ></textarea>
                    <InputError :message="formTestimonials.errors.description" class="mt-2" />
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
                    Testimonios
                </h4>
                <button
                    type="button"
                    @click="openAddModal"
                    class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                    <i class="fa fa-plus mr-2"></i>
                    Agregar Testimonio
                </button>
            </div>

            <!-- Lista de Testimonios -->
            <div v-if="formTestimonials.items.length === 0" class="p-8 text-center bg-gray-50 dark:bg-gray-700/50 rounded-lg border-2 border-dashed border-gray-200 dark:border-gray-600">
                <div class="flex flex-col justify-center">
                    <div class="flex items-center justify-center w-12 h-12 mx-auto bg-gray-100 dark:bg-gray-600 rounded-full mb-2">
                        <IconInbox class="w-8 h-8 text-gray-400" />
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        No hay testimonios agregados. Haz clic en "Agregar Testimonio" para crear el primero.
                    </p>
                </div>
            </div>

            <div v-else class="space-y-2">
                <div
                    v-for="(item, index) in formTestimonials.items"
                    :key="index"
                    class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-600"
                >
                    <!-- Foto -->
                    <div class="shrink-0">
                        <img
                            v-if="item.image && typeof item.image === 'string'"
                            :src="'/storage/' + item.image"
                            :alt="item.name"
                            class="w-12 h-12 rounded-full object-cover"
                        >
                        <img
                            v-else-if="item.image && typeof item.image === 'object'"
                            :src="item.image_preview"
                            :alt="item.name"
                            class="w-12 h-12 rounded-full object-cover"
                        >
                        <div v-else class="flex items-center justify-center w-12 h-12 rounded-full bg-gray-200 dark:bg-gray-600">
                            <IconUser class="w-6 h-6 text-gray-400" />
                        </div>
                    </div>

                    <!-- Nombre y Testimonio -->
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                            {{ item.name || 'Sin nombre' }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                            {{ item.presentation || 'Sin presentación' }}
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-300 mt-1 line-clamp-2">
                            {{ item.description || 'Sin testimonio' }}
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
                @click="saveTestimonialsSettings"
                :disabled="formTestimonials.processing"
                :class="{ 'opacity-25': formTestimonials.processing }"
                class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <IconLoader v-if="formTestimonials.processing" class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" />
                <IconSave v-else class="mr-2" />
                {{ formTestimonials.processing ? 'Guardando...' : 'Guardar Sección Testimonios' }}
            </button>
        </div>
    </div>

    <!-- Modal -->
    <ModalLarge :show="showModal" :onClose="closeModal" :icon="'/img/testimonial-icon.png'">
        <template #title>
            {{ editingIndex >= 0 ? 'Editar Testimonio' : 'Agregar Testimonio' }}
        </template>
        <template #message>
            Completa los datos del testimonio
        </template>
        <template #content>
            <div class="space-y-4">
                <!-- Imagen -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Fotografía
                    </label>
                    <div class="mt-1 flex items-center gap-4">
                        <div class="shrink-0">
                            <input
                                type="file"
                                accept="image/*"
                                @change="handleImageChange"
                                class="hidden"
                                id="testimonial-image"
                            >
                            <label
                                for="testimonial-image"
                                class="cursor-pointer inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
                            >
                                <i class="fa fa-upload mr-2"></i>
                                Seleccionar Imagen
                            </label>
                        </div>
                        <div v-if="modalItem.image_preview" class="relative">
                            <img
                                :src="modalItem.image_preview"
                                alt="Preview"
                                class="w-16 h-16 rounded-full object-cover"
                            >
                            <button
                                type="button"
                                @click="removeImage"
                                class="absolute -top-1 -right-1 bg-red-500 text-white rounded-full p-1 hover:bg-red-600"
                            >
                                <IconX class="w-4 h-4" />
                            </button>
                        </div>
                        <div v-else-if="modalItem.existing_image" class="relative">
                            <img
                                :src="modalItem.existing_image"
                                alt="Imagen existente"
                                class="w-16 h-16 rounded-full object-cover"
                            >
                            <button
                                type="button"
                                @click="removeImage"
                                class="absolute -top-1 -right-1 bg-red-500 text-white rounded-full p-1 hover:bg-red-600"
                            >
                                <IconX class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Tamaño máximo: 5MB. Formatos: jpg, png, jpeg, gif</p>
                </div>

                <!-- Nombre -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Nombre <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        v-model="modalItem.name"
                        class="form-input mt-1"
                        placeholder="Nombre de la persona"
                    >
                </div>

                <!-- Presentación -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Présentation
                    </label>
                    <input
                        type="text"
                        v-model="modalItem.presentation"
                        class="form-input mt-1"
                        placeholder="Ej: Estudiante del curso de Marketing Digital"
                    >
                </div>

                <!-- Testimonio -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Testimonio
                    </label>
                    <textarea
                        v-model="modalItem.description"
                        rows="6"
                        class="form-textarea mt-1"
                        placeholder="Escribe el testimonio del estudiante..."
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
                {{ editingIndex >= 0 ? 'Actualizar' : 'Agregar' }}
            </button>
        </template>
    </ModalLarge>
</template>
