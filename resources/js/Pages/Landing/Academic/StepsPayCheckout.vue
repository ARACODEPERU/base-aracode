<script setup>
import { onMounted, ref } from "vue";
import { usePage } from '@inertiajs/vue3';
import MercadoPagoForm from "./Partials/MercadoPagoForm.vue";

const page = usePage();

const publicKey = page.props.MERCADOPAGO_KEY;

const props = defineProps({
    preference: {
        type: String,
        default: null,
    },
    subscription:{
        type: Object,
        default: () => ({}),
    },
    samount: {
        type: Number,
        default: null,
    },
    personInvoice:{
        type: Array,
        default: {},
    }
});

</script>

<template>
        <div class="max-w-[85rem] px-4 py-10 sm:px-6 lg:px-8 lg:py-14 mx-auto">
        <!-- Title -->
        <div class="max-w-2xl mx-auto text-center">
            <h2 class="text-2xl font-bold md:text-4xl md:leading-tight dark:text-white">Formulario de pago</h2>
            <p class="mt-1 text-gray-600 dark:text-neutral-400">pago seguro.</p>
            <p class="mt-1 text-gray-600 dark:text-neutral-400">TOTAL A PAGAR</p>
            <div class="my-7 p-2.5 text-center text-lg group-hover:text-primary"><strong class="text-[#3b3f5c] dark:text-white-dark text-3xl lg:text-5xl group-hover:text-primary">S/ {{ samount }}</strong> / {{ subscription.period }} </div>
        </div>
        <!-- End Title -->
        <div class="flex flex-wrap justify-center gap-6">
            <div class="flex flex-col rounded-xl p-8 w-[800px]">
                <ol class="flex items-center w-full text-sm font-medium text-center text-gray-500 dark:text-gray-400 sm:text-base">
                    <li class="flex md:w-full items-center text-yellow-500 dark:text-yellow-600 sm:after:content-[''] after:w-full after:h-1 after:border-b after:border-gray-200 after:border-1 after:hidden sm:after:inline-block after:mx-6 xl:after:mx-10 dark:after:border-gray-700">
                        <span class="flex items-center after:content-['/'] sm:after:hidden after:mx-2 after:text-gray-200 dark:after:text-gray-500">
                            <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 me-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 8.207-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 0 1 1.414-1.414L9 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z"/>
                            </svg>
                            Acceso
                        </span>
                    </li>
                    <li class="flex md:w-full items-center after:content-[''] after:w-full after:h-1 after:border-b after:border-gray-200 after:border-1 after:hidden sm:after:inline-block after:mx-6 xl:after:mx-10 dark:after:border-gray-700">
                        <span class="flex items-center text-yellow-500 dark:text-yellow-600 after:content-['/'] sm:after:hidden after:mx-2 after:text-gray-200 dark:after:text-gray-500">
                            <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 me-2.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 8.207-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 0 1 1.414-1.414L9 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z"/>
                            </svg>
                            Verificación
                        </span>
                    </li>
                    <li class="flex md:w-full items-center after:content-[''] after:w-full after:h-1 after:border-b after:border-gray-200 after:border-1 after:hidden sm:after:inline-block after:mx-6 xl:after:mx-10 dark:after:border-gray-700">
                        <span class="flex items-center text-blue-600 dark:text-blue-500 after:content-['/'] sm:after:hidden after:mx-2 after:text-gray-200 dark:after:text-gray-500">
                            <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 me-2.5" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512">
                                <path d="M64 32C28.7 32 0 60.7 0 96l0 32 576 0 0-32c0-35.3-28.7-64-64-64L64 32zM576 224L0 224 0 416c0 35.3 28.7 64 64 64l448 0c35.3 0 64-28.7 64-64l0-192zM112 352l64 0c8.8 0 16 7.2 16 16s-7.2 16-16 16l-64 0c-8.8 0-16-7.2-16-16s7.2-16 16-16zm112 16c0-8.8 7.2-16 16-16l128 0c8.8 0 16 7.2 16 16s-7.2 16-16 16l-128 0c-8.8 0-16-7.2-16-16z"/>
                            </svg>
                            Pagar
                        </span>
                    </li>
                    <li class="flex items-center">
                        <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 me-2.5" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                            <path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM164.1 325.5C182 346.2 212.6 368 256 368s74-21.8 91.9-42.5c5.8-6.7 15.9-7.4 22.6-1.6s7.4 15.9 1.6 22.6C349.8 372.1 311.1 400 256 400s-93.8-27.9-116.1-53.5c-5.8-6.7-5.1-16.8 1.6-22.6s16.8-5.1 22.6 1.6zM144.4 208a32 32 0 1 1 64 0 32 32 0 1 1 -64 0zm192-32a32 32 0 1 1 0 64 32 32 0 1 1 0-64z"/>
                        </svg>
                        Hecho
                    </li>
                </ol>
                <!-- step uno -->
                <div class="flex justify-center p-8">
                    <MercadoPagoForm
                        :preference="preference"
                        :publicKey="publicKey"
                        :subscription="subscription"
                        :samount="samount"
                        :personInvoice="personInvoice"
                    />
                </div>
                <!-- finstep uno -->
            </div>

        </div>
    </div>
</template>
