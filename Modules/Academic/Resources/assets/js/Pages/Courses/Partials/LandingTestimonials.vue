<script setup>

import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';
import IconLoader from '@/Components/vristo/icon/icon-loader.vue';
import InputError from '@/Components/InputError.vue';
import IconSave from "@/Components/vristo/icon/icon-save.vue";
import ModalLarge from '@/Components/ModalLarge.vue';
import IconSearch from "@/Components/vristo/icon/icon-search.vue";
import IconPlus from '@/Components/vristo/icon/icon-plus.vue';
import IconEdit from '@/Components/vristo/icon/icon-edit.vue';
import IconTrash from '@/Components/vristo/icon/icon-trash.vue';
import IconUser from '@/Components/vristo/icon/icon-user.vue';
import IconInbox from '@/Components/vristo/icon/icon-inbox.vue';
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
    name: initialData?.name || 'Testimonios',
    title: initialData?.title || '',
    description: initialData?.description || '',
    items: initialData?.items || []
});

const showModal = ref(false);
const editingIndex = ref(-1);
const modalItem = ref({ person_id: '', description: '', person: null });

const searchQuery = ref('');
const searchResults = ref([]);
const isSearching = ref(false);
const selectedPersonData = ref(null);

const searchPeople = async () => {
    if (!searchQuery.value || searchQuery.value.length < 2) {
        Swal.fire({
            icon: 'warning',
            title: 'Atención',
            text: 'Ingresa al menos 2 caracteres para buscar',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
        return;
    }

    isSearching.value = true;

    try {
        const response = await axios.post(route('search_person_number'), {
            full_name: searchQuery.value,
            searchBy: 2
        });

        const data = response.data;

        if (data.status) {
            if (Array.isArray(data.person) && data.person.length > 1) {
                searchResults.value = data.person.map(p => ({
                    id: p.id,
                    image: p.image,
                    formatted_name: p.name || p.full_name,
                    ocupacion: p.ocupacion,
                }));
            } else if (Array.isArray(data.person) && data.person.length === 1) {
                const person = data.person[0];
                selectPerson({
                    id: person.id,
                    image: person.image,
                    formatted_name: person.name || person.full_name,
                    ocupacion: person.ocupacion,
                });
            } else {
                searchResults.value = [];
                Swal.fire({
                    icon: 'info',
                    title: 'Sin resultados',
                    text: 'No se encontró ninguna persona con ese nombre',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
            }
        } else {
            searchResults.value = [];
            Swal.fire({
                icon: 'info',
                title: 'Sin resultados',
                text: data.message || 'No se encontró ninguna persona con ese nombre',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
        }
    } catch (error) {

        searchResults.value = [];
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Hubo un error al buscar la persona',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    }

    isSearching.value = false;
};

const selectPerson = (person) => {
    modalItem.value.person_id = person.id;
    modalItem.value.person = {
        id: person.id,
        image: person.image,
        formatted_name: person.formatted_name,
        ocupacion: person.ocupacion,
    };
    selectedPersonData.value = person;
    searchResults.value = [];
    isSearching.value = false;
    searchQuery.value = '';
};

const clearSearch = () => {
    searchQuery.value = '';
    searchResults.value = [];
    isSearching.value = false;
    selectedPersonData.value = null;
};

const openAddModal = () => {
    modalItem.value = { person_id: '', description: '', person: null };
    editingIndex.value = -1;
    clearSearch();
    showModal.value = true;
};

const openEditModal = (index) => {
    modalItem.value = { ...formTestimonials.items[index] };
    editingIndex.value = index;
    selectedPersonData.value = modalItem.value.person;
    searchQuery.value = '';
    searchResults.value = [];
    isSearching.value = false;
    showModal.value = true;
};

const saveItemFromModal = () => {
    if (!modalItem.value.person_id) {
        Swal.fire({
            icon: 'warning',
            title: 'Error',
            text: 'Debe seleccionar una persona',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
        return;
    }

    const person = props.people.find(p => p.id === parseInt(modalItem.value.person_id));

    if (person) {
        modalItem.value.person = {
            id: person.id,
            image: person.image,
            formatted_name: person.full_name,
            ocupacion: person.ocupacion,
        };
    }

    if (editingIndex.value >= 0) {
        formTestimonials.items[editingIndex.value] = { ...modalItem.value };
    } else {
        formTestimonials.items.push({ ...modalItem.value });
    }
    showModal.value = false;
};

const closeModal = () => {
    showModal.value = false;
    clearSearch();
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
    formTestimonials.put(route('aca_courses_landing_update_testimonials', props.course.id),{
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
            <i class="fa fa-comments mr-2 text-blue-600"></i>
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
                <IconInbox class="w-12 h-12 text-gray-300 dark:text-gray-500 mb-2" />
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    No hay testimonios agregados. Haz clic en "Agregar Testimonio" para crear el primero.
                </p>
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
                            v-if="item.person?.image"
                            :src="'/storage/' + item.person.image"
                            :alt="item.person?.formatted_name"
                            class="w-12 h-12 rounded-full object-cover"
                        >
                        <div v-else class="flex items-center justify-center w-12 h-12 rounded-full bg-gray-200 dark:bg-gray-600">
                            <IconUser class="w-6 h-6 text-gray-400" />
                        </div>
                    </div>

                    <!-- Nombre y Testimonio -->
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                            {{ item.person?.formatted_name || 'Sin nombre' }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                            {{ item.person?.ocupacion || 'Sin ocupación' }}
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
    <ModalLarge :show="showModal" :onClose="closeModal" :icon="null">
        <template #title>
            {{ editingIndex >= 0 ? 'Editar Testimonio' : 'Agregar Testimonio' }}
        </template>
        <template #message>
            Completa el testimonio del estudiante
        </template>
        <template #content>
            <div class="space-y-4">
                <!-- Buscador de Persona -->
                <div class="flex gap-2 mt-1">
                    <input
                        type="text"
                        v-model="searchQuery"
                        @keyup.enter="searchPeople"
                        class="form-input flex-1"
                        placeholder="Escribe el nombre a buscar..."
                        :disabled="isSearching"
                    >
                    <button
                        type="button"
                        @click="searchPeople"
                        :disabled="isSearching"
                        class="btn btn-secondary text-xs uppercase disabled:opacity-50"
                    >
                        <IconLoader v-if="isSearching" class="animate-spin mr-2 h-4 w-4" />
                        <IconSearch v-else class="w-4 h-4 mr-2" />
                        {{ isSearching ? 'Buscando...' : 'Buscar' }}
                    </button>
                </div>
                <div class="w-full relative">
                    <!-- Resultados de Búsqueda - Lista Inline -->
                    <div v-if="searchResults.length > 0" class="w-full absolute z-40 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm max-h-[40vh] overflow-y-auto">
                        <div
                            v-for="person in searchResults"
                            :key="person.id"
                            @click="selectPerson(person)"
                            class="flex items-center gap-3 p-3 cursor-pointer hover:bg-blue-50 dark:hover:bg-blue-900/20 border-b border-gray-100 dark:border-gray-700 last:border-b-0"
                        >
                            <img
                                v-if="person.image"
                                :src="'/storage/' + person.image"
                                :alt="person.formatted_name"
                                class="w-10 h-10 rounded-full object-cover"
                            >
                            <div v-else class="flex items-center justify-center w-10 h-10 rounded-full bg-gray-200 dark:bg-gray-600">
                                <IconUser class="w-6 h-6 text-gray-400" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                    {{ person.formatted_name }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                    {{ person.ocupacion || 'Sin ocupación' }}
                                </p>
                            </div>
                            <IconPlus class="w-4 h-4 text-green-600" />
                        </div>
                    </div>
                </div>
                    <!-- Persona Seleccionada -->
                <div v-if="selectedPersonData" class="flex items-center gap-3 p-3 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                    <img
                        v-if="selectedPersonData.image"
                        :src="'/storage/' + selectedPersonData.image"
                        :alt="selectedPersonData.formatted_name"
                        class="w-12 h-12 rounded-full object-cover"
                    >
                    <div v-else class="flex items-center justify-center w-12 h-12 rounded-full bg-gray-200 dark:bg-gray-600">
                        <IconUser class="w-6 h-6 text-gray-400" />
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ selectedPersonData.formatted_name }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ selectedPersonData.ocupacion || 'Sin ocupación' }}
                        </p>
                    </div>
                    <button
                        type="button"
                        @click="clearSearch"
                        class="text-red-600 hover:text-red-800"
                    >
                        <IconX class="w-4 h-4" />
                    </button>
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
                Hecho
            </button>
        </template>
    </ModalLarge>
</template>
