<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import { useForm } from '@inertiajs/vue3';
    import { faGears } from "@fortawesome/free-solid-svg-icons";
    import Pagination from '@/Components/Pagination.vue';
    import Keypad from '@/Components/Keypad.vue';
    import GreenButton from '@/Components/GreenButton.vue';
    import PrimaryButton from '@/Components/PrimaryButton.vue';
    import { ref, onMounted } from 'vue';
    import ModalLargeX from '@/Components/ModalLargeX.vue';
    import InputLabel from '@/Components/InputLabel.vue';
    import TextInput from '@/Components/TextInput.vue';
    import InputError from '@/Components/InputError.vue';
    import DangerButton from '@/Components/DangerButton.vue';
    import DialogModal from '@/Components/DialogModal.vue';
    import Swal from "sweetalert2";
    import { Link, router } from '@inertiajs/vue3';

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
        affectations: {
            type: Object,
            default: () => ({}),
        },
        unitTypes:{
            type: Object,
            default: () => ({}),
        }
    });


    const displayModalDetails = ref(false);
    const displayLoaderDetails = ref(false);
    const documentDetails = ref([]);
    const disabledButtonDetailsSave = ref(false);
    const opemModalDetails = async (sales) => {
        if(sales.invoice_status ==='registrado' || sales.invoice_status ==='Rechazada' || sales.invoice_status ==='Pendiente'){
            disabledButtonDetailsSave.value = false
        }else{
            disabledButtonDetailsSave.value = true
        }

        documentDetails.value = sales.documents[0];
        displayModalDetails.value = true;
    }

    const closeModalDetails = async () => {
        displayModalDetails.value = false;
        showteButtonSave.value = false;
    }

    const sendSunatDocument = (document) => {
        Swal.fire({
            title: document.serie+'-'+document.number,
            text: 'Enviar documento',
            showCancelButton: true,
            confirmButtonText: 'Enviar',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return axios.get(route('saledocuments_send', [document.document_id,document.invoice_type_doc])).then((res) => {
                    if (!res.data.success) {
                        var cadena = `Error código: ${res.data.code}<br>Descripción:${res.data.message}`;
                        let notes = res.data.notes;
                        if (notes) {
                            cadena += `<br>Nota: ${notes}`;
                        }
                        Swal.showValidationMessage(cadena)
                    }
                    return res
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                var cadena = "";
                let array = JSON.parse(result.value.data.notes);
                for (var i = 0; i < array.length; i++) {
                    cadena += array[i] + "<br>";
                }

                Swal.fire({
                    title: `${result.value.data.message}`,
                    html: `${cadena}`,
                    icon: 'success',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                }).then(() => {
                    refreshTable();
                });

            }
        });
    }


    const getAffectation = (id) => {
        const selectedAffectation = props.affectations.find(affectation => affectation.id === id);
        //console.log(selectedAffectation.description)
        if (selectedAffectation) {
            return selectedAffectation.description;
        } else {
            return null;
        }
    }
    const getUnitTypes = (id) => {
        const selectedUnitType = props.unitTypes.find(unitType => unitType.id === id);
        //console.log(selectedAffectation.description)
        if (selectedUnitType) {
            return selectedUnitType.description;
        } else {
            return null;
        }
    }
    const showteButtonSave = ref(false);
    const editDetailsDocument = () => {
        showteButtonSave.value = !showteButtonSave.value;
    }

    const saveChangesDetails = () =>{
        displayLoaderDetails.value = true;
        axios.post(route('saledocuments_update_details'), documentDetails.value ).then((response) => {
            if(response.data.success){
                Swal.fire({
                    title: `Enhorabuena`,
                    html: `${response.data.message}`,
                    icon: 'success',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                }).then(() => {
                    displayLoaderDetails.value = false;
                    refreshTable();
                });
            }else{
                Swal.fire({
                    title: `Error`,
                    html: `${response.data.message}`,
                    icon: 'error',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
            }
        });
    };
    const calculateItemTotals = (key) => {
        let c = parseFloat(documentDetails.value.items[key].quantity) ?? 0;
        let p = parseFloat(documentDetails.value.items[key].price_sale) ?? 0;
        let d = parseFloat(documentDetails.value.items[key].mto_discount) ?? 0;

        p = p - d;

        let st = (c * p);

        // Verificar si el resultado es NaN y asignar 0 en su lugar
        if (isNaN(st)) {
            st = 0;
        }

        documentDetails.value.items[key].mto_total = st.toFixed(2);

    }

    const downloadDocument = (id,type,file,format = 'A4') => {
        let url = route('saledocuments_download',[id, type,file,format])
        window.open(url, "_blank");
    }

    const displayEditDocument = ref(false);
    const formHead = useForm({
        client_id: null,
        id: null,
        name: null,
        client_number: null,
        client_rzn_social: null,
        client_address: null,
        client_ubigeo_code: null,
        client_ubigeo_description: null,
        client_phone: null,
        client_email: null,
        invoice_broadcast_date: null,
        invoice_due_date: null,
        invoice_status: null
    });
    const closeModalEditDocument = () => {
        displayEditDocument.value = false ;
    }
    const showModalEditDocument = (document) => {
        formHead.client_id = document.client_id;
        formHead.id = document.document_id;
        formHead.name = document.serie+'-'+document.number;
        formHead.client_number = document.client_number;
        formHead.client_rzn_social = document.client_rzn_social;
        formHead.client_address = document.client_address;
        formHead.client_ubigeo_code = document.client_ubigeo_code;
        formHead.client_ubigeo_description = document.client_ubigeo_description;
        formHead.client_phone = document.client_phone;
        formHead.client_email = document.client_email;
        formHead.invoice_broadcast_date = document.invoice_broadcast_date;
        formHead.invoice_due_date = document.invoice_due_date;
        formHead.invoice_status = document.invoice_status;
        displayEditDocument.value = true ;
    }

    const saveHeadDocument = () => {
        formHead.processing = true;
        axios.post(route('saledocuments_update_head'),formHead).then(() =>{
            Swal.fire({
                title: `Enhorabuena`,
                html: `Documento Actualizado correctamente`,
                icon: 'success',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
            formHead.processing = false;
            formHead.reset();
            displayEditDocument.value = false ;
            refreshTable();
        });
    }

    const formatDate = (dateString) => {
        const date = new Date(dateString)
        return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')} ${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}:${String(date.getSeconds()).padStart(2, '0')}`
    }

    const createFormReason = () => {

        let formHTML = document.createElement('form');
        formHTML.classList.add('max-w-sm', 'mx-auto');


        let rLabel = document.createElement('label');
        rLabel.setAttribute('for', 'ctnTextareaReason');
        rLabel.classList.add('text-left','text-sm','mt-4');
        rLabel.textContent = 'Ingresar motivo de anulacion';

        let rInput = document.createElement('textarea');
        rInput.id = 'ctnTextareaReason';
        rInput.classList.add(
            'form-textarea'
        );

        rInput.required = true;
        rInput.rows = 3;

        formHTML.appendChild(rLabel);
        formHTML.appendChild(rInput);

        return formHTML;

    }

    const cancelDocument = (index, item) => {
        Swal.fire({
            icon: 'question',
            title: '¿Estas seguro?',
            text: "¡No podrás revertir esto!",
            showCancelButton: true,
            confirmButtonText: '¡Sí, Anularlo!',
            cancelButtonText: '¡No, cancelar!',
            padding: '2em',
            customClass: 'sweet-alerts',
        }).then((result) => {
            if (result.value) {
                Swal.fire({
                    html: createFormReason(),
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
                    preConfirm: async (input) => {
                        let textarea = document.getElementById("ctnTextareaReason").value;
                        let resp = null;
                        if(textarea){
                            resp = axios.post(route('saledocuments_cancel_document'), {
                                reason: textarea,
                                id: item.document_id,
                                type: item.invoice_type_doc
                            }).then((res) => {
                                if (!res.data.success) {
                                    Swal.showValidationMessage(res.data.alert)
                                }
                                return res
                            });
                        }else{
                            Swal.showValidationMessage('El motivo es obligatorio')
                        }
                        return resp;
                    },
                    allowOutsideClick: () => !Swal.isLoading()
                }).then((res) => {
                    if (res.isConfirmed) {
                        showMessage('El documento fue anulado correctamente');
                        //refreshTable();
                    }
                    refreshTable();
                });
            }
        });
    }

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
            data: null,
            render: '#action',
            title: 'Acciones'
        },
        { data: null, render: '#document', title: 'Nmr. Documento' },
        { data: null, render: '#created',title: 'Fecha Registrado' },
        { data: 'invoice_broadcast_date', title: 'Fecha Emitido' },
        { data: 'full_name', title: 'Cliente' },
        { data: 'total', title: 'Total' },
        { data: null, render: '#status', title: 'Estado' },
    ];

    const options = {
        responsive: true,
        language: es_PE,
        order: [[2, 'desc']]
    }

    const documentTable = ref(null);
    let instance = null;

    onMounted(() => {
        instance = documentTable.value?.dt;
    });

    const refreshTable = () => {
        // accede a la instancia del DataTable
        if (instance) {
            instance.ajax.url(route('saledocuments_table_document')).load();
        }
    };


</script>

<template>
    <AppLayout title="Documentos">
        <Navigation :routeModule="route('sales_dashboard')" :titleModule="'Facturación Electrónica'">
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

                <DataTable ref="documentTable" :options="options" :ajax="route('saledocuments_table_document')" :columns="columns">
                    <template #action="props">
                        <div class="flex gap-4 items-center justify-center">
                            <div class="dropdown">
                                <Popper :placement="'bottom-start'" offsetDistance="0" class="align-middle">
                                    <button type="button" class="btn btn-outline-primary px-2 py-2 dropdown-toggle">
                                        <font-awesome-icon :icon="faGears" />
                                    </button>
                                    <template #content="{ close }">
                                    <ul @click="close()" class="whitespace-nowrap">
                                        <li v-if="props.rowData.invoice_status != 'Aceptada'">
                                            <a @click="showModalEditDocument(props.rowData)" href="javascript:;">Editar</a>
                                        </li>
                                        <li v-if="props.rowData.invoice_status == 'Pendiente' && props.rowData.invoice_type_doc != '03'">
                                            <a @click="sendSunatDocument(props.rowData)" v-can="'invo_documento_envio_sunat'" href="javascript:;">Enviar a Sunat</a>
                                        </li>
                                        <li>
                                            <a @click="opemModalDetails(props.rowData)" href="javascript:;">Detalles</a>
                                        </li>
                                        <li v-if="props.rowData.status == 1 && props.rowData.invoice_type_doc == '03'">
                                            <a @click="cancelDocument(index, props.rowData)" href="javascript:;">Anular</a>
                                        </li>
                                        <li>
                                            <a @click="downloadDocument(props.rowData.document_id,props.rowData.invoice_type_doc,'PDF')" href="javascript:;">PDF A4</a>
                                        </li>
                                        <li>
                                            <a @click="downloadDocument(props.rowData.document_id,props.rowData.invoice_type_doc,'PDF','t80')" href="javascript:;">PDF 80x250</a>
                                        </li>
                                        <li v-if="props.rowData.invoice_status === 'Aceptada'">
                                            <a @click="downloadDocument(props.rowData.document_id,props.rowData.invoice_type_doc,'XML')" href="javascript:;">Descargar XML</a>
                                        </li>
                                        <li v-if="props.rowData.invoice_status === 'Aceptada'">
                                            <a @click="downloadDocument(props.rowData.document_id,props.rowData.invoice_type_doc,'CDR')" href="javascript:;">Descargar CDR</a>
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
                                {{ props.rowData.serie }}-{{ props.rowData.invoice_correlative }}
                            </h6>
                            <span v-if="props.rowData.invoice_response_description" class="block text-xs">
                                <code v-if="props.rowData.invoice_response_code != 0">
                                    Código: {{ props.rowData.invoice_response_code }}
                                </code>
                                <code>
                                    Descripción: {{ props.rowData.invoice_response_description }}
                                </code>
                            </span>
                        </div>
                        <p v-if="props.rowData.status == 3" class="text-xs font-black text-danger">Motivo de anulacion: {{ props.rowData.reason_cancellation }}</p>
                    </template>
                    <template #created="props">
                        {{ formatDate(props.rowData.created_at) }}
                    </template>
                    <template #status="props">
                        <div>
                            <span v-if="props.rowData.status == 1" class="bg-yellow-100 text-yellow-800 text-xs font-medium px-2.5 py-0.5 rounded dark:bg-gray-700 dark:text-yellow-300 border border-yellow-300">Activa</span>
                            <span v-else-if="props.rowData.status == 3" class="bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded dark:bg-gray-700 dark:text-red-400 border border-red-400">Anulado</span>
                        </div>
                        <span v-if="props.rowData.invoice_status">
                            <small>Estado Sunat:</small>
                            {{ props.rowData.invoice_status }}
                        </span>
                    </template>
                </DataTable>

            </div>
        </div>

        <ModalLargeX
            :show="displayModalDetails"
            :onClose="closeModalDetails"
            :icon="'/img/bienes.png'"
        >
            <template #title>
                Detalles de la venta
            </template>
            <template #message>
                Lista de Productos o Servicios Vendidos
            </template>
            <template #content>
                <div class="relative overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th style="width: 70px;" class="px-6 py-3 text-center">
                                    Código
                                </th>
                                <th scope="col" class="px-6 py-3">
                                    Descripción
                                </th>
                                <th scope="col" class="px-6 py-3">
                                    Medida
                                </th>
                                <th style="display: none;" scope="col" class="px-6 py-3">
                                    Tipo de Afectación del IGV
                                </th>
                                <th scope="col" class="px-6 py-3">
                                    Cantidad
                                </th>
                                <th scope="col" class="px-6 py-3">
                                    Precio
                                </th>
                                <th scope="col" class="px-6 py-3">
                                    Sub Total
                                </th>
                                <th scope="col" class="px-6 py-3">
                                    Descuento
                                </th>
                                <th scope="col" class="px-6 py-3 text-center">
                                    Total
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item,ko) in documentDetails.items" class="bg-white border-b border-stroke dark:bg-gray-900 dark:border-gray-700">
                                <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                    {{ item.cod_product }}
                                </th>
                                <td class="px-4 py-2">
                                    <span v-show="!showteButtonSave">{{ item.decription_product }}</span>
                                    <textarea v-show="showteButtonSave" v-model="item.decription_product" class="form-textarea" rows="3"></textarea>
                                </td>
                                <td class="px-4 py-2 text-center">
                                    <span v-show="!showteButtonSave">{{ getUnitTypes(item.unit_type) }}</span>
                                    <select v-show="showteButtonSave" v-model="item.unit_type" class="form-select form-select-sm">
                                        <option v-for="(row, ci) in unitTypes" :value="row.id">{{ row.description }}</option>
                                    </select>
                                </td>
                                <td style="display: none;" class="px-4 py-2">
                                    <span v-show="!showteButtonSave">{{ getAffectation(item.type_afe_igv) }}</span>
                                    <select v-show="showteButtonSave" v-model="item.type_afe_igv" class="form-select form-select-sm">
                                        <option v-for="(row, ci) in affectations" :value="row.id">{{ row.description }}</option>
                                    </select>
                                </td>
                                <td style="width: 110px;" class="px-4 py-2 text-right">
                                    <span v-show="!showteButtonSave">{{ item.quantity }}</span>
                                    <input v-show="showteButtonSave" v-model="item.quantity" @input="calculateItemTotals(ko)"  class="form-input form-input-sm text-right" type="text" />
                                </td>
                                <td style="width: 110px;" class="px-4 py-2 text-right">
                                    <span v-show="!showteButtonSave">{{ item.price_sale }}</span>
                                    <input v-show="showteButtonSave" v-model="item.price_sale" @input="calculateItemTotals(ko)"  class="form-input form-input-sm text-right" type="text" />
                                </td>
                                <td style="width: 120px;" class="px-4 py-2 text-right">
                                    {{ (item.quantity * item.price_sale).toFixed(2) }}
                                </td>
                                <td style="width: 120px;" class="px-4 py-2 text-right">
                                    <span v-show="!showteButtonSave">{{ item.mto_discount }}</span>
                                    <input v-show="showteButtonSave" v-model="item.mto_discount" @input="calculateItemTotals(ko)"  class="form-input form-input-sm text-right" type="text" />
                                </td>
                                <td style="width: 120px;" class="px-4 py-2 text-right">
                                    {{ item.mto_total }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </template>
            <template #buttons>
                <GreenButton
                    :disabled="disabledButtonDetailsSave"
                    @click="editDetailsDocument"
                    class="mr-2"
                >
                    Modificar
                </GreenButton>
               <PrimaryButton v-if="showteButtonSave" class="mr-2"
                    :class="{ 'opacity-25': displayLoaderDetails }" :disabled="displayLoaderDetails"
                    @click="saveChangesDetails"
                >
                    <svg v-show="displayLoaderDetails" aria-hidden="true" role="status" class="inline w-4 h-4 mr-3 text-gray-200 animate-spin dark:text-gray-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
                        <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="#1C64F2"/>
                    </svg>
                    Guardar Cambios
                </PrimaryButton>
            </template>
        </ModalLargeX>
        <DialogModal :show="displayEditDocument" @close="closeModalEditDocument">
            <template #title>
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100 dark:bg-green-800 sm:mx-0 sm:h-10 sm:w-10">
                    <!-- Heroicon name: outline/check -->
                        <img :src="'/img/editar-codigo.png'" />
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                            {{ formHead.name  }}
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Editar Cabecera del Documento
                            </p>
                        </div>
                    </div>
                </div>
            </template>

            <template #content>
                <div class="grid grid-cols-4 gap-4">
                    <div class="col-span-6 sm:col-span-1">
                        <InputLabel for="client_number" value="Cliente Num. Doc." />
                        <TextInput id="client_number" v-model="formHead.client_number" type="text" class="block w-full mt-1"
                            autofocus />
                        <InputError :message="formHead.errors.client_number" class="mt-2" />
                    </div>
                    <div class="col-span-6 sm:col-span-3">
                        <InputLabel for="client_rzn_social" value="Cliente Razon Social" />
                        <TextInput id="client_rzn_social" v-model="formHead.client_rzn_social" type="text" class="block w-full mt-1"/>
                        <InputError :message="formHead.errors.client_rzn_social" class="mt-2" />
                    </div>
                    <div class="col-span-6 sm:col-span-4">
                        <InputLabel for="client_address" value="Cliente Dirección" />
                        <TextInput id="client_address" v-model="formHead.client_address" type="text" class="block w-full mt-1"/>
                        <InputError :message="formHead.errors.client_address" class="mt-2" />
                    </div>
                    <div class="col-span-6 sm:col-span-4">
                        <InputLabel for="client_ubigeo_description" value="Departamento-Provincia-Distrito" />
                        <TextInput id="client_ubigeo_description" v-model="formHead.client_ubigeo_description" type="text" class="block w-full mt-1"/>
                        <InputError :message="formHead.errors.client_ubigeo_code" class="mt-2" />
                    </div>
                    <div class="col-span-6 sm:col-span-2">
                        <InputLabel for="client_phone" value="Teléfono" />
                        <TextInput id="client_phone" v-model="formHead.client_phone" type="text" class="block w-full mt-1"/>
                        <InputError :message="formHead.errors.client_phone" class="mt-2" />
                    </div>
                    <div class="col-span-6 sm:col-span-2">
                        <InputLabel for="client_email" value="Correo electrónico" />
                        <TextInput id="client_email" v-model="formHead.client_email" type="text" class="block w-full mt-1"/>
                        <InputError :message="formHead.errors.client_email" class="mt-2" />
                    </div>
                    <div class="col-span-6 sm:col-span-2">
                        <InputLabel for="invoice_broadcast_date" value="Fecha Emisión" />
                        <TextInput id="invoice_broadcast_date" v-model="formHead.invoice_broadcast_date" type="date" class="block w-full mt-1"/>
                        <InputError :message="formHead.errors.invoice_broadcast_date" class="mt-2" />
                    </div>
                    <div class="col-span-6 sm:col-span-2">
                        <InputLabel for="invoice_due_date" value="Fecha Vencimiento" />
                        <TextInput id="invoice_due_date" v-model="formHead.invoice_due_date" type="date" class="block w-full mt-1"/>
                        <InputError :message="formHead.errors.invoice_due_date" class="mt-2" />
                    </div>
                    <div class="col-span-6 sm:col-span-2">
                        <InputLabel for="invoice_status" value="Esatdo sunat" />
                        <select v-model="formHead.invoice_status" id="invoice_status" class="form-select">
                            <option :value="'Pendiente'">Pendiente</option>
                            <option :value="'Rechazada'">Rechazada</option>
                        </select>
                        <InputError :message="formHead.errors.invoice_status" class="mt-2" />
                    </div>
                </div>
            </template>

            <template #footer>
                <PrimaryButton class="mr-2" type="button"
                    @click="saveHeadDocument"
                    :class="{ 'opacity-25': formHead.processing }"
                    :disabled="formHead.processing"
                >
                    <svg v-show="formHead.processing" aria-hidden="true" role="status" class="inline w-4 h-4 mr-3 text-gray-200 animate-spin dark:text-gray-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
                        <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="#1C64F2"/>
                    </svg>
                    Guardar Cambios
                </PrimaryButton>
                <DangerButton @click="closeModalEditDocument" type="button">
                    Cancel
                </DangerButton>
            </template>
        </DialogModal>
    </AppLayout>
</template>
<style scoped>
.invoice-select{
    margin: 0px !important;
    padding: 1px !important;
    height: 26px !important;
    width: 100% !important;
    font-size: 12px;
}
.invoice-imput{
    margin: 0px !important;
    padding: 1px !important;
    height: 26px !important;
    width: 100% !important;
    font-size: 12px;
}
.invoice-textarea{
    margin: 0px !important;
    padding: 1px !important;
    width: 100% !important;
    font-size: 12px;
}
</style>
