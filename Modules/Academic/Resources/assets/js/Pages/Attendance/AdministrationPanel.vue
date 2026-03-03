<script setup>
    import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import ModalStatus from "@/Components/ModalStatus.vue";
    import { ref, watch, computed, onUnmounted } from 'vue';
    import axios from 'axios';
    import Swal2 from 'sweetalert2';

    const props = defineProps({
        courses: {
            type: Array,
            default: () => []
        }
    });

    const selectedCourse = ref(null);
    const selectedModule = ref(null);
    const selectedTheme = ref(null);
    const selectedContent = ref(null);

    const modules = ref([]);
    const themes = ref([]);
    const contents = ref([]);
    const students = ref([]);
    const contentInfo = ref(null);
    const courseInfo = ref(null);

    const loadingModules = ref(false);
    const loadingThemes = ref(false);
    const loadingContents = ref(false);
    const loadingStudents = ref(false);

    const canQuery = computed(() => {
        return selectedCourse.value && selectedModule.value && selectedTheme.value && selectedContent.value;
    });

    const presentCount = computed(() => {
        return students.value.filter(s => s.is_present).length;
    });

    const absentCount = computed(() => {
        return students.value.filter(s => !s.is_present).length;
    });

    const searchQuery = ref('');

    const filteredStudents = computed(() => {
        if (!searchQuery.value.trim()) {
            return students.value;
        }
        const query = searchQuery.value.toLowerCase().trim();
        return students.value.filter(student => 
            student.name.toLowerCase().includes(query)
        );
    });

    const displayModalExportStatus = ref(false);
    const currentJobId = ref(null);
    const mensajeExporting = ref([]);
    let pollingInterval = null;

    watch(selectedCourse, async (newVal) => {
        if (newVal) {
            selectedModule.value = null;
            selectedTheme.value = null;
            selectedContent.value = null;
            themes.value = [];
            contents.value = [];
            students.value = [];
            contentInfo.value = null;
            courseInfo.value = null;

            loadingModules.value = true;
            try {
                const response = await axios.get(route('aca_attendance_modules', newVal));
                modules.value = response.data;
            } catch (error) {
                console.error('Error loading modules:', error);
                modules.value = [];
            } finally {
                loadingModules.value = false;
            }
        }
    });

    watch(selectedModule, async (newVal) => {
        if (newVal) {
            selectedTheme.value = null;
            selectedContent.value = null;
            contents.value = [];
            students.value = [];
            contentInfo.value = null;

            loadingThemes.value = true;
            try {
                const response = await axios.get(route('aca_attendance_themes', newVal));
                themes.value = response.data;
            } catch (error) {
                console.error('Error loading themes:', error);
                themes.value = [];
            } finally {
                loadingThemes.value = false;
            }
        }
    });

    watch(selectedTheme, async (newVal) => {
        if (newVal) {
            selectedContent.value = null;
            students.value = [];
            contentInfo.value = null;

            loadingContents.value = true;
            try {
                const response = await axios.get(route('aca_attendance_contents', newVal));
                contents.value = response.data;
            } catch (error) {
                console.error('Error loading contents:', error);
                contents.value = [];
            } finally {
                loadingContents.value = false;
            }
        }
    });

    const queryStudents = async () => {
        if (!canQuery.value) return;

        loadingStudents.value = true;
        try {
            const response = await axios.post(route('aca_attendance_students'), {
                content_id: selectedContent.value
            });
            students.value = response.data.students;
            contentInfo.value = response.data.content;
            courseInfo.value = response.data.course;
        } catch (error) {
            console.error('Error loading students:', error);
            showAlert('Error al cargar la lista de estudiantes.', 'error');
        } finally {
            loadingStudents.value = false;
        }
    };

    const toggleAttendance = async (student) => {
        if (student.is_present) {
            const result = await Swal2.fire({
                title: 'Quitar asistencia',
                text: `¿Estás seguro de quitar la asistencia a ${student.name}?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Sí, quitar',
                cancelButtonText: 'Cancelar'
            });

            if (!result.isConfirmed) return;

            try {
                await axios.post(route('aca_attendance_update'), {
                    student_id: student.student_id,
                    content_id: selectedContent.value,
                    course_id: courseInfo.value.id,
                    action: 'remove'
                });
                student.is_present = false;
                student.registered_at = null;
                student.observation = null;
                student.user_edit_id = null;
                student.user_edit_name = null;
                showAlert('Asistencia eliminada correctamente.', 'success');
            } catch (error) {
                showAlert(error.response?.data?.message || 'Error al eliminar asistencia.', 'error');
            }
        } else {
            try {
                await axios.post(route('aca_attendance_update'), {
                    student_id: student.student_id,
                    content_id: selectedContent.value,
                    course_id: courseInfo.value.id,
                    action: 'add',
                    observation: student.observation || null
                });
                student.is_present = true;
                student.registered_at = new Date().toLocaleString('es-PE');
                student.user_edit_name = 'Tú';
                showAlert('Asistencia registrada correctamente.', 'success');
            } catch (error) {
                showAlert(error.response?.data?.message || 'Error al registrar asistencia.', 'error');
            }
        }
    };

    const saveObservation = async (student) => {
        if (!student.is_present) return;

        try {
            await axios.post(route('aca_attendance_observation'), {
                student_id: student.student_id,
                content_id: selectedContent.value,
                observation: student.observation || null
            });
            showAlert('Observación guardada.', 'success');
        } catch (error) {
            showAlert('Error al guardar observación.', 'error');
        }
    };

    const generateExcelAttendance = async () => {
        if (!selectedContent.value || students.value.length === 0) return;

        currentJobId.value = null;
        mensajeExporting.value = [];
        displayModalExportStatus.value = true;

        try {
            const response = await axios.post(route('aca_attendance_export'), {
                content_id: selectedContent.value
            });

            currentJobId.value = response.data.job_id;
            mensajeExporting.value.push({ success: true, label: 'Exportación iniciada.', path: null });
            startPolling();

        } catch (error) {
            mensajeExporting.value.push({
                success: false,
                label: 'Error al iniciar la exportación: ' + (error.response?.data?.message || 'Error desconocido'),
                path: null
            });
        }
    };

    const startPolling = () => {
        if (pollingInterval) {
            clearInterval(pollingInterval);
        }

        pollingInterval = setInterval(async () => {
            if (!currentJobId.value) {
                mensajeExporting.value.push({ success: false, label: 'No hay Job ID para hacer polling.', path: null });
                clearInterval(pollingInterval);
                pollingInterval = null;
                return;
            }

            try {
                const response = await axios.get(route('aca_attendance_export_status', currentJobId.value));
                const jobStatus = response.data;

                if (jobStatus.status === 'completed') {
                    mensajeExporting.value.push({
                        success: true,
                        label: 'Exportación completada. Archivo listo para descargar.',
                        path: jobStatus.download_url
                    });
                    clearInterval(pollingInterval);
                    pollingInterval = null;
                } else if (jobStatus.status === 'failed') {
                    mensajeExporting.value.push({
                        success: false,
                        label: 'Exportación fallida: ' + (jobStatus.error_message || 'Error desconocido'),
                        path: null
                    });
                    clearInterval(pollingInterval);
                    pollingInterval = null;
                } else {
                    mensajeExporting.value.push({
                        success: false,
                        label: 'Exportación en curso. Estado: ' + jobStatus.status,
                        path: null
                    });
                }
            } catch (error) {
                mensajeExporting.value.push({
                    success: false,
                    label: 'Error al verificar el estado de la exportación.',
                    path: null
                });
                clearInterval(pollingInterval);
                pollingInterval = null;
            }
        }, 3000);
    };

    const closeModalExportStatus = () => {
        displayModalExportStatus.value = false;
    };

    onUnmounted(() => {
        if (pollingInterval) {
            clearInterval(pollingInterval);
        }
    });

    const showAlert = (msg, icon = 'success') => {
        Swal2.fire({
            icon: icon,
            title: msg,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
        });
    };
</script>

<template>
    <AppLayout title="Asistencia de alumnos">
        <Navigation
            :routeModule="route('aca_dashboard')"
            :titleModule="'Académico'"
            :data="[
                { title: 'Asistencia de alumnos' }
            ]"
        />

        <div class="mt-5">
            <div class="panel p-0">
                <div class="p-5 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">
                        Panel de Administración de Asistencia
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Curso</label>
                            <select v-model="selectedCourse" class="form-select w-full">
                                <option :value="null">Seleccionar curso</option>
                                <option v-for="course in courses" :key="course.id" :value="course.id">
                                    {{ course.description }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Módulo
                                <svg v-if="loadingModules" class="animate-spin w-4 h-4 inline ml-2 text-primary" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                            </label>
                            <select v-model="selectedModule" class="form-select w-full" :disabled="!selectedCourse || loadingModules">
                                <option :value="null">Seleccionar módulo</option>
                                <option v-for="module in modules" :key="module.id" :value="module.id">
                                    {{ module.description }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Tema
                                <svg v-if="loadingThemes" class="animate-spin w-4 h-4 inline ml-2 text-primary" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                            </label>
                            <select v-model="selectedTheme" class="form-select w-full" :disabled="!selectedModule || loadingThemes">
                                <option :value="null">Seleccionar tema</option>
                                <option v-for="theme in themes" :key="theme.id" :value="theme.id">
                                    {{ theme.description }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Clase (Videoconferencia)
                                <svg v-if="loadingContents" class="animate-spin w-4 h-4 inline ml-2 text-primary" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                            </label>
                            <select v-model="selectedContent" class="form-select w-full" :disabled="!selectedTheme || loadingContents">
                                <option :value="null">Seleccionar clase</option>
                                <option v-for="content in contents" :key="content.id" :value="content.id">
                                    {{ content.description }}
                                </option>
                            </select>
                        </div>

                        <div class="flex items-end">
                            <button
                                @click="queryStudents"
                                :disabled="!canQuery || loadingStudents"
                                class="btn btn-primary w-full"
                            >
                                <svg v-if="loadingStudents" class="animate-spin w-4 h-4 mr-2" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                                Consultar
                            </button>
                        </div>
                    </div>
                </div>

                <div v-if="contentInfo && students.length > 0" class="p-5">
                    <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                        <div class="flex flex-wrap gap-4">
                            <div class="bg-blue-50 dark:bg-blue-900/20 px-4 py-2 rounded-lg">
                                <span class="text-sm text-gray-500 dark:text-gray-400">Curso:</span>
                                <span class="ml-2 font-medium text-gray-800 dark:text-white">{{ courseInfo?.description }}</span>
                            </div>
                            <div class="bg-purple-50 dark:bg-purple-900/20 px-4 py-2 rounded-lg">
                                <span class="text-sm text-gray-500 dark:text-gray-400">Clase:</span>
                                <span class="ml-2 font-medium text-gray-800 dark:text-white">{{ contentInfo?.description }}</span>
                            </div>
                            <div class="bg-green-50 dark:bg-green-900/20 px-4 py-2 rounded-lg">
                                <span class="text-sm text-gray-500 dark:text-gray-400">Presentes:</span>
                                <span class="ml-2 font-bold text-green-600 dark:text-green-400">{{ presentCount }}</span>
                            </div>
                            <div class="bg-red-50 dark:bg-red-900/20 px-4 py-2 rounded-lg">
                                <span class="text-sm text-gray-500 dark:text-gray-400">Ausentes:</span>
                                <span class="ml-2 font-bold text-red-600 dark:text-red-400">{{ absentCount }}</span>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-4">
                            <div class="relative">
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    class="form-input pr-10"
                                    placeholder="Buscar por nombre..."
                                    @keyup.enter
                                />
                                <svg v-if="searchQuery" @click="searchQuery = ''" class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 cursor-pointer hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </div>
                            <button
                                @click="generateExcelAttendance"
                                class="btn btn-warning"
                            >
                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20M10,19L12,15H9V10H15V15L13,19H10Z"/>
                                </svg>
                                Generar Excel
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table-hover">
                            <thead>
                                <tr>
                                    <th class="w-12">#</th>
                                    <th>Estudiante</th>
                                    <th class="w-28">DNI</th>
                                    <th class="w-28 text-center">Asistencia</th>
                                    <th class="w-36 text-center">Acciones</th>
                                    <th class="w-48">Observación</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(student, index) in filteredStudents" :key="student.student_id">
                                    <td>{{ index + 1 }}</td>
                                    <td>
                                        <div class="font-medium">{{ student.name }}</div>
                                        <div v-if="student.user_edit_name" class="text-xs text-gray-500 dark:text-gray-400">
                                            Modificado por: {{ student.user_edit_name }}
                                        </div>
                                    </td>
                                    <td class="font-mono">{{ student.dni }}</td>
                                    <td class="text-center">
                                        <span
                                            v-if="student.is_present"
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400"
                                        >
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                            </svg>
                                            Presente
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300"
                                        >
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                            </svg>
                                            Ausente
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="flex justify-center w-full">
                                            <button
                                                v-if="student.is_present"
                                                @click="toggleAttendance(student)"
                                                class="btn btn-sm btn-danger"
                                                v-tippy="'Quitar asistencia'"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                            </button>
                                            <button
                                                v-else
                                                @click="toggleAttendance(student)"
                                                class="btn btn-sm btn-success"
                                                v-tippy="'Marcar asistencia'"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flex gap-1">
                                            <input
                                                v-model="student.observation"
                                                type="text"
                                                class="form-input py-1 text-sm"
                                                placeholder="Observación..."
                                                :disabled="!student.is_present"
                                            />
                                            <button
                                                v-if="student.is_present"
                                                @click="saveObservation(student)"
                                                class="btn btn-sm btn-outline-primary"
                                                v-tippy="'Guardar'"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div v-else class="p-8">
                    <div class="p-10 text-center">
                        <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                        <p class="text-gray-500 dark:text-gray-400">
                            Selecciona los filtros y haz clic en <strong>Consultar</strong> para ver la lista de estudiantes.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <ModalStatus :show="displayModalExportStatus" :onClose="closeModalExportStatus">
            <template #title>Estado de Exportación</template>
            <template #content>
                <div v-if="mensajeExporting.length == 0">
                    <span class="mr-2">Iniciando</span>
                    <span class="animate-[ping_1.5s_0.5s_ease-in-out_infinite]">.</span>
                    <span class="animate-[ping_1.5s_0.7s_ease-in-out_infinite]">.</span>
                    <span class="animate-[ping_1.5s_0.9s_ease-in-out_infinite]">.</span>
                </div>
                <div v-for="(msg, inx) in mensajeExporting" :key="inx" class="space-y-4">
                    <div v-if="msg.success" class="text-[#9CA3AF]">{{ msg.label }}</div>
                    <div v-if="!msg.success" class="text-[#FFD60A]">{{ msg.label }}</div>
                    <div v-if="msg.path" class="flex justify-center">
                        <a :href="msg.path" type="button" class="btn btn-primary text-xs btn-sm uppercase" target="_blank">Descargar</a>
                    </div>
                    <div v-if="mensajeExporting.length > 0 && !msg.path && mensajeExporting[mensajeExporting.length - 1] === msg && (msg.label.includes('curso') || msg.label.includes('processing'))">
                        <span class="mr-2">Cargando</span>
                        <span class="animate-[ping_1.5s_0.5s_ease-in-out_infinite]">.</span>
                        <span class="animate-[ping_1.5s_0.7s_ease-in-out_infinite]">.</span>
                        <span class="animate-[ping_1.5s_0.9s_ease-in-out_infinite]">.</span>
                    </div>
                </div>
            </template>
        </ModalStatus>
    </AppLayout>
</template>
