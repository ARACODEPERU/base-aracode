<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import { ref, onMounted, computed, reactive, watch } from 'vue';
    import { Link, useForm } from '@inertiajs/vue3';
    import { usePage, router } from '@inertiajs/vue3';
    import { useAppStore } from '@/stores/index';
    import Swal from 'sweetalert2';
    import iconXCircle from '@/Components/vristo/icon/icon-x-circle.vue';
    import iconCircleCheck from '@/Components/vristo/icon/icon-circle-check.vue';


    const store = useAppStore();

    const props = defineProps({
        payTipe: {
            type: String,
            default: 'me'
        },
        personInvoice: {
            type: Object,
            default: null
        }
    });

    const itemsCart = ref([]);
    const totalSale = ref(0);
    const totalQuantity = ref(0);

    onMounted(() => {
        if(localStorage.getItem("shoppingCart")){
            itemsCart.value = JSON.parse(localStorage.getItem("shoppingCart"));
            calculateTotals();
        }
    });

    const calculateTotals = () => {
        const { totalPrice, totalQty } = itemsCart.value.reduce(
            (acc, item) => {
                acc.totalPrice += item.price * item.quantity;
                acc.totalQty += item.quantity;
                return acc;
            },
            { totalPrice: 0, totalQty: 0 }
        );

        // Asegura que totalSale tenga dos decimales
        // totalSale.value = parseFloat(totalPrice.toFixed(2));
        totalSale.value = totalPrice.toFixed(2);
        totalQuantity.value = totalQty;
    };


    const removeItemShoppingCart = (id, key) => {
        store.removeFromCart(id);
        itemsCart.value.splice(key, 1);
    }

    const pageFormMercadoPago = () => {
        router.visit(route('onlineshop_sales_formmercadopago'), {
            method: 'post',
            data: {
                items: itemsCart.value,
                person: formPerInvoice.value
            },
            replace: true,
        });
    }

    const formPerInvoice = ref({
        document_type: props.personInvoice.document_type_id == 6 ? 1 : 2,
        dni: props.personInvoice.number,
        ruc: props.personInvoice.document_type_id == 6 ? props.personInvoice.number : null,
        nombreCompleto: props.personInvoice.full_name,
        razonSocial: props.personInvoice.document_type_id == 6 ? props.personInvoice.full_name : null,
        email: props.personInvoice.email,
        statusRuc: null,
        conditionRuc: null
    });

    const apiesLoading = ref(false);
    const activeBtnPaymer = ref(false);

    const searchApispe = () => {
        apiesLoading.value = true;
        axios.post(route('sales_search_person_apies'), {
            document_type: 6,
            number: formPerInvoice.value.ruc
        }).then((res) => {
            if(res.data.success){
                formPerInvoice.value.razonSocial =  res.data.person.razonSocial;
                formPerInvoice.value.statusRuc = res.data.person.estado;
                formPerInvoice.value.conditionRuc = res.data.person.condicion;
                if(formPerInvoice.value.statusRuc == 'ACTIVO' && formPerInvoice.value.conditionRuc == 'HABIDO'){
                    activeBtnPaymer.value = false;
                }
            }else{
                Swal.fire({
                    icon: 'error',
                    text: res.data.error,
                    padding: '2em',
                    customClass: 'sweet-alerts',
                })
            }
        }).finally(()=> {
            apiesLoading.value = false;
        });
    }

    watch(() => {
        if(formPerInvoice.value.document_type == 1){
            activeBtnPaymer.value = true;
        }else{
            formPerInvoice.value.ruc = null;
            formPerInvoice.value.razonSocial = null;
            formPerInvoice.value.email = props.personInvoice.email;
            formPerInvoice.value.statusRuc = null;
            formPerInvoice.value.conditionRuc = null;
            activeBtnPaymer.value = false;
        }
    });
