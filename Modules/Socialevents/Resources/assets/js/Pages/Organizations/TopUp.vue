<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import IconLoader from '@/Components/vristo/icon/icon-loader.vue';
import { loadMercadoPago } from '@mercadopago/sdk-js';
import Swal2 from 'sweetalert2';
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    balance: { type: Number, default: 0 },
    publicKey: { type: String, default: '' },
    minimumAmount: { type: Number, default: 10 },
    organization: { type: Object, default: () => ({}) },
    // Vigencia de la organización resuelta por SportsOrganizationService::accessSummary.
    access: {
        type: Object,
        default: () => ({ status: 'none', label: '', until: null, days_left: null, expired: false, sponsored: false }),
    },
    payments: { type: Array, default: () => [] },
});

/**
 * La app móvil abre este checkout con `?amount=`, para que el organizador no
 * tenga que volver a escribir el monto que ya eligió en el celular.
 */
const montoInicial = Number(new URLSearchParams(window.location.search).get('amount'));
const amount = ref(
    Number.isFinite(montoInicial) && montoInicial >= props.minimumAmount && montoInicial <= 999999.99
        ? montoInicial
        : props.minimumAmount,
);
const method = ref('card');
const email = ref('');
const documentType = ref('DNI');
const documentNumber = ref('');
const phoneNumber = ref('');
const otp = ref('');
const loading = ref(false);
const error = ref('');
const cardFormContainer = ref(null);
let mercadoPago;
let cardForm;
const money = (value) => `S/ ${Number(value || 0).toFixed(2)}`;
const canPay = computed(() => Number(amount.value) >= props.minimumAmount && Number(amount.value) <= 999999.99);

/** La fecha llega como ISO (aaaa-mm-dd) y se muestra como dd/mm/aaaa. */
const fecha = (isoDate) => {
    if (! isoDate) return '';
    const [year, month, day] = String(isoDate).slice(0, 10).split('-');
    return year && month && day ? `${day}/${month}/${year}` : isoDate;
};

/**
 * Texto de la vigencia: el organizador necesita saber cuánto le queda antes de
 * que venza, para decidir si adelanta la recarga.
 */
const vigencia = computed(() => {
    const { until, days_left: days } = props.access;
    if (! until || days === null || days === undefined) return '';
    if (days > 0) return `Vence en ${days} día${days === 1 ? '' : 's'} · ${fecha(until)}`;
    if (days === 0) return `Vence hoy · ${fecha(until)}`;
    const vencidos = Math.abs(days);
    return `Venció hace ${vencidos} día${vencidos === 1 ? '' : 's'} · ${fecha(until)}`;
});

/** Color del chip de estado: primero la cortesía, luego el acceso vencido. */
const estadoClase = computed(() => {
    if (props.access.sponsored) return 'bg-primary/10 text-primary';
    if (props.access.expired) return 'bg-danger/10 text-danger';

    return props.access.status === 'active' ? 'bg-success/10 text-success' : 'bg-warning/10 text-warning';
});

const pagoAcreditado = (pago) => pago.status === 'credited' || pago.status === 'approved';

onMounted(async () => {
    if (!props.publicKey) {
        error.value = 'Mercado Pago no está configurado. Contacta al administrador.';
        return;
    }
    await loadMercadoPago();
    mercadoPago = new window.MercadoPago(props.publicKey, { locale: 'es-PE' });
    mountCardForm();
});

const mountCardForm = () => {
    if (!mercadoPago || cardForm) return;
    cardForm = mercadoPago.cardForm({
        amount: String(Number(amount.value).toFixed(2)),
        iframe: true,
        form: {
            id: 'topup-card-form',
            cardNumber: { id: 'topup-card-number', placeholder: 'Número de tarjeta' },
            expirationDate: { id: 'topup-card-expiration', placeholder: 'MM/YY' },
            securityCode: { id: 'topup-card-cvv', placeholder: 'CVV' },
            cardholderName: { id: 'topup-card-name', placeholder: 'Titular de la tarjeta' },
            issuer: { id: 'topup-card-issuer', placeholder: 'Banco emisor' },
            installments: { id: 'topup-card-installments', placeholder: 'Cuotas' },
            identificationType: { id: 'topup-card-document-type', placeholder: 'Tipo de documento' },
            identificationNumber: { id: 'topup-card-document-number', placeholder: 'Número de documento' },
            cardholderEmail: { id: 'topup-card-email', placeholder: 'Correo electrónico' },
        },
        callbacks: {
            onFormMounted: (formError) => {
                if (formError) error.value = 'No se pudo cargar el formulario de tarjeta.';
            },
            onSubmit: async (event) => {
                event.preventDefault();
                if (method.value !== 'card' || loading.value || !canPay.value) return;
                loading.value = true;
                error.value = '';
                try {
                    const card = cardForm.getCardFormData();
                    const { data } = await axios.post(route('even_organizations.topup.card'), {
                        amount: Number(amount.value),
                        token: card.token,
                        payment_method_id: card.paymentMethodId,
                        issuer_id: card.issuerId,
                        installments: card.installments,
                        payer: {
                            email: card.cardholderEmail,
                            identification: {
                                type: card.identificationType,
                                number: card.identificationNumber,
                            },
                        },
                    });
                    if (data.status === 'approved') {
                        await Swal2.fire({ icon: 'success', title: 'Pago aprobado', text: 'El saldo de la organización fue recargado.' });
                        window.location.reload();
                    } else {
                        error.value = 'El pago quedó pendiente o no fue aprobado. El saldo se actualizará cuando Mercado Pago confirme el pago.';
                    }
                } catch (paymentError) {
                    error.value = paymentError.response?.data?.message || 'No se pudo procesar el pago con tarjeta.';
                } finally {
                    loading.value = false;
                }
            },
            onError: () => { error.value = 'Revisa los datos de la tarjeta e inténtalo nuevamente.'; },
        },
    });
};

