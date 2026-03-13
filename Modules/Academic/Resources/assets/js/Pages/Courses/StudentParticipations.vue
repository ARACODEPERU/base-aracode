<script setup>
    import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import { Link, useForm, router } from '@inertiajs/vue3';
    import { ref, computed, watch } from 'vue';
    import axios from 'axios';
    import Swal2 from 'sweetalert2';
    import { faSearch, faComments, faCheck, faUserGraduate, faFilter, faSave } from "@fortawesome/free-solid-svg-icons";
    import SpinnerLoading from '@/Components/SpinnerLoading.vue';
    import ModalLarge from '@/Components/ModalLarge.vue';
    import Multiselect from '@suadelabs/vue3-multiselect';
    import '@suadelabs/vue3-multiselect/dist/vue3-multiselect.css';
import IconLoader from "@/Components/vristo/icon/icon-loader.vue";

    const props = defineProps({
        courses: {
            type: Object,
            default: () => ({}),
        },
    });

    // Datos reactivos
    const selectedCourse = ref(null);
    const selectedModule = ref(null);
    const selectedTheme = ref(null);
    const selectedContent = ref(null);
    const searchStudent = ref('');
    const loading = ref(false);
    const searched = ref(false);
    const studentsList = ref([]);

    // Obtener módulos del curso seleccionado
    const modules = computed(() => {
        return selectedCourse.value?.modules || [];
    });

    // Obtener temas del módulo seleccionado
    const themes = computed(() => {
        if (!selectedModule.value) return [];
        const module = modules.value.find(m => m.id === selectedModule.value);
        return module ? (module.themes || []) : [];
    });

    // Obtener contenidos del tema seleccionado
    const contents = computed(() => {
        if (!selectedTheme.value) return [];
        const theme = themes.value.find(t => t.id === selectedTheme.value);
        return theme ? (theme.contents || []) : [];
    });

    // Estudiantes filtrados según búsqueda por nombre
    const filteredStudents = computed(() => {
        if (!searchStudent.value) return studentsList.value;

        const search = searchStudent.value.toLowerCase();
        return studentsList.value.filter(s =>
            s.name.toLowerCase().includes(search)
        );
    });

    // Funciones helper para v-model
    const getScore = (student) => {
        const score = student.participation?.participation_score;
        return score !== null && score !== undefined ? score : '';
    };

    const setScore = (student, value) => {
        if (!student.participation) {
            student.participation = { id: null, participation_score: null, teacher_comment: '' };
        }
        // Convertir a número o null si está vacío
        const numValue = value === '' || value === null ? null : Number(value);
        student.participation.participation_score = numValue;
    };

    const getComment = (student) => {
        return student.participation?.teacher_comment ?? '';
    };

    const setComment = (student, value) => {
        if (!student.participation) {
            student.participation = { id: null, participation_score: null, teacher_comment: '' };
        }
        student.participation.teacher_comment = value;
    };

    // Buscar estudiantes
    const searchStudents = async () => {
        if (!selectedCourse.value) {
            Swal2.fire({
                title: 'Advertencia',
                text: 'Por favor seleccione un curso',
                icon: 'warning',
                padding: '2em',
            });
            return;
        }

        if (!selectedModule.value) {
            Swal2.fire({
                title: 'Advertencia',
                text: 'Por favor seleccione un módulo',
                icon: 'warning',
                padding: '2em',
            });
            return;
        }

        loading.value = true;
        searched.value = true;

        try {
            const response = await axios.put(route('aca_course_participation_search', selectedCourse.value.id), {
                module_id: selectedModule.value,
                theme_id: selectedTheme.value,
                content_id: selectedContent.value,
            });

            studentsList.value = (response.data.students || []).map(student => ({
                ...student,
                participation: student.participation || {
                    id: null,
                    participation_score: null,
                    teacher_comment: ''
                }
            }));

            if (studentsList.value.length === 0) {
                Swal2.fire({
                    title: 'Información',
                    text: 'No se encontraron estudiantes registrados en este curso',
                    icon: 'info',
                    padding: '2em',
                });
            }
        } catch (error) {
            console.error('Error searching students:', error);
            Swal2.fire({
                title: 'Error',
                text: 'Ocurrió un error al buscar estudiantes',
                icon: 'error',
                padding: '2em',
            });
        } finally {
            loading.value = false;
        }
    };

    // Resetear al cambiar filtros
    watch([selectedModule, selectedTheme, selectedContent], () => {
        searchStudent.value = '';
        studentsList.value = [];
        searched.value = false;
    });

    // Resetear todo cuando cambia el curso
    watch(selectedCourse, () => {
        selectedModule.value = null;
        selectedTheme.value = null;
        selectedContent.value = null;
        searchStudent.value = '';
        studentsList.value = [];
        searched.value = false;
    });

    // Resetear temas y contenidos cuando cambia el módulo
    watch(selectedModule, (newVal) => {
        selectedTheme.value = null;
        selectedContent.value = null;
    });

    watch(selectedTheme, (newVal) => {
        selectedContent.value = null;
    });

    // Mostrar comentarios
    const showCommentsModal = ref(false);
    const commentsStudentId = ref(null);
    const commentsStudentName = ref('');
    const studentComments = ref([]);
    const loadingComments = ref(false);

    const openCommentsModal = async (student) => {
        commentsStudentId.value = student.id;
        commentsStudentName.value = student.name;
        loadingComments.value = true;
        showCommentsModal.value = true;

        try {
            const response = await axios.post(route('aca_theme_comments_by_student'),{
                    student_id: student.id,
                    module_id: selectedModule.value,
                    theme_id: selectedTheme.value,
                });
            studentComments.value = response.data.comments || [];
        } catch (error) {
            console.error('Error loading comments:', error);
            studentComments.value = [];
        } finally {
            loadingComments.value = false;
        }
    };

    const closeCommentsModal = () => {
        showCommentsModal.value = false;
        commentsStudentId.value = null;
        commentsStudentName.value = '';
        studentComments.value = [];
    };

    // Formulario de participación
    const participationForm = useForm({
        student_id: null,
        course_id: selectedCourse.value?.id,
        module_id: null,
        theme_id: null,
        content_id: null,
        participation_score: null,
        teacher_comment: '',
    });

    // Estado de carga por estudiante
    const savingIds = ref([]);
    const savingAll = ref(false);

    // Guardar participación
    const saveParticipation = async (student, showConfirm = true) => {
        // Si showConfirm es true, mostrar alerta de confirmación
        if (showConfirm) {
            const result = await Swal2.fire({
                title: '¿Guardar cambios?',
                text: 'Estos cambios se verán reflejados en Gestión de Calificaciones. Si ya ha guardado calificaciones en esa sección, ya no se podra modificar.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, guardar',
                cancelButtonText: 'Cancelar',
                padding: '2em',
                customClass: 'sweet-alerts',
            });

            if (!result.isConfirmed) return;
        }

        // Agregar a la lista de loading
        savingIds.value.push(student.id);

        const formData = {
            student_id: student.id,
            course_id: selectedCourse.value.id,
            module_id: selectedModule.value,
            theme_id: selectedTheme.value,
            content_id: selectedContent.value,
            participation_score: student.participation?.participation_score ?? null,
            teacher_comment: student.participation?.teacher_comment ?? '',
        };

        try {
            const response = await axios.post(route('aca_course_participation_store'), formData);

            // Actualizar la lista local
            const index = studentsList.value.findIndex(s => s.id === student.id);
            if (index !== -1) {
                studentsList.value[index].participation = {
                    id: response.data.participation.id,
                    participation_score: formData.participation_score,
                    teacher_comment: formData.teacher_comment,
                };
            }

            Swal2.fire({
                title: '¡Éxito!',
                text: response.data.message,
                icon: 'success',
                padding: '2em',
            });

        } catch (error) {
            console.error('Error saving participation:', error);
            Swal2.fire({
                title: 'Error',
                text: 'Ocurrió un error al guardar la participación',
                icon: 'error',
                padding: '2em',
            });
        } finally {
            // Remover de la lista de loading
            savingIds.value = savingIds.value.filter(id => id !== student.id);
        }
    };

    // Verificar si un estudiante está siendo guardado
    const isSaving = (studentId) => {
        return savingIds.value.includes(studentId);
    };

    // Guardar todas las participaciones
    const saveAllParticipations = async () => {
        const result = await Swal2.fire({
            title: '¿Guardar todos los cambios?',
            text: 'Estos cambios se verán reflejados en Gestión de Calificaciones. Si ya ha guardado calificaciones en esa sección, ya no se podra modificar.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, guardar todo',
            cancelButtonText: 'Cancelar',
            padding: '2em',
            customClass: 'sweet-alerts',
        });

        if (!result.isConfirmed) return;

        savingAll.value = true;

        try {
            const participationsData = filteredStudents.value.map(student => ({
                student_id: student.id,
                course_id: selectedCourse.value.id,
                module_id: selectedModule.value,
                theme_id: selectedTheme.value,
                content_id: selectedContent.value,
                participation_score: student.participation?.participation_score ?? null,
                teacher_comment: student.participation?.teacher_comment ?? '',
            }));

            const response = await axios.post(route('aca_course_participation_store_all'), {
                participations: participationsData
            });

            Swal2.fire({
                title: '¡Éxito!',
                text: response.data.message,
                icon: 'success',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
        } catch (error) {
            console.error('Error saving all participations:', error);
            Swal2.fire({
                title: 'Error',
                text: error.response?.data?.message || 'Ocurrió un error al guardar las participaciones',
                icon: 'error',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
        } finally {
            savingAll.value = false;
        }
    };
</script>

<template>
    <AppLayout title="Calificación de participacioness">
        <Navigation :routeModule="route('aca_dashboard')" :titleModule="'Académico'"
            :data="[
                {title: 'Calificación de participaciones'},
            ]"
        />

        <div class="pt-5 space-y-6">
            <!-- Filtros -->
            <div class="panel p-0">
                <div class="p-5 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">
                        Participaciones - {{ selectedCourse?.description || 'Seleccione un curso' }}
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 ">
                        <!-- Curso -->
                        <div class="md:col-span-2">
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                Curso
                            </label>
                            <multiselect
                                v-model="selectedCourse"
                                :options="courses"
                                :searchable="true"
                                placeholder="Buscar curso..."
                                selected-label="seleccionado"
                                select-label="Elegir"
                                deselect-label="Quitar"
                                label="description"
                                track-by="id"
                                class="custom-multiselect"
                            ></multiselect>
                        </div>

                        <!-- Módulo -->
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                Módulo
                            </label>
                            <select v-model="selectedModule" :disabled="!selectedCourse" class="form-select w-full">
                                <option :value="null">Seleccionar módulo</option>
                                <option v-for="mod in modules" :key="mod.id" :value="mod.id">
                                    {{ mod.description }}
                                </option>
                            </select>
                        </div>

                        <!-- Tema -->
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                Tema
                            </label>
                            <select v-model="selectedTheme" :disabled="!selectedModule" class="form-select w-full">
                                <option :value="null">Seleccionar tema</option>
                                <option v-for="theme in themes" :key="theme.id" :value="theme.id">
                                    {{ theme.description }}
                                </option>
                            </select>
                        </div>

                        <!-- Contenido -->
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                Contenido
                            </label>
                            <select v-model="selectedContent" :disabled="!selectedTheme" class="form-select w-full">
                                <option :value="null">Seleccionar contenido (opcional)</option>
                                <option v-for="content in contents" :key="content.id" :value="content.id">
                                    {{ content.description }}
                                </option>
                            </select>
                        </div>

                        <!-- Buscar -->
                        <div class="flex items-end">
                            <button
                                @click="searchStudents"
                                :disabled="!selectedModule || loading"
                                class="btn btn-primary w-full"
                                :class="{ 'opacity-50': !selectedModule || loading }"
                            >
                                <font-awesome-icon :icon="faSearch" class="w-4 h-4 mr-2" />
                                {{ loading ? 'Buscando...' : 'Buscar' }}
                            </button>
                        </div>
                    </div>
                </div>
                <!-- Resultados de búsqueda -->
                <div v-if="searched && !loading">
                    <div class="flex items-center justify-between p-4">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                            Lista de Estudiantes ({{ filteredStudents.length }})
                        </h3>

                        <!-- Buscador y Botón Guardar Todo -->
                        <div class="flex items-center gap-2">
                            <div class="relative">
                                <input
                                    type="text"
                                    v-model="searchStudent"
                                    placeholder="Buscar por nombre..."
                                    class="form-input pl-10 w-64 text-sm"
                                />
                                <div class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                                    <font-awesome-icon :icon="faSearch" class="w-4 h-4" />
                                </div>
                            </div>
                            <button
                                @click="saveAllParticipations"
                                :disabled="savingAll || filteredStudents.length === 0"
                                class="btn btn-success"
                                :class="{ 'opacity-50': savingAll || filteredStudents.length === 0 }"
                            >
                                <IconLoader v-if="savingAll" class="animate-spin w-4 h-4 mr-2" />
                                <font-awesome-icon v-else :icon="faSave" class="w-4 h-4 mr-2" />
                                {{ savingAll ? 'Guardando...' : 'Guardar Todo' }}
                            </button>
                        </div>
                    </div>
                    <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-400">
                        <p class="text-sm text-yellow-800 dark:text-yellow-200">
                            <strong>Nota por asistencia:</strong> Si un estudiante no tiene participación registrada pero sí asistencia, se mostrará una nota de 12 puntos.
                        </p>
                        <p class="text-sm text-yellow-800 dark:text-yellow-200 mt-1">
                            <strong>Importante:</strong> Es necesario guardar los cambios para que se vean reflejados en Gestión de Calificaciones.
                        </p>
                    </div>
                    <!-- Tabla de estudiantes -->
                    <div v-if="searched && filteredStudents && filteredStudents.length > 0" class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th class="px-6 py-3">Estudiante</th>
                                    <th class="px-6 py-3">Comentarios</th>
                                    <th class="px-6 py-3">Nota (0-20)</th>
                                    <th class="px-6 py-3">Observación</th>
                                    <th class="px-6 py-3 w-20">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="student in filteredStudents" :key="student.id"
                                    class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                                        <div class="flex items-center gap-2">
                                            <font-awesome-icon :icon="faUserGraduate" class="w-4 h-4 text-gray-400" />
                                            <div class="flex flex-col">
                                                <span>{{ student.name }}</span>
                                                <span>{{ student.number }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex justify-center w-full">
                                            <button
                                                @click="openCommentsModal(student)"
                                                class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 flex items-center gap-1"
                                            >
                                                <font-awesome-icon :icon="faComments" class="w-4 h-4" />
                                                <span class="text-xs">Ver</span>
                                            </button>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <input
                                            type="number"
                                            :value="getScore(student)"
                                            @input="setScore(student, $event.target.value)"
                                            min="0"
                                            max="20"
                                            step="0.5"
                                            class="form-input w-20 text-center"
                                            placeholder="0"
                                        />
                                    </td>
                                    <td class="px-6 py-4">
                                        <textarea
                                            :value="getComment(student)"
                                            @input="setComment(student, $event.target.value)"
                                            rows="2"
                                            class="form-textarea w-full"
                                            placeholder="Observación..."
                                        ></textarea>
                                    </td>
                                    <td class="px-6 py-4">
                                        <button
                                            @click="saveParticipation(student)"
                                            class="btn btn-success btn-sm"
                                            title="Guardar"
                                            :disabled="isSaving(student.id)"
                                        >
                                            <IconLoader v-if="isSaving(student.id)" class="w-4 h-4" />
                                            <font-awesome-icon v-else :icon="faCheck" class="w-4 h-4" />
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- Sin estudiantes -->
                    <div v-else class="text-center py-8">
                        <font-awesome-icon :icon="faUserGraduate" class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
                        <p class="text-gray-500 dark:text-gray-400">
                            No se encontraron estudiantes
                        </p>
                    </div>
                    <!-- aaca un mensaje relacionado a la vista o una leyenda explicando el interfaz-->

                    <!-- Leyenda explicativa -->
                    <div class="px-4 py-3 bg-gray-50 dark:bg-gray-800 rounded-2xl">
                        <div class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                            <!-- <p><strong>Nota por asistencia:</strong> Si un estudiante no tiene participación registrada pero sí asistencia, se mostrará una nota de 12 puntos.</p> -->
                            <p><strong>Guardar:</strong> Haga click en el botón ✓ para guardar la participación de cada estudiante.</p>
                            <p><strong>Rango:</strong> Las notas van de 0 a 20 puntos.</p>
                        </div>
                    </div>
                </div>

                <!-- Loading -->
                <div v-if="loading" class="text-center py-12">
                    <SpinnerLoading :display="true" />
                    <p class="text-gray-500 dark:text-gray-400 mt-4">Buscando estudiantes...</p>
                </div>

                <!-- Instrucciones iniciales -->
                <div v-if="!searched && !loading" class="text-center py-12">
                    <font-awesome-icon :icon="faFilter" class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
                    <p class="text-gray-500 dark:text-gray-400">
                        Seleccione un curso y luego un módulo, luego haga click en "Buscar" para ver los estudiantes
                    </p>
                </div>
            </div>
        </div>
        <!-- Modal de Comentarios -->
        <ModalLarge
            :show="showCommentsModal"
            :onClose="closeCommentsModal"
            icon="/img/comentario-positivo.png"
        >
            <template #title>
                Comentarios de {{ commentsStudentName }}
            </template>
            <template #content>
                <div v-if="loadingComments" class="flex justify-center py-8">
                    <div class="text-center">
                        <SpinnerLoading :display="true"/>
                        <p class="text-gray-500 dark:text-gray-400 mt-2">Cargando comentarios...</p>
                    </div>
                </div>
                <div v-else-if="studentComments.length === 0" class="text-gray-500 dark:text-gray-400 py-4 text-center">
                    Este estudiante no tiene comentarios en este módulo/tema
                </div>
                <div v-else class="space-y-3 max-h-80 overflow-y-auto">
                    <div v-for="(comment, index) in studentComments" :key="index"
                        class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <p class="text-sm text-gray-900 dark:text-white">
                            {{ comment.description }}
                        </p>
                        <p class="text-xs text-gray-500 mt-2">
                            {{ comment.created_at ? new Date(comment.created_at).toLocaleString() : '' }}
                        </p>
                    </div>
                </div>
            </template>
            <template #buttons>

            </template>
        </ModalLarge>
    </AppLayout>
</template>
