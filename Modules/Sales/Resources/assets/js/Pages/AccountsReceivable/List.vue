<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import { useForm } from '@inertiajs/vue3';
    import { faGears } from "@fortawesome/free-solid-svg-icons";
    import iconX from '@/Components/vristo/icon/icon-x.vue';
    import Keypad from '@/Components/Keypad.vue';
    import iconLoader from '@/Components/vristo/icon/icon-loader.vue';
    import iconChecks from '@/Components/vristo/icon/icon-checks.vue';
    import PrimaryButton from '@/Components/PrimaryButton.vue';
    import { ref, onMounted } from 'vue';
    import ModalLargeX from '@/Components/ModalLargeX.vue';
    import InputLabel from '@/Components/InputLabel.vue';
    import TextInput from '@/Components/TextInput.vue';
    import InputError from '@/Components/InputError.vue';
    import DangerButton from '@/Components/DangerButton.vue';
    import ModalLargeXX from '@/Components/ModalLargeXX.vue';
    import Swal from "sweetalert2";
    import { Link, router } from '@inertiajs/vue3';
    import ModalLarge from '@/Components/ModalLarge.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import DataTable from 'datatables.net-vue3';
    import DataTablesCore from 'datatables.net';
    import 'datatables.net-responsive';
    import '@/Components/vristo/datatables/datatables.css'
    import '@/Components/vristo/datatables/style.css'
    import 'datatables.net-buttons'; // Importa el plugin de botones
    import 'datatables.net-buttons-dt'; // Importa los estilos de los botones
    import es_PE from '@/Components/vristo/datatables/datatables-es.js'



    DataTable.use(DataTablesCore);

    const props = defineProps({
        payments: {
            type: Object,
            default: () => ({}),
        },
    });



    const showMessage = (msg = '', type = 'success') => {
        const toast = Swal.mixin({
            toast: true,
            position: 'top',
            showConfirmButton: false,
            timer: 3000,
            customClass: { container: 'toast' },
        });
        toast.fire({
            icon: type,
            title: msg,
            padding: '10px 20px',
        });
    };

    const columns = [
        {
            data: null, // No necesita datos específicos, solo es el control
            className: 'control', // Clase especial de DataTables para el botón '+'
            render: '#actions',
            orderable: false,
            searchable: false,
            responsivePriority: 2 // Prioridad un poco menor que tus acciones, pero aún importante
        },
        {
            data: null,
            render: '#botones', // Tu slot para los botones de acción
            title: 'Acciones',
            orderable: false,   // Las columnas de acción no suelen ser ordenables
            searchable: false,  // Ni buscables
            responsivePriority: 1 // Asegura que esta columna sea de alta prioridad y se mantenga visible
        },

        { data: null, render: '#document', title: 'Nmr. Documento' },
        { data: null, render: '#created', title: 'Fecha Registrado' },
        { data: null, render: '#broadcast_date' ,title: 'Fecha Emitido' },
        { data: null, render: '#full_name', title: 'Cliente' },
        { data: 'total', title: 'Total Documento' },
        { data: null, render: '#status', title: 'Estado del documento' },
        { data: 'advancement', title: 'Total pagado' },
        { data: null, render: '#status_credit', title: 'Estado del crédito' },
        {
            data: 'payment_status_text', // <-- ¡AHORA USAMOS EL CAMPO DEL BACKEND DIRECTAMENTE!
            title: 'Estado de pagos',
            // --- ¡YA NO NECESITAS render: '#overdue_fee' AQUÍ! ---
            // Puedes agregar aquí una función de render si quieres estilizar el texto
            render: function (data, type, row) {
                // 'data' es el valor de 'payment_status_text' (Vencido, Pagado, Atiempo)
                // 'row' es el objeto de datos completo de la fila
                let bgColor = '';
                let textColor = 'text-white'; // Color de texto por defecto

                if (data === 'Vencido') {
                    bgColor = 'bg-red-500';
                } else if (data === 'Pagado') {
                    bgColor = 'bg-info'; // Asegúrate de que 'bg-info' esté definida en tu Tailwind o CSS
                } else { // Atiempo
                    bgColor = 'bg-success'; // Asegúrate de que 'bg-success' esté definida
                }

                return `<span class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded uppercase text-xs font-medium ${bgColor} ${textColor}">
                            ${data}
                        </span>`;
            }
        },
    ];

    const options = {
        responsive: true,
        language: es_PE,
        order: [[2, 'desc']],
    }

    const documentTable = ref(null);
    let instance = null;

    onMounted(() => {
        instance = documentTable.value?.dt;
    });

    const refreshTable = () => {
        // accede a la instancia del DataTable
        if (instance) {
            instance.ajax.url(route('acco_table_document')).load();
        }
    };


    const formatDate = (dateString) => {
        const date = new Date(dateString)
        return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')} ${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}:${String(date.getSeconds()).padStart(2, '0')}`
    }

    const displayModalQuotas = ref(false);
    const plan = ref(null);
    const displayAbonar = ref(null);
    const abonos = useForm({
        quota_id: null,
        payments: []
    });


    const openModalQuotas = (document) => {
        plan.value = document;
        displayModalQuotas.value = true;
    }

    const closeModalQuotas = () => {
        displayModalQuotas.value = false;
    }

    const getCurrentDate = () => {
        const currentDate = new Date();
        const year = currentDate.getFullYear();
        const month = String(currentDate.getMonth() + 1).padStart(2, '0'); // Los meses son base 0, por eso se suma 1
        const day = String(currentDate.getDate()).padStart(2, '0');

        return `${year}-${month}-${day}`;
    };


    const openAddAbonos = (quota) => {

        abonos.payments = quota.payments.length > 0 ? quota.payments : [];
        abonos.quota_id = quota.id;

        if(abonos.payments.length == 0){
            abonos.payments.push({
                id: null,
                payment_method_id: 4,
                sale_document_quota_id: abonos.quota_id,
                reference: null,
                payment_date: getCurrentDate(),
                amount_applied: quota.balance,
                description: null,
                created_at: getCurrentDate(),
                updated_at: getCurrentDate()
            });
        }

        displayAbonar.value === quota.id ? (displayAbonar.value = null) : (displayAbonar.value = quota.id)
    }

    const removePayment = (index, id, key) => {
        Swal.fire({
            title: '¿Estás seguro?',
            text: "¡No podrás revertir esto!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: '¡Sí, Eliminar!',
            cancelButtonText: 'Cancelar',
            showLoaderOnConfirm: true,
            padding: '2em',
            customClass: 'sweet-alerts',
            preConfirm: () => {
                // Axios se ejecutará aquí. Si hay un error de red o de servidor (ej. 404, 500),
                // la promesa se rechazará y SweetAlert lo detectará como un fallo.
                return axios.delete(route('acco_table_document_payment_destroy', id))
                    .then((res) => {
                        // Si el servidor devuelve un status 2xx pero con 'success: false' en el body,
                        // mostramos un mensaje de validación en SweetAlert.
                        if (!res.data.success) {
                            Swal.showValidationMessage(res.data.message);
                        }
                        return res.data; // Esto se pasa al 'result.value' del .then final
                    })
                    .catch(error => {
                        // Manejar errores de Axios (ej. red caída, servidor no responde, 400s, 500s)
                        let errorMessage = 'Hubo un error inesperado al eliminar.';
                        if (error.response && error.response.data && error.response.data.message) {
                            errorMessage = error.response.data.message;
                        } else if (error.message) {
                            errorMessage = error.message;
                        }
                        Swal.showValidationMessage(`Error: ${errorMessage}`);
                        // Importante: Rechazar la promesa para que SweetAlert sepa que la operación falló
                        return Swal.noop(); // Noop evita que el 'result.isConfirmed' se dispare si es un error de preConfirm
                    });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            // Este bloque se ejecuta después de que el usuario hace clic en un botón
            // y la `preConfirm` se ha resuelto.

            if (result.isConfirmed) {
                // El usuario hizo clic en "Sí, Eliminar" Y el `preConfirm` (axios.delete)
                // se resolvió exitosamente (es decir, res.data.success fue true).

                // result.value contendrá lo que `preConfirm` devolvió (res.data)
                if (result.value && result.value.success) {
                    // Asignar los valores actualizados de la cuota y el avance de la venta
                    // Asumo que tu backend devuelve la cuota actualizada y el avance de la venta
                    // en 'result.value.quota' y 'result.value.sale.advancement'
                    plan.value.document.quotas[key] = result.value.quota;
                    plan.value.advancement = result.value.sale.advancement;

                    // --- ¡MOVER ESTA LÍNEA AQUÍ! ---
                    // Solo se ejecuta si la confirmación fue exitosa y el backend respondió con éxito
                    abonos.payments.splice(index, 1);
                    // --------------------------------

                    showMessage('Se eliminó correctamente el pago', 'success'); // Mensaje de éxito
                    refreshTable(); // Refrescar la tabla si es necesario
                } else {
                    // Esto se ejecutaría si `preConfirm` resolvió, pero `res.data.success` era `false`
                    // o si `result.value` es null (ej. si preConfirm regresó `Swal.noop()`)
                    // Ya el `Swal.showValidationMessage` dentro de `preConfirm` se encargó del mensaje.
                    // Aquí puedes añadir un mensaje genérico si no se mostró ya.
                    if (!result.value) { // Si el preConfirm regresó noop(), no hay result.value
                        showMessage('La eliminación fue cancelada o falló.', 'error');
                    }
                }
            } else if (result.isDismissed) {
                // El usuario hizo clic en "Cancelar" o cerró el SweetAlert
                // La fila no se borra, que es lo que queremos.
                showMessage('Operación de eliminación cancelada', 'info');
            }
        });
    };
    const addPayment = (quota) => {
        //console.log(quota)
        if(quota.balance > 0){
            abonos.payments.push({
                id: null,
                payment_method_id: 4,
                sale_document_quota_id: quota.id,
                reference: null,
                payment_date: getCurrentDate(),
                amount_applied: quota.balance,
                description: null,
                created_at: getCurrentDate(),
                updated_at: getCurrentDate()
            });
        }

    };

    // --- Nueva Lógica para deshabilitar el botón "Abonar" ---
    const isAbonarDisabled = (currentIndex, currentQuota) => {
        // Si la cuota ya está completamente pagada, deshabilita el botón.
        if (currentQuota.status === 'Pagada' && currentQuota.balance === 0) {
            return true;
        }

        // Si es la primera cuota, no hay una cuota anterior que revisar.
        if (currentIndex == 0) {
            return false;
        }

        const previousQuota = plan.value.document.quotas[currentIndex - 1];
        return previousQuota.balance > 0;
    };

    const savePayment = (index, key) => {
        abonos.processing =  true;
        axios.post(route('acco_table_document_payment_store'), {
            sale_document_quota_id: abonos.payments[index].sale_document_quota_id,
            payment_method_id: abonos.payments[index].payment_method_id,
            amount_applied: abonos.payments[index].amount_applied,
            reference: abonos.payments[index].reference,
            payment_date: abonos.payments[index].payment_date,
            description: abonos.payments[index].description,
        }).then(function (response) {
            //abonos.resetAndClearErrors();
            abonos.payments[index] = response.data.payment;
            plan.value.document.quotas[key] = response.data.quota;
            plan.value.advancement = response.data.sale.advancement;
            showMessage('Se registro correctamente','success');
            refreshTable();
        }).catch(function (error) {
           setFormError(error, index);
        }).finally(() => {
            abonos.processing =  false;
        });
    }

    const setFormError = (error, index) => {
        let validationErrors = error.response?.data?.errors;

        if(error.status == 422){
            if (validationErrors && validationErrors.sale_document_quota_id) {
                const saleIdeErrors = validationErrors.sale_document_quota_id;
                for (let i = 0; i < saleIdeErrors.length; i++) {
                    abonos.setError('payments.'+index+'.sale_document_quota_id', saleIdeErrors[i]);
                }
            }
            if (validationErrors && validationErrors.payment_method_id) {
                const payMeErrors = validationErrors.payment_method_id;
                for (let i = 0; i < payMeErrors.length; i++) {
                    abonos.setError('payments.'+index+'.payment_method_id', payMeErrors[i]);
                }
            }
            if (validationErrors && validationErrors.amount_applied) {
                const mount_appliedErrors = validationErrors.amount_applied;
                for (let i = 0; i < mount_appliedErrors.length; i++) {
                    abonos.setError('payments.'+index+'.amount_applied', mount_appliedErrors[i]);
                }
            }
            if (validationErrors && validationErrors.reference) {
                const referenceErrors = validationErrors.reference;
                for (let i = 0; i < referenceErrors.length; i++) {
                    abonos.setError('payments.'+index+'.reference', referenceErrors[i]);
                }
            }

            if (validationErrors && validationErrors.payment_date) {
                const payment_dateErrors = validationErrors.payment_date;
                for (let i = 0; i < payment_dateErrors.length; i++) {
                    abonos.setError('payments.'+index+'.payment_date', payment_dateErrors[i]);
                }
            }

            if (validationErrors && validationErrors.description) {
                const descriptionErrors = validationErrors.description;

                for (let i = 0; i < descriptionErrors.length; i++) {
                    abonos.setError('payments.'+index+'.description', descriptionErrors[i]);
                }
            }
            showMessage('Faltan ingresar datos importantes','error')
        }else{
            showMessage(error.message,'error')
        }
        abonos.processing =  false;
    }

    const displayModalPayFull = ref(false);
    const payFullForm = useForm({
        document_id: null,
        total: null,
        advancement: null,
        payment_method_id: 4,
        reference: null,
        payment_date: getCurrentDate(),
        amount_applied: 0,
        description: null
    });

    const openModalPayFull = (sale) => {
        if (sale.total == sale.advancement){
            Swal.fire({
                icon: 'info',
                title: 'Información importante',
                text: 'Este documento ya ha sido pagado en su totalidad. No se requieren acciones adicionales.',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
        } else {
            displayModalPayFull.value = true;
            payFullForm.document_id = sale.document.id;
            payFullForm.total = sale.total;
            payFullForm.advancement = sale.advancement;
            payFullForm.amount_applied = (sale.total - sale.advancement);
        }
    }

    const closeModalPayFull = () => {
        displayModalPayFull.value = false;
    }

    const savePaymentFull = () => {
        payFullForm.post(route('acco_table_document_payment_full_store'), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                payFullForm.reset();
                showMessage('Se registro correctamente','success');
                refreshTable();
                displayModalPayFull.value = false;
            }
        });
    }

    const displayModalPaymentList = ref(false);
    const paymenysDocument = ref(null);
    const openModalPaymentList = (document) => {
        displayModalPaymentList.value = true;
        paymenysDocument.value = document;
    }

    const closeModalPaymentList = () => {
        displayModalPaymentList.value = false;
    }

    const formatAmount = (amount) => {
        if (typeof amount === 'number' || (typeof amount === 'string' && !isNaN(parseFloat(amount)))) {
            return parseFloat(amount).toFixed(2);
        }
        return amount; // Devuelve el valor original si no es un número válido
    };

    const downloadDocument = (id, type, file, format = 'A4') => {
        let url = route('saledocuments_download',[id, type, file, format])
        window.open(url, "_blank");
    };
</script>

<template>
    <AppLayout title="Documentos">
        <Navigation :routeModule="route('sales_dashboard')" :titleModule="'Cuentas por cobrar'">
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Lista de Documentos </span>
            </li>
        </Navigation>
        <div class="mt-5">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <h2 class="text-xl">Lista de Documentos </h2>
                <div class="flex sm:flex-row flex-col sm:items-center sm:gap-3 gap-4 w-full sm:w-auto">
                    <div class="flex gap-3">
                        <Keypad>
                            <template #botones>
                                <div v-can="'invo_documento_nuevo'">
                                    <Link :href="route('saledocuments_create')" class="inline-block px-6 py-2.5 bg-blue-900 text-white font-medium text-xs leading-tight uppercase rounded shadow-md hover:bg-blue-700 hover:shadow-lg focus:bg-blue-700 focus:shadow-lg focus:outline-none focus:ring-0 active:bg-blue-800 active:shadow-lg transition duration-150 ease-in-out">Nuevo</Link>
                                </div>
                            </template>
                        </Keypad>

                    </div>
                </div>
            </div>
            <div class="panel pb-1.5 mt-6">

                <DataTable ref="documentTable" :options="options" :ajax="route('acco_table_document')" :columns="columns">
                    <template #actions="props">
                        <svg class="w-5 h-5 text-info"  xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
                            <path fill="currentColor" d="M160 96C124.7 96 96 124.7 96 160L96 480C96 515.3 124.7 544 160 544L480 544C515.3 544 544 515.3 544 480L544 160C544 124.7 515.3 96 480 96L160 96zM296 408L296 344L232 344C218.7 344 208 333.3 208 320C208 306.7 218.7 296 232 296L296 296L296 232C296 218.7 306.7 208 320 208C333.3 208 344 218.7 344 232L344 296L408 296C421.3 296 432 306.7 432 320C432 333.3 421.3 344 408 344L344 344L344 408C344 421.3 333.3 432 320 432C306.7 432 296 421.3 296 408z"/>
                        </svg>
                    </template>
                    <template #botones="props">
                        <div class="flex gap-4 items-center justify-center">
                            <div class="dropdown">
                                <Popper :placement="'bottom-start'" offsetDistance="0" class="align-middle">
                                    <button type="button" class="btn btn-outline-primary px-2 py-2 dropdown-toggle">
                                        <font-awesome-icon :icon="faGears" />
                                    </button>
                                    <template #content="{ close }">
                                        <ul @click="close()" class="whitespace-nowrap">
                                            <li>
                                                <a @click="openModalQuotas(props.rowData)" href="javascript:;">Plan de Pagos</a>
                                            </li>
                                            <li>
                                                <a @click="openModalPayFull(props.rowData)" href="javascript:;">Saldar Deuda Completa</a>
                                            </li>
                                            <li>
                                                <a @click="openModalPaymentList(props.rowData)" href="javascript:;">Lista de pagos</a>
                                            </li>
                                            <li>
                                                <a @click="downloadDocument(props.rowData.document.id,props.rowData.document.invoice_type_doc,'PDF')" href="javascript:;">PDF A4</a>
                                            </li>
                                        </ul>
                                    </template>
                                </Popper>
                            </div>
                        </div>
                    </template>
                    <template #document="props">
                        <div>
                            <h6 class="font-semibold" :class="props.rowData.status == 3 ? 'line-through': ''" >
                                {{ props.rowData.document.invoice_serie }}-{{ props.rowData.document.invoice_correlative }}
                            </h6>
                            <span v-if="props.rowData.document.invoice_response_description" class="block text-xs">
                                <code v-if="props.rowData.document.invoice_response_code != 0">
                                    Código: {{ props.rowData.document.invoice_response_code }}
                                </code>
                                <code>
                                    Descripción: {{ props.rowData.document.invoice_response_description }}
                                </code>
                            </span>
                        </div>
                    </template>
                    <template #broadcast_date="props">
                        {{ props.rowData.document.invoice_broadcast_date }}
                    </template>
                    <template #full_name="props">
                        {{ props.rowData.client.full_name }}
                    </template>
                    <template #created="props">
                        {{ formatDate(props.rowData.created_at) }}
                    </template>
                    <template #status="props">
                        <div>
                            <span v-if="props.rowData.document.status == 1" class="bg-yellow-100 text-yellow-800 text-xs font-medium px-2.5 py-0.5 rounded dark:bg-gray-700 dark:text-yellow-300 border border-yellow-300">Activa</span>
                            <span v-else-if="props.rowData.document.status == 3" class="bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded dark:bg-gray-700 dark:text-red-400 border border-red-400">Anulado</span>
                        </div>
                        <span v-if="props.rowData.document.invoice_status">
                            <small>Estado Sunat:</small>
                            {{ props.rowData.document.invoice_status }}
                        </span>
                    </template>
                    <template #status_credit="props">
                        <span v-if="props.rowData.document.status_pay" class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded uppercase text-xs font-medium bg-yellow-500 text-white">
                            Pagado
                        </span>
                        <span v-else class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded uppercase text-xs font-medium bg-red-500 text-white">
                            Pendiente
                        </span>
                    </template>
                    <template #overdue_fee="props">
                        <span v-if="props.rowData.document.overdue_fee && !props.rowData.document.status_pay" class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded uppercase text-xs font-medium bg-red-500 text-white">
                            Vencido
                        </span>
                        <span v-else-if="!props.rowData.document.overdue_fee && props.rowData.total == props.rowData.advancement" class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded uppercase text-xs font-medium bg-info text-white">
                            Pagado
                        </span>
                        <span v-else class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded uppercase text-xs font-medium bg-success text-white">
                            Atiempo
                        </span>
                    </template>
                </DataTable>
            </div>
        </div>
        <ModalLargeX :show="displayModalQuotas" :onClose="closeModalQuotas" :icon="'/img/pago.png'" >
            <template #title>Plan de Pagos</template>
            <template #message>Monto Pendiente: S/. {{ plan.total - plan.advancement }}</template>
            <template #content>
                <div class="bg-yellow-50 border border-yellow-200 text-sm text-yellow-800 rounded-lg p-4 dark:bg-yellow-800/10 dark:border-yellow-900 dark:text-yellow-500" role="alert" tabindex="-1" aria-labelledby="hs-with-description-label">
                    <div class="flex">
                        <div class="shrink-0">
                            <svg class="w-8 h-8" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512">
                                <path fill="currentColor" d="M64 0C28.7 0 0 28.7 0 64L0 448c0 35.3 28.7 64 64 64l256 0c35.3 0 64-28.7 64-64l0-277.5c0-17-6.7-33.3-18.7-45.3L258.7 18.7C246.7 6.7 230.5 0 213.5 0L64 0zM325.5 176L232 176c-13.3 0-24-10.7-24-24L208 58.5 325.5 176zM64 384l0-64c0-17.7 14.3-32 32-32l192 0c17.7 0 32 14.3 32 32l0 64c0 17.7-14.3 32-32 32L96 416c-17.7 0-32-14.3-32-32zM88 64l48 0c13.3 0 24 10.7 24 24s-10.7 24-24 24l-48 0c-13.3 0-24-10.7-24-24S74.7 64 88 64zm0 96l48 0c13.3 0 24 10.7 24 24s-10.7 24-24 24l-48 0c-13.3 0-24-10.7-24-24s10.7-24 24-24z"/>
                            </svg>
                        </div>
                        <div class="ms-4">
                            <h3 class="text-sm font-semibold">
                                Documento {{ plan.document.invoice_serie }}-{{ plan.document.invoice_correlative }}
                            </h3>
                            <div class="mt-1 text-sm text-yellow-700">
                                La <strong>{{ plan.document.serie.document_type.description }}</strong> registra un saldo pendiente de pago. Utilice este plan para gestionar las cuotas restantes.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex flex-col mt-6">
                    <div class="-m-1.5 overflow-x-auto">
                        <div class="p-1.5 min-w-full inline-block align-middle">
                            <div class="border border-gray-200 rounded-lg overflow-hidden dark:border-neutral-700">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
                                    <thead class="bg-gray-50 dark:bg-neutral-700">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase dark:text-neutral-400">Num. Cuota</th>
                                            <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase dark:text-neutral-400">Estado</th>
                                            <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase dark:text-neutral-400">fecha de vencimiento</th>
                                            <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase dark:text-neutral-400">Total a pagar</th>
                                            <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase dark:text-neutral-400">Saldo pendiente</th>
                                            <th scope="col" class="px-6 py-3 text-end text-xs font-medium text-gray-500 uppercase dark:text-neutral-400">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-neutral-700">
                                        <template v-for="(pago, key) in plan.document.quotas">
                                            <tr :class="displayAbonar == pago.id ? 'bg-[#fbf1e2]' : ''">
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-800 dark:text-neutral-200">
                                                    {{ pago.quota_number }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 dark:text-neutral-200">
                                                    {{ pago.due_date }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-800 dark:text-neutral-200">
                                                    <span
                                                        class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-full text-xs font-medium"
                                                        :class="[
                                                            { 'bg-teal-100 text-teal-800 dark:bg-teal-800/30 dark:text-teal-500': pago.status == 'Pendiente' },
                                                            { 'bg-red-100 text-red-800 dark:bg-red-800/30 dark:text-red-500': pago.status == 'Vencido' },
                                                            { 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800/30 dark:text-yellow-500': pago.status == 'Amortizado' },
                                                            { 'bg-blue-100 text-blue-800 dark:bg-blue-800/30 dark:text-blue-500': pago.status == 'Pagado' }
                                                        ]"
                                                    >
                                                        {{ pago.status }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-800 dark:text-neutral-200">
                                                    {{ pago.amount }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-800 dark:text-neutral-200">
                                                    {{ pago.balance }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-end text-sm font-medium">
                                                    <button
                                                        @click="openAddAbonos(pago)"
                                                        type="button"
                                                        class="inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent text-blue-600 hover:text-blue-800 focus:outline-hidden focus:text-blue-800 disabled:opacity-50 disabled:pointer-events-none dark:text-blue-500 dark:hover:text-blue-400 dark:focus:text-blue-400"
                                                        :disabled="isAbonarDisabled(key, pago)"
                                                    >
                                                        {{ pago.balance > 0 ? 'Abonar' : displayAbonar == pago.id ? 'Ocultar' :'Ver pagos' }}
                                                    </button>
                                                </td>
                                            </tr>
                                            <tr v-if="displayAbonar == pago.id" :class="displayAbonar == pago.id ? 'bg-[#fbf1e2]' : ''">
                                                <td colspan="6">
                                                    <div class="grid sm:grid-cols-2 items-center gap-6 ml-4">
                                                        <p style="font-size: 14px;" class="italic font-bold uppercase mb-4">Pagos Recibidos <button @click="addPayment(pago)" v-if="pago.balance > 0" type="button" class="inline-block px-0 py-2 bg-transparent text-blue-600 font-medium text-xs leading-tight uppercase rounded transition duration-150 ease-in-out">Agregar (+)</button></p>
                                                    </div>
                                                    <table class="w-full border-none">
                                                        <tbody>
                                                            <template  v-for="(row, index) in abonos.payments" v-bind:key="index">
                                                                <tr class="border-none">
                                                                    <td style="width: 70px;" class="text-right">
                                                                        <div class="flex items-center justify-center gap-2">
                                                                            <button @click="removePayment(index, row.id, key)" type="button" class="btn btn-sm btn-outline-danger py-1.5 px-1.5">
                                                                                <icon-x class="w-4 h-4" />
                                                                            </button>
                                                                            <button @click="savePayment(index, key)" v-if="!row.id" :class="{ 'opacity-25': abonos.processing }" :disabled="abonos.processing" type="button" class="btn btn-sm btn-outline-primary py-1.5 px-1.5">
                                                                                <iconLoader v-if="abonos.processing" class="w-4 h-4"  />
                                                                                <icon-checks v-else class="w-4 h-4" />
                                                                            </button>
                                                                        </div>
                                                                    </td>
                                                                    <td>
                                                                        <input v-model="row.payment_date" type="date" :disabled="row.id ? true : false" class="form-input form-input-sm" :class="row.id ? 'bg-gray-100' : ''" />
                                                                        <InputError :message="abonos.errors[`payments.${index}.payment_date`]" class="mt-2" />
                                                                    </td>
                                                                    <td>
                                                                        <textarea v-model="row.description" :disabled="row.id ? true : false" class="form-textarea form-textarea-sm" rows="1" placeholder="Descripción (Opcional)" :class="row.id ? 'bg-gray-100' : ''"></textarea>
                                                                        <InputError :message="abonos.errors[`payments.${index}.description`]" class="mt-2" />
                                                                    </td>
                                                                    <td>
                                                                        <select v-model="row.payment_method_id" :disabled="row.id ? true : false" class="form-select text-white-dark py-1.5 text-xs" :class="row.id ? 'bg-gray-100' : ''">
                                                                            <template v-for="(payment) in payments">
                                                                                <option :value="payment.id">{{ payment.description }}</option>
                                                                            </template>
                                                                        </select>
                                                                        <InputError :message="abonos.errors[`payments.${index}.payment_method_id`]" class="mt-2" />
                                                                    </td>
                                                                    <td>
                                                                        <input v-model="row.reference" type="text" :disabled="row.id ? true : false" id="reference" class="form-input form-input-sm text-right" placeholder="Referencia" :class="row.id ? 'bg-gray-100' : ''">
                                                                        <InputError :message="abonos.errors[`payments.${index}.reference`]" class="mt-2" />
                                                                    </td>
                                                                    <td style="width: 110px;">
                                                                        <input v-model="row.amount_applied" type="text" :disabled="row.id ? true : false" id="amount_applied" class="form-input form-input-sm text-right" placeholder="Monto" :class="row.id ? 'bg-gray-100' : ''">
                                                                        <InputError :message="abonos.errors[`payments.${index}.amount_applied`]" class="mt-2" />
                                                                    </td>
                                                                </tr>
                                                            </template>
                                                        </tbody>
                                                    </table>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </ModalLargeX>
        <ModalLarge :show="displayModalPayFull" :icon="'/img/metodo-de-pago.png'" :onClose="closeModalPayFull">
            <template #title>Pago Total de la Deuda</template>
            <template #message>Monto Pendiente: S/. {{ payFullForm.total - payFullForm.advancement }}</template>
            <template #content>
                <div class="grid sm:grid-cols-2 gap-6">
                    <div class="">
                        <label for="payment_date">Fecha de pago</label>
                        <div class="flex-1">
                            <input v-model="payFullForm.payment_date" id="payment_date" type="date" class="form-input" />
                            <InputError :message="payFullForm.errors.payment_date" class="mt-2" />
                        </div>
                    </div>
                    <div class="">
                        <label for="method_id">Método de pago</label>
                        <div class="flex-1">
                            <select v-model="payFullForm.payment_method_id" id="method_id" class="form-select text-white-dark" >
                                <template v-for="(payment) in payments">
                                    <option :value="payment.id">{{ payment.description }}</option>
                                </template>
                            </select>
                            <InputError :message="payFullForm.errors.payment_method_id" class="mt-2" />
                        </div>
                    </div>
                    <div class="">
                        <label for="fullreference">Referencia</label>
                        <div class="flex-1">
                            <input v-model="payFullForm.reference" type="text" id="fullreference" class="form-input" />
                            <InputError :message="payFullForm.errors.reference" class="mt-2" />
                        </div>
                    </div>
                    <div class="">
                        <label for="fullamount">Monto</label>
                        <div class="flex-1">
                            <input v-model="payFullForm.amount_applied" disabled type="text" id="fullamount" class="form-input bg-gray-100" />
                            <InputError :message="payFullForm.errors.amount_applied" class="mt-2" />
                        </div>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="fulldescription">Descripción (Opcional)</label>
                        <div class="flex-1">
                            <textarea v-model="payFullForm.description" class="form-textarea" rows="2" id="fulldescription"></textarea>
                            <InputError :message="payFullForm.errors.description" class="mt-2" />
                        </div>
                    </div>
                </div>
            </template>
            <template #buttons>
                <PrimaryButton @click="savePaymentFull" :class="{ 'opacity-25': payFullForm.processing }" :disabled="payFullForm.processing" >
                    <iconLoader v-if="payFullForm.processing" class="w-4 h-4 mr-2"  />
                    Guardar
                </PrimaryButton>
            </template>
        </ModalLarge>
        <ModalLargeXX :show="displayModalPaymentList" :onClose="closeModalPaymentList" :icon="'/img/lista.png'">
            <template #title>Listado de pagos del documento</template>
            <template #message>Documento: {{ paymenysDocument.document.invoice_serie }}-{{ paymenysDocument.document.invoice_correlative }} Importe total: {{ paymenysDocument.total }}</template>
            <template #content>
                <div class="flex flex-col">
                    <div class="-m-1.5 overflow-x-auto">
                        <div class="p-1.5 min-w-full inline-block align-middle">
                            <div class="border border-gray-200 rounded-lg shadow-xs overflow-hidden dark:border-neutral-700 dark:shadow-gray-900">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
                                    <thead class="bg-gray-50 dark:bg-neutral-700">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase dark:text-neutral-400">Descripción</th>
                                            <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase dark:text-neutral-400">Fecha</th>
                                            <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase dark:text-neutral-400">Método</th>
                                            <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase dark:text-neutral-400">Código de referencia</th>
                                            <th scope="col" class="px-6 py-3 text-end text-xs font-medium text-gray-500 uppercase dark:text-neutral-400">Monto</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-neutral-700">
                                        <template v-if="paymenysDocument.document.single_payment">
                                            <template v-for="(quota, key) in paymenysDocument.document.quotas">
                                                <template v-for="(pay, index) in quota.payments">
                                                    <tr v-if="pay.estado">
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-800 dark:text-neutral-200">
                                                            <h4 class="text-xl dark:text-white"><em>Pago de la cuota:</em> {{ quota.quota_number }}</h4>
                                                            <p class="text-base dark:text-white">{{ pay.description }}</p>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-800 dark:text-neutral-200">
                                                            {{ pay.payment_date }}
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 dark:text-neutral-200">
                                                            {{ pay.payment_method_id }}
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 dark:text-neutral-200">
                                                            {{ pay.reference }}
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-end text-sm font-medium">
                                                            {{ formatAmount(pay.amount_applied) }}
                                                        </td>
                                                    </tr>
                                                </template>
                                            </template>
                                            <tr v-for="(pay, key) in paymenysDocument.payments" class="bg-orange-100 px-6 py-4 whitespace-nowrap text-sm text-gray-800 dark:text-neutral-800">
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-800 dark:text-neutral-200">
                                                    <p class="text-xl dark:text-white">{{ pay.description }}</p>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-800 dark:text-neutral-200">
                                                    {{ pay.payment_date }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 dark:text-neutral-200">
                                                    {{ pay.type }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 dark:text-neutral-200">
                                                    {{ pay.reference }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-end text-sm font-medium">
                                                    {{ formatAmount(pay.amount) }}
                                                </td>
                                            </tr>
                                        </template>
                                        <template v-else>
                                            <template v-for="(quota, key) in paymenysDocument.document.quotas">
                                                <tr v-for="(pay, index) in quota.payments">
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-800 dark:text-neutral-200">
                                                        <h4 class="text-xl dark:text-white"><em>Pago de la cuota:</em> {{ quota.quota_number }}</h4>
                                                        <p class="text-base dark:text-white">{{ pay.description }}</p>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-800 dark:text-neutral-200">
                                                        {{ pay.payment_date }}
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 dark:text-neutral-200">
                                                        {{ pay.payment_method_id }}
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800 dark:text-neutral-200">
                                                        {{ pay.reference }}
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-end text-sm font-medium">
                                                        {{ formatAmount(pay.amount_applied) }}
                                                    </td>
                                                </tr>
                                            </template>
                                        </template>
                                    </tbody>

                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </ModalLargeXX>
    </AppLayout>
</template>

