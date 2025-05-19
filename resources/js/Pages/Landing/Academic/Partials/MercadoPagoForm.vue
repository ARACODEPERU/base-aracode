<script setup>
import { onMounted, ref } from "vue";
import { loadMercadoPago } from "@mercadopago/sdk-js";
import { router } from '@inertiajs/vue3'

const cardPaymentBrickContainer = ref(null);

const props = defineProps({
    preference: {
        type: String,
        default: null,
    },
    publicKey: {
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

let mp;

onMounted(async () => {
    // Carga el SDK de MercadoPago
    await loadMercadoPago();

    // Inicializa MercadoPago
    mp = new window.MercadoPago(props.publicKey, { locale: "es" });

    const bricksBuilder = mp.bricks();

    // Renderiza el brick una vez que el DOM está disponible
    await renderCardPaymentBrick(bricksBuilder);
});

const renderCardPaymentBrick = async (bricksBuilder) => {
    const settings = {
        initialization: {
            preferenceId: props.preference,
            amount: props.samount,
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
                console.log("Brick está listo");
            },
            onSubmit: (cardFormData) => {
                cardFormData.personInvoice = props.personInvoice
                return axios({
                        method: 'PUT',
                        url: route("aca_mercadopago_processpayment", props.subscription.id),
                        data: cardFormData
                    }).then((response) => {
                        return response.data;
                    }).then((data) => {
                        if (data.status === "approved") {
                            router.visit(data.url, {
                                method: 'get',
                                replace: true,
                                preserveState: false,
                                preserveScroll: false,
                            });
                        } else {
                            alert(data.message);
                            router.visit(route('academic_step_verification',props.subscription.id), {
                                method: 'get',
                            });
                        }
                    }).catch((error) => {
                        alert(error.message || "Error al procesar el pago.");
                        router.visit(route('academic_step_verification',props.subscription.id), {
                            method: 'get',
                        });
                    });
            },
            onError: (error) => {
                console.error("Error en Brick:", error);
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
        console.error("El contenedor 'cardPaymentBrick_container' no está disponible.");
    }
};
</script>

<template>
    <!-- Asegúrate de que el contenedor esté presente en el DOM -->
    <div id="cardPaymentBrick_container" ref="cardPaymentBrickContainer"></div>
</template>
