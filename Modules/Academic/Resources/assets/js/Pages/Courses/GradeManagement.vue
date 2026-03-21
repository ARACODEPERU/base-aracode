<script setup>
    import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import { Link, router } from '@inertiajs/vue3';
    import { ref, computed } from 'vue';
    import axios from 'axios';
    import Swal2 from 'sweetalert2';
    import { faSearch, faFilter, faSave, faCheck } from "@fortawesome/free-solid-svg-icons";
    import SpinnerLoading from '@/Components/SpinnerLoading.vue';
    import IconChecks from "@/Components/vristo/icon/icon-checks.vue";
    import IconLoader from "@/Components/vristo/icon/icon-loader.vue";

    const props = defineProps({
        courses: {
            type: Array,
            default: () => [],
        },
    });

    // Datos reactivos
    const selectedCourse = ref(null);
    const dateStart = ref('');
    const dateEnd = ref('');
    const loading = ref(false);
    const searched = ref(false);
    const courseData = ref(null);
    const modulesData = ref([]);
    const studentsData = ref([]);
    const saving = ref({});
    const savingAll = ref(false);

    // Buscar estudiantes
    const searchStudents = async () => {
        if (!selectedCourse.value) {
            Swal2.fire({
                title: 'Advertencia',
                text: 'Por favor seleccione un curso',
                icon: 'warning',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
            return;
        }

        loading.value = true;
        searched.value = true;

        try {
            const response = await axios.post(route('aca_grade_management_search'), {
                course_id: selectedCourse.value,
                date_start: dateStart.value,
                date_end: dateEnd.value,
            });

            courseData.value = response.data.course;
            modulesData.value = response.data.modules || [];
            studentsData.value = response.data.students || [];

            // Redondear valores al cargar
            const roundGradeValue = (value) => {
                return value !== null && value !== '' ? Math.round(Number(value)) : null;
            };

            // Agregar propiedad editing a cada módulo y redondear valores
            studentsData.value.forEach(student => {
                student.modules.forEach(module => {
                    module.editing = false;
                    module.exam_score = roundGradeValue(module.exam_score);
                    module.participation_score = roundGradeValue(module.participation_score);
                });
            });

            if (studentsData.value.length === 0) {
                Swal2.fire({
                    title: 'Información',
                    text: 'No se encontraron estudiantes registrados en este curso',
                    icon: 'info',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
            }
        } catch (error) {
            console.error('Error searching students:', error);
            Swal2.fire({
                title: 'Error',
                text: 'Ocurrió un error al buscar estudiantes',
                icon: 'error',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
        } finally {
            loading.value = false;
        }
    };

    // Calcular promedio de un módulo (se recalcula cuando cambian las notas)
    const calculateModuleAverage = (module) => {
        const exam = module.exam_score !== null && module.exam_score !== '' ? Number(module.exam_score) : 0;
        const participation = module.participation_score !== null && module.participation_score !== '' ? Number(module.participation_score) : 0;

        const average = (exam * 0.6) + (participation * 0.4);
        return average > 0 ? Math.round(average) : null;
    };

    // Calcular promedio final del estudiante (se recalcula cuando cambian las notas)
    const calculateFinalAverage = (student) => {
        const averages = student.modules
            .map(m => calculateModuleAverage(m))
            .filter(a => a !== null);

        if (averages.length === 0) return null;

        const sum = averages.reduce((acc, val) => acc + Number(val), 0);
        return Math.round(sum / averages.length);
    };

    // Obtener clase de color según la nota
    const getScoreClass = (score) => {
        if (score === null || score === '') return '';
        const numScore = Number(score);
        if (numScore >= 11) return 'text-blue-600 dark:text-blue-400 font-semibold';
        return 'text-red-600 dark:text-red-400 font-semibold';
    };

    // Guardar notas de un módulo
    const saveModuleGrades = async (student, module) => {
        console.log('Guardando notas:', student.id, module.module_id, {
            exam_score: module.exam_score,
            attendance_score: module.attendance_score,
            participation_score: module.participation_score,
        });

        module.editing = false;

        Swal2.fire({
            title: '¡Éxito!',
            text: 'Notas guardadas correctamente',
            icon: 'success',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    };

    // Guardar todas las notas de un estudiante
    const saveStudentGrades = async (student) => {
        // Mostrar alerta de confirmación
        const result = await Swal2.fire({
            title: '¿Guardar calificaciones?',
            text: 'Una vez guardado, este estudiante no podrá ser modificado desde Calificación de participaciones. ¿Está seguro de guardar los cambios?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, guardar',
            cancelButtonText: 'Cancelar',
            padding: '2em',
            customClass: 'sweet-alerts',
        });

        if (!result.isConfirmed) return;

        saving.value[student.id] = true;

        const gradeData = {
            student_id: student.id,
            registration_id: student.registration_id,
            course_id: selectedCourse.value,
            final_average: calculateFinalAverage(student),
            observations: null,
            modules: student.modules.map(m => ({
                module_id: m.module_id,
                exam_score: m.exam_score,
                attendance_score: m.attendance_score,
                participation_score: m.participation_score,
                average: calculateModuleAverage(m),
            }))
        };

        try {
            const response = await axios.post(route('aca_grade_management_store'), {
                grades: [gradeData]
            });

            Swal2.fire({
                title: '¡Éxito!',
                text: response.data.message || 'Notas guardadas correctamente',
                icon: 'success',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
        } catch (error) {
            console.error('Error saving grades:', error);
            Swal2.fire({
                title: 'Error',
                text: error.response?.data?.message || 'Ocurrió un error al guardar las notas',
                icon: 'error',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
        } finally {
            saving.value[student.id] = false;
        }
    };

    // Guardar todas las notas de todos los estudiantes
    const saveAllGrades = async () => {
        // Mostrar alerta de confirmación
        const result = await Swal2.fire({
            title: '¿Guardar todas las calificaciones?',
            text: 'Una vez guardado, estos estudiantes no podrán ser modificados desde Calificación de participaciones . ¿Está seguro de guardar los cambios?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, guardar todo',
            cancelButtonText: 'Cancelar',
            padding: '2em',
            customClass: 'sweet-alerts',
        });

        if (!result.isConfirmed) return;

        savingAll.value = true;

        const gradesData = studentsData.value.map(student => ({
            student_id: student.id,
            registration_id: student.registration_id,
            course_id: selectedCourse.value,
            final_average: calculateFinalAverage(student),
            observations: null,
            modules: student.modules.map(m => ({
                module_id: m.module_id,
                exam_score: m.exam_score,
                attendance_score: m.attendance_score,
                participation_score: m.participation_score,
                average: calculateModuleAverage(m),
            }))
        }));

        try {
            const response = await axios.post(route('aca_grade_management_store'), {
                grades: gradesData
            });

            Swal2.fire({
                title: '¡Éxito!',
                text: response.data.message || 'Todas las notas guardadas correctamente',
                icon: 'success',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
        } catch (error) {
            Swal2.fire({
                title: 'Error',
                text: error.response?.data?.message || 'Ocurrió un error al guardar las notas',
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
    <AppLayout title="Gestión de Calificaciones">
        <Navigation :routeModule="route('aca_dashboard')" :titleModule="'Académico'"
            :data="[
                {title: 'Gestión de Calificaciones'}
            ]"
        />

        <div class="pt-5 space-y-6">
            <!-- Filtros -->
            <div class="panel p-0">
                <div class="p-5 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        Gestión de Calificaciones
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <!-- Curso -->
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                Curso
                            </label>
                            <select v-model="selectedCourse" class="form-select w-full">
                                <option :value="null">Seleccionar curso</option>
                                <option v-for="course in courses" :key="course.id" :value="course.id">
                                    {{ course.description }}
                                </option>
                            </select>
                        </div>

                        <!-- Fecha inicio -->
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                Fecha inicio (matrícula)
                            </label>
                            <input v-model="dateStart" type="date" class="form-input w-full" />
                        </div>

                        <!-- Fecha fin -->
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                Fecha fin (matrícula)
                            </label>
                            <input v-model="dateEnd" type="date" class="form-input w-full" />
                        </div>

                        <!-- Buscar -->
                        <div class="flex items-end">
                            <button
                                @click="searchStudents"
                                :disabled="!selectedCourse || loading"
                                class="btn btn-primary w-full"
                                :class="{ 'opacity-50': !selectedCourse || loading }"
                            >
                                <font-awesome-icon :icon="faSearch" class="w-4 h-4 mr-2" />
                                {{ loading ? 'Consultando...' : 'Consultar' }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Loading -->
                <div v-if="loading" class="panel text-center py-12">
                    <SpinnerLoading />
                    <p class="text-gray-500 dark:text-gray-400 mt-4">Consultando estudiantes...</p>
                </div>

                <!-- Mensaje instructivo -->
                <div v-else-if="!searched" class="panel text-center py-12">
                    <font-awesome-icon :icon="faFilter" class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
                    <p class="text-gray-500 dark:text-gray-400">
                        Seleccione un curso y haga click en "Consultar" para ver los registros
                    </p>
                </div>

                <!-- Tabla de estudiantes con notas por módulo -->
                <div v-else-if="!loading && searched && studentsData.length > 0">
                    <div class="p-5 flex justify-between items-center">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ courseData?.description }} - {{ studentsData.length }} estudiantes
                            </h3>
                            <div class="text-xs mt-2 flex gap-4 text-gray-600 dark:text-gray-400">
                                <span class="inline-flex items-center">
                                    <span class="w-3 h-3 rounded bg-blue-500 mr-1"></span>
                                    Aprobado (≥ 11)
                                </span>
                                <span class="inline-flex items-center">
                                    <span class="w-3 h-3 rounded bg-red-500 mr-1"></span>
                                    Desaprobado (&lt; 11)
                                </span>
                            </div>
                        </div>
                        <button
                            @click="saveAllGrades"
                                class="btn btn-success"
                                :class="{ 'opacity-25': savingAll }"
                                :disabled="savingAll"
                            >
                            <IconLoader v-if="savingAll" class="animate-spin w-4 h-4 mr-2" />
                            <font-awesome-icon v-else :icon="faSave" class="w-4 h-4 mr-2" />
                            {{ savingAll ? 'Guardando...' : 'Guardar Todos' }}
                        </button>
                    </div>
                    <!-- Mensaje explicativo -->
                    <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-400 mt-4">
                        <p class="text-sm text-yellow-800 dark:text-yellow-200">
                            <strong>Participación y Asistencia (40%):</strong> Es el promedio de todas las participaciones y notas registradas en el módulo desde la vista "Calificar Participaciones".
                        </p>
                        <p class="text-sm text-yellow-800 dark:text-yellow-200 mt-1">
                            <strong>Evaluación (60%):</strong> Es la nota del examen final por módulo que dio el alumno.
                        </p>
                        <p class="text-sm text-yellow-800 dark:text-yellow-200 mt-1">
                            <strong>Nota importante:</strong> Una vez registradas las calificaciones desde esta vista, no podrán ser modificadas desde su origen correspondiente (Calificar Participaciones o Exámenes). Solo podrán ser editadas desde esta vista por un usuario con los permisos adecuados.
                        </p>
                        <p class="text-sm text-yellow-800 dark:text-yellow-200 mt-1">
                            <strong>Certificados:</strong> Los alumnos que aprueben con promedio final mayor o igual a 11 podrán descargar su certificado.
                        </p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full mb-6">
                            <thead>
                                <!-- Fila de títulos de módulos -->
                                <tr>
                                    <th class="pl-4 pr-2 py-3 sticky left-0 z-20 min-w-[150px] border-r" rowspan="2">
                                        Nombre del Estudiante
                                    </th>
                                    <th v-for="module in modulesData" :key="module.id" :colspan="3"
                                        class="px-1 py-3 text-center border-r">
                                        {{ module.description }}
                                    </th>
                                    <th class="pl-2 pr-4 py-3 text-center sticky right-0 z-20 min-w-[100px] border-l" rowspan="2">
                                        Promedio Final
                                    </th>
                                </tr>
                                <!-- Fila de headers: E, A, P, Prom -->
                                <tr>
                                    <template v-for="module in modulesData" :key="'h-'+module.id">
                                        <th class="px-1 py-1 text-center text-[10px] border-l">A y P (40%)</th>
                                        <th class="px-1 py-1 text-center text-[10px] border-l">E (60%)</th>
                                        <th class="px-1 py-1 text-center text-[10px] font-bold border-l">Prom</th>
                                    </template>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="student in studentsData" :key="student.id">
                                    <!-- Nombre del estudiante -->
                                    <td class="pl-4 pr-2 py-3 font-medium sticky left-0 bg-white dark:bg-gray-800 z-10">
                                        <div class="flex flex-col">
                                            <span>{{ student.name }}</span>
                                            <span v-if="calculateFinalAverage(student) >= 11" class="text-[10px] text-blue-600 dark:text-blue-400 font-semibold">
                                                ✓ Aprobado
                                            </span>
                                            <span v-else-if="calculateFinalAverage(student) !== null && calculateFinalAverage(student) < 11" class="text-[10px] text-red-600 dark:text-red-400 font-semibold">
                                                ✗ Desaprobado
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Notas por módulo (horizontal) -->
                                    <template v-for="(module, index) in student.modules" :key="module.module_id">
                                        <!-- Participación -->
                                        <td class="px-1 py-2 text-center">
                                            <input
                                                v-model="module.participation_score"
                                                type="number"
                                                min="0"
                                                max="20"
                                                step="1"
                                                class="form-input text-center"
                                                :class="getScoreClass(module.participation_score)"
                                                placeholder="-"
                                            />
                                        </td>
                                        <!-- Examen -->
                                        <td class="px-1 py-2 border-l text-center">
                                            <input
                                                v-model="module.exam_score"
                                                type="number"
                                                min="0"
                                                max="20"
                                                step="1"
                                                class="form-input text-center"
                                                :class="getScoreClass(module.exam_score)"
                                                placeholder="-"
                                            />
                                        </td>
                                        <!-- Promedio del módulo -->
                                        <td class="px-1 py-2 text-center">
                                            <div class="font-bold py-2.5 px-2 rounded"
                                                :class="calculateModuleAverage(module) >= 11 ? 'grade-approved' :
                                                    calculateModuleAverage(module) !== null ? 'grade-disapproved' :
                                                    'grade-pending'">
                                                {{ calculateModuleAverage(module) ?? '-' }}
                                            </div>
                                        </td>
                                    </template>

                                    <!-- Promedio final -->
                                    <td class="pl-2 pr-4 py-3 text-center sticky right-0 bg-white dark:bg-gray-800 z-10 border-l">
                                        <div class="flex flex-col sm:flex-row justify-between items-center gap-1">
                                            <span class="text-lg font-bold px-2 py-2 rounded-lg"
                                                :class="calculateFinalAverage(student) >= 11 ? 'grade-approved' :
                                                    calculateFinalAverage(student) !== null ? 'grade-disapproved' :
                                                    'grade-pending'">
                                                {{ calculateFinalAverage(student) ?? '-' }}
                                            </span>
                                            <button
                                                @click="saveStudentGrades(student)"
                                                class="btn btn-success px-3 py-2.5"
                                                :class="{ 'opacity-25': saving[student.id] }"
                                                :disabled="saving[student.id]"
                                                title="Guardar"
                                            >
                                                <IconLoader v-if="saving[student.id]" class="animate-spin w-5 h-5" />
                                                <IconChecks v-else class="w-5 h-5" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Sin estudiantes -->
                <div v-else-if="!loading && searched && studentsData.length === 0" class="panel text-center py-12">
                    <font-awesome-icon :icon="faFilter" class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
                    <p class="text-gray-500 dark:text-gray-400">
                        No se encontraron estudiantes
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.sticky {
    position: sticky;
}

table thead th {
    vertical-align: middle;
}

table tbody tr td.sticky {
    background-color: var(--table-row-bg);
}

.dark table tbody tr td.sticky {
    background-color: var(--table-dark-row-bg);
}
</style>
