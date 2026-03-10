<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import iconSearch from '@/Components/vristo/icon/icon-search.vue';
    import iconLoader from '@/Components/vristo/icon/icon-loader.vue';
    import IconX from '@/Components/vristo/icon/icon-x.vue';
    import ModalStatus from '@/Components/ModalStatus.vue';
    import { ref } from 'vue';
    import IconExcel from '@/Components/vristo/icon/icon-excel.vue';

    const props = defineProps({
        courses: {
            type: Array,
            default: () => []
        }
    });

    const form = ref({
        course_id: null,
        year: null,
        month: null
    });

    const dataStudents = ref([]);
    const dataModules = ref([]);
    const loaderSearch = ref(false);
    const searched = ref(false);

    const currentYear = new Date().getFullYear();
    const years = Array.from({ length: 5 }, (_, i) => currentYear - i);

    const months = [
        { value: 1, label: 'Enero' },
        { value: 2, label: 'Febrero' },
        { value: 3, label: 'Marzo' },
        { value: 4, label: 'Abril' },
        { value: 5, label: 'Mayo' },
        { value: 6, label: 'Junio' },
        { value: 7, label: 'Julio' },
        { value: 8, label: 'Agosto' },
        { value: 9, label: 'Septiembre' },
        { value: 10, label: 'Octubre' },
        { value: 11, label: 'Noviembre' },
        { value: 12, label: 'Diciembre' }
    ];

    const calculateModuleAverage = (module) => {
        return module.average;
    };

    const calculateFinalAverage = (student) => {
        return student.final_average;
    };

    const getScoreClass = (score) => {
        if (score === null || score === '') return '';
        const numScore = Number(score);
        if (numScore >= 11) return 'grade-approved';
        return 'grade-disapproved';
    };

    const searchPerformanceTable = () => {
        if (!form.value.course_id) {
            alert('Por favor seleccione un curso');
            return;
        }

        loaderSearch.value = true;
        searched.value = true;
        axios({
            method: 'post',
            url: route('aca_student_performance_report_table'),
            data: form.value
        }).then(function (response) {
            dataStudents.value = response.data.items || [];
            dataModules.value = response.data.modules || [];
        }).finally(() => {
            loaderSearch.value = false;
        });
    };

    // Variables para exportación
    const isExporting = ref(false);
    const displayModalExportStatus = ref(false);
    const downloadUrl = ref(null);
    const fileName = ref('');
    const errorMessage = ref(null);
    const currentJobId = ref(null);
    const mensajeExporting = ref([]);
    let pollingInterval = null;

    const closeModalExportStatus = () => {
        displayModalExportStatus.value = false;
        if (pollingInterval) {
            clearInterval(pollingInterval);
            pollingInterval = null;
        }
    };

    const startExport = async () => {
        if (!form.value.course_id) {
            alert('Por favor seleccione un curso');
            return;
        }

        isExporting.value = true;
        downloadUrl.value = null;
        fileName.value = '';
        errorMessage.value = null;
        currentJobId.value = null;
        mensajeExporting.value = [];
        displayModalExportStatus.value = true;

        try {
            const response = await axios.post(route('aca_student_performance_export'), form.value);
            currentJobId.value = response.data.job_id;
            mensajeExporting.value.push({ success: true, label: 'Exportación iniciada. Procesando...' });
            startPolling();
        } catch (error) {
            const backendMessage = error.response?.data?.message || 'Error desconocido.';
            errorMessage.value = `Hubo un problema al iniciar la exportación: ${backendMessage}`;
            isExporting.value = false;
            mensajeExporting.value.push({ success: false, label: `Error: ${errorMessage.value}` });
        }
    };

    const startPolling = () => {
        if (pollingInterval) {
            clearInterval(pollingInterval);
        }

        pollingInterval = setInterval(async () => {
            if (!currentJobId.value) {
                mensajeExporting.value.push({ success: false, label: 'No hay Job ID para hacer polling. Deteniendo.' });
                stopPolling();
                isExporting.value = false;
                return;
            }

            try {
                const response = await axios.get(route('aca_student_performance_export_status', currentJobId.value));
                const jobStatus = response.data;

                if (jobStatus.status === 'pending' || jobStatus.status === 'processing') {
                    if (mensajeExporting.value.length > 0) {
                        const lastMsg = mensajeExporting.value[mensajeExporting.value.length - 1];
                        if (lastMsg.label.startsWith('Exportación en curso') || lastMsg.label.startsWith('Exportación iniciada')) {
                            lastMsg.label = `Exportación en curso. Estado: ${jobStatus.status} (${jobStatus.progress || 0}%)`;
                        } else {
                            mensajeExporting.value.push({ success: true, label: `Exportación en curso. Estado: ${jobStatus.status} (${jobStatus.progress || 0}%)` });
                        }
                    } else {
                        mensajeExporting.value.push({ success: true, label: `Exportación en curso. Estado: ${jobStatus.status} (${jobStatus.progress || 0}%)` });
                    }
                }

                if (jobStatus.status === 'completed') {
                    downloadUrl.value = jobStatus.download_url;
                    fileName.value = jobStatus.file_name;
                    // Limpiar mensajes anteriores y solo mostrar el de éxito
                    mensajeExporting.value = [];
                    mensajeExporting.value.push({ success: true, label: '¡Exportación completada! El archivo está listo para descargar.', path: jobStatus.download_url });
                    stopPolling();
                    isExporting.value = false;
                }

                if (jobStatus.status === 'failed') {
                    errorMessage.value = jobStatus.error_message || 'Error desconocido';
                    mensajeExporting.value.push({ success: false, label: `Error: ${errorMessage.value}` });
                    stopPolling();
                    isExporting.value = false;
                }

            } catch (error) {
                mensajeExporting.value.push({ success: false, label: 'Error al verificar estado' });
                stopPolling();
                isExporting.value = false;
            }
        }, 2000);
    };

    const stopPolling = () => {
        if (pollingInterval) {
            clearInterval(pollingInterval);
            pollingInterval = null;
        }
    };

    const downloadFile = () => {
        if (downloadUrl.value) {
            window.open(downloadUrl.value, '_blank');
        }
    };