const payYape = async () => {
    if (loading.value || !canPay.value) return;
    if (!email.value || !/^9\d{8}$/.test(phoneNumber.value) || !otp.value) {
        error.value = 'Completa un correo, el celular registrado en Yape y el código OTP.';
        return;
    }
    loading.value = true;
    error.value = '';
    try {
        const token = await mercadoPago.yape({ otp: otp.value, phoneNumber: phoneNumber.value }).create();
        const { data } = await axios.post(route('even_organizations.topup.yape'), {
            amount: Number(amount.value),
            token: token.id,
            phone_number: phoneNumber.value,
            payer: {
                email: email.value,
                identification: { type: documentType.value, number: documentNumber.value || null },
            },
        });
        otp.value = '';
        if (data.status === 'approved') {
            await Swal2.fire({ icon: 'success', title: 'Pago aprobado', text: 'El saldo de la organización fue recargado.' });
            window.location.reload();
        } else {
            error.value = 'El pago con Yape no fue aprobado. Comprueba el estado e inténtalo de nuevo.';
        }
    } catch (paymentError) {
        error.value = paymentError.response?.data?.message || 'No se pudo procesar el pago con Yape.';
    } finally {
        loading.value = false;
    }
};

const selectMethod = async (selected) => {
    if (method.value === selected) return;
    if (cardForm) {
        cardForm.unmount();
        cardForm = null;
    }
    method.value = selected;
    await nextTick();
    if (selected === 'card') mountCardForm();
};

onBeforeUnmount(() => cardForm?.unmount());
</script>

