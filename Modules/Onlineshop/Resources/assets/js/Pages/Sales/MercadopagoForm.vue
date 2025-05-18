<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import { ref, onMounted, computed, reactive, watch } from 'vue';
    import { Link } from '@inertiajs/vue3';
    import { loadMercadoPago } from "@mercadopago/sdk-js";
    import { usePage, router } from '@inertiajs/vue3';
    import Swal from 'sweetalert2';
    import { useAppStore } from '@/stores/index';

    const store = useAppStore();

    const props = defineProps({
        preference_id: {
            type: String,
            default: null
        },
        personInvoice: {
            type: Array,
            default: {}
        }
    });

    const itemsCart = ref([]);
    const cardPaymentBrickContainer = ref(null);
    const totalSale = ref(0);
    const totalQuantity = ref(0);

    const page = usePage();

    const publicKey = ref(page.props.MERCADOPAGO_KEY);

    let mp;

    onMounted( async () => {
        if(localStorage.getItem("shoppingCart")){
            itemsCart.value = JSON.parse(localStorage.getItem("shoppingCart"));
            calculateTotals();
            // Carga el SDK de MercadoPago
            // Carga el SDK de MercadoPago
            await loadMercadoPago();

            // Inicializa MercadoPago
            mp = new window.MercadoPago(publicKey.value, { locale: "es" });

            const bricksBuilder = mp.bricks();

            // Renderiza el brick una vez que el DOM está disponible
            await renderCardPaymentBrick(bricksBuilder);

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

    const renderCardPaymentBrick = async (bricksBuilder, preference) => {
        const settings = {
            initialization: {
                preferenceId: props.preference_id,
                amount: totalSale.value,
            },
            customization: {
                visual: {
                    style: {
                        customVariables: {
                            theme: "bootstrap",
                        },
                    },
                },
                paymentMethods: {
                    maxInstallments: 1,
                },
            },
            callbacks: {
                onReady: () => {
                    showAlertToast("Formulario de pago está listo","success");
                },
                onSubmit: (cardFormData) => {
                    cardFormData.products = itemsCart.value;
                    cardFormData.personInvoice = props.personInvoice
                    return axios({
                            method: 'POST',
                            url: route("academic_processpayment_courses_mercadopago"),
                            data: cardFormData
                        }).then((response) => {
                            return response.data;
                        }).then((data) => {
                            console.log(data)
                            if (data.status === "approved") {
                                removeItemsShoppingCart();
                                showAlertToast("El curso ya está disponible.","success");
                                router.visit(data.url, {
                                    method: 'get',
                                    replace: true,
                                    preserveState: false,
                                    preserveScroll: false,
                                });
                            } else {
                                showAlertToast(data.message,"error");
                                reloadPageFormMercadoPago();
                            }
                        }).catch((error) => {
                            let msxg = error.message || "Error al procesar el pago.";
                            showAlertToast(msxg, "error");
                            reloadPageFormMercadoPago();
                        });
                },
                onError: (error) => {
                    console.log(error)
                    showAlertToast("Error en Brick:"+ error.message, "error");
                },
            },
        };

        // Asegúrate de que el contenedor existe antes de crear el brick
        const container = cardPaymentBrickContainer.value;
        if (container) {
            window.cardPaymentBrickController = await bricksBuilder.create(
                "cardPayment",
                "cardPaymentBrick_container",
                settings
            );
        } else {
            showAlertToast("El contenedor 'cardPaymentBrick_container' no está disponible.","error");
        }
    };


    const showAlertToast = async (msg, type = null) => {
        const toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            padding: '2em',
            customClass: 'sweet-alerts',
        });
        toast.fire({
            icon: type,
            title: msg,
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    }

    const reloadPageFormMercadoPago = () => {
        router.visit(route('onlineshop_sales_formmercadopago'), {
            method: 'post',
            data: {items: itemsCart.value},
            replace: true,
        });
    }
    const removeItemsShoppingCart = () => {
        console.log('ya puede entrar al curso')
        store.clearCart();
    }
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
                                    </div>
                                </div>

                                <div class="w-full min-w-0 flex-1 space-y-4 md:order-2 md:max-w-md">
                                    <a href="#" class="text-base font-medium text-gray-900 hover:underline dark:text-white">
                                        {{ item.name }}
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div>
                            <p class="text-xl font-semibold text-gray-900 dark:text-white">DATOS DEL PAGADOR</p>
                            <div id="cardPaymentBrick_container" ref="cardPaymentBrickContainer"></div>
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

                        <button @click="pageFormMercadoPago" class="flex w-full items-center justify-between rounded-lg bg-blue-700 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
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