</script>

<template>
    <AppLayout title="Reportes">
        <Navigation :routeModule="route('aca_dashboard')" :titleModule="'Académico'"
            :data="[
                {route: route('aca_reports_dashboard'), title: 'Reportes'},
                {title: 'Reporte de Seguimiento de Desempeño de los Estudiantes'}
            ]"
        />
        <div class="mt-5">
            <div class="panel p-0">
                <div class="p-5 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        Filtros de Búsqueda
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                        <div class="md:col-span-2">
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                Curso
                            </label>
                            <select v-model="form.course_id" class="form-select">
                                <option :value="null">Seleccionar curso</option>
                                <option v-for="course in courses" :key="course.id" :value="course.id">
                                    {{ course.description }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                Año (matrícula)
                            </label>
                            <select v-model="form.year" class="form-select">
                                <option :value="null">Todos los años</option>
                                <option v-for="year in years" :key="year" :value="year">
                                    {{ year }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                Mes (matrícula)
                            </label>
                            <select v-model="form.month" class="form-select">
                                <option :value="null">Todos los meses</option>
                                <option v-for="month in months" :key="month.value" :value="month.value">
                                    {{ month.label }}
                                </option>
                            </select>
                        </div>
                        <div class="sm:col-span-2 flex items-end gap-4">
                            <button @click="searchPerformanceTable" :class="{ 'opacity-25': loaderSearch }" :disabled="loaderSearch" type="button" class="btn btn-primary">
                                <icon-loader v-if="loaderSearch" class="w-4 h-4 mr-2" />
                                <icon-search v-else class="w-4 h-4 mr-2" />
                                {{ loaderSearch ? 'Consultar' : 'Consultar' }}
                            </button>
                            <button @click="startExport" :class="{ 'opacity-25': isExporting }" :disabled="isExporting || !searched" type="button" class="btn btn-success">
                                <icon-loader v-if="isExporting" class="w-4 h-4 mr-2" />
                                <IconExcel v-else class="w-4 h-4 mr-2" />
                                {{ isExporting ? 'Exportando...' : 'Exportar Excel' }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Loading -->
                <div v-if="loaderSearch" class="text-center py-12">
                    <icon-loader class="w-8 h-8 animate-spin mx-auto text-primary" />
                    <p class="text-gray-500 dark:text-gray-400 mt-4">Cargando datos...</p>
                </div>

                <!-- Mensaje instructivo -->
                <div v-else-if="!searched" class="text-center py-12">
                    <div class="flex justify-center items-center flex-col">
                        <svg class="w-12 h-12 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        <p class="text-gray-500">Seleccione los filtros y haga clic en Buscar</p>
                    </div>
                </div>

                <!-- Tabla de resultados -->
                <div v-else-if="!loaderSearch && searched && dataStudents.length > 0">
                    <div class="p-5">
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

                    <div class="overflow-x-auto" style="margin-bottom: 1.5rem !important;">
                        <table class="w-full">
                            <thead>
                                <!-- Fila de títulos de módulos -->
                                <tr>
                                    <th class="px-2 py-3 sticky left-0 z-20 min-w-[50px] text-center border-r" rowspan="2">
                                        #
                                    </th>
                                    <th class="px-2 py-3 sticky left-0 z-20 min-w-[250px] border-r" colspan="2">
                                        <div class="text-[10px] uppercase text-center">Estudiantes</div>
                                    </th>
                                    <th v-for="module in dataModules" :key="module.id" :colspan="4"
                                        class="px-1 py-3 text-center border-l text-xs">
                                        {{ module.description }}
                                    </th>
                                    <th class="px-2 py-3 text-center sticky right-0 z-20 min-w-[100px] border-l" rowspan="2">
                                        <div class="text-[10px] uppercase">Promedio Final</div>
                                    </th>
                                </tr>
                                <!-- Fila de headers: P, A, E, Prom -->
                                <tr>
                                    <th class="px-2 py-1 text-left text-[10px] border-b sticky left-0 bg-gray-50 dark:bg-gray-600 z-10 border-r">
                                        Nombres y Apellidos
                                    </th>
                                    <th class="px-2 py-1 text-left text-[10px] border-b sticky left-0 bg-gray-50 dark:bg-gray-600 z-10">
                                        DNI
                                    </th>
                                    <template v-for="module in dataModules" :key="'h-'+module.id">
                                        <th class="px-1 py-1 text-center text-[10px] border-l">P</th>
                                        <th class="px-1 py-1 text-center text-[10px]">A</th>
                                        <th class="px-1 py-1 text-center text-[10px]">E</th>
                                        <th class="px-1 py-1 text-center text-[10px] font-bold">Prom</th>
                                    </template>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(student, key) in dataStudents" :key="student.id">
                                    <!-- # -->
                                    <td class="px-2 py-3 text-center">
                                        {{ key + 1 }}
                                    </td>

                                    <!-- Nombre del estudiante -->
                                    <td class="px-2 py-3 font-medium sticky left-0 bg-white dark:bg-gray-800 z-10 border-r">
                                        {{ student.student?.full_name }}
                                    </td>

                                    <!-- DNI -->
                                    <td class="px-2 py-3 font-mono text-sm sticky left-0 bg-white dark:bg-gray-800 z-10">
                                        {{ student.student?.dni }}
                                    </td>

                                    <!-- Notas por módulo -->
                                    <template v-for="(module, index) in student.modules" :key="module.module_id">
                                        <!-- Participación -->
                                        <td class="px-1 py-2 border-l text-center text-sm">
                                            {{ module.participation_score ?? '-' }}
                                        </td>
                                        <!-- Asistencia -->
                                        <td class="px-1 py-2 text-center text-sm">
                                            {{ module.attendance_score ?? '-' }}
                                        </td>
                                        <!-- Examen -->
                                        <td class="px-1 py-2 text-center text-sm">
                                            {{ module.exam_score ?? '-' }}
                                        </td>
                                        <!-- Promedio del módulo -->
                                        <td class="px-1 py-2 text-center">
                                            <div class="text-xs font-bold py-1 px-2 rounded inline-block"
                                                :class="calculateModuleAverage(module) >= 11 ? 'grade-approved' :
                                                    calculateModuleAverage(module) !== null ? 'grade-disapproved' :
                                                    'grade-pending'">
                                                {{ calculateModuleAverage(module) ?? '-' }}
                                            </div>
                                        </td>
                                    </template>

                                    <!-- Promedio final -->
                                    <td class="px-2 py-3 text-center sticky right-0 bg-white dark:bg-gray-800 z-10">
                                        <span class="text-sm font-bold px-3 py-2 rounded-lg"
                                            :class="calculateFinalAverage(student) >= 11 ? 'grade-approved' :
                                                calculateFinalAverage(student) !== null ? 'grade-disapproved' :
                                                'grade-pending'">
                                            {{ calculateFinalAverage(student) ?? '-' }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Nota informativa -->
                    <div class="px-5 pb-5 text-xs text-gray-500 dark:text-gray-400">
                        <p class="font-semibold mb-1">Leyenda de notas por módulo:</p>
                        <div class="flex gap-4">
                            <span><strong>P:</strong> Participación</span>
                            <span><strong>A:</strong> Asistencia</span>
                            <span><strong>E:</strong> Examen</span>
                            <span><strong>Prom:</strong> Promedio del módulo</span>
                        </div>
                        <p class="mt-2">
                            <span class="inline-flex items-center mr-3">
                                <span class="w-3 h-3 rounded bg-blue-500 mr-1"></span>
                                Aprobado (≥ 11)
                            </span>
                            <span class="inline-flex items-center">
                                <span class="w-3 h-3 rounded bg-red-500 mr-1"></span>
                                Desaprobado (&lt; 11)
                            </span>
                        </p>
                    </div>
                </div>

                <!-- Sin resultados -->
                <div v-else-if="!loaderSearch && searched && dataStudents.length === 0" class="text-center py-12">
                    <div class="flex justify-center items-center flex-col">
                        <svg class="w-12 h-12 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        <p class="text-gray-500">No se encontraron registros</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Estado de Exportación -->
        <ModalStatus :show="displayModalExportStatus" :onClose="closeModalExportStatus">
            <template #title>
                Estado de Exportación
            </template>
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
                    <div v-if="isExporting">
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
