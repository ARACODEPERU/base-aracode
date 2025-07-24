<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import { useForm } from '@inertiajs/vue3';
    import Pagination from '@/Components/Pagination.vue';
    import Keypad from '@/Components/Keypad.vue';
    import PrimaryButton from '@/Components/PrimaryButton.vue';
    import { ref, onMounted } from 'vue';
    import ModalLargeX from '@/Components/ModalLargeX.vue';
    import Swal from "sweetalert2";
    import { Link, router } from '@inertiajs/vue3';
    import IconBell from "@/Components/vristo/icon/icon-bell.vue";
    import IconX from "@/Components/vristo/icon/icon-x.vue";
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import iconFileCode from '@/Components/vristo/icon/icon-file-code.vue';
    import iconZipFile from '@/Components/vristo/icon/icon-zip-file.vue';
    import ModalLarge from '@/Components/ModalLarge.vue';

    const props = defineProps({
        summaries: {
            type: Object,
            default: () => ({}),
        },
        filters: {
            type: Object,
            default: () => ({}),
        },
    });

    const form = useForm({
        search: props.filters.search,
    });

    const displayModalCreateSummary = ref(false);

    const closeModalCreateSummary = () => {
        displayModalCreateSummary.value = false;
    }
    const openModalCreateSummary = () => {
        displayModalCreateSummary.value = true;
    }

    const documents = ref([]);
    const searchDate = ref({});

    const getCurrentDate = () => {
        const currentDate = new Date();
        const year = currentDate.getFullYear();
        const month = String(currentDate.getMonth() + 1).padStart(2, '0'); // Los meses son base 0, por eso se suma 1
        const day = String(currentDate.getDate()).padStart(2, '0');

        // Formato de fecha: YYYY-MM-DD
        searchDate.value = `${year}-${month}-${day}`;
        //formDocument.date_end = `${year}-${month}-${day}`;
    };

    onMounted(() => {
        getCurrentDate();
    });

    const displaySearchLoading = ref(false);
    const displaySaveLoading = ref(false);
    const searchDocumentEarring = () => {
        displaySearchLoading.value = true;
        axios.get(route('salesummaries_search_date', searchDate.value)).then((res) => {
            if (res.data.success) {
                documents.value = res.data.documents
            }else{
                Swal.fire({
                    title: 'Información Importante',
                    text: 'No existen documentos pendientes para la fecha indicada',
                    icon: 'info',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
            }
            displaySearchLoading.value = false;
        });
    }
    const saveSummary = () => {
        displaySaveLoading.value = true;
        let data = {
            documents: documents.value,
            generation_date: searchDate.value
        }
        axios.post(route('salesummaries_store_date'),data).then((res) => {
            if (res.data.success) {
                Swal.fire({
                    title: 'Información Importante',
                    text: 'El resumen se creó y envió correctamente',
                    icon: 'success',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
                router.visit(route('salesummaries_list'), {
                    replace: false,
                    preserveState: true,
                    preserveScroll: true,
                });
            }else{
                Swal.fire({
                    title: 'Error',
                    html: 'Codigo: '+ res.data.code + '<br> Descripcion: ' + res.data.message ,
                    icon: 'error',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
            }
            displaySaveLoading.value = false;
        }).then(() => {
            router.visit(route('salesummaries_list'), {
                replace: false,
                preserveState: true,
                preserveScroll: true,
            });
        });
    }


    const statusTicket = (id,ticket,index) => {

        let btnCheck = document.getElementById('btn-check-summary'+ index);
        let spCheck = document.getElementById('sp-check-summary'+ index);
        let spBtn= document.getElementById('sp-btn-summary'+ index);

        btnCheck.style.width = '120';
        btnCheck.style.cursor = 'not-allowed';
        spCheck.style.display = 'block';
        btnCheck.style.opacity = '0.35';
        axios.get(route('salesummaries_store_check',[id,ticket])).then((res) => {
            if (res.data.success) {
                Swal.fire({
                    title: 'Información Importante',
                    text: res.data.message,
                    icon: 'success',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
                router.visit(route('salesummaries_list'), {
                    replace: false,
                    preserveState: true,
                    preserveScroll: true,
                });
            }else{
                Swal.fire({
                    title: 'Error',
                    html: 'Codigo: '+ res.data.code + '<br> Descripcion: ' + res.data.message ,
                    icon: 'error',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
                router.visit(route('salesummaries_list'), {
                    replace: false,
                    preserveState: true,
                    preserveScroll: true,
                });
            }
        });
    }
    const deleteSummary = (id,index) => {

        let btnCheck = document.getElementById('btn-delete-summary'+ index);
        let spCheck = document.getElementById('sp-delete-summary'+ index);
        btnCheck.style.width = '120';
        btnCheck.style.cursor = 'not-allowed';
        spCheck.style.display = 'block';
        btnCheck.style.opacity = '0.35';
        axios.get(route('salesummaries_destroy',id)).then((res) => {
            if (res.data.success) {
                Swal.fire({
                    title: 'Información Importante',
                    text: 'Se elimino correctamente',
                    icon: 'success',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
                router.visit(route('salesummaries_list'), {
                    replace: false,
                    preserveState: true,
                    preserveScroll: true,
                });
            }
        });
    }

    const  openDownloadTap = (id,type) => {
        window.open(route('salesummaries_download',[id,type]), "_blank");
    }

    const displayModalDetailDocuments = ref(false);
    const detailDocuments = ref([]);
    const openModalDetailsDocuments = (summary) => {
        detailDocuments.value = summary;
        displayModalDetailDocuments.value = true;
    }
    const cloceModalDetailsDocuments = () => {
        displayModalDetailDocuments.value = false;
    }
</script>

<template>
    <AppLayout title="Resumen">
        <Navigation :routeModule="route('sales_dashboard')" :titleModule="'Facturación Electrónica'">
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Resumen </span>
            </li>
        </Navigation>
        <div class="mt-5 space-y-8">
            <div class="relative flex items-center border p-3.5 rounded text-success bg-success-light border-success ltr:border-l-[64px] rtl:border-r-[64px] dark:bg-success-dark-light">
                <span class="absolute ltr:-left-11 rtl:-right-11 inset-y-0 text-white w-6 h-6 m-auto">
                    <icon-bell class="w-6 h-6"/>
                </span>
                <span class="ltr:pr-2 rtl:pl-2"><strong class="ltr:mr-1 rtl:ml-1">Resumen diario:</strong>Para comunicar las boletas de ventas emitidas o anuladas, así como las notas de crédito/débito releacionadas, necesita hacerlo mediante un resumen diario. A diferencia del envío de una factura, donde la respuesta es inmediata, en este documento debemos hacer un consulta adicional para conocer su estado utilizando el numero de ticket.</span>
                <button type="button" class="ltr:ml-auto rtl:mr-auto hover:opacity-80">
                    <icon-x />
                </button>
            </div>
            <!-- ====== Table Section Start -->
            <div class="flex flex-col gap-10">
                <!-- ====== Table One Start -->
                <div class="panel p-0">
                    <div class="w-full p-4">
                        <div class="grid grid-cols-3">
                            <div class="col-span-3 sm:col-span-1">
                                <form @submit.prevent="form.get(route('salesummaries_list'))" class="flex items-center gap-4">
                                    <input v-model="form.search" type="date" id="table-search-users" class="form-input pl-12" placeholder="Buscar por fecha">
                                    <button type="submit" class="btn btn-primary" v-tippy="{content:'Buscar',placement:'bottom'}">
                                        <svg v-if="form.processing" aria-hidden="true" role="status" class="inline w-4 h-4 text-gray-200 animate-spin dark:text-gray-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
                                            <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="#1C64F2"/>
                                        </svg>
                                        <svg v-else class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                                        </svg>
                                    </button>
                                    <button @click="form.search = null;form.get(route('salesummaries_list'))" v-tippy="{content:'Mostrar Todos',placement:'bottom'}" type="button" class="btn btn-success">
                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" fill="currentColor">
                                            <path d="M40 48C26.7 48 16 58.7 16 72l0 48c0 13.3 10.7 24 24 24l48 0c13.3 0 24-10.7 24-24l0-48c0-13.3-10.7-24-24-24L40 48zM192 64c-17.7 0-32 14.3-32 32s14.3 32 32 32l288 0c17.7 0 32-14.3 32-32s-14.3-32-32-32L192 64zm0 160c-17.7 0-32 14.3-32 32s14.3 32 32 32l288 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-288 0zm0 160c-17.7 0-32 14.3-32 32s14.3 32 32 32l288 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-288 0zM16 232l0 48c0 13.3 10.7 24 24 24l48 0c13.3 0 24-10.7 24-24l0-48c0-13.3-10.7-24-24-24l-48 0c-13.3 0-24 10.7-24 24zM40 368c-13.3 0-24 10.7-24 24l0 48c0 13.3 10.7 24 24 24l48 0c13.3 0 24-10.7 24-24l0-48c0-13.3-10.7-24-24-24l-48 0z"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                            <div class="col-span-3 sm:col-span-2">
                                <Keypad>
                                    <template #botones>
                                        <PrimaryButton
                                            class="mr-2"
                                            @click="openModalCreateSummary()"
                                        >
                                            Crear Resumen
                                        </PrimaryButton>
                                    </template>
                                </Keypad>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th class="text-center" >
                                        Acciones
                                    </th>
                                    <th>
                                        Nmr. Documento
                                    </th>
                                    <th>
                                        Fecha
                                    </th>
                                    <th>
                                        Estado
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-for="(summary, index) in summaries.data" :key="summary.id">
                                    <tr :class="summary.status ==='registrado' ? '' : summary.status ==='Rechazado' ? 'text-danger': summary.status ==='Enviado'? 'text-success' : 'text-primary'">
                                        <td class="text-center">
                                            <div class="flex space-x-2 items-center justify-center">
                                                <button :id="'btn-check-summary'+index" @click="statusTicket(summary.id,summary.ticket,index)" v-if="summary.status ==='Enviado'" type="button" class="btn btn-info text-sm btn-sm flex">
                                                    <svg :id="'sp-check-summary'+index" style="display: none;" aria-hidden="true" role="status" class="inline w-4 h-4 mr-2 text-gray-200 animate-spin dark:text-gray-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
                                                        <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="#1C64F2"/>
                                                    </svg>
                                                    Consultar
                                                </button>
                                                <button :id="'btn-delete-summary'+index" @click="deleteSummary(summary.id,index)" v-if="summary.status ==='Rechazado'" type="button" class="btn btn-danger text-sm btn-sm flex">
                                                    <svg :id="'sp-delete-summary'+index" style="display: none;" aria-hidden="true" role="status" class="inline w-4 h-4 mr-3 text-gray-200 animate-spin dark:text-gray-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
                                                        <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="#1C64F2"/>
                                                    </svg>
                                                    Eliminar
                                                </button>
                                                <button v-if="summary.status ==='Aceptado'"
                                                    @click="openDownloadTap(summary.id,'XML')"
                                                    type="button" class="text-success"
                                                    v-tippy="{ content: 'Descargar xml', placement: 'bottom'}"
                                                >
                                                    <icon-file-code class="w-5 h-5" />

                                                </button>
                                                <button v-if="summary.status ==='Aceptado'"
                                                    @click="openDownloadTap(summary.id,'CDR')"
                                                    type="button"
                                                    class="text-warning"
                                                    v-tippy="{ content: 'Descargar cdr', placement: 'bottom'}"
                                                >
                                                    <icon-zip-file class="w-5 h-5" />

                                                </button>
                                                <button v-if="summary.status ==='Aceptado'"
                                                    @click="openModalDetailsDocuments(summary)"
                                                    type="button"
                                                    class="text-info"
                                                    v-tippy="{ content: 'Lista de Boletas', placement: 'bottom'}"
                                                >
                                                    <svg class="w-5 h-5" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512">
                                                        <path d="M64 0C28.7 0 0 28.7 0 64L0 448c0 35.3 28.7 64 64 64l256 0c35.3 0 64-28.7 64-64l0-288-128 0c-17.7 0-32-14.3-32-32L224 0 64 0zM256 0l0 128 128 0L256 0zM80 64l64 0c8.8 0 16 7.2 16 16s-7.2 16-16 16L80 96c-8.8 0-16-7.2-16-16s7.2-16 16-16zm0 64l64 0c8.8 0 16 7.2 16 16s-7.2 16-16 16l-64 0c-8.8 0-16-7.2-16-16s7.2-16 16-16zm16 96l192 0c17.7 0 32 14.3 32 32l0 64c0 17.7-14.3 32-32 32L96 352c-17.7 0-32-14.3-32-32l0-64c0-17.7 14.3-32 32-32zm0 32l0 64 192 0 0-64L96 256zM240 416l64 0c8.8 0 16 7.2 16 16s-7.2 16-16 16l-64 0c-8.8 0-16-7.2-16-16s7.2-16 16-16z"/>
                                                    </svg>

                                                </button>
                                            </div>
                                        </td>
                                        <td>
                                            <h6 class="font-semibold">
                                                {{ summary.summary_name }}
                                                 <span v-if="summary.status == 'Rechazado' || summary.status == 'Aceptado'" class="block text-xs">
                                                    <code v-if="summary.response_code != 0">
                                                    Código: {{ summary.response_code }}
                                                    </code>
                                                    <code>
                                                        Descripción: {{ summary.response_description }}
                                                    </code>
                                                 </span>
                                            </h6>
                                            <p v-if="summary.notes" class="text-xs">{{ summary.notes }}</p>
                                            <p v-if="summary.reason" class="text-xs font-black text-danger">BOLETA ANULADA POR: {{ summary.reason }}</p>
                                        </td>
                                        <td>
                                            {{ summary.summary_date }}
                                        </td>
                                        <td>
                                            <small>Estado Sunat:</small>
                                            {{ summary.status }}
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                        <Pagination :data="summaries" />
                    </div>
                </div>
            </div>
        </div>
        <ModalLargeX
            :show="displayModalCreateSummary"
            :onClose="closeModalCreateSummary"
            :icon="'/img/papel.png'"
        >
            <template #title>
                Crear Resumen
            </template>
            <template #message>
                Buscar por Fecha de emisión documentos que faltantes de envio
            </template>
            <template #content>
                <div class="grid grid-cols-6">
                    <div class="col-span-6 sm:col-span-2">
                        <form class="flex items-center mb-4">
                            <div>
                                <input v-model="searchDate" type="date" id="simple-search" class="form-input" placeholder="Search branch name..." required>
                            </div>
                            <button @click="searchDocumentEarring" type="button" class="p-2.5 ml-2 text-sm font-medium text-white bg-blue-700 rounded-lg border border-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                                <svg v-if="displaySearchLoading" aria-hidden="true" role="status" class="inline w-4 h-4 text-gray-200 animate-spin dark:text-gray-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
                                    <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="#1C64F2"/>
                                </svg>
                                <svg v-else class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                                </svg>
                                <span class="sr-only">Search</span>
                            </button>
                        </form>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="w-full">
                        <thead class="uppercase text-sm">
                            <tr>
                                <th>

                                </th>
                                <th scope="col">
                                    Tipo documento
                                </th>
                                <th class="text-center">
                                    Serie y numero
                                </th>
                                <th scope="col" >
                                    Fecha de emisión
                                </th>
                                <th >
                                    Cliente
                                </th>
                                <th>
                                    total
                                </th>
                                <th>
                                    estado
                                </th>
                              </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, ko) in documents" class="text-sm">
                                <th scope="row">

                                </th>
                                <td>
                                   {{ item.type_description }}
                                </td>
                                <td class="text-center">
                                    {{ item.invoice_serie }}-{{ item.number }}
                                </td>

                                <td class="text-center">
                                    {{ item.invoice_broadcast_date }}
                                </td>
                                <td class="text-left">
                                    {{ item.client_number }}-{{ item.client_rzn_social }}
                                </td>
                                <td class="text-right">
                                    {{ item.invoice_mto_imp_sale }}
                                </td>
                                <td class="text-center">
                                    <span v-if="item.status == 1" class="bg-yellow-100 text-yellow-800 text-xs font-medium mr-2 px-2.5 py-0.5 rounded dark:bg-gray-700 dark:text-yellow-300 border border-yellow-300">Registrado</span>
                                    <span v-else class="bg-red-100 text-red-800 text-xs font-medium mr-2 px-2.5 py-0.5 rounded dark:bg-gray-700 dark:text-red-400 border border-red-400">Anulado</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </template>
            <template #buttons>
                <PrimaryButton class="mr-2"
                    :class="{ 'opacity-25': displaySaveLoading }" :disabled="displaySaveLoading"
                    @click="saveSummary()"
                    >
                    <svg v-show="displaySaveLoading" aria-hidden="true" role="status" class="inline w-4 h-4 mr-3 text-gray-200 animate-spin dark:text-gray-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
                        <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="#1C64F2"/>
                    </svg>
                    Guardar
                </PrimaryButton>
            </template>
        </ModalLargeX>
        <ModalLarge :show="displayModalDetailDocuments" :onClose="cloceModalDetailsDocuments" :icon="'/img/lupa-documento.png'">
            <template #title>{{ detailDocuments.summary_name }}</template>
            <template #message>Lista de boletas</template>
            <template #content>
                <div class="max-h-96 overflow-y-auto">
                    <table>
                        <thead>
                            <tr>
                                <th>Serie - Numero</th>
                                <th>Cliente</th>
                                <th class="text-center">total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template v-for="item in detailDocuments.details">
                                <tr>
                                    <td>{{ item.document.invoice_serie }}-{{ item.document.invoice_correlative }}</td>
                                    <td>{{ item.document.client_rzn_social }}</td>
                                    <td class="text-right">{{ item.document.overall_total }}</td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </template>
        </ModalLarge>
    </AppLayout>
</template>
