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
    }
});

</script>

<template>
    <div class="max-w-[85rem] px-4 py-10 sm:px-6 lg:px-8 lg:py-14 mx-auto">
        <!-- Title -->
        <div class="max-w-2xl mx-auto text-center">
            <h2 class="text-2xl font-bold md:text-4xl md:leading-tight dark:text-white">Formulario de pago</h2>
            <p class="mt-1 text-gray-600 dark:text-neutral-400">pago seguro en aracode.</p>
        </div>
        <!-- End Title -->
        <div class="flex flex-wrap justify-center gap-6">
            <div class="flex flex-col rounded-xl p-8 w-[500px]">
                <MercadoPagoForm :preference="preference" :publicKey="publicKey" :subscription="subscription" /> 
            </div>
        </div>
    </div>
</template>
