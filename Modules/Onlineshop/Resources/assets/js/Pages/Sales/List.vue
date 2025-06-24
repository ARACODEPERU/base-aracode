<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import Keypad from '@/Components/Keypad.vue';
    import Pagination from '@/Components/Pagination.vue';
    import ModalLarge from '@/Components/ModalLarge.vue';
    import Swal from "sweetalert2";
    import { useForm, Link, usePage, router } from '@inertiajs/vue3';
    import { faMagnifyingGlass, faRotate } from "@fortawesome/free-solid-svg-icons";
    import { ref, watch, onMounted, nextTick } from "vue";
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import { TransitionRoot, TransitionChild, Dialog, DialogPanel, DialogOverlay } from '@headlessui/vue';
    import textWriting from '@/Components/loader/text-writing.vue';

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

    const contador = 0.5;

    const appCodeUnique = import.meta.env.VITE_APP_CODE ?? 'ARACODE';
    const channelListenOnli = "onli-email-status-" + appCodeUnique + '-' + usePage().props.auth.user.id;

    const emailStatus = ref([])
    const porsentaje = ref(0);
    const progressSend = ref(0);
    const loadingSend = ref(false);
    const displayModalSendDetails = ref(false);
    const scrollContainer = ref(null);

    const emailForm = useForm({
        csrfToken: null,
        apiBackenStepOne: route('aca_create_students_tickets'),
        apiBackenStepTwo: route('aca_send_email_student_boleta'),
        channelListen: channelListenOnli,
        documenttypeId: 2,
        serie: null,
        enline: true,
        local: 1,
        ventas: [],
        userId: usePage().props.auth.user.id
    });


    const sendOnliEmails = async () => {
        emailStatus.value = [];
        porsentaje.value = 0;
        progressSend.value = 0;

        // Validar si el array 'para' está vacío
        if (emailForm.ventas.length === 0) {
            showMessage("Debe agregar al menos un destinatario.",'info');
            return; // Detiene la ejecución si no hay destinatarios
        }

        loadingSend.value = true;

        displayModalSendDetails.value = true;
        const url = import.meta.env.VITE_SOCKET_IO_SERVER + '/api/onlineshop/send-mails-tickets'; // Cambia por la URL de tu API
        let emailstotal = emailForm.ventas.length;

        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        emailForm.csrfToken = csrfToken;
        porsentaje.value = (contador / parseInt(emailstotal)) * 100;

         axios.post(url,emailForm,{
            headers: {
                'Content-Type': 'application/json'
            },
            timeout: 0,
        }).then(() => {
            router.visit(route('onlineshop_sales'), {
                method: 'get',
                replace: false,
                preserveState: true,
                preserveScroll: false,
            });

            emailForm.csrfToken = null;

            emailForm.ventas = [];

        })
        .finally(()=>{
            loadingSend.value = false;
        });

    }

    const closeModalSendDetails = () => {
        displayModalSendDetails.value = false
    }

    const selectAll = ref(false);

    // Función para verificar si un item ya está seleccionado
    const isSelected = (item) => {
        return emailForm.ventas.some((venta) => venta.id === item.id);
    };

    // Función para agregar o eliminar un item del array
    const toggleItem = (item) => {
        if (isSelected(item)) {
            // Si el item ya está seleccionado, lo eliminamos
            emailForm.ventas = emailForm.ventas.filter((venta) => venta.id !== item.id);
        } else {
            // Si el item no está seleccionado, lo agregamos
            emailForm.ventas.push(item);
        }
    };

    // Función para seleccionar o deseleccionar todos los items
    const toggleSelectAll = () => {
        if (selectAll.value) {
            // Agregar todos los items al array
            emailForm.ventas = [...props.sales.data];
        } else {
            // Limpiar el array
            emailForm.ventas = [];
        }
    };

    // Watcher para sincronizar el estado de "Seleccionar todos"
    watch(emailForm.ventas, (newVal) => {
        selectAll.value = newVal.length === props.sales.data.length;
    });

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

    const loadingStep = ref(1);

    onMounted(() => {
        window.socketIo.on(channelListenOnli, (status) => {
            emailStatus.value.push(status);
            loadingStep.value = status.step;
            progressSend.value = parseFloat(progressSend.value) + parseFloat(porsentaje.value)
            nextTick(() => {
                if (scrollContainer.value) {
                    scrollContainer.value.scrollTop = scrollContainer.value.scrollHeight;
                }
            });
        });
    });
