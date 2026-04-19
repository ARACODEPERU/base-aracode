<script setup>

import { ref, computed, watch } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import IconLoader from '@/Components/vristo/icon/icon-loader.vue';
import InputError from '@/Components/InputError.vue';
import IconSave from "@/Components/vristo/icon/icon-save.vue";
import ModalLarge from '@/Components/ModalLarge.vue';
import IconTrash from '@/Components/vristo/icon/icon-trash.vue';
import IconEdit from '@/Components/vristo/icon/icon-edit.vue';
import IconPlus from '@/Components/vristo/icon/icon-plus.vue';

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

const getInitialFaqData = () => {
    const data = props.landing.faq_section;
    if (data && typeof data === 'string') {
        try {
            return JSON.parse(data);
        } catch (e) {
            return null;
        }
    }
    return data || null;
};

const initialData = getInitialFaqData();

const getDefaultItems = () => {
    if (initialData?.items && initialData.items.length > 0) {
        return initialData.items.map(item => ({
            question: item.question || '',
            answer: item.answer || '',
            sort: item.sort || 1,
            visible: item.visible !== undefined ? item.visible : true,
        }));
    }
    return [];
};

const formFaq = useForm({
    name: initialData?.name || 'Preguntas Frecuentes',
    title: initialData?.title || '',
    description: initialData?.description || '',
    items: getDefaultItems(),
});

const showModal = ref(false);
const editingIndex = ref(null);

const modalTitle = computed(() => editingIndex.value !== null ? 'Editar Pregunta' : 'Nueva Pregunta');

const getNewItem = () => ({
    question: '',
    answer: '',
    sort: formFaq.items.length + 1,
    visible: true,
});

const openAddModal = () => {
    editingIndex.value = null;
    formModal.question = '';
    formModal.answer = '';
    formModal.sort = formFaq.items.length + 1;
    formModal.visible = true;
    showModal.value = true;
};

const openEditModal = (index) => {
    editingIndex.value = index;
    const item = formFaq.items[index];
    formModal.question = item.question;
    formModal.answer = item.answer;
    formModal.sort = item.sort || index + 1;
    formModal.visible = item.visible !== false;
    showModal.value = true;
};

const formModal = useForm({
    question: '',
    answer: '',
    sort: 1,
    visible: true,
});

const saveFaqItem = () => {
    if (editingIndex.value !== null) {
        formFaq.items[editingIndex.value] = {
            question: formModal.question,
            answer: formModal.answer,
            sort: formModal.sort,
            visible: formModal.visible,
        };
    } else {
        formFaq.items.push({
            question: formModal.question,
            answer: formModal.answer,
            sort: formModal.sort,
            visible: formModal.visible,
        });
    }
    showModal.value = false;
};

const removeFaqItem = (index) => {
    Swal.fire({
        title: '¿Eliminar pregunta?',
        text: 'Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc2626',
    }).then((result) => {
        if (result.isConfirmed) {
            formFaq.items.splice(index, 1);
        }
    });
};

