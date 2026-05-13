<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import { useForm } from '@inertiajs/vue3';
    import Pagination from '@/Components/Pagination.vue';
    import Keypad from '@/Components/Keypad.vue';
    import PrimaryButton from '@/Components/PrimaryButton.vue';
    import { ref, onMounted, computed } from 'vue';
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

    const allSelected = computed(() => {
        return documents.value.length > 0 && documents.value.every(doc => doc.selected);
    });

    const toggleAll = () => {
        const newValue = !allSelected.value;
        documents.value.forEach(doc => {
            doc.selected = newValue;
        });
    };

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
    const alreadySentWarning = ref([]);
    const showAlreadySentWarning = ref(false);

    const searchDocumentEarring = () => {
        displaySearchLoading.value = true;
        axios.get(route('salesummaries_search_date', searchDate.value)).then((res) => {
            if (res.data.success) {
                alreadySentWarning.value = [];

                documents.value = res.data.documents.map(doc => {
                    if (doc.already_sent) {
                        alreadySentWarning.value.push(doc.invoice_serie + '-' + doc.invoice_correlative + ' (ya enviado ' + doc.summary_count + ' vez' + (doc.summary_count > 1 ? 'es' : '') + ')');
                        return { ...doc, selected: false };
                    }
                    return { ...doc, selected: true };
                });

                showAlreadySentWarning.value = alreadySentWarning.value.length > 0;
            } else {
                documents.value = [];
                showAlreadySentWarning.value = false;
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

        const selectedDocuments = documents.value.filter(doc => doc.selected);

        if (selectedDocuments.length === 0) {
            Swal.fire({
                title: 'Advertencia',
                text: 'Debes seleccionar al menos un documento',
                icon: 'warning',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
            displaySaveLoading.value = false;
            return;
        }

        let data = {
            documents: selectedDocuments,
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
                documents.value = [];
                getCurrentDate();
                displayModalCreateSummary.value = false;
                router.visit(route('salesummaries_list'), {
                    replace: false,
                    preserveState: true,
                    preserveScroll: true,
                });
            } else if (res.data.is_sunat_unavailable) {
                Swal.fire({
                    title: 'SUNAT no disponible',
                    html: 'Los servidores de SUNAT están saturados temporalmente. El sistema reintentará el envío automáticamente en unos minutos.<br><br><b>Código:</b> '+ (res.data.code || 'N/A') + '<br><b>Descripción:</b> ' + (res.data.message || 'Sin detalles'),
                    icon: 'warning',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
                documents.value = [];
                getCurrentDate();
                displayModalCreateSummary.value = false;
                router.visit(route('salesummaries_list'), {
                    replace: false,
                    preserveState: true,
                    preserveScroll: true,
                });
            } else if (res.data.is_already_sent) {
                Swal.fire({
                    title: 'Ya enviado anteriormente',
                    html: 'El comprobante ya había sido recibido por SUNAT.<br><br><b>Código:</b> '+ (res.data.code || 'N/A') + '<br><b>Descripción:</b> ' + (res.data.message || 'Sin detalles'),
                    icon: 'info',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
                documents.value = [];
                getCurrentDate();
                displayModalCreateSummary.value = false;
                router.visit(route('salesummaries_list'), {
                    replace: false,
                    preserveState: true,
                    preserveScroll: true,
                });
            } else {
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
            } else if (res.data.is_sunat_unavailable) {
                Swal.fire({
                    title: 'SUNAT no disponible',
                    html: 'Los servidores de SUNAT están saturados temporalmente. El sistema reintentará el envío automáticamente en unos minutos. No es necesario que haga nada.<br><br><b>Código:</b> '+ (res.data.code || 'N/A') + '<br><b>Descripción:</b> ' + (res.data.message || 'Sin detalles'),
                    icon: 'warning',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
                router.visit(route('salesummaries_list'), {
                    replace: false,
                    preserveState: true,
                    preserveScroll: true,
                });
            } else if (res.data.is_already_sent) {
                Swal.fire({
                    title: 'Ya enviado anteriormente',
                    html: 'El comprobante ya había sido recibido por SUNAT.<br><br><b>Código:</b> '+ (res.data.code || 'N/A') + '<br><b>Descripción:</b> ' + (res.data.message || 'Sin detalles'),
                    icon: 'info',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
                router.visit(route('salesummaries_list'), {
                    replace: false,
                    preserveState: true,
                    preserveScroll: true,
                });
            } else {
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

    const displayModalSunatCodes = ref(false);
    const openModalSunatCodes = () => { displayModalSunatCodes.value = true; }
    const closeModalSunatCodes = () => { displayModalSunatCodes.value = false; }

    const sunatCodes = [
        { code: '0', description: 'Aceptado', type: 'success' },
        { code: '0109', description: 'Error de autenticaci\u00f3n - SUNAT no disponible temporalmente', type: 'warning' },
        { code: '0127', description: 'El ticket de consulta no existe o ha expirado', type: 'error' },
        { code: '2223', description: 'El archivo ya fue presentado anteriormente ante SUNAT', type: 'warning' },
        { code: '2325', description: 'Aceptado con observaciones', type: 'warning' },
        { code: '2335', description: 'El comprobante no cumple con el formato establecido', type: 'error' },
        { code: '2016', description: 'El tipo de documento no es v\u00e1lido', type: 'error' },
        { code: '2024', description: 'El n\u00famero de comprobante ya existe', type: 'error' },
        { code: '2033', description: 'El monto total no coincide con los montos informados', type: 'error' },
        { code: '2045', description: 'La fecha de emisi\u00f3n no corresponde al periodo declarado', type: 'error' },
        { code: '2062', description: 'El RUC del emisor no est\u00e1 autorizado', type: 'error' },
        { code: '2064', description: 'El comprobante fue rechazado por SUNAT', type: 'error' },
        { code: '130-139', description: 'Errores de conexi\u00f3n con SUNAT (timeout, servidor ca\u00eddo, etc.)', type: 'error' },
        { code: '-1', description: 'Timeout - SUNAT no respondi\u00f3 a tiempo', type: 'error' },
        { code: '1033', description: 'Error interno del servicio SUNAT', type: 'error' },
    ];

    const retrySummary = (id, index) => {
        let btnCheck = document.getElementById('btn-retry-summary'+ index);
        let spCheck = document.getElementById('sp-retry-summary'+ index);
        btnCheck.style.width = '120';
        btnCheck.style.cursor = 'not-allowed';
        spCheck.style.display = 'block';
        btnCheck.style.opacity = '0.35';

        axios.get(route('salesummaries_retry', id)).then((res) => {
            if (res.data.success || res.data.is_already_sent) {
                Swal.fire({
                    title: 'Informaci\u00f3n Importante',
                    text: 'El resumen se reenvi\u00f3 correctamente',
                    icon: 'success',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
            } else if (res.data.is_sunat_unavailable) {
                Swal.fire({
                    title: 'SUNAT no disponible',
                    text: 'Los servidores de SUNAT est\u00e1n saturados temporalmente. El sistema reintentar\u00e1 el env\u00edo autom\u00e1ticamente en unos minutos. No es necesario que haga nada.',
                    icon: 'warning',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
            } else {
                Swal.fire({
                    title: 'Error',
                    html: 'Codigo: '+ res.data.code + '<br> Descripcion: ' + res.data.message,
                    icon: 'error',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
            }
            router.visit(route('salesummaries_list'), {
                replace: false,
                preserveState: true,
                preserveScroll: true,
            });
        });
    }
</script>

<template>
    <AppLayout title="Resumen">
        <Navigation :routeModule="route('sales_dashboard')" :titleModule="'Facturación Electrónica'"
            :data="[
                {title: 'Resumen'}
            ]"
        />
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

            <!-- Leyenda de estados -->
            <div class="flex flex-wrap items-center gap-4 px-4 py-2 text-xs border rounded-lg bg-gray-50 dark:bg-zinc-800 dark:border-zinc-600">
                <span class="font-semibold text-gray-700 dark:text-gray-300">Estados:</span>
                <span class="flex items-center gap-1">
                    <span class="inline-block w-3 h-3 rounded-full bg-green-500"></span> Enviado
                </span>
                <span class="flex items-center gap-1">
                    <span class="inline-block w-3 h-3 rounded-full bg-blue-500"></span> Aceptado
                </span>
                <span class="flex items-center gap-1">
                    <span class="inline-block w-3 h-3 rounded-full bg-orange-400"></span> SUNAT no disponible
                </span>
                <span class="flex items-center gap-1">
                    <span class="inline-block w-3 h-3 rounded-full bg-purple-500"></span> Ya enviado
                </span>
                <span class="flex items-center gap-1">
                    <span class="inline-block w-3 h-3 rounded-full bg-red-500"></span> Rechazado
                </span>
                <span class="flex items-center gap-1">
                    <span class="inline-block w-3 h-3 rounded-full bg-gray-400"></span> Registrado
                </span>
                <button @click="openModalSunatCodes" type="button" class="ml-auto btn btn-xs btn-outline-info flex items-center gap-1" v-tippy="{ content: 'Ver c\u00f3digos de respuesta SUNAT', placement: 'bottom' }">
                    <svg class="w-4 h-4" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM216 336h24V272H216c-13.3 0-24-10.7-24-24s10.7-24 24-24h48c13.3 0 24 10.7 24 24v88h8c13.3 0 24 10.7 24 24s-10.7 24-24 24H216c-13.3 0-24-10.7-24-24s10.7-24 24-24zm40-208a32 32 0 1 1 0 64 32 32 0 1 1 0-64z"/></svg>
                    Códigos SUNAT
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
                                    <tr :class="summary.status ==='registrado' ? '' : summary.status ==='Rechazado' ? 'text-danger': summary.status ==='Enviado'? 'text-success' : summary.status ==='sunat_disponible' ? 'text-warning' : summary.status ==='fue_enviado' ? 'text-secondary' : 'text-primary'">
                                        <td class="text-center">
                                            <div class="flex space-x-2 items-center justify-center">
                                                <button :id="'btn-check-summary'+index" @click="statusTicket(summary.id,summary.ticket,index)" v-if="summary.status ==='Enviado'" type="button" class="btn btn-info text-sm btn-sm flex">
                                                    <svg :id="'sp-check-summary'+index" style="display: none;" aria-hidden="true" role="status" class="inline w-4 h-4 mr-2 text-gray-200 animate-spin dark:text-gray-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
                                                        <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="#1C64F2"/>
                                                    </svg>
                                                    Consultar
                                                </button>
                                                <button :id="'btn-retry-summary'+index" @click="retrySummary(summary.id,index)" v-if="summary.status ==='sunat_disponible'" type="button" class="btn btn-warning text-sm btn-sm flex">
                                                    <svg :id="'sp-retry-summary'+index" style="display: none;" aria-hidden="true" role="status" class="inline w-4 h-4 mr-2 text-gray-200 animate-spin dark:text-gray-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
                                                        <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="#1C64F2"/>
                                                    </svg>
                                                    Reintentar
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
                                                <span v-else class="block text-xs text-red-500">
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
                                            <span v-if="summary.status ==='sunat_disponible'" class="bg-orange-100 text-orange-800 text-xs font-medium px-2.5 py-0.5 rounded dark:bg-orange-900/50 dark:text-orange-300 border border-orange-300">
                                                SUNAT no disponible
                                            </span>
                                            <span v-else-if="summary.status ==='fue_enviado'" class="bg-purple-100 text-purple-800 text-xs font-medium px-2.5 py-0.5 rounded dark:bg-purple-900/50 dark:text-purple-300 border border-purple-300">
                                                Ya enviado
                                            </span>
                                            <span v-else-if="summary.status ==='Enviado'" class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded dark:bg-green-900/50 dark:text-green-300 border border-green-300">
                                                Enviado
                                            </span>
                                            <span v-else-if="summary.status ==='Aceptado'" class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded dark:bg-blue-900/50 dark:text-blue-300 border border-blue-300">
                                                Aceptado
                                            </span>
                                            <span v-else-if="summary.status ==='Rechazado'" class="bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded dark:bg-red-900/50 dark:text-red-300 border border-red-300">
                                                Rechazado
                                            </span>
                                            <span v-else class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded dark:bg-gray-700 dark:text-gray-300 border border-gray-300">
                                                {{ summary.status }}
                                            </span>
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
                <div v-if="showAlreadySentWarning" class="mb-3 p-3 bg-amber-50 border border-amber-300 rounded-lg">
                    <div class="flex items-center">
                        <svg class="flex-shrink-0 w-8 h-8 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l.818 1.44c.841 1.49.332 3.467-1.165 3.467h1.475c1.497 0 2.45 1.577 1.729 2.851l-.72 1.27c-.565.998-1.563 1.691-2.691 1.691H7.277c-1.128 0-2.126-.693-2.691-1.691l-.72-1.27c-.721-1.274.232-2.851 1.729-2.851h1.475c-1.497 0-2.006-1.977-1.165-3.467l.818-1.44zM9 6a1 1 0 11-2 0 1 1 0 012 0zm-1 5a1 1 0 011 1v3a1 1 0 11-2 0v-3a1 1 0 011-1z" clip-rule="evenodd"/>
                        </svg>
                        <div class="ml-3">
                            <p class="text-sm text-amber-800 font-medium">
                                Algunos documentos ya fueron enviados anteriormente
                            </p>
                            <ul class="mt-1 text-xs text-amber-700 list-disc list-inside max-h-24 overflow-y-auto">
                                <li v-for="(doc, idx) in alreadySentWarning" :key="idx">
                                    {{ doc }}
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="w-full">
                        <thead class="uppercase text-sm">
                            <tr>
                                <th class="text-center w-10">
                                    <input type="checkbox"
                                        :checked="allSelected"
                                        @change="toggleAll"
                                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
                                    >
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
                                <th scope="row" class="text-center">
                                    <input type="checkbox"
                                        v-model="item.selected"
                                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
                                    >
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

        <!-- Modal C&oacute;digos SUNAT -->
        <ModalLarge :show="displayModalSunatCodes" :onClose="closeModalSunatCodes" :icon="'/img/lupa-documento.png'">
            <template #title>C&oacute;digos de respuesta SUNAT</template>
            <template #message>Significado de los c&oacute;digos de respuesta de SUNAT</template>
            <template #content>
                <div class="max-h-96 overflow-y-auto">
                    <table class="w-full">
                        <thead>
                            <tr>
                                <th class="w-24">C&oacute;digo</th>
                                <th>Descripci&oacute;n</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in sunatCodes" :key="item.code" class="text-sm">
                                <td>
                                    <span :class="item.type === 'success' ? 'text-success' : item.type === 'warning' ? 'text-warning' : 'text-danger'" class="font-semibold">
                                        {{ item.code }}
                                    </span>
                                </td>
                                <td>{{ item.description }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </template>
        </ModalLarge>
    </AppLayout>
</template>
