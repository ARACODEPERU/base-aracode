<script setup>
import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
import EditForm from './Partials/EditForm.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';

const props = defineProps({
    integration: {
        type: Object,
        default: () => ({}),
    },
    errors: {
        type: Object,
        default: () => ({}),
    },
    apiRoutes: {
        type: Array,
        default: () => [],
    },
    scheduler: {
        type: Object,
        default: () => ({}),
    },
    mercadopagoWebhook: {
        type: Object,
        default: () => ({}),
    },
});

const esMercadoPago = () => String(props.integration?.name || '').toLowerCase() === 'mercadopago';

const copiarUrlWebhook = async () => {
    try {
        await navigator.clipboard.writeText(props.mercadopagoWebhook.webhook_url);
        window.alertaSistema('URL copiada', 'Pega esta URL en MercadoPago → Webhooks.', 'success');
    } catch (error) {
        window.alertaSistema('No se pudo copiar', 'Selecciona la URL del campo y cópiala manualmente.', 'warning');
    }
};
</script>

<template>
    <AppLayout :title="'Editar: ' + integration.name">
        <Navigation :routeModule="route('integrationhub_listado')" :titleModule="'Centro de Integraciones'"
            :data="[
                {route: route('integrationhub_listado'), title: 'Integraciones'},
                {title: integration.name}
            ]"
        />

        <!-- Estado y guía del webhook de MercadoPago (solo en esa integración). -->
        <div v-if="esMercadoPago()" class="pt-5">
            <div class="panel" :class="mercadopagoWebhook.configured ? 'border-l-4 border-success' : 'border-l-4 border-warning'">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h5 class="font-semibold" :class="mercadopagoWebhook.configured ? 'text-success' : 'text-warning'">
                            Webhook de MercadoPago:
                            {{ mercadopagoWebhook.configured ? 'configurado' : 'sin configurar' }}
                        </h5>
                        <p class="text-sm mt-1">
                            <template v-if="mercadopagoWebhook.configured">
                                Las notificaciones de cobros automáticos se validan
                                <span v-if="mercadopagoWebhook.source === 'integrationhub'">con el secreto guardado en esta integración</span>
                                <span v-else>con el secreto del .env (respaldo); se recomienda registrarlo aquí</span>.
                            </template>
                            <template v-else>
                                Sin el secreto, el webhook rechaza todas las notificaciones de MercadoPago (401) y las
                                suscripciones no se renuevan solas.
                            </template>
                        </p>
                    </div>
                    <span class="badge" :class="mercadopagoWebhook.configured ? 'bg-success' : 'bg-warning'">
                        {{ mercadopagoWebhook.configured ? 'Configurado' : 'Pendiente' }}
                    </span>
                </div>

                <div class="mt-4">
                    <label class="text-xs font-semibold uppercase text-gray-500">URL del webhook</label>
                    <div class="flex flex-wrap gap-2 mt-1">
                        <input
                            :value="mercadopagoWebhook.webhook_url"
                            type="text"
                            readonly
                            class="form-input flex-1 min-w-[260px]"
                            @focus="$event.target.select()"
                        />
                        <button type="button" class="btn btn-primary" @click="copiarUrlWebhook">Copiar URL</button>
                    </div>
                    <p v-if="!mercadopagoWebhook.has_secret_field" class="text-sm text-warning mt-2">
                        El campo <code>webhook_secret</code> no existe en esta integración. Ejecuta las migraciones
                        pendientes para crearlo.
                    </p>
                </div>

                <div class="mt-5">
                    <h6 class="font-semibold mb-2">Cómo obtenerlo y configurarlo</h6>
                    <ol class="list-decimal ml-5 space-y-2 text-sm">
                        <li>
                            Entra a <strong>mercadopago.com.pe</strong> con la cuenta que cobra las suscripciones y abre
                            <strong>Tus integraciones → tu aplicación → Webhooks</strong> (en algunas cuentas aparece como
                            <em>Tu negocio → Configuración → Webhooks</em>).
                            MercadoPago exige una URL pública <strong>HTTPS</strong>; en desarrollo local necesitarás un túnel
                            (por ejemplo ngrok) que apunte a esta misma ruta.
                        </li>
                        <li>
                            En <strong>URL de producción o de prueba</strong> pega la URL del webhook copiada arriba.
                            Usa la de prueba si esta integración trabaja con credenciales <code>TEST-</code>.
                        </li>
                        <li>
                            Marca el evento <strong>“Suscripciones” / <code>subscription_authorized_payment</code></strong>
                            (los cobros mensuales llegan con ese evento). Guarda los cambios.
                        </li>
                        <li>
                            Copia la <strong>firma secreta</strong> que muestra esa misma pantalla de Webhooks.
                        </li>
                        <li>
                            En la sección <strong>Autenticación</strong> de esta página, edita el campo
                            <code>webhook_secret</code>, pega la firma, guarda y vuelve a esta pantalla para confirmar que
                            el estado cambió a <strong>Configurado</strong>.
                        </li>
                    </ol>
                    <p class="text-xs text-gray-500 mt-3">
                        Cada notificación queda registrada en <code>mercadopago_events</code> con su resultado, para
                        diagnosticar reintentos o cobros rechazados.
                    </p>
                </div>
            </div>
        </div>

        <div class="pt-5">
            <EditForm :integration="integration" :api-routes="apiRoutes" :scheduler="scheduler" />
        </div>
    </AppLayout>
</template>