const saveFaqSettings = () => {
    formFaq.put(route('aca_courses_landing_update_faq', props.course.id), {
        errorBag: 'saveFaqSettings',
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: 'Éxito',
                text: 'Sección Preguntas Frecuentes guardada correctamente',
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
            <i class="fa fa-question-circle mr-2 text-blue-600"></i>
            Sección Preguntas Frecuentes
        </h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Gestiona las preguntas frecuentes de tu curso.
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
                        v-model="formFaq.name"
                        class="form-input"
                        placeholder="Preguntas Frecuentes"
                    >
                    <InputError :message="formFaq.errors.name" class="mt-2" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Título
                    </label>
                    <input
                        type="text"
                        v-model="formFaq.title"
                        class="form-input"
                        placeholder="Título de la sección"
                    >
                    <InputError :message="formFaq.errors.title" class="mt-2" />
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Descripción
                    </label>
                    <textarea
                        v-model="formFaq.description"
                        rows="3"
                        class="form-textarea"
                        placeholder="Descripción de la sección..."
                    ></textarea>
                    <InputError :message="formFaq.errors.description" class="mt-2" />
                </div>
            </div>
        </div>

        <!-- Lista de Preguntas -->
        <div class="">
            <div class="flex items-center justify-between mb-4">
                <h4 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider flex items-center">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-green-200 text-green-600 dark:bg-green-900/50 dark:text-green-400 mr-2">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                            <path fill="currentColor" d="M256 48C141.31 48 48 141.31 48 256s93.31 208 208 208 208-93.31 208-208S370.69 48 256 48zm75.31 260.69a16 16 0 1 1-22.62 22.62L256 278.63l-52.69 52.68a16 16 0 1 1-22.62-22.62L233.37 256l-52.68-52.69a16 16 0 1 1 22.62-22.62L256 233.37l52.69-52.68a16 16 0 1 1 22.62 22.62L278.63 256z"/>
                        </svg>
                    </span>
                    Preguntas
                </h4>
                <button
                    type="button"
                    @click="openAddModal"
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                    <IconPlus class="w-4 h-4 mr-2" />
                    Agregar Pregunta
                </button>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-16">
                                #
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Pregunta
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Respuesta
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-24">
                                Visible
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-32">
                                Acciones
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-if="formFaq.items && formFaq.items.length > 0" v-for="(item, index) in formFaq.items" :key="index">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                {{ index + 1 }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                {{ item.question || '(sin pregunta)' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                {{ item.answer ? (item.answer.length > 80 ? item.answer.substring(0, 80) + '...' : item.answer) : '(sin respuesta)' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span 
                                    :class="item.visible ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'"
                                    class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full"
                                >
                                    {{ item.visible ? 'Sí' : 'No' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button
                                    type="button"
                                    @click="openEditModal(index)"
                                    class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 mr-3"
                                >
                                    <IconEdit class="w-4 h-4" />
                                </button>
                                <button
                                    type="button"
                                    @click="removeFaqItem(index)"
                                    class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                >
                                    <IconTrash class="w-4 h-4" />
                                </button>
                            </td>
                        </tr>
                        <tr v-else>
                            <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                No hay preguntas agregadas. Haga clic en "Agregar Pregunta" para crear una.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Save Button -->
        <div class="flex justify-end">
            <button
                @click="saveFaqSettings"
                :disabled="formFaq.processing"
                :class="{ 'opacity-25': formFaq.processing }"
                class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <IconLoader v-if="formFaq.processing" class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" />
                <IconSave v-else class="mr-2" />
                {{ formFaq.processing ? 'Guardando...' : 'Guardar Preguntas Frecuentes' }}
            </button>
        </div>
    </div>

    <!-- Modal Large para Crear/Editar Pregunta -->
    <ModalLarge :show="showModal" :onClose="() => showModal = false" :icon="null">
        <template #title>
            {{ modalTitle }}
        </template>
        <template #message>
            Completa los datos de la pregunta frecuente
        </template>
        <template #content>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Pregunta
                    </label>
                    <textarea
                        v-model="formModal.question"
                        rows="2"
                        class="form-textarea mt-1"
                        placeholder="Escribe la pregunta..."
                    ></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Respuesta
                    </label>
                    <textarea
                        v-model="formModal.answer"
                        rows="4"
                        class="form-textarea mt-1"
                        placeholder="Escribe la respuesta..."
                    ></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Orden (N°)
                        </label>
                        <input
                            type="number"
                            v-model="formModal.sort"
                            class="form-input mt-1"
                            min="1"
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Visible
                        </label>
                        <div class="mt-2">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="formModal.visible" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                                <span class="ml-3 text-sm font-medium" :class="formModal.visible ? 'text-green-600' : 'text-gray-500'">
                                    {{ formModal.visible ? 'Visible' : 'Oculto' }}
                                </span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </template>
        <template #buttons>
            <button
                type="button"
                @click="saveFaqItem"
                class="px-4 py-2 border border-transparent rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700"
            >
                {{ editingIndex !== null ? 'Actualizar' : 'Agregar' }}
            </button>
        </template>
    </ModalLarge>
</template>