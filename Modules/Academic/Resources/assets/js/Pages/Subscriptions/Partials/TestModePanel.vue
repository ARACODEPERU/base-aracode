<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
    subscription: {
        type: Object,
        default: () => ({})
    },
    state: {
        type: Object,
        default: () => ({})
    }
});

const email = ref(props.state.default_email || '');
const trabajando = ref(null);

const credenciales = () => props.state.credentials || {};
const listo = () => credenciales().ready === true;

const colorNivel = (nivel) => {
    const mapa = {
        success: 'badge bg-success',
        warning: 'badge bg-warning',
        error: 'badge bg-danger',
        info: 'badge bg-info'
    };
    return mapa[nivel] || 'badge bg-secondary';
};

const fecha = (valor) => {
    if (!valor) return '—';
    try {
        return new Date(valor).toLocaleString('es-ES');
    } catch (e) {
        return valor;
    }
};

/**
 * Pide confirmación y ejecuta la acción del botón. El resultado se muestra con
 * el mismo canal de alertas del sistema (Aracodito o SweetAlert2).
 */
const ejecutar = async (url, payload, titulo, texto, confirmar, icono = 'question') => {
    const confirmacion = await window.alertaSistema(titulo, texto, icono, {
        duration: 0,
        showCancelButton: true,
        confirmButtonText: confirmar,
        cancelButtonText: 'Cancelar',
    });

    if (!confirmacion?.isConfirmed) return;

    trabajando.value = url;

    try {
        const { data } = await axios.post(url, payload || {});
        await window.alertaSistema('Listo', data.message || 'Operación completada.', 'success');
        router.reload({ only: ['subscription', 'subscriptionTest'] });
    } catch (error) {
        const mensaje = error.response?.data?.message
            || Object.values(error.response?.data?.errors || {}).flat()?.[0]
            || 'No se pudo completar la operación.';
        window.alertaSistema('No se pudo completar', mensaje, 'error');
    } finally {
        trabajando.value = null;
    }
};

const crearPrueba = async () => {
    if (!email.value) {
        window.alertaSistema('Falta el correo', 'Escribe el correo del alumno de prueba.', 'warning');
        return;
    }

    // MercadoPago valida al pagador por correo: solo acepta el de un usuario de
    // prueba comprador (siempre termina en @testuser.com).
    if (!/@testuser\.com$/i.test(email.value.trim())) {
        const seguir = await window.alertaSistema(
            'Ese correo no parece de un usuario de prueba',
            'MercadoPago rechaza el alta si el correo del pagador no es de una cuenta de prueba comprador (test_user_…@testuser.com). ¿Continuar de todos modos?',
            'warning',
            { duration: 0, showCancelButton: true, confirmButtonText: 'Continuar', cancelButtonText: 'Corregir correo' }
        );

        if (!seguir?.isConfirmed) return;
    }

    ejecutar(
        route('aca_subscriptions_test_start', props.subscription.id),
        { email: email.value },
        'Crear suscripción de prueba',
        'Se creará un alumno de prueba y una suscripción con la tarjeta de prueba en el ambiente de PRUEBA de MercadoPago. El primer cobro lo acredita el webhook real. ¿Continuar?',
        'Sí, crear prueba',
    );
};

const simularRenovacion = () => {
    ejecutar(
        route('aca_subscriptions_test_renew', props.subscription.id),
        {},
        'Simular renovación',
        'Se acreditará el periodo siguiente (1, 3 o 12 meses según el plan) con el mismo flujo del webhook: venta, ingreso en Tesorería, extensión del acceso y correo de renovación. Si el primer cobro sigue pendiente, se concilia primero. ¿Continuar?',
        'Sí, simular cobro',
    );
};

const enviarAvisoPago = () => {
    ejecutar(
        route('aca_subscriptions_test_notice', props.subscription.id),
        {},
        'Enviar aviso de pago exitoso',
        'Se encolará el correo de renovación al correo del alumno de prueba para confirmar que llega. No se cobra nada ni se extiende el acceso. ¿Continuar?',
        'Sí, enviar aviso',
    );
};

const eliminarSuscripcion = () => {
    ejecutar(
        route('aca_subscriptions_test_cancel', props.subscription.id),
        {},
        'Eliminar suscripción de prueba',
        'Se cancelará la suscripción en MercadoPago y se desactivará el acceso del alumno de prueba. Los registros quedan para poder revisarlos. ¿Continuar?',
        'Sí, eliminar suscripción',
        'warning',
    );
};