</script>
<template>
    <AppLayout title="Resumen">
        <Navigation :routeModule="route('onlineshop_dashboard')" :titleModule="'Ventas en línea'">
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Tu carrito</span>
            </li>
        </Navigation>
        <div class="mt-5">
            <div class="grid grid-cols-6 gap-6">
                <div class="col-span-6 sm:col-span-4">
                    <div v-if="itemsCart.length > 0" class="space-y-6">
                        <div v-for="(item, key) in itemsCart" class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 md:p-6">
                            <div class="space-y-4 md:flex md:items-center md:justify-between md:gap-6 md:space-y-0">
                                <a href="#" class="shrink-0 md:order-1">
                                    <img class="h-20 w-20 dark:hidden" :src="item.image" alt="imac image" />
                                </a>

                                <label for="counter-input" class="sr-only">Elija la cantidad:</label>
                                <div class="flex items-center justify-between md:order-3 md:justify-end">
                                    <div class="flex items-center">
                                        <button type="button" id="decrement-button" data-input-counter-decrement="counter-input" class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-md border border-gray-300 bg-gray-100 hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-700 dark:hover:bg-gray-600 dark:focus:ring-gray-700">
                                            <svg class="h-2.5 w-2.5 text-gray-900 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 18 2">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 1h16" />
                                            </svg>
                                        </button>
                                        <input type="text" disabled id="counter-input" data-input-counter class="w-10 shrink-0 border-0 bg-transparent text-center text-sm font-medium text-gray-900 focus:outline-none focus:ring-0 dark:text-white" placeholder="" :value="item.quantity" required />
                                        <button type="button" id="increment-button" data-input-counter-increment="counter-input" class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-md border border-gray-300 bg-gray-100 hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-700 dark:hover:bg-gray-600 dark:focus:ring-gray-700">
                                            <svg class="h-2.5 w-2.5 text-gray-900 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 18 18">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 1v16M1 9h16" />
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="text-end md:order-4 md:w-32">
                                        <p class="text-base font-bold text-gray-900 dark:text-white">S./ {{ item.price }}</p>
                                        <p v-if="item.price_old" class="text-xs font-bold text-red-500 dark:text-red-300 line-through">S./ {{ item.price_old }}</p>
                                    </div>
                                </div>

                                <div class="w-full min-w-0 flex-1 space-y-4 md:order-2 md:max-w-md">
                                    <a href="#" class="text-base font-medium text-gray-900 hover:underline dark:text-white">
                                        {{ item.name }}
                                    </a>

                                    <div class="flex items-center gap-4">
                                        <!-- <button type="button" class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-gray-900 hover:underline dark:text-gray-400 dark:hover:text-white">
                                            <svg class="me-1.5 h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12.01 6.001C6.5 1 1 8 5.782 13.001L12.011 20l6.23-7C23 8 17.5 1 12.01 6.002Z" />
                                            </svg>
                                            Add to Favorites
                                        </button> -->

                                        <button @click="removeItemShoppingCart(item.id, key)" type="button" class="inline-flex items-center text-sm font-medium text-red-600 hover:underline dark:text-red-500">
                                            <svg class="me-1.5 h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 17.94 6M18 18 6.06 6" />
                                            </svg>
                                            Eliminar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                    <div v-else class="space-y-6">
                        <div id="alert-additional-content-4" class="p-4 mb-4 text-yellow-800 border border-yellow-300 rounded-lg bg-yellow-50 dark:bg-gray-800 dark:text-yellow-300 dark:border-yellow-800" role="alert">
                            <div class="flex items-center">
                                <svg class="shrink-0 w-4 h-4 me-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                                </svg>
                                <span class="sr-only">Info</span>
                                <h3 class="text-lg font-medium">Tu carrito está vacío, ¡es el momento perfecto para empezar a aprender!</h3>
                            </div>
                            <div class="mt-2 mb-4 text-sm">
                                Agrega cursos a tu carrito y comienza a invertir en tu crecimiento profesional. Con nuestros programas de aprendizaje, adquirirás habilidades clave que te permitirán destacar en el mercado laboral, mantenerte actualizado y alcanzar un nivel competitivo. ¡No dejes pasar esta oportunidad de aprender cosas nuevas y potenciar tu carrera!
                            </div>
                            <div class="flex">
                                <Link :href="route('aca_mycourses')" type="button" class="text-white bg-yellow-800 hover:bg-yellow-900 focus:ring-4 focus:outline-none focus:ring-yellow-300 font-medium rounded-lg text-xs px-3 py-1.5 me-2 text-center inline-flex items-center dark:bg-yellow-300 dark:text-gray-800 dark:hover:bg-yellow-400 dark:focus:ring-yellow-800">
                                    <svg class="me-2 h-3 w-3" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
                                        <path d="M0 96C0 43 43 0 96 0L384 0l32 0c17.7 0 32 14.3 32 32l0 320c0 17.7-14.3 32-32 32l0 64c17.7 0 32 14.3 32 32s-14.3 32-32 32l-32 0L96 512c-53 0-96-43-96-96L0 96zM64 416c0 17.7 14.3 32 32 32l256 0 0-64L96 384c-17.7 0-32 14.3-32 32zM247.4 283.8c-3.7 3.7-6.2 4.2-7.4 4.2s-3.7-.5-7.4-4.2c-3.8-3.7-8-10-11.8-18.9c-6.2-14.5-10.8-34.3-12.2-56.9l63 0c-1.5 22.6-6 42.4-12.2 56.9c-3.8 8.9-8 15.2-11.8 18.9zm42.7-9.9c7.3-18.3 12-41.1 13.4-65.9l31.1 0c-4.7 27.9-21.4 51.7-44.5 65.9zm0-163.8c23.2 14.2 39.9 38 44.5 65.9l-31.1 0c-1.4-24.7-6.1-47.5-13.4-65.9zM368 192a128 128 0 1 0 -256 0 128 128 0 1 0 256 0zM145.3 208l31.1 0c1.4 24.7 6.1 47.5 13.4 65.9c-23.2-14.2-39.9-38-44.5-65.9zm31.1-32l-31.1 0c4.7-27.9 21.4-51.7 44.5-65.9c-7.3 18.3-12 41.1-13.4 65.9zm56.1-75.8c3.7-3.7 6.2-4.2 7.4-4.2s3.7 .5 7.4 4.2c3.8 3.7 8 10 11.8 18.9c6.2 14.5 10.8 34.3 12.2 56.9l-63 0c1.5-22.6 6-42.4 12.2-56.9c3.8-8.9 8-15.2 11.8-18.9z"/>
                                    </svg>
                                    Ir a mis cursos
                                </Link>
                                <Link :href="route('academic_prices')" type="button" class="text-yellow-800 bg-transparent border border-yellow-800 hover:bg-yellow-900 hover:text-white focus:ring-4 focus:outline-none focus:ring-yellow-300 font-medium rounded-lg text-xs px-3 py-1.5 text-center dark:hover:bg-yellow-300 dark:border-yellow-300 dark:text-yellow-300 dark:hover:text-gray-800 dark:focus:ring-yellow-800" data-dismiss-target="#alert-additional-content-4" aria-label="Close">
                                    ver suscripciones
                                </Link>
                            </div>
                        </div>
                    </div>
                    <!-- horizontal form label sizing -->
                    <div class="mt-6 panel">
                        <!-- simple pills -->
                        <div class="sm:grid sm:grid-cols-2 gap-6 w-full mb-6">
                            <div class="flex items-center ps-4 border border-gray-200 rounded-sm dark:border-gray-700">
                                <label class="inline-flex py-4 ms-2">
                                    <input v-model="formPerInvoice.document_type" type="radio" :value="2" name="square_radio" class="form-radio rounded-none" />
                                    <span>Boleta electrónica</span>
                                </label>
                            </div>
                            <div class="flex items-center ps-4 border border-gray-200 rounded-sm dark:border-gray-700">
                                <label class="inline-flex py-4 ms-2">
                                    <input v-model="formPerInvoice.document_type" type="radio" :value="1" name="square_radio" class="form-radio rounded-none" />
                                    <span>Factura electrónica</span>
                                </label>
                            </div>
                        </div>
                        <div v-if="formPerInvoice.document_type == 2">
                            <h4 class="font-semibold text-2xl mb-4">Datos para la boleta</h4>
                            <form class="space-y-5" id="boleta">
                                <div class="grid grid-cols-6 gap-4">
                                    <label for="dni" class="sm:col-span-2">DNI</label>
                                    <input v-model="formPerInvoice.dni" id="dni" type="text"  class="form-input sm:col-span-4" />
                                </div>
                                <div class="grid grid-cols-6 gap-4">
                                    <label for="nombrecompleto" class="sm:col-span-2">Nombre Completo</label>
                                    <input v-model="formPerInvoice.nombreCompleto" id="nombrecompleto" type="text" class="form-input sm:col-span-4" />
                                </div>
                                <div class="grid grid-cols-6 gap-4">
                                    <label for="email" class="sm:col-span-2">Email</label>
                                    <input v-model="formPerInvoice.email" id="email" type="email" class="form-input sm:col-span-4" />
                                </div>
                            </form>
                        </div>
                        <div v-if="formPerInvoice.document_type == 1">
                            <h4 class="font-semibold text-2xl mb-4">Datos para la factura</h4>
                            <form class="space-y-5" id="boleta">
                                <div class="grid grid-cols-6 gap-4 items-center">
                                    <label for="dni" class="sm:col-span-2">RUC</label>
                                    <div class="col-span-4">
                                        <div class="flex">
                                            <input v-model="formPerInvoice.ruc" id="addonsRight" type="text" placeholder="" class="form-input ltr:rounded-r-none rtl:rounded-l-none" />
                                            <button
                                                @click="searchApispe"
                                                type="button"
                                                class="btn btn-warning ltr:rounded-l-none rtl:rounded-r-none"
                                                :class="{ 'opacity-25': apiesLoading }" :disabled="apiesLoading"
                                            >
                                                <svg v-show="apiesLoading" aria-hidden="true" role="status" class="inline w-4 h-4 mr-3 text-gray-200 animate-spin dark:text-gray-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
                                                    <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="#1C64F2"/>
                                                </svg>
                                                Buscar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="grid grid-cols-6 gap-4 items-center">
                                    <label for="nombrecompleto" class="sm:col-span-2">Razón social</label>
                                    <input v-model="formPerInvoice.razonSocial" id="nombrecompleto" type="text" class="form-input sm:col-span-4" />
                                </div>
                                <div class="grid grid-cols-6 gap-4 items-center">
                                    <label for="email" class="sm:col-span-2">Email</label>
                                    <input v-model="formPerInvoice.email" id="email" type="email" class="form-input sm:col-span-4" />
                                </div>
                                <div class="grid grid-cols-6 gap-4 items-center">
                                    <label for="email" class="sm:col-span-2">Estado</label>
                                    <div class="flex sm:col-span-4 gap-4">
                                        <span v-if="formPerInvoice.statusRuc" class="badge py-1 my-4 flex justify-between items-center text-xs"
                                            :class="formPerInvoice.statusRuc === 'ACTIVO' ? 'bg-success' : 'bg-danger'"
                                        >
                                            <span class="ltr:ml-2 rtl:mr-2">{{ formPerInvoice.statusRuc }}</span>
                                            <span class="ltr:ml-4 rtl:mr-4 cursor-pointer hover:opacity-90">
                                                <icon-circle-check v-if="formPerInvoice.statusRuc === 'ACTIVO'" class="w-4 h-4" />
                                                <icon-x-circle v-else class="w-4 h-4" />
                                            </span>
                                        </span>
                                        <span v-if="formPerInvoice.conditionRuc" class="badge py-1 my-4 flex justify-between items-center text-xs"
                                            :class="formPerInvoice.conditionRuc === 'HABIDO' ? 'bg-success' : 'bg-danger'"
                                        >
                                            <span class="ltr:ml-2 rtl:mr-2">{{ formPerInvoice.conditionRuc }}</span>
                                            <span class="ltr:ml-2 rtl:mr-2 cursor-pointer hover:opacity-90">
                                                <icon-circle-check v-if="formPerInvoice.conditionRuc === 'HABIDO'" class="w-4 h-4" />
                                                <icon-x-circle v-else class="w-4 h-4" />
                                            </span>
                                        </span>
                                    </div>
                                </div>
                            </form>
                        </div>

                    </div>
                </div>
                <div class="col-span-6 sm:col-span-2">
                    <div class="space-y-4 rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:p-6">
                        <p class="text-xl font-semibold text-gray-900 dark:text-white">Resumen del pedido</p>

                        <div class="space-y-4">
                            <div class="space-y-2">
                                <dl class="flex items-center justify-between gap-4">
                                    <dt class="text-base font-normal text-gray-500 dark:text-gray-400">Precio original</dt>
                                    <dd class="text-base font-medium text-gray-900 dark:text-white">S./ {{ totalSale }}</dd>
                                </dl>

                                <!-- <dl class="flex items-center justify-between gap-4">
                                    <dt class="text-base font-normal text-gray-500 dark:text-gray-400">Savings</dt>
                                    <dd class="text-base font-medium text-green-600">-$299.00</dd>
                                </dl>

                                <dl class="flex items-center justify-between gap-4">
                                    <dt class="text-base font-normal text-gray-500 dark:text-gray-400">Store Pickup</dt>
                                    <dd class="text-base font-medium text-gray-900 dark:text-white">$99</dd>
                                </dl>

                                <dl class="flex items-center justify-between gap-4">
                                    <dt class="text-base font-normal text-gray-500 dark:text-gray-400">IGV</dt>
                                    <dd class="text-base font-medium text-gray-900 dark:text-white">$799</dd>
                                </dl> -->
                            </div>

                            <dl class="flex items-center justify-between gap-4 border-t border-gray-200 pt-2 dark:border-gray-700">
                                <dt class="text-base font-bold text-gray-900 dark:text-white">Total</dt>
                                <dd class="text-base font-bold text-gray-900 dark:text-white">S./ {{ totalSale }}</dd>
                            </dl>
                        </div>

                        <button
                            :class="{ 'opacity-25': activeBtnPaymer }" :disabled="activeBtnPaymer"
                            @click="pageFormMercadoPago" class="flex w-full items-center justify-between rounded-lg bg-blue-700 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                            <svg class="w-4 h-4" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512">
                                <path d="M512 80c8.8 0 16 7.2 16 16l0 32L48 128l0-32c0-8.8 7.2-16 16-16l448 0zm16 144l0 192c0 8.8-7.2 16-16 16L64 432c-8.8 0-16-7.2-16-16l0-192 480 0zM64 32C28.7 32 0 60.7 0 96L0 416c0 35.3 28.7 64 64 64l448 0c35.3 0 64-28.7 64-64l0-320c0-35.3-28.7-64-64-64L64 32zm56 304c-13.3 0-24 10.7-24 24s10.7 24 24 24l48 0c13.3 0 24-10.7 24-24s-10.7-24-24-24l-48 0zm128 0c-13.3 0-24 10.7-24 24s10.7 24 24 24l112 0c13.3 0 24-10.7 24-24s-10.7-24-24-24l-112 0z"/>
                            </svg>
                            <span>TARJETA</span>
                        </button>
                        <!-- <Link :href="route('onlineshop_sales_shoppingcart','me')" class="flex w-full items-center justify-between rounded-lg bg-red-700 px-5 py-2.5 text-sm font-medium text-white hover:bg-red-800 focus:outline-none focus:ring-4 focus:ring-red-300 dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-800">
                            <svg class="w-4 h-4" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512">
                                <path d="M16 64C16 28.7 44.7 0 80 0L304 0c35.3 0 64 28.7 64 64l0 384c0 35.3-28.7 64-64 64L80 512c-35.3 0-64-28.7-64-64L16 64zM144 448c0 8.8 7.2 16 16 16l64 0c8.8 0 16-7.2 16-16s-7.2-16-16-16l-64 0c-8.8 0-16 7.2-16 16zM304 64L80 64l0 320 224 0 0-320z"/>
                            </svg>
                            <span>YAPE</span>
                        </Link> -->
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
