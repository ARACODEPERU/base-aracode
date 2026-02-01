<script setup>
    import { useForm } from "@inertiajs/vue3"
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import Pagination from '@/Components/Pagination.vue';
    import { faTrashAlt, faPencilAlt, faTimes } from "@fortawesome/free-solid-svg-icons";
    import Keypad from '@/Components/Keypad.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import FlatPickr from 'vue-flatpickr-component';
    import 'flatpickr/dist/flatpickr.css';
    import { Spanish } from "flatpickr/dist/l10n/es.js";
    import axios from 'axios';
    import Swal2 from 'sweetalert2';
    import ModalStatus from '@/Components/ModalStatus.vue';
    import iconExcel from '@/Components/vristo/icon/icon-excel.vue';
    import { onUnmounted, ref } from 'vue';


    const props = defineProps({
        subscribers: {
            type: Object,
            default: () => ({})
        },
        filters: {
            type: Object,
            default: () => ({})
        }
    });

    const form = useForm({
        search: props.filters.search,
        dates: props.filters.dates || null,
    });

    const configFlatPickr = {
        dateFormat: 'Y-m-d',
        mode: 'range',
        locale: Spanish,
    };

    // Estado de la exportación
    const isExporting = ref(false);
    const downloadUrl = ref(null);
    const fileName = ref('');
    const errorMessage = ref(null);
    const displayModalExportStatus = ref(false);
    let pollingInterval = null; // Para controlar el intervalo de polling
    let currentJobId = null; // Para guardar el ID del job actual
    const mensajeExporting = ref([]);

    const generateExcelSubscribers = async () => {
        // Resetear estados
        isExporting.value = true;
        downloadUrl.value = null;
        fileName.value = '';
        errorMessage.value = null;
        currentJobId = null; // Resetear el ID del job anterior
        displayModalExportStatus.value = true;

        try {
            // 1. Iniciar la exportación en el backend y obtener el jobId
            const response = await axios.post(route('cms_export_subscribers_excel'), {
                dates: form.dates,
                search: form.search
            });

            // 2. Obtener el jobId de la respuesta
            currentJobId = response.data.job_id;
            mensajeExporting.value.push({success: true, label: 'Exportación iniciada.', path: null});
            // 3. Iniciar el polling para verificar el estado
            startPolling();

        } catch (error) {
            errorMessage.value = error.response?.data?.message || 'Hubo un problema al iniciar la exportación.';
            isExporting.value = false; // Detener el indicador de carga
            mensajeExporting.value.push({success: false, label: 'Error al iniciar la exportación: '+ error.response?.data?.message, path: null});
        }
    }

    const startPolling = () => {
        // Limpiar cualquier intervalo anterior para evitar múltiples pollings
        if (pollingInterval) {
            clearInterval(pollingInterval);
        }

        // Configurar un intervalo para verificar el estado del job cada 3 segundos
        pollingInterval = setInterval(async () => {
            if (!currentJobId) {
                mensajeExporting.value.push({success: false, label: 'No hay Job ID para hacer polling. Deteniendo polling.', path: null});
                clearInterval(pollingInterval);
                pollingInterval = null;
                isExporting.value = false;
                return;
            }

            try {
                const response = await axios.get(route('cms_export_subscribers_excel_status', currentJobId));
                const jobStatus = response.data;

                if (jobStatus.status === 'completed') {
                    downloadUrl.value = jobStatus.download_url; // La URL de descarga del archivo Excel
                    fileName.value = jobStatus.file_name;
                    isExporting.value = false; // Detener el indicador de carga
                    clearInterval(pollingInterval); // Detener el polling
                    pollingInterval = null;
                    mensajeExporting.value.push({success: true, label: 'Exportación completada. Archivo listo para descargar', path: downloadUrl.value});
                } else if (jobStatus.status === 'failed') {
                    errorMessage.value = jobStatus.error_message || 'La exportación falló por un error desconocido.';
                    isExporting.value = false; // Detener el indicador de carga
                    clearInterval(pollingInterval); // Detener el polling
                    pollingInterval = null;
                    mensajeExporting.value.push({success: false, label: 'Exportación fallida:'+ jobStatus.error_message, path: null});
                } else {
                    // El job sigue en 'pending' o 'processing'
                    mensajeExporting.value.push({success: false, label: 'Exportación en curso. Estado:'+ jobStatus.status, path: null});
                }
            } catch (error) {
                console.error('Error al obtener el estado de la exportación:', error);
                errorMessage.value = 'No se pudo verificar el estado de la exportación.';
                isExporting.value = false;
                clearInterval(pollingInterval);
                pollingInterval = null;
                mensajeExporting.value.push({success: false, label: errorMessage.value, path: null});
            }
        }, 3000); // Poll cada 3 segundos
    };

    onUnmounted(() => {
        if (pollingInterval) {
            clearInterval(pollingInterval);
        }
    });

    const closeModalExportStatus = () => {
        displayModalExportStatus.value = false;
    }

