<script setup>
import { ref, computed, watch } from 'vue';
import ModalLarge from '@/Components/ModalLarge.vue';
import IconLoader from '@/Components/vristo/icon/icon-loader.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    courseId: {
        type: Number,
        required: true,
    },
    section: {
        type: String,
        required: true,
    },
    sectionLabel: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['close', 'copied']);

const courses = ref([]);
const loading = ref(false);
const fetching = ref(false);
const searchQuery = ref('');
const selectedCourseId = ref(null);

const loadCourses = async () => {
    loading.value = true;
    try {
        const response = await window.axios.get(
            route('aca_courses_landing_with_landing', props.courseId)
        );
        courses.value = response.data;
    } catch (error) {
        console.error('Error al cargar cursos:', error);
    } finally {
        loading.value = false;
    }
};

watch(
    () => props.show,
    (newVal) => {
        if (newVal) {
            selectedCourseId.value = null;
            searchQuery.value = '';
            loadCourses();
        }
    }
);

const filteredCourses = computed(() => {
    if (!searchQuery.value) return courses.value;
    const query = searchQuery.value.toLowerCase();
    return courses.value.filter((c) =>
        c.description.toLowerCase().includes(query)
    );
});

const selectCourse = (id) => {
    selectedCourseId.value = id;
};

const copyData = async () => {
    if (!selectedCourseId.value) return;
    fetching.value = true;
    try {
        const response = await window.axios.get(
            route('aca_courses_landing_get_section', {
                courseId: selectedCourseId.value,
                section: props.section,
            })
        );
        emit('copied', response.data);
        emit('close');
    } catch (error) {
        console.error('Error al copiar datos:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudieron copiar los datos. Intenta de nuevo.',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    } finally {
        fetching.value = false;
    }
};

const closeModal = () => {
    emit('close');
};
</script>

<template>
    <ModalLarge :show="show" :onClose="closeModal" :icon="null">
        <template #title>
            Copiar {{ sectionLabel || section }} de otra Landing
        </template>
        <template #message>
            Selecciona un curso del cual deseas copiar la información de
            "{{ sectionLabel || section }}". Los datos se cargarán en el formulario actual.
        </template>
        <template #content>
            <!-- Buscador -->
            <div class="relative mb-4">
                <input
                    type="text"
                    v-model="searchQuery"
                    placeholder="Buscar curso..."
                    class="form-input pl-10"
                />
                <svg
                    class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2"
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <circle cx="11" cy="11" r="8" />
                    <path d="m21 21-4.35-4.35" />
                </svg>
            </div>

            <!-- Lista de cursos -->
            <div v-if="loading" class="flex justify-center py-8">
                <IconLoader class="animate-spin h-8 w-8 text-blue-600" />
            </div>

            <div
                v-else-if="courses.length === 0"
                class="text-center py-8 text-gray-500 dark:text-gray-400"
            >
                <svg
                    class="w-12 h-12 mx-auto mb-3 text-gray-300 dark:text-gray-600"
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.5"
                >
                    <path
                        d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm0 18a8 8 0 1 1 8-8 8 8 0 0 1-8 8z"
                    />
                    <path d="M12 6v6l4 2" />
                </svg>
                <p>No hay otros cursos con landing disponible.</p>
            </div>

            <div v-else class="max-h-80 overflow-y-auto space-y-2">
                <div
                    v-for="course in filteredCourses"
                    :key="course.id"
                    @click="selectCourse(course.id)"
                    class="flex items-center gap-3 p-3 rounded-lg border-2 cursor-pointer transition-all duration-200"
                    :class="
                        selectedCourseId === course.id
                            ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                            : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:border-blue-300 dark:hover:border-blue-700'
                    "
                >
                    <div
                        class="flex items-center justify-center w-5 h-5 rounded-full border-2 shrink-0"
                        :class="
                            selectedCourseId === course.id
                                ? 'border-blue-500 bg-blue-500'
                                : 'border-gray-300 dark:border-gray-600'
                        "
                    >
                        <div
                            v-if="selectedCourseId === course.id"
                            class="w-2 h-2 rounded-full bg-white"
                        ></div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                            {{ course.description }}
                        </p>
                    </div>
                </div>

                <p
                    v-if="filteredCourses.length === 0 && searchQuery"
                    class="text-center py-4 text-sm text-gray-500"
                >
                    No se encontraron cursos que coincidan con "{{ searchQuery }}"
                </p>
            </div>
        </template>
        <template #buttons>
            <button
                type="button"
                @click="copyData"
                :disabled="!selectedCourseId || fetching"
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <IconLoader
                    v-if="fetching"
                    class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
                />
                <svg
                    v-else
                    class="w-4 h-4 mr-2"
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2" />
                    <path
                        d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"
                    />
                </svg>
                {{ fetching ? 'Copiando...' : 'Copiar información' }}
            </button>
        </template>
    </ModalLarge>
</template>
