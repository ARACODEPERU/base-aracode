<script setup>
import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
import EditForm from './Partials/EditForm.vue';
import TestModePanel from './Partials/TestModePanel.vue';
import { Head, Link } from '@inertiajs/vue3';
import Navigation from '@/Components/vristo/layout/Navigation.vue';

defineProps({
    subscription: {
        type: Object,
        default: () => ({})
    },
    periods: {
        type: Object,
        default: () => ({})
    },
    mercadopagoWebhook: {
        type: Object,
        default: () => ({})
    },
    subscriptionTest: {
        type: Object,
        default: () => ({})
    }
});
</script>

<template>
    <AppLayout title="Editar">
        <Head>
            <meta name="aracodito-view" content="new" />
        </Head>
        <Navigation :routeModule="route('aca_dashboard')" :titleModule="'Académico'">
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <Link :href="route('aca_subscriptions_list')" class="text-primary hover:underline">Tipo de suscripcion</Link>
            </li>
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <span>Editar</span>
            </li>
        </Navigation>

        <div class="pt-5">
            <!-- Aviso: el cobro automático depende del webhook de MercadoPago. -->
            <div v-if="!mercadopagoWebhook.configured" class="panel mb-5 border-l-4 border-warning">
                <h5 class="font-semibold text-warning">Webhook de MercadoPago sin configurar</h5>
                <p class="text-sm mt-2">
                    Los planes con <strong>cobro automático mensual</strong> no podrán acreditar sus pagos ni renovar la
                    suscripción mientras falte el secreto del webhook. Tampoco se enviarán los avisos de renovación,
                    cobro fallido o suspensión.
                </p>
                <p class="text-sm mt-2">
                    Debe llenarse el campo <code>webhook_secret</code> en <strong>IntegrationHub → MercadoPago</strong>.
                    Ahí encontrarás la guía paso a paso para obtenerlo desde el panel de MercadoPago.
                </p>
                <a :href="mercadopagoWebhook.edit_url" class="btn btn-primary mt-4">
                    Ir a IntegrationHub para configurarlo
                </a>
            </div>

            <div v-else class="panel mb-5 border-l-4 border-success">
                <h5 class="font-semibold text-success">Webhook de MercadoPago configurado</h5>
                <p class="text-sm mt-2">
                    Los cobros automáticos pueden acreditarse correctamente
                    <span v-if="mercadopagoWebhook.source === 'env'">(secreto tomado del .env como respaldo; se recomienda registrarlo en IntegrationHub)</span>.
                </p>
                <a :href="mercadopagoWebhook.edit_url" class="text-primary hover:underline text-sm mt-2 inline-block">
                    Revisar configuración en IntegrationHub
                </a>
            </div>

            <!-- Panel de pruebas: solo si el plan tiene activado el modo prueba. -->
            <TestModePanel
                v-if="subscription.test_mode"
                :subscription="subscription"
                :state="subscriptionTest"
            />

            <EditForm :subscription="subscription" :periods="periods" />
        </div>
    </AppLayout>
</template>