<template>
    <AppLayout title="Mis pagos">
        <Navigation :routeModule="route('even_dashboard')" titleModule="GESTIÓN DEPORTIVA" :data="[{ title: 'Mis pagos' }]" />
        <main class="mx-auto max-w-3xl space-y-5 p-4 md:p-6">
            <section class="panel space-y-4">
                <header class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 class="text-xl font-bold">Mis pagos</h1>
                        <p v-if="organization.name" class="text-sm text-gray-500">{{ organization.name }}</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 text-sm">
                        <span class="rounded-full px-3 py-1 font-semibold" :class="estadoClase">
                            {{ access.label || 'Sin estado' }}
                        </span>
                        <span class="rounded-full bg-primary/10 px-4 py-2 font-semibold">Saldo actual: {{ money(balance) }}</span>
                    </div>
                </header>

                <p v-if="vigencia" class="text-sm text-gray-600 dark:text-gray-400">
                    {{ access.sponsored ? 'Sin fecha de vencimiento: la cuenta está bonificada.' : vigencia }}
                </p>

                <!-- El acceso vencido deja el módulo en solo lectura, pero la vista
                     de pagos sigue abierta: aquí se recarga el saldo. -->
                <p v-if="access.expired && ! access.sponsored" role="alert" class="rounded border border-amber-300 bg-amber-50 p-3 text-sm text-amber-800">
                    Tu acceso está vencido o bloqueado: el módulo quedó en solo lectura. Puedes recargar saldo aquí mismo; el administrador confirma el
                    pago y fija la fecha de reactivación.
                </p>
                <p v-else-if="access.status === 'trial'" class="text-sm text-gray-500">
                    Estás en el período de prueba. Puedes recargar saldo desde ahora para no interrumpir la generación de partidos.
                </p>
            </section>

            <section class="panel space-y-4">
                <header>
                    <h2 class="text-lg font-semibold dark:text-white-light">Recargar saldo</h2>
                    <p class="text-sm text-gray-500">
                        El saldo prepago es lo que permite registrar partidos; recargarlo no cambia la fecha de vencimiento del acceso.
                    </p>
                </header>

                <label class="block max-w-xs space-y-1 text-sm font-medium">
                    Monto a recargar (soles)
                    <input v-model.number="amount" class="form-input" type="number" min="10" max="999999.99" step="0.01" required />
                    <span class="text-xs text-gray-500">Mínimo {{ money(minimumAmount) }}.</span>
                </label>

                <div class="flex flex-wrap gap-2" role="group" aria-label="Medio de pago">
                    <button type="button" class="btn" :class="method === 'card' ? 'btn-primary' : 'btn-outline-primary'" @click="selectMethod('card')">Tarjeta</button>
                    <button type="button" class="btn" :class="method === 'yape' ? 'btn-primary' : 'btn-outline-primary'" @click="selectMethod('yape')">Yape</button>
                </div>

                <form v-show="method === 'card'" id="topup-card-form" class="space-y-4" @submit.prevent>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div id="topup-card-number" class="form-input"></div>
                        <div id="topup-card-expiration" class="form-input"></div>
                        <div id="topup-card-cvv" class="form-input"></div>
                        <input id="topup-card-name" class="form-input" placeholder="Titular de la tarjeta" />
                        <select id="topup-card-issuer" class="form-select"><option value="">Banco emisor</option></select>
                        <select id="topup-card-installments" class="form-select"><option value="">Cuotas</option></select>
                        <select id="topup-card-document-type" class="form-select"><option value="DNI">DNI</option><option value="CE">Carnet de extranjería</option><option value="RUC">RUC</option></select>
                        <input id="topup-card-document-number" class="form-input" placeholder="Número de documento" />
                        <input id="topup-card-email" class="form-input sm:col-span-2" type="email" placeholder="Correo electrónico" />
                    </div>
                    <button id="form-checkout__submit" type="submit" class="btn btn-success" :disabled="loading || !canPay">
                        <IconLoader v-if="loading" class="mr-2 h-4 w-4 animate-spin" />
                        Pagar {{ money(amount) }} con tarjeta
                    </button>
                </form>

                <form v-show="method === 'yape'" class="space-y-3" @submit.prevent="payYape">
                    <p class="text-sm text-gray-600">Ingresa el celular afiliado a Yape y el código de verificación generado desde la app.</p>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <input v-model="email" class="form-input" type="email" placeholder="Correo electrónico" required />
                        <input v-model="phoneNumber" class="form-input" type="tel" inputmode="numeric" placeholder="Celular Yape (9 dígitos)" maxlength="9" required />
                        <select v-model="documentType" class="form-select"><option value="DNI">DNI</option><option value="CE">Carnet de extranjería</option></select>
                        <input v-model="documentNumber" class="form-input" placeholder="Documento (opcional)" />
                        <input v-model="otp" class="form-input sm:col-span-2" inputmode="numeric" autocomplete="one-time-code" placeholder="Código OTP de Yape" required />
                    </div>
                    <button type="submit" class="btn btn-success" :disabled="loading || !canPay">
                        <IconLoader v-if="loading" class="mr-2 h-4 w-4 animate-spin" />
                        Pagar {{ money(amount) }} con Yape
                    </button>
                </form>

                <p v-if="error" role="alert" class="rounded border border-red-300 bg-red-50 p-3 text-sm text-red-700">{{ error }}</p>
                <p v-if="!publicKey" class="text-sm text-red-600">No hay una clave pública configurada para Mercado Pago.</p>
            </section>

            <section class="panel space-y-4">
                <header class="flex flex-wrap items-center justify-between gap-2">
                    <h2 class="text-lg font-semibold dark:text-white-light">Historial de pagos</h2>
                    <span class="text-xs text-gray-500">Últimos {{ payments.length }} movimientos</span>
                </header>

                <div class="overflow-x-auto">
                    <table class="table-hover w-full text-sm">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Medio</th>
                                <th class="text-right">Monto</th>
                                <th>Estado</th>
                                <th>Referencia</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="pago in payments" :key="pago.id">
                                <td>{{ pago.created_at }}</td>
                                <td>{{ pago.method }}</td>
                                <td class="text-right font-semibold" :class="pagoAcreditado(pago) ? 'text-success' : ''">{{ money(pago.amount) }}</td>
                                <td>
                                    <span class="rounded-full px-2 py-0.5 text-xs" :class="pagoAcreditado(pago) ? 'bg-success/10 text-success' : 'bg-warning/10 text-warning'">
                                        {{ pago.status_label }}
                                    </span>
                                </td>
                                <td class="font-mono text-xs">{{ pago.reference }}</td>
                            </tr>
                            <tr v-if="!payments.length">
                                <td colspan="5" class="text-center text-gray-500">Todavía no registraste recargas ni pagos.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <Link :href="route('even_ediciones_listado')" class="btn btn-outline-primary">Volver a ediciones</Link>
            </section>
        </main>
    </AppLayout>
</template>
