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
        users: {
            type: Array,
            default: () => []
        }
    });

    const form = ref({
        user_id: null,
        date_start: '',
        date_end: ''
    });

    const dataItems = ref([]);
    const loaderSearch = ref(false);
    const searched = ref(false);

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

    const searchTable = () => {
        if (!form.value.date_start && !form.value.date_end && !form.value.user_id) {
            alert('Por favor seleccione al menos un filtro');
            return;
        }

        loaderSearch.value = true;
        searched.value = true;
        axios({
            method: 'post',
            url: route('aca_enrollment_documents_report_table'),
            data: form.value
        }).then(function (response) {
            dataItems.value = response.data.items || [];
        }).finally(() => {
            loaderSearch.value = false;
        });
    };

    const startExport = async () => {
        if (!form.value.date_start && !form.value.date_end && !form.value.user_id) {
            alert('Por favor seleccione al menos un filtro');
            return;
        }

        isExporting.value = true;
        downloadUrl.value = null;
        fileName.value = '';
        errorMessage.value = null;
        currentJobId.value = null;
        mensajeExporting.value = [];
        displayModalExportStatus.value = true;

        const formData = {
            ...form.value,
            user_id: form.value.user_id && !['con', 'sin'].includes(form.value.user_id)
                ? parseInt(form.value.user_id)
                : form.value.user_id
        };

        try {
            const response = await axios.post(route('aca_enrollment_documents_export'), formData);
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
                const response = await axios.get(route('aca_enrollment_documents_export_status', currentJobId.value));
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
</script>

<template>
    <AppLayout title="Reportes">
        <Navigation :routeModule="route('aca_dashboard')" :titleModule="'Académico'"
            :data="[
                {route: route('aca_reports_dashboard'), title: 'Reportes'},
                {title: 'Reporte Detallado de Documentos de Ventas - Cursos Matriculados'}
            ]"
        />
        <div class="mt-5">
            <div class="panel p-0">
                <div class="p-5 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        Filtros de Búsqueda
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                Responsable
                            </label>
                            <select v-model="form.user_id" class="form-select">
                                <option :value="null">Todos</option>
                                <option value="con">Con responsable</option>
                                <option value="sin">Sin responsable</option>
                                <option v-for="user in users" :key="user.id" :value="user.id">
                                    {{ user.name }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                Fecha inicio
                            </label>
                            <input v-model="form.date_start" type="date" class="form-input" />
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                Fecha fin
                            </label>
                            <input v-model="form.date_end" type="date" class="form-input" />
                        </div>
                        <div class="flex items-end gap-2">
                            <button @click="searchTable" :class="{ 'opacity-25': loaderSearch }" :disabled="loaderSearch" type="button" class="btn btn-primary flex-1">
                                <icon-loader v-if="loaderSearch" class="w-4 h-4 mr-2" />
                                <icon-search v-else class="w-4 h-4 mr-2" />
                                {{ loaderSearch ? 'Consultar' : 'Consultar' }}
                            </button>
                            <button @click="startExport" :class="{ 'opacity-25': isExporting }" :disabled="isExporting || !searched" type="button" class="btn btn-success flex-1">
                                <icon-loader v-if="isExporting" class="w-4 h-4 mr-2" />
                                <IconExcel v-else class="w-4 h-4 mr-2" />
                                {{ isExporting ? 'Exportar' : 'Exportar' }}
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
                <div v-else-if="!loaderSearch && searched && dataItems.length > 0">
                    <div class="table-responsive overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-center">#</th>
                                    <th class="px-4 py-3 text-left">Nombres y Apellidos</th>
                                    <th class="px-4 py-3 text-left">Fecha de Matrícula</th>
                                    <th class="px-4 py-3 text-left">Curso</th>
                                    <th class="px-4 py-3 text-left">Comprobante de Pago</th>
                                    <th class="px-4 py-3 text-left">Responsable</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, key) in dataItems" :key="item.id" class="border-b border-gray-200 dark:border-gray-700">
                                    <td class="px-4 py-3 text-center">{{ key + 1 }}</td>
                                    <td class="px-4 py-3 font-medium">{{ item.student_name }}</td>
                                    <td class="px-4 py-3">{{ item.registration_date }}</td>
                                    <td class="px-4 py-3">{{ item.course_name }}</td>
                                    <td class="px-4 py-3 font-mono">{{ item.comprobante }}</td>
                                    <td class="px-4 py-3">{{ item.responsible }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- Leyenda / Resumen -->
                    <div class="px-4 py-3 bg-gray-50 dark:bg-gray-800 rounded-bl-2xl rounded-br-2xl">
                        <div class="flex flex-wrap justify-between items-center text-sm gap-2">
                            <div>
                                <span class="text-gray-600 dark:text-gray-400">
                                    Total de registros: <strong>{{ dataItems.length }}</strong>
                                </span>
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 flex flex-wrap gap-3">
                                <span>📋 Matrículas con documentos de pago</span>
                                <span>👤 Responsable asignado</span>
                                <span>📅 Rango: {{ form.date_start || 'Inicio' }} - {{ form.date_end || 'Fin' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sin resultados -->
                <div v-else-if="!loaderSearch && searched && dataItems.length === 0" class="text-center py-12">
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