</script>

<template>
    <AppLayout title="Resumen">
        <Navigation :routeModule="route('onlineshop_dashboard')" :titleModule="'Ventas en línea'">
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Pedidos</span>
            </li>
        </Navigation>
        <div class="mt-5">
            <!-- ====== Table Section Start -->
            <div class="flex flex-col gap-10">
                <!-- ====== Table One Start -->
                <div class="panel p-0">
                    <div class="w-full p-4">
                        <div class="grid grid-cols-3">
                            <div class="col-span-3 sm:col-span-1">
                                <form id="form-search-items" @submit.prevent="form.get(route('cms_items_list'))">
                                    <label for="table-search" class="sr-only">Search</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                            <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path></svg>
                                        </div>
                                        <input v-model="form.search" type="text" id="table-search-users" class="block p-2 pl-10 text-sm text-gray-900 border border-gray-300 rounded-lg w-80 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="Buscar por Descripción">
                                    </div>
                                </form>
                            </div>
                            <div class="col-span-3 sm:col-span-2">
                                <Keypad>
                                    <template #botones>
                                        <button v-can="'onli_pedidos_enviar_boletas'" @click="sendOnliEmails" class="btn btn-primary uppercase text-xs">
                                            <svg class="w-4 h-4 mr-2" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512">
                                                <path d="M128 0C110.3 0 96 14.3 96 32l0 192 96 0 0-32c0-35.3 28.7-64 64-64l224 0 0-96c0-17.7-14.3-32-32-32L128 0zM256 160c-17.7 0-32 14.3-32 32l0 32 96 0c35.3 0 64 28.7 64 64l0 128 192 0c17.7 0 32-14.3 32-32l0-192c0-17.7-14.3-32-32-32l-320 0zm240 64l32 0c8.8 0 16 7.2 16 16l0 32c0 8.8-7.2 16-16 16l-32 0c-8.8 0-16-7.2-16-16l0-32c0-8.8 7.2-16 16-16zM64 256c-17.7 0-32 14.3-32 32l0 13L187.1 415.9c1.4 1 3.1 1.6 4.9 1.6s3.5-.6 4.9-1.6L352 301l0-13c0-17.7-14.3-32-32-32L64 256zm288 84.8L216 441.6c-6.9 5.1-15.3 7.9-24 7.9s-17-2.8-24-7.9L32 340.8 32 480c0 17.7 14.3 32 32 32l256 0c17.7 0 32-14.3 32-32l0-139.2z"/>
                                            </svg>
                                            Crear Comprobante Electrónico y Enviar correos
                                        </button>
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
                                        Total
                                    </th>
                                    <th >
                                        Fecha
                                    </th>
                                    <th class=" ">
                                        Comprobante Electrónico
                                        <label class="w-12 h-6 relative">
                                            <input type="checkbox" v-model="selectAll" @change="toggleSelectAll" class="ventas_all absolute w-full h-full opacity-0 z-10 cursor-pointer peer" id="custom_switch_all" />
                                            <span for="ventas_all" class="outline_checkbox bg-icon border-2 border-[#bcc8e0] dark:border-white-dark block h-full rounded-full before:absolute before:left-1 before:bg-[#ebedf2] dark:before:bg-white-dark before:bottom-1 before:w-4 before:h-4 before:rounded-full before:bg-[url(/themes/vristo/images/close.svg)] before:bg-no-repeat before:bg-center peer-checked:before:left-7 peer-checked:before:bg-[url(/themes/vristo/images/checked.svg)] peer-checked:border-primary peer-checked:before:bg-primary before:transition-all before:duration-300"></span>
                                        </label>
                                    </th>
                                    <th >
                                        Estado
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-for="(item, index) in sales.data" :key="item.id">
                                    <tr >
                                        <td class="text-center">
                                            <button @click="openModalDetails(item)" type="button" title="ver detalles" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-full text-sm p-2.5 text-center inline-flex items-center me-2 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                                                <font-awesome-icon :icon="faMagnifyingGlass" />
                                            </button>
                                            <!-- <button @click="destroyItem(item.id)" type="button" title="Consultar a mercado pago" class="text-white bg-gray-700 hover:bg-gray-800 focus:ring-4 focus:outline-none focus:ring-gray-300 font-medium rounded-full text-sm p-2.5 text-center inline-flex items-center me-2 dark:bg-gray-600 dark:hover:bg-gray-700 dark:focus:ring-gray-800">
                                                <font-awesome-icon :icon="faRotate" />
                                            </button> -->
                                        </td>
                                        <td >
                                            {{ item.clie_full_name }}
                                        </td>
                                        <td >
                                            {{ item.telephone }}
                                        </td>
                                        <td >
                                            {{ item.email }}
                                        </td>
                                        <td >
                                            {{ item.total }}
                                        </td>
                                        <td >
                                            {{ item.created_at }}
                                        </td>
                                        <td>
                                            <template v-if="item.response_status == 'approved'">
                                                <label v-if="!item.email_sent" class="w-12 h-6 relative">
                                                    <input
                                                        :checked="isSelected(item)"
                                                        @change="toggleItem(item)"
                                                        type="checkbox"
                                                        class="custom_switch absolute w-full h-full opacity-0 z-10 cursor-pointer peer" :id="`venta${index}`" />
                                                    <span :for="`venta${index}`" class="outline_checkbox bg-icon border-2 border-[#bcc8e0] dark:border-white-dark block h-full rounded-full before:absolute before:left-1 before:bg-[#ebedf2] dark:before:bg-white-dark before:bottom-1 before:w-4 before:h-4 before:rounded-full before:bg-[url(/themes/vristo/images/close.svg)] before:bg-no-repeat before:bg-center peer-checked:before:left-7 peer-checked:before:bg-[url(/themes/vristo/images/checked.svg)] peer-checked:border-primary peer-checked:before:bg-primary before:transition-all before:duration-300"></span>
                                                </label>
                                                <span v-else class="bg-blue-100 text-blue-800 text-xs font-medium mr-2 px-2.5 py-0.5 rounded dark:bg-gray-700 dark:text-blue-400 border border-blue-400">
                                                    <template v-if="item.invoice_type == 1">
                                                        Factura enviada
                                                    </template>
                                                    <template v-if="item.invoice_type == 2">
                                                        Boleta enviada
                                                    </template>
                                                </span>
                                            </template>
                                            <template v-else>
                                                <div class="flex items-center justify-center">
                                                    <svg class="w-4 h-4 text-red" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                                        <path d="M367.2 412.5L99.5 144.8C77.1 176.1 64 214.5 64 256c0 106 86 192 192 192c41.5 0 79.9-13.1 111.2-35.5zm45.3-45.3C434.9 335.9 448 297.5 448 256c0-106-86-192-192-192c-41.5 0-79.9 13.1-111.2 35.5L412.5 367.2zM0 256a256 256 0 1 1 512 0A256 256 0 1 1 0 256z"/>
                                                    </svg>
                                                </div>
                                            </template>
                                            <template v-if="!item.email_sent && item.invoice_type == 1">
                                                <span class="max-w-40 truncate whitespace-nowrap inline-block py-1 px-2 rounded-lg text-xs font-medium bg-red-100 text-red-800 dark:bg-red-800/30 dark:text-red-500">Factura pendiente</span>
                                            </template>
                                            <template v-if="!item.email_sent && item.invoice_type == 2">
                                                <span class="max-w-40 truncate whitespace-nowrap inline-block py-1 px-2 rounded-lg text-xs font-medium bg-red-100 text-red-800 dark:bg-red-800/30 dark:text-red-500">Boleta pendiente</span>
                                            </template>
                                        </td>
                                        <td class="text-center">
                                           <span v-if="item.response_status == 'pendiente'"  class="bg-red-100 text-red-800 text-xs font-medium mr-2 px-2.5 py-0.5 rounded dark:bg-gray-700 dark:text-red-400 border border-red-400">No completó el pago</span>
                                           <span v-else-if="item.response_status == 'approved'" class="bg-blue-100 text-blue-800 text-xs font-medium mr-2 px-2.5 py-0.5 rounded dark:bg-gray-700 dark:text-blue-400 border border-blue-400">Pago aprobado</span>
                                           <span v-else class="bg-yellow-100 text-yellow-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded dark:bg-yellow-900 dark:text-yellow-300">Error en la transacción</span>
                                        </td>
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
                VEN-{{ saleDetails.id }} | {{ saleDetails.student_number }} - {{ saleDetails.student_name }}
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
                        <tbody v-if="saleDetails.details.length > 0">
                            <tr v-for="(row, key) in JSON.parse(saleDetails.details)" class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                <td scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                    <p v-if="row.product.title" class="text-lg">{{ row.product.title }}</p>
                                    <p>{{ row.product.description }}</p>
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
        <TransitionRoot appear :show="displayModalSendDetails" as="template">
            <Dialog as="div" @close="closeModalSendDetails" class="relative z-50">
                <TransitionChild as="template" enter="duration-300 ease-out" enter-from="opacity-0" enter-to="opacity-100" leave="duration-200 ease-in" leave-from="opacity-100" leave-to="opacity-0">
                    <DialogOverlay class="fixed inset-0 bg-[black]/60" />
                </TransitionChild>

                <div class="fixed inset-0 overflow-y-auto">
                    <div class="flex min-h-full items-start justify-center px-4 py-8">
                    <TransitionChild
                        as="template"
                        enter="duration-300 ease-out"
                        enter-from="opacity-0 scale-95"
                        enter-to="opacity-100 scale-100"
                        leave="duration-200 ease-in"
                        leave-from="opacity-100 scale-100"
                        leave-to="opacity-0 scale-95"
                    >
                        <DialogPanel class="relative overflow-hidden w-full max-w-3xl py-8">
                            <button @click="closeModalSendDetails" type="button" class="absolute top-4 ltr:right-4 rtl:left-4 text-gray-400 hover:text-gray-800 dark:hover:text-gray-600 outline-none" >
                                <svg width="24" height="24" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512">
                                    <path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/>
                                </svg>
                            </button>
                            <div class="p-5">
                                <div
                                    style="background-color: #000; color: #fff; padding: 1rem; border-radius: 8px;"
                                >
                                    <div class="mb-4">
                                        <div class="flex justify-between mb-1">
                                            <span class="text-base font-medium text-gray-100 dark:text-white">Resultados del envío de correos:</span>
                                            <span class="text-sm font-medium text-gray-200 dark:text-white">{{ parseInt(progressSend) == 99 ? 100 : parseInt(progressSend) }}%</span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                                            <div class="bg-blue-600 h-2.5 rounded-full" :style="`width: ${progressSend}%`"></div>
                                        </div>
                                    </div>
                                    <template v-if="emailStatus.length > 0">
                                        <div ref="scrollContainer" class="scroll-box-result">
                                            <div>
                                                <template v-for="(resEmail, co) in emailStatus">

                                                    <template v-if="resEmail.status && resEmail.step == 1">
                                                        <div v-if="resEmail.status">
                                                            <code style="color: #60a5fa;">
                                                                <span>COMPROBANTE: <strong>{{ resEmail.data.document.invoice_serie }}-{{ resEmail.data.document.invoice_correlative }}</strong> CLIENTE:  <strong>{{ resEmail.data.document.client_rzn_social }}</strong>&nbsp;</span>
                                                                <span style="color: #a9cdf7;">Creado correctamente</span>
                                                            </code>
                                                        </div>

                                                        <div v-if="!resEmail.status" >
                                                            <code style="color: #ef4444;">
                                                                {{ resEmail.status }} {{ resEmail.message }}
                                                            </code>
                                                        </div>
                                                    </template>

                                                    <template v-if="resEmail.status && resEmail.step == 2">
                                                        <div v-if="resEmail.status" v-bind:style="{ borderBottom: co !== emailStatus.length - 1 ? '1px dotted #a9cdf7' : 'none' }">
                                                            <code style="color: #60a5fa;">
                                                                <span>DESTINO: <strong>{{ resEmail.data.email }}</strong> ESTADO: <span style="color: #a9cdf7;">{{ resEmail.data.message }}</span> </span>
                                                            </code>
                                                        </div>
                                                    </template>
                                                </template>
                                            </div>
                                            <template v-if="loadingStep == 1">
                                                <text-writing :texto="'...............'" />
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>
    </AppLayout>
</template>
