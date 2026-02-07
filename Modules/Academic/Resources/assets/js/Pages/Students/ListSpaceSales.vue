<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import { useForm } from '@inertiajs/vue3';
    import { faGears } from "@fortawesome/free-solid-svg-icons";
    import { ref, onMounted } from 'vue';
    import ModalLarge from '@/Components/ModalLarge.vue';
    import ModalLargeXX from '@/Components/ModalLargeXX.vue';
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
        },
        student:{
            type: Object,
            default: () => ({}),
        },
        creditNoteType: {
            type: Object,
            default: () => ({}),
        },
        debitNoteType: {
            type: Object,
            default: () => ({}),
        }
    });

    const findObjectById = (data, id) => {
        return data.find(item => item.id === id);
    };

    const openDialogCreateFeeDocument = (sale) => {
        let fromId = 'v2';
        let url = route('acco_sales_special_rates_quota_create', [sale.id, fromId]);

        if (Number(sale.advancement) >= Number(sale.total)) {
            showMessage('El documento ya fue pagado en su totalidad', 'success');
            return;
        }

        let width = 900;
        let height = 700;

        let screenWidth = window.screen.availWidth;
        let screenHeight = window.screen.availHeight;

        let left = (screenWidth - width) / 2;
        let top = (screenHeight - height) / 2;

        // 1. Abrir ventana
        const w = window.open(
            "",
            "feeWindow",
            `width=${width},height=${height},
            top=${top},left=${left},
            resizable=yes,scrollbars=yes`
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



    const formatDate = (dateString) => {
        const date = new Date(dateString)
        return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')} ${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}:${String(date.getSeconds()).padStart(2, '0')}`
    }


    const showMessage = (msg = '', type = 'success') => {
        const toast = Swal.mixin({
            toast: true,
            position: 'top',
            showConfirmButton: false,
            timer: 5000,
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
        { data: null, render: '#created',title: 'Fecha Registrado' },
        { data: null, render: '#updated', title: 'Fecha Ultimo pago' },
        { data: 'total', title: 'Total deuda' },
        { data: 'advancement', title: 'Monto pagado' },
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
        if (instance) {
            instance.ajax.url(route('aca_student_space_sales_list_table', props.student.person.id)).load();
        }
    };

    const displayModalDocument = ref(false);
    const dataDocuments = ref({});
    const openModalDocuments = (sale) => {
        displayModalDocument.value = true;
        dataDocuments.value = sale.documents;
    }

    const closeModalDocuments = () => {
        displayModalDocument.value = false;
    }

    const downloadDocument = (id,type,file,format = 'A4') => {
        let url = route('saledocuments_download',[id, type,file,format])
        window.open(url, "_blank");
    }

        const sendSunatDocument = (document) => {
        Swal.fire({
            title: document.invoice_serie+'-'+document.number,
            text: 'Enviar documento',
            showCancelButton: true,
            confirmButtonText: 'Enviar',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return axios.get(route('saledocuments_send', [document.id,document.invoice_type_doc])).then((res) => {
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

    const sendEmailDocument = (sale) => {
        Swal.fire({
            icon: 'question',
            title: '¿Estas seguro?',
            html: createFormSendEmail(sale),
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

    const createFormSendEmail = (sale) => {

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
        rInput.value = sale.client_email;
        rInput.classList.add(
            'form-input'
        );
        rInput.required = true;
        formHTML.appendChild(rParrafo);
        formHTML.appendChild(rLabel);
        formHTML.appendChild(rInput);

        return formHTML;

    }

    const displayModalDetails = ref(false);
    const saleDetails = ref(null);
    const openModalDetails = (data) => {
        saleDetails.value = data;
        console.log(saleDetails.value)
        displayModalDetails.value = true;
    }
    const closeModalDetails = () => {
        displayModalDetails.value = false;
    }
    onMounted(() => {
        window.addEventListener("message", (event) => {
            if (event.data === "refresh-payment-students") {
                refreshTable();
            }
        });
    });
</script>

<template>
    <AppLayout title="Documentos">
        <Navigation :routeModule="route('aca_dashboard')" :titleModule="'Académico'"
            :data="[
                {route: route('aca_students_list'), title: 'Estudiantes'},
                {route: route('aca_students_edit', student.id), title: student.person.full_name},
                {title: 'Cuotas pendientes especiales'}
            ]"
        />
        <div class="mt-5">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <h2 class="text-xl">Cuotas pendientes especiales</h2>
                <div class="flex sm:flex-row flex-col sm:items-center sm:gap-3 gap-4 w-full sm:w-auto">
                    <div class="flex gap-3">
                        <div v-can="'aca_estudiante_listado'">
                            <Link :href="route('aca_students_list')" class="ml-2 inline-block px-6 py-2.5 bg-green-500 text-white font-medium text-xs leading-tight uppercase rounded shadow-md hover:bg-green-600 hover:shadow-lg focus:bg-green-600 focus:shadow-lg focus:outline-none focus:ring-0 active:bg-green-700 active:shadow-lg transition duration-150 ease-in-out">Ir al Listado</Link>
                        </div>

                    </div>
                </div>
            </div>
            <div class="panel pb-1.5 mt-6">

                <DataTable ref="documentTable" :options="options" :ajax="route('aca_student_space_sales_list_table', student.person.id)" :columns="columns">
                    <template #action="props">
                        <div class="flex gap-4 items-center justify-center">
                            <div class="dropdown">
                                <Popper :placement="'bottom-start'" offsetDistance="0" class="align-middle">
                                    <button type="button" class="btn btn-outline-primary px-2 py-2 dropdown-toggle">
                                        <font-awesome-icon :icon="faGears" />
                                    </button>
                                    <template #content="{ close }">
                                    <ul @click="close()" class="whitespace-nowrap">
                                        <li>
                                            <a @click="openModalDetails(props.rowData)" href="javascript:;">Ver detalles</a>
                                        </li>
                                        <li>
                                            <a @click="openModalDocuments(props.rowData)" href="javascript:;" class="text-red-600 hover:text-red-800">Ver Documentos de venta</a>
                                        </li>
                                        <li>
                                            <a @click="openDialogCreateFeeDocument(props.rowData)" href="javascript:;" class="text-blue-600 underline hover:text-blue-800 visited:text-purple-700" >Registrar pago</a>
                                        </li>
                                    </ul>
                                    </template>
                                </Popper>
                            </div>
                        </div>
                    </template>
                    <template #created="props">
                        {{ formatDate(props.rowData.created_at) }}
                    </template>
                    <template #updated="props">
                        {{ formatDate(props.rowData.updated_at) }}
                    </template>
                    <template #status="props">
                        <div>
                            <span v-if="props.rowData.status == 1" class="bg-yellow-100 text-yellow-800 text-xs font-medium px-2.5 py-0.5 rounded dark:bg-gray-700 dark:text-yellow-300 border border-yellow-300">Activa</span>
                            <span v-else-if="props.rowData.status == 3" class="bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded dark:bg-gray-700 dark:text-red-400 border border-red-400">Anulado</span>
                        </div>
                    </template>
                </DataTable>

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
        <ModalLargeXX :onClose="closeModalDocuments" :show="displayModalDocument" :icon="'/img/papel.png'" >
            <template #title>Documentos de venta</template>
            <template #message>Lista de pago realizados</template>
            <template #content>
                <div>
                    <table>
                        <thead>
                            <tr>
                                <th class="text-center">Acciones</th>
                                <th>Nmr. Documento</th>
                                <th>Fecha Registrado</th>
                                <th>Fecha Emitido</th>
                                <th>Total</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template v-for="document in dataDocuments">
                                <tr>
                                    <td>
                                        <div class="flex gap-4 items-center justify-center">
                                            <button @click="downloadDocument(document.id,document.invoice_type_doc,'PDF')" type="button" class="btn btn-sm btn-outline-primary uppercase">Imprimir A4</button>
                                            <template v-if="document.invoice_status == 'Pendiente' && document.invoice_type_doc != '03'">
                                                <button @click="sendSunatDocument(document)" v-can="'invo_documento_envio_sunat'" type="button" class="btn btn-sm btn-outline-success uppercase">Enviar a sunat</button>
                                            </template>
                                            <button @click="sendEmailDocument(document)" type="button" class="btn btn-sm btn-outline-info uppercase">Enviar correo al cliente</button>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <h6 class="font-semibold" :class="document.status == 3 ? 'line-through': ''" >
                                                {{ document.invoice_serie }}-{{ document.invoice_correlative }}
                                            </h6>
                                            <span v-if="document.invoice_response_description" class="block text-xs">
                                                <code v-if="document.invoice_response_code != 0">
                                                    Código: {{ document.invoice_response_code }}
                                                </code>
                                                <code>
                                                    Descripción: {{ document.invoice_response_description }}
                                                </code>
                                            </span>
                                        </div>
                                        <div v-if="document.status == 3 && document.note">
                                            <h6 class="font-semibold" >
                                                NOTA DE {{ document.note.invoice_type_doc == '07' ? 'CRÉDITO': 'DÉBITO' }}: {{ document.note.invoice_serie }}-{{ document.note.invoice_correlative }}
                                            </h6>
                                            <p  class="text-xs font-black text-danger">
                                                <template v-if="document.note.invoice_type_doc == '07'" class="text-xs font-black text-danger">MOTIVO: {{ findObjectById(creditNoteType, document.note.note_type_operation_id)?.description }}</template>
                                                <template v-if="document.note.invoice_type_doc == '08'" class="text-xs font-black text-danger">MOTIVO: {{ findObjectById(debitNoteType, document.note.note_type_operation_id)?.description }}</template>
                                            </p>
                                        </div>
                                    </td>
                                    <td>
                                        {{ formatDate(document.created_at) }}
                                    </td>
                                    <td>
                                        {{ document.invoice_broadcast_date }}
                                    </td>
                                    <td>
                                        {{ document.overall_total }}
                                    </td>
                                    <td>
                                        <div>
                                            <span v-if="document.status == 1" class="bg-yellow-100 text-yellow-800 text-xs font-medium px-2.5 py-0.5 rounded dark:bg-gray-700 dark:text-yellow-300 border border-yellow-300">Activa</span>
                                            <span v-else-if="document.status == 3" class="bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded dark:bg-gray-700 dark:text-red-400 border border-red-400">Anulado</span>
                                        </div>
                                        <span v-if="document.invoice_status">
                                            <small>Estado Sunat:</small>
                                            {{ document.invoice_status }}
                                        </span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </template>
        </ModalLargeXX>
    </AppLayout>
</template>

