<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import Keypad from '@/Components/Keypad.vue';
    import Pagination from '@/Components/Pagination.vue';
    import ModalLarge from '@/Components/ModalLarge.vue';
    import ModalLargeX from '@/Components/ModalLargeX.vue';
    import Swal from "sweetalert2";
    import { useForm, Link, usePage, router } from '@inertiajs/vue3';
    import { faGears } from "@fortawesome/free-solid-svg-icons";
    import { ref, watch, onMounted, nextTick } from "vue";
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import { TransitionRoot, TransitionChild, Dialog, DialogPanel, DialogOverlay } from '@headlessui/vue';
    import textWriting from '@/Components/loader/text-writing.vue';
    import iconExcel from '@/Components/vristo/icon/icon-excel.vue';


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
        console.log(saleDetails.value)
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
        let url = route('acco_sales_special_rates_quota_create', sale.id);

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
            if (event.data === "refresh-payment") {
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
</script>

<template>
    <AppLayout title="Resumen">
        <Navigation :routeModule="route('sales_dashboard')" :titleModule="'Cuentas por cobrar'">
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Gestión Ventas en Cuotas</span>
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
                                        <button class="btn btn-success uppercase text-xs">
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
                                    <!-- <th >
                                        Estado
                                    </th> -->
                                </tr>
                            </thead>
                            <tbody>
                                <template v-for="(item, index) in sales.data" :key="item.id">
                                    <tr >
                                        <td class="text-center">
                                            <div class="flex gap-4 items-center justify-center">
                                                <div class="dropdown">
                                                    <Popper :placement="'bottom-start'" offsetDistance="0" class="align-middle">
                                                        <button type="button" class="btn btn-outline-primary px-2 py-2 dropdown-toggle">
                                                            <font-awesome-icon :icon="faGears" />
                                                        </button>
                                                        <template #content="{ close }">
                                                        <ul @click="close()" class="whitespace-nowrap">
                                                            <li>
                                                                <a @click="openModalDetails(item)" href="javascript:;">Ver detalles</a>
                                                            </li>
                                                            <li>
                                                                <a @click="openModalCronograma(item)" href="javascript:;">Ver cronograma</a>
                                                            </li>
                                                            <li>
                                                                <a @click="openModalDocuments(item)" href="javascript:;" class="text-red-600 hover:text-red-800">Ver Documentos de venta</a>
                                                            </li>
                                                            <li>
                                                                <a @click="openDialogCreateFeeDocument(item)" href="javascript:;" class="text-blue-600 underline hover:text-blue-800 visited:text-purple-700" >Registrar pago</a>
                                                            </li>
                                                        </ul>
                                                        </template>
                                                    </Popper>
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
                                    {{ formatDate(row.updated_at) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </template>
        </ModalLargeX>
        <ModalLarge
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
                                <th scope="col" class="px-6 py-3">
                                    serie-número
                                </th>
                                <th scope="col" class="px-6 py-3">
                                    Fecha emisión
                                </th>
                                <th scope="col" class="px-6 py-3">
                                    Detalle
                                </th>
                                <th scope="col" class="px-6 py-3">
                                    total pago
                                </th>
                            </tr>
                        </thead>
                        <tbody v-if="saleDocuments.documents.length > 0">
                            <tr v-for="(row, key) in saleDocuments.documents" class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                <td>
                                    <a @click="downloadDocument(row.id,row.invoice_type_doc,'PDF')" href="javascript:;" class="text-blue-600 underline hover:text-blue-800 visited:text-purple-700">{{ row.invoice_serie+'-'+row.invoice_correlative }}</a>
                                </td>
                                <td scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                    {{ formatDate(row.invoice_broadcast_date) }}
                                </td>
                                <td class="px-6 py-4 text-justify hyphens-auto">
                                    {{ row.items[0].decription_product }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    {{ row.overall_total }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </template>
        </ModalLarge>
    </AppLayout>
</template>
