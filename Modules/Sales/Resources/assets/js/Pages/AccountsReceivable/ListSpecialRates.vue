<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import Keypad from '@/Components/Keypad.vue';
    import Pagination from '@/Components/Pagination.vue';
    import ModalLarge from '@/Components/ModalLarge.vue';
    import ModalLargeX from '@/Components/ModalLargeX.vue';
    import Swal from "sweetalert2";
    import { useForm, Link, usePage, router } from '@inertiajs/vue3';
    import { faGears, faEdit, faTrashAlt } from "@fortawesome/free-solid-svg-icons";
    import { ref, watch, onMounted, nextTick, onUnmounted, computed } from "vue";
    import axios from 'axios';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import textWriting from '@/Components/loader/text-writing.vue';
    import iconExcel from '@/Components/vristo/icon/icon-excel.vue';
    import { Dropdown, Menu, MenuItem } from 'ant-design-vue';
    import ModalStatus from "@/Components/ModalStatus.vue";
    import flatPickr from 'vue-flatpickr-component';
    import 'flatpickr/dist/flatpickr.css';
    import { Spanish } from "flatpickr/dist/l10n/es.js"
    import iconMail from '@/Components/vristo/icon/icon-mail.vue';
    import iconLink from '@/Components/vristo/icon/icon-link.vue';
    import IconLoader from '@/Components/vristo/icon/icon-loader.vue';

    const props = defineProps({
        sales: {
            type: Object,
            default: () => ({}),
        },
        filters: {
            type: Object,
            default: () => ({}),
        }
    });

    const form = useForm({
        search: props.filters.search,
        issue_date: null
    });

     // Función auxiliar para formatear la fecha a 'YYYY-MM-DD'
    const formatDateToYYYYMMDD = (date) => {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0'); // Meses son 0-11
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    };

    const today = new Date();
    const todayFormatted = formatDateToYYYYMMDD(today);

    const basic = ref({
        dateFormat: 'Y-m-d',
        mode: 'range',
        locale: Spanish,
        defaultDate: [todayFormatted, todayFormatted]
    });



    const displayModalDetails = ref(false);
    const saleDetails = ref(null);
    const openModalDetails = (data) => {
        saleDetails.value = data;
        displayModalDetails.value = true;
    }
    const closeModalDetails = () => {
        displayModalDetails.value = false;
    }

    const showMessage = (msg = '', type = 'success') => {
        const toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            customClass: { container: 'toast' },
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.onmouseenter = Swal.stopTimer;
                toast.onmouseleave = Swal.resumeTimer;
            }
        });
        toast.fire({
            icon: type,
            title: msg,
            padding: '10px 20px',
        });
    };

    function formatDate(dateString) {
        // Detectar si el string trae hora
        const hasTime = /\d{2}:\d{2}/.test(dateString);

        // Convertir a Date
        const date = new Date(dateString);

        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();

        // Si NO tiene hora → solo devolver fecha
        if (!hasTime) {
            return `${day}-${month}-${year}`;
        }

        // Si tiene hora → devolver fecha y hora
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        const seconds = String(date.getSeconds()).padStart(2, '0');

        // Detectar si el string original tenía segundos o no
        const hasSeconds = /\d{2}:\d{2}:\d{2}/.test(dateString);

        if (hasSeconds) {
            return `${day}-${month}-${year} ${hours}:${minutes}:${seconds}`;
        }

        return `${day}-${month}-${year} ${hours}:${minutes}`;
    }

    const openDialogCreateFeeDocument = (sale) => {
        let fromId = 'v1';
        let url = route('acco_sales_special_rates_quota_create', [sale.id, fromId]);

        if (Number(sale.advancement) >= Number(sale.total)) {
            showMessage('El documento ya fue pagado en su totalidad', 'success');
            return;
        }

        // Usamos el total disponible de la pantalla
        let screenWidth = window.screen.availWidth;
        let screenHeight = window.screen.availHeight;

        // Ponemos 0 en top y left para que pegue a la esquina superior izquierda
        const w = window.open(
            "",
            "feeWindow",
            `width=${screenWidth},height=${screenHeight},top=0,left=0,resizable=yes,scrollbars=yes`
        );

        // 2. Mostrar loader temporal
        w.document.write(`
            <html>
                <head>
                    <title>Cargando...</title>
                    <style>
                        body {
                            display: flex;
                            justify-content: center;
                            align-items: center;
                            flex-direction: column;
                            height: 100vh;
                            margin: 0;
                            background: #f7f7f7;
                            font-family: sans-serif;
                            text-align: center;
                        }

                        .loader-con {
                            position: relative;
                            width: 100%;            /* Ocupar todo el ancho */
                            max-width: 600px;       /* Para que no exceda demasiado */
                            height: 120px;
                            overflow: hidden;
                        }

                        .pfile {
                            position: absolute;
                            bottom: 25px;
                            width: 50px;            /* Más grande para mejor visibilidad */
                            height: 60px;
                            background: linear-gradient(90deg, #b324db, #ac8dcb);
                            border-radius: 4px;
                            transform-origin: center;
                            animation: flyRight 3s ease-in-out infinite;
                            opacity: 0;
                        }

                        .pfile::before {
                            content: "";
                            position: absolute;
                            top: 8px;
                            left: 8px;
                            width: 30px;
                            height: 5px;
                            background-color: #ffffff;
                            border-radius: 2px;
                        }

                        .pfile::after {
                            content: "";
                            position: absolute;
                            top: 18px;
                            left: 8px;
                            width: 20px;
                            height: 5px;
                            background-color: #ffffff;
                            border-radius: 2px;
                        }

                        @keyframes flyRight {
                            0% {
                                left: -15%;          /* Más atrás */
                                transform: scale(0.3);
                                opacity: 0;
                            }
                            50% {
                                left: 40%;
                                transform: scale(1.3);
                                opacity: 1;
                            }
                            100% {
                                left: 110%;          /* Más adelante */
                                transform: scale(0.3);
                                opacity: 0;
                            }
                        }

                        .pfile {
                            animation-delay: calc(var(--i) * 0.5s);
                        }
                    </style>
                </head>

                <body>
                    <div class="loader-con">
                        <div style="--i: 0;" class="pfile"></div>
                        <div style="--i: 1;" class="pfile"></div>
                        <div style="--i: 2;" class="pfile"></div>
                        <div style="--i: 3;" class="pfile"></div>
                        <div style="--i: 4;" class="pfile"></div>
                        <div style="--i: 5;" class="pfile"></div>
                    </div>

                    <p style="margin-top: 20px; color: #555; font-size: 18px;">
                        Cargando contenido...
                    </p>
                </body>
            </html>

        `);

        // 3. Cargar la URL real
        w.location.href = url;
    };

    const displayModalCronograma = ref(false);
    const saleCronograma = ref(null);
    const openModalCronograma = (sale) => {
        saleCronograma.value = sale;
        displayModalCronograma.value = true;
    }
    const closeModalCronograma = () => {
        displayModalCronograma.value = false;
    }

    onMounted(() => {
        window.addEventListener("message", (event) => {
            if (event.data === "refresh-payment-all") {
                router.visit(route('acco_sales_special_rates'), {
                    only: ['sales'], // opcional
                    replace: true,
                    preserveScroll: true,
                    preserveState: true
                });
            }
        });
    });

    const displayModalDocuments = ref(false);
    const saleDocuments = ref(null);

    const openModalDocuments= (sale) => {
        saleDocuments.value = sale;
        displayModalDocuments.value = true;
    }
    const closeModalDocuments = () => {
        displayModalDocuments.value = false;
    }

    const downloadDocument = (id,type,file,format = 'A4') => {
        let url = route('saledocuments_download',[id, type,file,format])
        window.open(url, "_blank");
    }

    const unlinkDocument = (document, sale) => {
        if (!document.schedule_id) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se encontró la cuota vinculada al documento',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
            return;
        }

        Swal.fire({
            title: '¿Estás seguro?',
            html: `¿Desvincular el documento <strong>${document.invoice_serie}-${document.invoice_correlative}</strong>?<br><br>Esta acción no se puede revertir.<br><br>El monto del documento (S/. ${document.overall_total}) será reintegrado a la cuota.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, desvincular',
            cancelButtonText: 'Cancelar',
            padding: '2em',
            customClass: 'sweet-alerts',
            showLoaderOnConfirm: true,
            allowOutsideClick: () => !Swal.isLoading(),
            preConfirm: () => {
                return axios.post(route('acco_quota_unlink_document'), {
                    document_id: document.id
                }).then((res) => {
                    if (!res.data.success) {
                        Swal.showValidationMessage(res.data.message);
                    }
                    return res.data;
                }).catch((error) => {
                    Swal.showValidationMessage('Error al procesar la solicitud');
                });
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    icon: 'success',
                    title: 'Éxito',
                    text: result.value.message,
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
                // Recargar datos de la tabla
                router.visit(route('acco_sales_special_rates'), {
                    only: ['sales'], // opcional
                    replace: true,
                    preserveScroll: true,
                    preserveState: true
                });
            }
        });
    }

    // ============ EDICIÓN DE CUOTAS ============
    const displayModalEdit = ref(false);
    const editSaleData = ref(null);
    const editSchedules = ref([]);
    const newQuotaCount = ref(0);
    const isSaving = ref(false);

    // Computed para calcular el monto pendiente (retorna número)
    const pendingAmount = computed(() => {
        if (!editSaleData.value) return 0;
        const total = parseFloat(editSaleData.value.total) || 0;
        const advancement = parseFloat(editSaleData.value.advancement) || 0;
        return total - advancement;
    });

    // Computed para calcular monto promedio por cuota
    const amountPerQuota = computed(() => {
        const pendingSchedules = editSchedules.value.filter(s => !s.is_paid);
        if (pendingSchedules.length === 0) return 0;
        const totalPending = pendingSchedules.reduce((sum, s) => sum + (parseFloat(s.amount_to_pay) || 0), 0);
        return totalPending / pendingSchedules.length;
    });

    const openModalEdit = (sale) => {
        editSaleData.value = sale;
        // Cargar las cuotas de la venta (convertir a número)
        editSchedules.value = sale.schedules ? sale.schedules.map(s => ({
            id: s.id,
            installment_number: s.installment_number,
            payment_date: s.payment_date,
            amount_to_pay: parseFloat(s.amount_to_pay) || 0,
            amount_paid: parseFloat(s.amount_paid) || 0,
            remaining_amount: parseFloat(s.remaining_amount) || 0,
            is_paid: parseFloat(s.amount_paid || 0) > 0
        })) : [];
        displayModalEdit.value = true;
    };

    const closeModalEdit = () => {
        displayModalEdit.value = false;
        editSaleData.value = null;
        editSchedules.value = [];
    };

    const recalculateAmounts = () => {
        if (newQuotaCount.value <= 0 || !editSaleData.value) {
            return;
        }

        // Función helper para verificar si una cuota tiene algún pago
        const hasAnyPayment = (schedule) => {
            return parseFloat(schedule.amount_paid || 0) > 0;
        };

        // 1. Separar cuotas con pagos y sin pagos
        const schedulesWithPayment = editSchedules.value.filter(s => hasAnyPayment(s));
        const schedulesToRedistribute = editSchedules.value.filter(s => !hasAnyPayment(s));

        // 2. Mantener las cuotas con pagos (parciales o totales)
        editSchedules.value = [...schedulesWithPayment];

        // 3. Calcular el monto por cada nueva cuota (división entera)
        const totalPending = pendingAmount.value;
        const baseAmount = Math.floor(totalPending / newQuotaCount.value);
        const remainder = totalPending % newQuotaCount.value;

        // 4. Obtener fecha de la última cuota con pago para calcular las nuevas fechas
        let baseDate = new Date();
        if (schedulesWithPayment.length > 0) {
            const lastPaid = schedulesWithPayment[schedulesWithPayment.length - 1];
            baseDate = new Date(lastPaid.payment_date);
        }

        // 5. Crear las nuevas cuotas
        for (let i = 0; i < newQuotaCount.value; i++) {
            // Calcular monto (la última cuota obtiene el resto)
            let amount = baseAmount;
            if (i === newQuotaCount.value - 1) {
                amount = baseAmount + remainder;
            }

            // Calcular fecha (sumar 1 mes por cada cuota)
            const paymentDate = new Date(baseDate);
            paymentDate.setMonth(paymentDate.getMonth() + (i + 1));

            editSchedules.value.push({
                id: null, // Nueva cuota sin ID
                installment_number: schedulesWithPayment.length + i + 1,
                payment_date: paymentDate.toISOString().split('T')[0],
                amount_to_pay: amount,
                amount_paid: 0,
                remaining_amount: amount,
                is_paid: false
            });
        }
    };

    const updateAllDatesFrom = (index) => {
        const currentSchedule = editSchedules.value[index];
        if (!currentSchedule || currentSchedule.is_paid) return;

        const newDate = new Date(currentSchedule.payment_date);

        // Actualizar las cuotas siguientes
        for (let i = index + 1; i < editSchedules.value.length; i++) {
            const schedule = editSchedules.value[i];
            if (schedule.is_paid) continue;

            const nextDate = new Date(newDate);
            nextDate.setMonth(nextDate.getMonth() + (i - index));
            schedule.payment_date = nextDate.toISOString().split('T')[0];
        }
    };

    const addNewQuota = () => {
        if (!editSaleData.value) return;

        // Obtener la última cuota no pagada
        const pendingSchedules = editSchedules.value.filter(s => !s.is_paid);
        const lastPending = pendingSchedules[pendingSchedules.length - 1];

        let lastDate = lastPending ? new Date(lastPending.payment_date) : new Date();
        lastDate.setMonth(lastDate.getMonth() + 1);

        const newInstallmentNumber = editSchedules.value.length > 0
            ? Math.max(...editSchedules.value.map(s => s.installment_number)) + 1
            : 1;

        editSchedules.value.push({
            id: null, // Nueva cuota sin ID
            installment_number: newInstallmentNumber,
            payment_date: lastDate.toISOString().split('T')[0],
            amount_to_pay: 0,
            amount_paid: 0,
            remaining_amount: 0,
            is_paid: false
        });
    };

    const removeQuota = (index) => {
        const schedule = editSchedules.value[index];
        if (schedule && !schedule.is_paid && schedule.id === null) {
            editSchedules.value.splice(index, 1);
        }
    };

    const saveEdit = async () => {
        isSaving.value = true;
        try {
            // Filtrar solo las cuotas pendientes para enviar al backend
            const pendingSchedules = editSchedules.value.filter(s => !s.is_paid);

            const response = await axios.put(route('acco_sales_special_rates_update', editSaleData.value.id), {
                schedules: pendingSchedules.map(s => ({
                    id: s.id,
                    payment_date: s.payment_date,
                    amount_to_pay: s.amount_to_pay
                }))
            });

            if (response.data.success) {
                showMessage('Cuotas actualizadas correctamente', 'success');
                closeModalEdit();
                // Recargar solo la lista usando Inertia router
                router.reload();
            }
        } catch (error) {
            showMessage(error.response?.data?.message || 'Error al guardar los cambios', 'error');
        } finally {
            isSaving.value = false;
        }
    };

    // ============ ESTADO DE EXPORTACIÓN ============
    const isExporting = ref(false);
    const downloadUrl = ref(null);
    const fileName = ref('');
    const errorMessage = ref(null);
    const displayModalExportStatus = ref(false);
    let pollingInterval = null; // Para controlar el intervalo de polling
    let currentJobId = null; // Para guardar el ID del job actual
    const mensajeExporting = ref([]);

    const generateExcelSales = async () => {
        mensajeExporting.value = [];
        // Resetear estados
        isExporting.value = true;
        downloadUrl.value = null;
        fileName.value = '';
        errorMessage.value = null;
        currentJobId = null; // Resetear el ID del job anterior
        displayModalExportStatus.value = true;

        try {
            // 1. Iniciar la exportación en el backend y obtener el jobId
            // Usa axios.post directamente si no necesitas enviar datos del form (e.g. filtros)
            const response = await axios.post(route('acco_sales_special_rates_quota_excel'), form);

            // 2. Obtener el jobId de la respuesta
            currentJobId = response.data.job_id;
            // console.log('Exportación iniciada. Job ID:', currentJobId);
            mensajeExporting.value.push({success: true, label: 'Exportación iniciada.', path: null});
            // 3. Iniciar el polling para verificar el estado
            startPolling();

        } catch (error) {
            // console.error('Error al iniciar la exportación:', error);
            // Mostrar mensaje de error al usuario
            errorMessage.value = error.response?.data?.message || 'Hubo un problema al iniciar la exportación.';
            isExporting.value = false; // Detener el indicador de carga
            mensajeExporting.value.push({success: false, label: 'Error al iniciar la exportación:'+ error.response?.data?.message, path: null});
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
                //console.warn('No hay Job ID para hacer polling. Deteniendo polling.');
                mensajeExporting.value.push({success: false, label: 'No hay Job ID para hacer polling. Deteniendo polling.', path: null});
                clearInterval(pollingInterval);
                pollingInterval = null;
                isExporting.value = false;
                return;
            }

            try {
                const response = await axios.get(route('acco_export_status', currentJobId));
                const jobStatus = response.data;

                // Actualizar el estado local con los datos del job
                // (aunque no haya barra de progreso, estos datos son útiles para depuración o si decides añadir una barra mínima)
                // progress.value = jobStatus.progress; // Puedes mantener esta línea si el backend la sigue enviando
                // processedCount.value = jobStatus.processed_count;
                // totalCount.value = jobStatus.total_count;

                if (jobStatus.status === 'completed') {
                    downloadUrl.value = jobStatus.download_url; // La URL de descarga del archivo Excel
                    fileName.value = jobStatus.file_name;
                    isExporting.value = false; // Detener el indicador de carga
                    clearInterval(pollingInterval); // Detener el polling
                    pollingInterval = null;
                    //console.log('Exportación completada. Archivo listo para descargar:', downloadUrl.value);
                    mensajeExporting.value.push({success: true, label: 'Exportación completada. Archivo listo para descargar', path: downloadUrl.value});
                } else if (jobStatus.status === 'failed') {
                    errorMessage.value = jobStatus.error_message || 'La exportación falló por un error desconocido.';
                    isExporting.value = false; // Detener el indicador de carga
                    clearInterval(pollingInterval); // Detener el polling
                    pollingInterval = null;
                    // console.error('Exportación fallida:', jobStatus.error_message);
                    mensajeExporting.value.push({success: false, label: 'Exportación fallida:'+ jobStatus.error_message, path: null});
                } else {
                    // El job sigue en 'pending' o 'processing'
                    // console.log('Exportación en curso. Estado:', jobStatus.status);
                    mensajeExporting.value.push({success: false, label: 'Exportación en curso. Estado:'+ jobStatus.status, path: null});
                }
            } catch (error) {
                //console.error('Error al obtener el estado de la exportación:', error);
                errorMessage.value = 'No se pudo verificar el estado de la exportación.';
                isExporting.value = false;
                clearInterval(pollingInterval);
                pollingInterval = null;
                mensajeExporting.value.push({success: false, label: errorMessage.value, path: null});
            }
        }, 3000); // Poll cada 3 segundos (ajusta según necesites)
    };

    // Limpiar el intervalo cuando el componente se desmonte para evitar fugas de memoria
    onUnmounted(() => {
        if (pollingInterval) {
            clearInterval(pollingInterval);
        }
    });

    const closeModalExportStatus = () => {
        displayModalExportStatus.value = false;
    }

    const sendEmailDocument = (sale, email) => {
        Swal.fire({
            icon: 'question',
            title: '¿Estas seguro?',
            html: createFormSendEmail(sale, email),
            showCloseButton: true,
            showCancelButton: true,
            focusConfirm: false,
            confirmButtonText: 'Aceptar',
            cancelButtonText: 'Cancelar',
            padding: '2em',
            customClass: 'sweet-alerts',
            showLoaderOnConfirm: true,
            allowOutsideClick: false,
            allowEscapeKey: false,
            backdrop: true,
            preConfirm: async (input) => {
                let inputEmailSend = document.getElementById("ctnTextSendEmailDocument").value;
                let resp = null;
                if(inputEmailSend){
                    resp = axios.post(route('aca_send_email_student_document'), {
                        person_email: inputEmailSend,
                        person_name: sale.client_rzn_social,
                        document_id: sale.id,
                        onlisaleId: null
                    },
                    {
                        timeout: 0,
                    }).then((res) => {
                        if (!res.data.success) {
                            Swal.showValidationMessage(res.data.alert)
                        }
                        return res
                    });
                }else{
                    Swal.showValidationMessage('El correo electrónico es obligatorio.')
                }
                return resp;
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((res) => {
            console.log();
            if (res.isConfirmed) {
                showMessage('El mensaje se envió correctamente.');
            }
            refreshTable();
        });

    }

    const createFormSendEmail = (sale, email) => {

        let formHTML = document.createElement('form');
        formHTML.classList.add('max-w-sm', 'mx-auto');

        let rParrafo = document.createElement('p');
        rParrafo.classList.add('text-center','text-lg','mt-4');
        rParrafo.innerHTML = 'Se enviará un mensaje con el documento de venta <b>' + sale.invoice_serie + '-' + sale.invoice_correlative +'</b> adjunto';

        let rLabel = document.createElement('label');
        rLabel.setAttribute('for', 'ctnTextSendEmailDocument');
        rLabel.classList.add('text-left','text-sm','mt-4');
        rLabel.textContent = 'Correo Electronico';

        let rInput = document.createElement('input');
        rInput.id = 'ctnTextSendEmailDocument';
        rInput.value = email;
        rInput.classList.add(
            'form-input'
        );
        rInput.required = true;
        formHTML.appendChild(rParrafo);
        formHTML.appendChild(rLabel);
        formHTML.appendChild(rInput);

        return formHTML;

    }
</script>

<template>
    <AppLayout title="Resumen">
        <Navigation :routeModule="route('sales_dashboard')" :titleModule="'Cuentas por cobrar'"
            :data="[
                {title: 'Gestión Ventas en Cuotas'}
            ]"
        />
        <div class="mt-5">
            <!-- ====== Table Section Start -->
            <div class="flex flex-col gap-10">
                <!-- ====== Table One Start -->
                <div class="panel p-0">
                    <div class="w-full p-4">
                        <div class="grid grid-cols-3">
                            <div class="col-span-3 sm:col-span-1">
                                <form  class="grid sm:grid-cols-2 gap-6">
                                    <div class="w-full">
                                        <flat-pickr v-model="form.issue_date" class="form-input" :config="basic"></flat-pickr>
                                    </div>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                            <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path></svg>
                                        </div>
                                        <input v-model="form.search" type="text" id="table-search-users" class="block p-2 pl-10 text-sm text-gray-900 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="Buscar por Descripción">
                                    </div>
                                </form>
                            </div>
                            <div class="col-span-3 sm:col-span-2">
                                <Keypad>
                                    <template #botones>
                                        <button @click="form.get(route('acco_sales_special_rates'))" type="button" class="btn btn-danger text-xs">BUSCAR</button>
                                        <button v-can="'acco_pagos_cuotas_especiales_excel'" v-on:click="generateExcelSales()" class="btn btn-success uppercase text-xs">
                                            <icon-excel class="w-4 h-4 mr-1" />
                                            Exportar Excel
                                        </button>
                                        <Link :href="route('acco_sales_special_rates_create')" class="btn btn-primary uppercase text-xs">Nuevo</Link>
                                    </template>
                                </Keypad>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead >
                                <tr >
                                    <th >
                                        Acciones
                                    </th>
                                    <th >
                                        Nombre Completo
                                    </th>
                                    <th >
                                        Teléfono
                                    </th>
                                    <th >
                                        Email
                                    </th>
                                    <th >
                                        Fecha registrado
                                    </th>
                                    <th >
                                        Fecha ultimo pago
                                    </th>
                                    <th >
                                        Monto Total
                                    </th>
                                    <th >
                                        Monto Abonado
                                    </th>
                                    <th class=" ">
                                        Saldo por Pagar
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-for="(item, index) in sales.data" :key="item.id">
                                    <tr >
                                        <td class="text-center">
                                            <div class="flex gap-4 items-center justify-center">
                                                <div class="dropdown">
                                                    <Dropdown>
                                                        <button type="button" class="btn btn-outline-primary px-2 py-2 dropdown-toggle">
                                                            <font-awesome-icon :icon="faGears" />
                                                        </button>
                                                        <template #overlay>
                                                            <Menu>
                                                                <MenuItem>
                                                                    <a @click="openModalDetails(item)" href="javascript:;">Ver detalles</a>
                                                                </MenuItem>
                                                                <MenuItem>
                                                                    <a @click="openModalCronograma(item)" href="javascript:;">Ver cronograma</a>
                                                                </MenuItem>
                                                                <MenuItem>
                                                                    <a @click="openModalDocuments(item)" href="javascript:;" class="text-red-600 hover:text-red-800">Ver Documentos de venta</a>
                                                                </MenuItem>
                                                                <MenuItem>
                                                                    <a @click="openDialogCreateFeeDocument(item)" href="javascript:;" class="text-blue-600 underline hover:text-blue-800 visited:text-purple-700" >Registrar pago</a>
                                                                </MenuItem>
                                                                <MenuItem>
                                                                    <a @click="openModalEdit(item)" href="javascript:;" class="text-yellow-600 hover:text-yellow-800">Editar cuotas y montos</a>
                                                                </MenuItem>
                                                            </Menu>
                                                        </template>
                                                    </Dropdown>
                                                </div>
                                            </div>
                                        </td>
                                        <td >
                                            {{ item.client.full_name }}
                                        </td>
                                        <td >
                                            {{ item.client.telephone }}
                                        </td>
                                        <td >
                                            {{ item.client.email }}
                                        </td>
                                        <td >
                                            {{ formatDate(item.created_at) }}
                                        </td>
                                        <td >
                                            <template v-if="item.advancement > 0">
                                                {{ formatDate(item.updated_at) }}
                                            </template>
                                            <template v-else>
                                                <span class="w-full text-center">⚠</span>
                                            </template>
                                        </td>
                                        <td >
                                            {{ item.total }}
                                        </td>
                                        <td >
                                            {{ item.advancement }}
                                        </td>
                                        <td >
                                            {{ item.total - item.advancement }}
                                        </td>
                                        <!-- <td class="text-center">
                                           <span v-if="item.response_status == 'pendiente'"  class="bg-red-100 text-red-800 text-xs font-medium mr-2 px-2.5 py-0.5 rounded dark:bg-gray-700 dark:text-red-400 border border-red-400">No completó el pago</span>
                                           <span v-if="item.response_status == 'pago_en_cuotas'"
                                                v-tippy="{ content: 'El alumno cuenta con acceso activo, pero tiene un saldo pendiente por completar dentro del plazo acordado para el pago de cuotas.', placement: 'bottom'}"
                                                class="bg-red-100 text-red-800 text-xs font-medium mr-2 px-2.5 py-0.5 rounded dark:bg-gray-700 dark:text-red-400 border border-red-400">Venta activa con saldo pendiente</span>
                                           <span v-else-if="item.response_status == 'approved'" class="bg-blue-100 text-blue-800 text-xs font-medium mr-2 px-2.5 py-0.5 rounded dark:bg-gray-700 dark:text-blue-400 border border-blue-400">Pago aprobado</span>
                                           <span v-else class="bg-yellow-100 text-yellow-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded dark:bg-yellow-900 dark:text-yellow-300">Error en la transacción</span>
                                        </td> -->
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <Pagination :data="sales" />
                </div>
            </div>
        </div>
        <ModalLarge
            :show="displayModalDetails"
            :onClose="closeModalDetails"
            :icon="'/img/lupa-documento.png'"
        >
            <template v-if="saleDetails" #title>
                VEN-{{ saleDetails.id }} | {{ saleDetails.client.number }} - {{ saleDetails.client.full_name }}
            </template>
            <template #message>
                Detalles de la venta
            </template>
            <template #content>
                <div class="border rounded-lg py-4 px-4 dark:border-gray-700 mb-6">
                    <h4 class="mb-4">Información para la Boleta o Factura</h4>
                    <div class="space-y-3">
                        <dl class="flex flex-col sm:flex-row gap-1">
                            <dt class="min-w-40">
                                <span class="block text-sm text-gray-500 dark:text-neutral-500">Nombre o Razón social:</span>
                            </dt>
                            <dd>
                                <span>{{ saleDetails.invoice_razon_social }}</span>
                            </dd>
                        </dl>
                        <dl class="flex flex-col sm:flex-row gap-1">
                            <dt class="min-w-40">
                                <span class="block text-sm text-gray-500 dark:text-neutral-500">DNI o RUC:</span>
                            </dt>
                            <dd>
                                <span>{{ saleDetails.invoice_ruc }}</span>
                            </dd>
                        </dl>
                    </div>
                </div>
                <div  v-if="saleDetails" class="relative overflow-x-auto shadow-md sm:rounded-lg">
                    <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th scope="col" class="px-6 py-3">
                                    Producto o servicio
                                </th>
                                <th scope="col" class="px-6 py-3">
                                    Precio
                                </th>
                            </tr>
                        </thead>
                        <tbody v-if="saleDetails.sale_product.length > 0">
                            <tr v-for="(row, key) in saleDetails.sale_product" class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                <td scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                    <p v-if="JSON.parse(row.product).title" class="text-lg">{{ JSON.parse(row.product).title }}</p>
                                    <p>{{ JSON.parse(row.product).description }}</p>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    {{ row.price }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </template>
        </ModalLarge>
        <ModalLargeX
            :show="displayModalCronograma"
            :onClose="closeModalCronograma"
            :icon="'/img/pago-automatico.png'"
        >
            <template v-if="saleCronograma" #title>
                VEN-{{ saleCronograma.id }} | {{ saleCronograma.client.number }} - {{ saleCronograma.client.full_name }}
            </template>
            <template #message>
                Detalles de cronograma de pagos
            </template>
            <template #content>
                <div class="border rounded-lg py-4 px-4 dark:border-gray-700 mb-6">
                    <h4 class="mb-4">Información</h4>
                    <div class="space-y-3">
                        <dl class="flex flex-col sm:flex-row gap-1">
                            <dt class="min-w-40">
                                <span class="block text-sm text-gray-500 dark:text-neutral-500">Cantidad documentos de venta:</span>
                            </dt>
                            <dd>
                                <span>{{ saleCronograma.documents.length }}</span>
                            </dd>
                        </dl>
                        <dl class="flex flex-col sm:flex-row gap-1">
                            <dt class="min-w-40">
                                <span class="block text-sm text-gray-500 dark:text-neutral-500">Cantidad de cuotas:</span>
                            </dt>
                            <dd>
                                <span>{{ saleCronograma.schedules.length }}</span>
                            </dd>
                        </dl>
                    </div>
                </div>
                <div  v-if="saleCronograma" class="relative overflow-x-auto shadow-md sm:rounded-lg">
                    <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th scope="col" class="px-6 py-3">
                                    Num. Cuota
                                </th>
                                <th scope="col" class="px-6 py-3">
                                    Fecha Programada
                                </th>
                                <th scope="col" class="px-6 py-3">
                                    Total a pagar
                                </th>
                                <th scope="col" class="px-6 py-3">
                                    Monto pagado
                                </th>
                                <th scope="col" class="px-6 py-3">
                                    Saldo pendiente
                                </th>
                                <th scope="col" class="px-6 py-3">
                                    Fecha pagado
                                </th>
                            </tr>
                        </thead>
                        <tbody v-if="saleCronograma.schedules.length > 0">
                            <tr v-for="(row, key) in saleCronograma.schedules" class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                <td class="text-center">{{ row.installment_number }}</td>
                                <td scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                    {{ formatDate(row.payment_date) }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    {{ row.amount_to_pay }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    {{ row.amount_paid }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    {{ row.remaining_amount }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span v-if="row.amount_paid > 0">{{ formatDate(row.updated_at) }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </template>
        </ModalLargeX>
        <ModalLargeX
            :show="displayModalDocuments"
            :onClose="closeModalDocuments"
            :icon="'/img/papel.png'"
        >
            <template v-if="saleDocuments" #title>
                VEN-{{ saleDocuments.id }} | {{ saleDocuments.client.number }} - {{ saleDocuments.client.full_name }}
            </template>
            <template #message>
                Detalles de cronograma de pagos
            </template>
            <template #content>
                <div class="border rounded-lg py-4 px-4 dark:border-gray-700 mb-6">
                    <h4 class="mb-4">Información</h4>
                    <div class="space-y-3">
                        <dl class="flex flex-col sm:flex-row gap-1">
                            <dt class="min-w-40">
                                <span class="block text-sm text-gray-500 dark:text-neutral-500">Cantidad documentos de venta:</span>
                            </dt>
                            <dd>
                                <span>{{ saleDocuments.documents.length }}</span>
                            </dd>
                        </dl>
                        <dl class="flex flex-col sm:flex-row gap-1">
                            <dt class="min-w-40">
                                <span class="block text-sm text-gray-500 dark:text-neutral-500">Cantidad de cuotas:</span>
                            </dt>
                            <dd>
                                <span>{{ saleDocuments.schedules.length }}</span>
                            </dd>
                        </dl>
                    </div>
                </div>
                <div  v-if="saleDocuments" class="relative overflow-x-auto shadow-md sm:rounded-lg">
                    <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th scope="col" class="px-4 py-3">
                                    Acción
                                </th>
                                <th scope="col" class="px-4 py-3">
                                    serie-número
                                </th>
                                <th scope="col" class="px-4 py-3">
                                    Estado
                                </th>
                                <th scope="col" class="px-4 py-3">
                                    Estado SUNAT
                                </th>
                                <th scope="col" class="px-4 py-3">
                                    Fecha emisión
                                </th>
                                <th scope="col" class="px-4 py-3">
                                    Detalle
                                </th>
                                <th scope="col" class="px-4 py-3">
                                    total pago
                                </th>
                            </tr>
                        </thead>
                        <tbody v-if="saleDocuments.documents.length > 0">
                            <tr v-for="(row, key) in saleDocuments.documents" class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                <td class="text-center">
                                    <div v-if="row.schedule_id" class="flex items-center justify-center gap-2">
                                        <button @click="sendEmailDocument(row, saleDocuments.client.email)" type="button" title="Enviar por email">
                                            <iconMail class="w-5 h-5" />
                                        </button>
                                        <button v-if="row.status == 3" @click="unlinkDocument(row, saleDocuments)" type="button" title="Desvincular de la cuota">
                                            <svg class="w-5 h-5 text-red-600 hover:text-red-800" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
                                               <path fill="currentColor" d="M128 64C92.7 64 64 92.7 64 128L64 512C64 547.3 92.7 576 128 576L308 576C285.3 544.5 272 505.8 272 464C272 363.4 349.4 280.8 448 272.7L448 234.6C448 217.6 441.3 201.3 429.3 189.3L322.7 82.7C310.7 70.7 294.5 64 277.5 64L128 64zM389.5 240L296 240C282.7 240 272 229.3 272 216L272 122.5L389.5 240zM464 608C543.5 608 608 543.5 608 464C608 384.5 543.5 320 464 320C384.5 320 320 384.5 320 464C320 543.5 384.5 608 464 608zM523.3 427.3L486.6 464L523.3 500.7C529.5 506.9 529.5 517.1 523.3 523.3C517.1 529.5 506.9 529.5 500.7 523.3L464 486.6L427.3 523.3C421.1 529.5 410.9 529.5 404.7 523.3C398.5 517.1 398.5 506.9 404.7 500.7L441.4 464L404.7 427.3C398.5 421.1 398.5 410.9 404.7 404.7C410.9 398.5 421.1 398.5 427.3 404.7L464 441.4L500.7 404.7C506.9 398.5 517.1 398.5 523.3 404.7C529.5 410.9 529.5 421.1 523.3 427.3z"/>
                                            </svg>
                                        </button>
                                    </div>
                                    <div v-else class="flex items-center justify-center gap-2 text-red-800">
                                        <svg class="w-6 h-6 text-red-800" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                            <path fill="currentColor" d="M367.2 412.5L99.5 144.8c-22.4 31.4-35.5 69.8-35.5 111.2 0 106 86 192 192 192 41.5 0 79.9-13.1 111.2-35.5zm45.3-45.3c22.4-31.4 35.5-69.8 35.5-111.2 0-106-86-192-192-192-41.5 0-79.9 13.1-111.2 35.5L412.5 367.2zM0 256a256 256 0 1 1 512 0 256 256 0 1 1 -512 0z"/>
                                        </svg>
                                    </div>
                                </td>
                                <td>
                                    <a @click="downloadDocument(row.id,row.invoice_type_doc,'PDF')" href="javascript:;" class="text-blue-600 underline hover:text-blue-800 visited:text-purple-700">{{ row.invoice_serie+'-'+row.invoice_correlative }}</a>
                                </td>
                                <td class="px-4 py-4">
                                    <span v-if="row.status == 1" class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800/30 dark:text-green-500">
                                        Normal
                                    </span>
                                    <span v-else-if="row.status == 3" class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-800/30 dark:text-red-500">
                                        Anulado
                                    </span>
                                    <span v-else class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800/30 dark:text-gray-500">
                                        {{ row.status }}
                                    </span>
                                </td>
                                <td class="px-4 py-4">
                                    <span v-if="row.invoice_status == 'Enviada Por Anular'" class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-800/30 dark:text-orange-500">
                                        {{ row.invoice_status }}
                                    </span>
                                    <span v-else-if="row.invoice_status == 'Pendiente'" class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-800/30 dark:text-yellow-500">
                                        {{ row.invoice_status }}
                                    </span>
                                    <span v-else class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-800/30 dark:text-blue-500">
                                        {{ row.invoice_status }}
                                    </span>
                                </td>
                                <td scope="row" class="px-4 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                    {{ formatDate(row.invoice_broadcast_date) }}
                                </td>
                                <td class="px-4 py-4 text-justify hyphens-auto">
                                    <template v-if="row.items.length > 0">
                                        {{ row.items[0].decription_product }}
                                    </template>
                                </td>
                                <td class="px-4 py-4 text-right">
                                    {{ row.overall_total }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </template>
        </ModalLargeX>
        <!-- Modal Editar Cuotas -->
        <ModalLargeX :show="displayModalEdit" :onClose="closeModalEdit">
            <template v-if="editSaleData" #title>
                Editar Cuotas y Montos - {{ editSaleData.client.full_name }}
            </template>
            <template #message>
                Configure las cuotas pendientes y sus montos
            </template>
            <template #content>
                <div v-if="editSaleData" class="space-y-6">
                    <!-- Información de la venta -->
                    <div class="grid grid-cols-3 gap-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div>
                            <span class="block text-sm text-gray-500 dark:text-gray-400">Total Deuda:</span>
                            <span class="text-lg font-bold text-gray-900 dark:text-white">S/. {{ editSaleData.total }}</span>
                        </div>
                        <div>
                            <span class="block text-sm text-gray-500 dark:text-gray-400">Monto Pagado:</span>
                            <span class="text-lg font-bold text-green-600">S/. {{ editSaleData.advancement }}</span>
                        </div>
                        <div>
                            <span class="block text-sm text-gray-500 dark:text-gray-400">Saldo Pendiente:</span>
                            <span class="text-lg font-bold text-red-600">S/. {{ pendingAmount.toFixed(2) }}</span>
                        </div>
                    </div>

                    <!-- Redistribución de cuotas -->
                    <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                        <h4 class="font-bold text-gray-900 dark:text-white mb-3">Redistribuir Cuotas</h4>
                        <div class="flex flex-wrap gap-4 items-end">
                            <div>
                                <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Nuevo número de cuotas:</label>
                                <input v-model.number="newQuotaCount" type="number" min="1" class="form-input w-32" />
                            </div>
                            <button @click="recalculateAmounts" class="btn btn-primary">
                                Redistribuir
                            </button>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                            Saldo Pendiente: <span class="font-bold">S/. {{ pendingAmount.toFixed(2) }}</span>
                        </p>
                    </div>

                    <!-- Lista de cuotas -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-100 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th class="px-4 py-3">#</th>
                                    <th class="px-4 py-3">Fecha de Pago</th>
                                    <th class="px-4 py-3">Monto</th>
                                    <th class="px-4 py-3">Pagado</th>
                                    <th class="px-4 py-3">Pendiente</th>
                                    <th class="px-4 py-3">Estado</th>
                                    <th class="px-4 py-3">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(schedule, index) in editSchedules" :key="index" class="border-b dark:border-gray-700">
                                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                        {{ schedule.installment_number }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <input
                                            v-if="!schedule.is_paid"
                                            v-model="schedule.payment_date"
                                            type="date"
                                            class="form-input w-full"
                                            @change="updateAllDatesFrom(index)"
                                        />
                                        <input
                                            v-else
                                            :value="schedule.payment_date"
                                            type="date"
                                            class="form-input w-full bg-gray-100 dark:bg-gray-700 cursor-not-allowed"
                                            disabled
                                        />
                                    </td>
                                    <td class="px-4 py-3">
                                        <input
                                            v-if="!schedule.is_paid"
                                            v-model.number="schedule.amount_to_pay"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            class="form-input w-28 text-right"
                                        />
                                        <span v-else class="text-right block w-28 font-medium">S/. {{ schedule.amount_to_pay }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        S/. {{ schedule.amount_paid }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        S/. {{ schedule.remaining_amount }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span v-if="schedule.is_paid && schedule.remaining_amount <= 0" class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                            ✓ Pagada
                                        </span>
                                        <span v-else-if="schedule.is_paid && schedule.remaining_amount > 0" class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                                            Amortizada
                                        </span>
                                        <span v-else class="px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400">
                                            Pendiente
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <button
                                            v-if="!schedule.is_paid && schedule.id === null"
                                            @click="removeQuota(index)"
                                            class="text-red-600 hover:text-red-800"
                                        >
                                            <font-awesome-icon :icon="faTrashAlt" />
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Agregar nueva cuota -->
                    <button @click="addNewQuota" class="btn btn-outline-primary">
                        + Agregar Cuota (Prórroga)
                    </button>

                </div>
            </template>
            <template #buttons>
                <button @click="saveEdit" :class="{ 'opacity-25': isSaving }" :disabled="isSaving" type="button" class="btn btn-primary">
                    <IconLoader class="w-4 h-4 mr-2" v-if="isSaving" />
                    {{ isSaving ? 'Guardando...' : 'Guardar Cambios' }}
                </button>
            </template>
        </ModalLargeX>
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