const borrarDatos = () => {
    ejecutar(
        route('aca_subscriptions_test_purge', props.subscription.id),
        {},
        'Borrar datos de prueba',
        'Se eliminarán solo los pagos, ventas, ingresos de Tesorería, suscripciones y alumnos marcados como prueba de este plan. Las suscripciones reales no se tocan. ¿Continuar?',
        'Sí, borrar datos',
        'warning',
    );
};
</script>

<template>
    <div class="panel mb-5 border-l-4 border-danger">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h5 class="font-semibold text-lg dark:text-white-light">Modo prueba de cobros recurrentes</h5>
                <p class="text-sm mt-1">
                    Prueba el alta, el webhook, la renovación, los correos y Tesorería sin usar la cuenta real.
                    Solo se usan las credenciales de <strong>PRUEBA</strong> de MercadoPago.
                </p>
                <p class="text-xs text-gray-500 mt-1">
                    El correo se pide aquí porque MercadoPago exige un <strong>pagador</strong> en toda suscripción: en la
                    venta real se toma solo de la ficha del alumno, por eso al pagar con tarjeta nunca lo escribes.
                </p>
            </div>
            <span class="badge bg-danger">MODO PRUEBA ACTIVO</span>
        </div>

        <!-- Credenciales de prueba -->
        <div v-if="!listo()" class="mt-4 p-4 rounded border border-warning bg-warning/10">
            <p class="font-semibold text-warning">Faltan las credenciales de prueba</p>
            <p class="text-sm mt-1">
                Carga en IntegrationHub → MercadoPago los campos
                <code>test_access_token</code> y <code>test_public_key</code>.
                <span class="block mt-1">
                    Nunca uses el access token de producción: el panel se bloquea si coincide con el de producción.
                </span>
            </p>
            <p v-if="credenciales().same_as_production" class="text-sm text-danger font-semibold mt-1">
                El token cargado como prueba es el mismo de producción.
            </p>
            <p v-else-if="credenciales().missing?.length" class="text-sm mt-1">
                Falta: <strong>{{ credenciales().missing.join(', ') }}</strong>.
            </p>
            <a :href="credenciales().edit_url" class="btn btn-primary mt-3">Cargar credenciales de prueba</a>
        </div>

        <div v-else class="mt-4 p-3 rounded border border-success bg-success/10 text-sm">
            Credenciales de prueba listas
            <span v-if="credenciales().source === 'env'">(tomadas del .env; se recomienda registrarlas en IntegrationHub)</span>.
        </div>

        <!-- Acciones -->
        <div class="mt-5 grid sm:grid-cols-2 gap-4">
            <div class="col-span-2 sm:col-span-1">
                <label class="text-sm font-medium">Correo del alumno de prueba</label>
                <input v-model="email" type="email" class="form-input mt-1" placeholder="test_user_123456@testuser.com" />
                <p class="text-xs text-gray-500 mt-1">
                    Este correo viaja a MercadoPago como correo del <strong>pagador</strong>, no puede ser uno inventado.
                </p>
                <div class="text-xs text-gray-500 mt-2 p-2 rounded border border-warning bg-warning/10">
                    <p class="font-semibold text-warning">De dónde sale este correo</p>
                    <ol class="list-decimal ml-4 mt-1 space-y-0.5">
                        <li>Entra a MercadoPago → <strong>Tus integraciones</strong> → tu aplicación.</li>
                        <li>Abre <strong>Cuentas de prueba</strong> → <strong>Crear cuenta de prueba</strong>.</li>
                        <li>Elige tipo <strong>Comprador</strong> (no Vendedor) y guarda.</li>
                        <li>MercadoPago muestra un correo tipo <code>test_user_123456789@testuser.com</code>: pégalo aquí.</li>
                    </ol>
                    <p class="mt-1">
                        No sirve tu correo personal, ni el del dueño de la aplicación o del token, ni un correo que no
                        exista en MercadoPago: el alta se rechaza con “Resource not found”. Ahí llegarán los avisos de
                        renovación, cobro fallido y suspensión (revisa también spam).
                    </p>
                </div>
            </div>

            <div class="col-span-2 sm:col-span-1 flex flex-wrap items-end gap-2">
                <button type="button" class="btn btn-primary" :disabled="!listo() || trabajando" @click="crearPrueba">
                    {{ trabajando === route('aca_subscriptions_test_start', subscription.id) ? 'Creando…' : 'Crear suscripción de prueba' }}
                </button>
                <button type="button" class="btn btn-outline-primary" :disabled="trabajando" @click="simularRenovacion">
                    {{ trabajando === route('aca_subscriptions_test_renew', subscription.id) ? 'Procesando…' : 'Simular renovación' }}
                </button>
                <button type="button" class="btn btn-outline-success" :disabled="trabajando" @click="enviarAvisoPago">
                    {{ trabajando === route('aca_subscriptions_test_notice', subscription.id) ? 'Enviando…' : 'Enviar aviso de pago exitoso' }}
                </button>
                <button type="button" class="btn btn-outline-danger" :disabled="trabajando" @click="eliminarSuscripcion">
                    Eliminar suscripción
                </button>
                <button type="button" class="btn btn-danger" :disabled="trabajando" @click="borrarDatos">
                    Borrar datos de prueba
                </button>
            </div>
        </div>

        <p class="text-xs text-gray-500 mt-3">
            El primer cobro lo acredita el webhook real de MercadoPago. Si aún no tienes una URL pública configurada,
            usa «Simular renovación» para ver el registro en Tesorería, la extensión del acceso y el correo.
            «Enviar aviso de pago exitoso» reenvía solo el correo, sin cobrar, para confirmar que llega.
            Las notificaciones recibidas aparecen abajo.
        </p>

        <!-- Suscripciones y pagos de prueba -->
        <div class="grid lg:grid-cols-2 gap-5 mt-6">
            <div>
                <h6 class="font-semibold mb-2">Suscripciones de prueba</h6>
                <div class="table-responsive">
                    <table class="table-auto w-full text-sm">
                        <thead>
                            <tr>
                                <th class="text-left p-2">Preapproval</th>
                                <th class="text-left p-2">Estado</th>
                                <th class="text-right p-2">Monto</th>
                                <th class="text-left p-2">Próximo cobro</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="!state.preapprovals?.length">
                                <td colspan="4" class="p-2 text-gray-500">Sin suscripciones de prueba.</td>
                            </tr>
                            <tr v-for="item in state.preapprovals" :key="item.id">
                                <td class="p-2 break-all">{{ item.preapproval_id }}</td>
                                <td class="p-2">{{ item.status }}</td>
                                <td class="p-2 text-right">S/ {{ item.amount }}</td>
                                <td class="p-2">{{ item.next_payment_date || '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <h6 class="font-semibold mt-4 mb-2">Pagos de prueba</h6>
                <div class="table-responsive">
                    <table class="table-auto w-full text-sm">
                        <thead>
                            <tr>
                                <th class="text-left p-2">Periodo</th>
                                <th class="text-left p-2">Estado</th>
                                <th class="text-right p-2">Monto</th>
                                <th class="text-left p-2">Cobrado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="!state.payments?.length">
                                <td colspan="4" class="p-2 text-gray-500">Sin pagos de prueba.</td>
                            </tr>
                            <tr v-for="pago in state.payments" :key="pago.id">
                                <td class="p-2">{{ pago.date_start }} → {{ pago.date_end }}</td>
                                <td class="p-2">{{ pago.status }}</td>
                                <td class="p-2 text-right">S/ {{ pago.amount }}</td>
                                <td class="p-2">{{ fecha(pago.paid_at) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div>
                <h6 class="font-semibold mb-2">Últimas notificaciones de MercadoPago</h6>
                <div class="table-responsive">
                    <table class="table-auto w-full text-sm">
                        <thead>
                            <tr>
                                <th class="text-left p-2">Tema</th>
                                <th class="text-left p-2">data.id</th>
                                <th class="text-left p-2">Procesado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="!state.events?.length">
                                <td colspan="3" class="p-2 text-gray-500">Aún no llega ninguna notificación.</td>
                            </tr>
                            <tr v-for="evento in state.events" :key="evento.id">
                                <td class="p-2">{{ evento.topic }}</td>
                                <td class="p-2 break-all">{{ evento.data_id || '—' }}</td>
                                <td class="p-2">
                                    <span v-if="evento.processed_at" class="text-success">{{ fecha(evento.processed_at) }}</span>
                                    <span v-else-if="evento.error" class="text-danger" :title="evento.error">Error</span>
                                    <span v-else class="text-warning">Pendiente</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Bitácora -->
        <div class="mt-6">
            <h6 class="font-semibold mb-2">Bitácora de la prueba</h6>
            <div class="table-responsive">
                <table class="table-auto w-full text-sm">
                    <thead>
                        <tr>
                            <th class="text-left p-2">Fecha</th>
                            <th class="text-left p-2">Acción</th>
                            <th class="text-left p-2">Detalle</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="!state.logs?.length">
                            <td colspan="3" class="p-2 text-gray-500">Todavía no se ejecutó ninguna acción.</td>
                        </tr>
                        <tr v-for="log in state.logs" :key="log.id">
                            <td class="p-2 whitespace-nowrap">{{ fecha(log.created_at) }}</td>
                            <td class="p-2">
                                <span :class="colorNivel(log.level)">{{ log.action }}</span>
                            </td>
                            <td class="p-2">{{ log.message }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