</script>

<template>
    <AppLayout title="Blog Suscriptores">
        <Navigation :routeModule="route('cms_dashboard')" :titleModule="'CMS'">
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Suscriptores</span>
            </li>
        </Navigation>
        <div class="pt-5">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div class="grid grid-cols-8 w-full gap-4">
                    <div class="col-span-6 sm:col-span-5">
                        <div class="flex items-center gap-4">
                            <div class="relative w-60">
                                <FlatPickr v-model="form.dates" :config="configFlatPickr" class="form-input w-full" placeholder="Selecciona rango de fechas" />
                                <button v-if="form.dates" @click="form.dates = null" type="button" class="absolute right-3 top-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 focus:outline-none" aria-label="Limpiar fechas">
                                    <font-awesome-icon :icon="faTimes" class="w-4 h-4" />
                                </button>
                            </div>
                            <div>
                                <input v-model="form.search" type="text" id="table-search-users" class="form-input pl-10" placeholder="Buscar por correo">
                            </div>
                        </div>
                    </div>
                    <div class="col-span-8 sm:col-span-3">
                        <div class="flex items-center justify-end gap-4">
                            <button @click="form.get(route('blog_subscriber'))" class="btn btn-primary text-xs uppercase">Buscar</button>
                            <div v-can="'cms_suscriptores_exportar_excel'">
                                <button v-on:click="generateExcelSubscribers()" :disabled="isExporting" :class="{ 'opacity-25': isExporting }"
                                class="btn btn-warning text-xs uppercase">
                                    <template v-if="isExporting">
                                        <svg aria-hidden="true" role="status" class="inline w-4 h-4 mr-3 text-gray-200 animate-spin dark:text-gray-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
                                            <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="#1C64F2"/>
                                        </svg>
                                    </template>
                                    <icon-excel v-else class="w-4 h-4 ltr:mr-2 rtl:ml-2" />
                                    Generar Excel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-5">
                <div class="mt-5 panel p-0 border-0 overflow-hidden">
                    <Pagination :data="subscribers" >
                        <div class="table-responsive">
                            <table class="table-striped table-hover" id="table_export">
                                <thead>
                                    <tr>

                                        <th>
                                            Fecha
                                        </th>
                                        <th>
                                            Nombres
                                        </th>
                                        <th>
                                            Correo
                                        </th>
                                        <th>
                                            Teléfono
                                        </th>
                                        <th>
                                            Mensaje
                                        </th>
                                        <th>
                                            Acción realizada
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(subscriber, index) in subscribers.data" :key="subscriber.id" class="bg-white border dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                        <td >
                                            {{ formatDateTime(subscriber.created_at) }}
                                        </td>
                                        <td >
                                            {{ subscriber.full_name }}
                                        </td>
                                        <td >
                                            {{ subscriber.email }}
                                        </td>
                                        <td >
                                            {{ subscriber.phone }}
                                        </td>
                                        <td >
                                            <span class="cursor-help"><b>Curso/Tema</b></span>
                                            <div class="absolute z-10 invisible group-hover:visible bg-gray-800 text-white text-xs rounded py-1 px-2 bottom-full left-1/2 transform -translate-x-1/2 mb-2 whitespace-nowrap">
                                                {{ subscriber.subject }}
                                            </div>
                                        </td>
                                        <td >
                                            {{ subscriber.message }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                    </Pagination>
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
                <div v-for="(msg, inx) in mensajeExporting" class="space-y-4">
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
<script>
function formatDateTime(dateTimeString) {
  const date = new Date(dateTimeString);
  const formattedDate = date.toISOString().slice(0, 10);
  const formattedTime = date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
  return `${formattedDate} ${formattedTime}`;
}
</script>
