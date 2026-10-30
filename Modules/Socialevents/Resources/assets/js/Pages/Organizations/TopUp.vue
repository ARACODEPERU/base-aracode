<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import IconLoader from '@/Components/vristo/icon/icon-loader.vue';
import { loadMercadoPago } from '@mercadopago/sdk-js';
import Swal2 from 'sweetalert2';
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';

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
    // Equipo y espacios de usuario de la organización.
    seats: { type: Object, default: () => ({ used: 0, limit: null, available: null, unlimited: true }) },
    seatPrice: { type: Number, default: 1 },
    members: { type: Array, default: () => [] },
    capabilities: { type: Array, default: () => [] },
    seatPurchases: { type: Array, default: () => [] },
    documentRequests: { type: Array, default: () => [] },
    documentTypes: { type: Array, default: () => [] },
    billingDefaults: { type: Object, default: () => ({}) },
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

// Mismo tema azul noche y lima que las pantallas de la edición.
const page = usePage();
const sportsMode = computed(() => page.props.sportsOrganization?.mode === true);

// Tokens de los controles para que los campos se pinten sobre la tarjeta navy
// (la hoja global los tiñe de verde claro dentro de html.theme-torneos).
const sportsControlVars = computed(() => ({
    '--torneos-control-bg': '#0e2532',
    '--torneos-control-text': '#e2e8f0',
    '--torneos-control-border': '#2b4b5d',
    '--torneos-control-placeholder': '#64748b',
    '--torneos-dropdown-bg': '#122c3a',
}));



const method = ref('card');
const email = ref('');
const documentType = ref('DNI');
const documentNumber = ref('');
const phoneNumber = ref('');
const otp = ref('');
const loading = ref(false);
const error = ref('');
const cardFormContainer = ref(null);
// "topup" recarga saldo; "seats" compra espacios de usuario.
const paymentTarget = ref('topup');
const seatQuantity = ref(1);
let mercadoPago;
let cardForm;
const money = (value) => `S/ ${Number(value || 0).toFixed(2)}`;
const seatTotal = computed(() => Math.round(Number(props.seatPrice || 0) * Math.max(1, Number(seatQuantity.value) || 1) * 100) / 100);
const paymentAmount = computed(() => (paymentTarget.value === 'seats' ? seatTotal.value : Number(amount.value)));
const paymentLabel = computed(() => (paymentTarget.value === 'seats' ? 'espacios' : 'recarga'));
const canPay = computed(() => paymentTarget.value === 'seats'
    ? seatTotal.value > 0 && Number(seatQuantity.value) >= 1 && Number(seatQuantity.value) <= 50
    : Number(amount.value) >= props.minimumAmount && Number(amount.value) <= 999999.99);

// Atajos de recarga: el mínimo, su doble, quíntuple y décuplo.
const atajos = computed(() => {
    const base = Math.max(1, Number(props.minimumAmount) || 10);

    return [...new Set([base, base * 2, base * 5, base * 10])];
});

const setAmount = (valor) => {
    paymentTarget.value = 'topup';
    amount.value = Number(valor);
};

/** Lleva al formulario de pago con el carrito de espacios ya preparado. */
const startSeatPurchase = () => {
    if (!Number(seatQuantity.value) || Number(seatQuantity.value) < 1) {
        seatQuantity.value = 1;
    }
    paymentTarget.value = 'seats';
    remountCardForm();
    nextTick(() => document.getElementById('topup-payment')?.scrollIntoView({ behavior: 'smooth', block: 'start' }));
};

const remountCardForm = () => {
    if (cardForm) {
        cardForm.unmount();
        cardForm = null;
    }
    if (method.value === 'card') nextTick(() => mountCardForm());
};

const backToTopUp = () => {
    paymentTarget.value = 'topup';
    remountCardForm();
};

const metodoEtiqueta = computed(() => (method.value === 'yape' ? 'Yape' : 'Tarjeta'));

const saldoDespues = computed(() => Number(props.balance) + (Number(amount.value) || 0));

// Cuántos partidos permite el saldo (actual o después de la recarga).
const partidosConSaldo = computed(() => {
    const tarifa = Number(props.organization?.match_rate) || 0;

    if (tarifa <= 0) return null;

    return Math.floor(saldoDespues.value / tarifa);
});

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
    if (sportsMode.value) {
        if (props.access.sponsored || props.access.status === 'active') {
            return 'border border-lime-300/25 bg-lime-400/15 text-lime-200';
        }
        if (props.access.expired) return 'border border-rose-300/25 bg-rose-400/15 text-rose-200';

        return 'border border-amber-300/25 bg-amber-300/10 text-amber-200';
    }

    if (props.access.sponsored) return 'bg-primary/10 text-primary';
    if (props.access.expired) return 'bg-danger/10 text-danger';

    return props.access.status === 'active' ? 'bg-success/10 text-success' : 'bg-warning/10 text-warning';
});

const pagoAcreditado = (pago) => pago.status === 'credited' || pago.status === 'approved';

/** Chip del historial: acreditado en lima, en proceso en ámbar y el resto en rosa. */
const estadoPagoClase = (pago) => {
    if (pagoAcreditado(pago)) {
        return sportsMode.value ? 'border border-lime-300/25 bg-lime-400/15 text-lime-200' : 'bg-success/10 text-success';
    }

    if (['processing', 'pending', 'in_process'].includes(pago.status)) {
        return sportsMode.value ? 'border border-amber-300/25 bg-amber-300/10 text-amber-200' : 'bg-warning/10 text-warning';
    }

    return sportsMode.value ? 'border border-rose-300/25 bg-rose-400/15 text-rose-200' : 'bg-danger/10 text-danger';
};

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
        amount: String(Number(paymentAmount.value).toFixed(2)),
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
                    const isSeats = paymentTarget.value === 'seats';
                    const payload = {
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
                    };
                    if (isSeats) {
                        payload.quantity = Number(seatQuantity.value);
                    } else {
                        payload.amount = Number(amount.value);
                    }
                    const { data } = await axios.post(
                        isSeats ? route('even_organizations.topup.seats.card') : route('even_organizations.topup.card'),
                        payload,
                    );
                    if (data.status === 'approved') {
                        await Swal2.fire({
                            icon: 'success',
                            title: 'Pago aprobado',
                            text: isSeats
                                ? `Se agregaron ${seatQuantity.value} espacio(s) a tu organización.`
                                : 'El saldo de la organización fue recargado.',
                        });
                        window.location.reload();
                    } else {
                        error.value = 'El pago quedó pendiente o no fue aprobado. La compra se actualizará cuando Mercado Pago confirme el pago.';
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
        const isSeats = paymentTarget.value === 'seats';
        const payload = {
            token: token.id,
            phone_number: phoneNumber.value,
            payer: {
                email: email.value,
                identification: { type: documentType.value, number: documentNumber.value || null },
            },
        };
        if (isSeats) {
            payload.quantity = Number(seatQuantity.value);
        } else {
            payload.amount = Number(amount.value);
        }
        const { data } = await axios.post(
            isSeats ? route('even_organizations.topup.seats.yape') : route('even_organizations.topup.yape'),
            payload,
        );
        otp.value = '';
        if (data.status === 'approved') {
            await Swal2.fire({
                icon: 'success',
                title: 'Pago aprobado',
                text: isSeats
                    ? `Se agregaron ${seatQuantity.value} espacio(s) a tu organización.`
                    : 'El saldo de la organización fue recargado.',
            });
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

// ---------------------------------------------------------------------
// Equipo de la organización: alta, tareas y bajas.
// ---------------------------------------------------------------------
const memberForm = useForm({
    name: '',
    email: '',
    password: '',
    telefono: '',
    tipo_identidad: '1',
    numero_identidad: '',
    capabilities: ['results'],
});
const memberForms = reactive({});
const memberFormFor = (member) => {
    if (!memberForms[member.id]) {
        memberForms[member.id] = useForm({
            capabilities: Array.isArray(member.capabilities) && member.capabilities.length
                ? [...member.capabilities]
                : ['full'],
        });
    }

    return memberForms[member.id];
};
const addMember = () => memberForm.post(route('even_organizations.team.store'), {
    preserveScroll: true,
    onSuccess: () => {
        memberForm.reset();
        memberForm.capabilities = ['results'];
    },
});
const saveMember = (member) => memberFormFor(member).put(route('even_organizations.team.update', member.id), {
    preserveScroll: true,
});
const deactivateMember = async (member) => {
    const result = await Swal2.fire({
        title: '¿Desactivar este usuario?',
        text: 'Su espacio quedará disponible para otra persona de la organización.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Desactivar',
        cancelButtonText: 'Cancelar',
    });
    if (!result.isConfirmed) return;
    useForm({}).delete(route('even_organizations.team.destroy', member.id), { preserveScroll: true });
};
const reactivateMember = (member) => memberFormFor(member).post(route('even_organizations.team.reactivate', member.id), {
    preserveScroll: true,
});
const capabilityLabel = (key) => props.capabilities.find((item) => item.key === key)?.label || key;
const memberCapabilities = (member) => (Array.isArray(member.capabilities) ? member.capabilities : [])
    .map(capabilityLabel);

// ---------------------------------------------------------------------
// Comprobantes: se piden desde una compra ya acreditada.
// ---------------------------------------------------------------------
const documentTarget = ref(null);
const documentForm = useForm({
    purchase_type: 'seat_purchase',
    purchase_id: null,
    document_type_id: props.documentTypes.find((type) => String(type.sunat_id) === '03')?.id || '',
    billing_data: { ...props.billingDefaults },
    channels: ['email'],
});
const purchases = computed(() => {
    const seats = (props.seatPurchases || [])
        .filter((purchase) => ['credited', 'approved'].includes(purchase.status))
        .map((purchase) => ({
            key: `seat-${purchase.id}`,
            purchase_type: 'seat_purchase',
            purchase_id: purchase.id,
            label: `${purchase.quantity} espacio(s) de usuario`,
            amount: Number(purchase.total || 0),
            created_at: purchase.created_at,
            requested: (props.documentRequests || []).some((request) => request.purchase_type === 'seat_purchase' && Number(request.purchase_id) === Number(purchase.id) && request.status !== 'rejected'),
        }));
    const topups = (props.payments || [])
        .filter((payment) => payment.status === 'credited' || payment.status === 'approved')
        .map((payment) => ({
            key: `topup-${payment.id}`,
            purchase_type: 'topup',
            purchase_id: payment.id,
            label: 'Recarga de saldo',
            amount: Number(payment.amount || 0),
            created_at: payment.created_at,
            requested: (props.documentRequests || []).some((request) => request.purchase_type === 'topup' && Number(request.purchase_id) === Number(payment.id) && request.status !== 'rejected'),
        }));

    return [...seats, ...topups];
});
const openDocumentRequest = (purchase) => {
    documentTarget.value = purchase;
    documentForm.clearErrors();
    documentForm.purchase_type = purchase.purchase_type;
    documentForm.purchase_id = purchase.purchase_id;
    documentForm.billing_data = { ...props.billingDefaults };
    documentForm.channels = ['email'];
    documentForm.document_type_id = props.documentTypes.find((type) => String(type.sunat_id) === '03')?.id || '';
};
const sendDocumentRequest = () => documentForm.post(route('even_organizations.documents.request'), {
    preserveScroll: true,
    onSuccess: () => {
        documentTarget.value = null;
        documentForm.reset();
    },
});
const documentTypeChanged = () => {
    const type = props.documentTypes.find((item) => Number(item.id) === Number(documentForm.document_type_id));
    if (String(type?.sunat_id) === '01') {
        documentForm.billing_data.business_name = documentForm.billing_data.business_name || props.organization.name || '';
    }
};

onBeforeUnmount(() => cardForm?.unmount());
</script>

<template>
    <AppLayout title="Mi plan">
        <Navigation :routeModule="route('even_dashboard')" titleModule="GESTIÓN DEPORTIVA" :data="[{ title: 'Mi plan' }]" />
        <main class="mx-auto mt-5 max-w-6xl space-y-5 px-3 pb-8 sm:px-5 lg:px-6" :class="sportsMode ? 'text-slate-100' : ''">
            <section
                class="relative overflow-hidden rounded-2xl border p-5 sm:p-6"
                :class="sportsMode
                    ? 'border-white/10 bg-[#0b1f2b] text-white shadow-[0_26px_70px_rgba(0,0,0,0.38)]'
                    : 'panel space-y-4'"
            >
                <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                    <div class="min-w-0">
                        <div
                            class="mb-2 inline-flex items-center gap-2 rounded-full border px-3 py-1 text-[0.65rem] font-bold uppercase tracking-[0.2em]"
                            :class="sportsMode ? 'border-lime-300/25 bg-lime-300/10 text-lime-200' : 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200'"
                        >
                            <span class="h-1.5 w-1.5 rounded-full bg-lime-400"></span> Pagos
                        </div>
                        <h1 class="text-2xl font-black tracking-tight sm:text-3xl" :class="sportsMode ? 'text-white' : 'text-gray-900 dark:text-white'">
                            Mi plan
                        </h1>
                        <p class="mt-1 text-sm" :class="sportsMode ? 'text-slate-300' : 'text-gray-600 dark:text-gray-300'">
                            {{ organization.name || 'Mi organización' }}
                            <span v-if="organization.match_rate"> · S/ {{ Number(organization.match_rate).toFixed(2) }} por partido</span>
                        </p>
                        <p v-if="vigencia" class="mt-1 text-xs" :class="sportsMode ? 'text-slate-400' : 'text-gray-500'">
                            {{ access.sponsored ? 'Sin fecha de vencimiento: la cuenta está bonificada.' : vigencia }}
                        </p>
                    </div>

                    <div class="grid w-full gap-3 sm:grid-cols-2 lg:w-auto lg:min-w-[24rem]">
                        <div
                            class="rounded-xl border p-4"
                            :class="sportsMode ? 'border-[#234052] bg-[#122c3a]' : 'border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800'"
                        >
                            <p class="text-[11px] font-bold uppercase tracking-wide" :class="sportsMode ? 'text-slate-400' : 'text-gray-500'">Saldo disponible</p>
                            <p class="mt-1 text-2xl font-black" :class="sportsMode ? 'text-lime-300' : 'text-gray-900 dark:text-white'">{{ money(balance) }}</p>
                            <span class="mt-2 inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-bold" :class="estadoClase">
                                {{ access.label || 'Sin estado' }}
                            </span>
                        </div>
                        <div
                            class="rounded-xl border p-4"
                            :class="sportsMode ? 'border-[#234052] bg-[#122c3a]' : 'border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800'"
                        >
                            <p class="text-[11px] font-bold uppercase tracking-wide" :class="sportsMode ? 'text-slate-400' : 'text-gray-500'">Partidos con este saldo</p>
                            <p v-if="partidosConSaldo !== null" class="mt-1 text-2xl font-black" :class="sportsMode ? 'text-white' : 'text-gray-900 dark:text-white'">
                                {{ Math.max(partidosConSaldo, 0) }}
                            </p>
                            <p v-else class="mt-1 text-sm font-semibold" :class="sportsMode ? 'text-slate-300' : 'text-gray-700 dark:text-gray-200'">Sin tarifa configurada</p>
                            <p class="mt-1 text-[11px]" :class="sportsMode ? 'text-slate-400' : 'text-gray-500'">Contando la recarga de {{ money(amount) }}</p>
                        </div>
                    </div>
                </div>

                <!-- El acceso vencido deja el módulo en solo lectura, pero la vista
                     de pagos sigue abierta: aquí se recarga el saldo. -->
                <div
                    v-if="access.expired && ! access.sponsored"
                    role="alert"
                    class="mt-5 rounded-xl border p-3 text-sm"
                    :class="sportsMode ? 'border-rose-300/25 bg-rose-400/10 text-rose-100' : 'border-amber-300 bg-amber-50 text-amber-800'"
                >
                    Tu acceso está vencido o bloqueado: el módulo quedó en solo lectura. Puedes recargar saldo aquí mismo; el administrador confirma el
                    pago y fija la fecha de reactivación.
                </div>
                <p
                    v-else-if="access.status === 'trial'"
                    class="mt-5 rounded-xl border p-3 text-sm"
                    :class="sportsMode ? 'border-amber-300/25 bg-amber-300/10 text-amber-100' : 'border-amber-200 bg-amber-50 text-amber-800'"
                >
                    Estás en el período de prueba. Puedes recargar saldo desde ahora para no interrumpir la generación de partidos.
                </p>
            </section>

            <!-- Equipo de la organización: espacios, tareas y bajas. -->
            <section
                class="rounded-2xl border p-5 sm:p-6"
                :class="sportsMode ? 'border-[#234052] bg-[#0b1f2b] text-slate-100 shadow-[0_26px_70px_rgba(0,0,0,0.38)]' : 'panel space-y-4'"
            >
                <header class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold" :class="sportsMode ? 'text-white' : 'text-gray-900 dark:text-white-light'">Mi equipo</h2>
                        <p class="mt-1 max-w-2xl text-sm" :class="sportsMode ? 'text-slate-400' : 'text-gray-500'">
                            Cada persona entra con su propio correo y ve solo las tareas que le asignes. Los espacios se compran por adelantado.
                        </p>
                    </div>
                    <span class="rounded-full border px-3 py-1 text-xs font-bold" :class="sportsMode ? 'border-[#2b4b5d] bg-[#122c3a] text-lime-200' : 'border-gray-200 bg-gray-50 text-gray-700'">
                        {{ seats.used }} en uso
                        <template v-if="seats.limit !== null"> · {{ seats.limit }} disponibles en total</template>
                        <template v-else> · sin límite</template>
                    </span>
                </header>

                <div class="mt-5 grid gap-5 lg:grid-cols-[20rem_minmax(0,1fr)]">
                    <aside class="h-fit rounded-xl border p-4" :class="sportsMode ? 'border-[#234052] bg-[#122c3a]' : 'border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800'">
                        <p class="text-[11px] font-bold uppercase tracking-[0.18em]" :class="sportsMode ? 'text-lime-300' : 'text-gray-500'">Espacios de usuario</p>
                        <p class="mt-2 text-2xl font-black" :class="sportsMode ? 'text-white' : 'text-gray-900 dark:text-white'">
                            {{ seats.used }}<span class="text-sm font-semibold" :class="sportsMode ? 'text-slate-400' : 'text-gray-500'"> / {{ seats.limit === null ? '∞' : seats.limit }}</span>
                        </p>
                        <p class="mt-1 text-xs" :class="sportsMode ? 'text-slate-400' : 'text-gray-500'">
                            Precio por espacio: <strong>{{ money(seatPrice) }}</strong>
                        </p>

                        <template v-if="seats.limit !== null">
                            <label class="mt-4 block text-xs font-bold uppercase tracking-wide" :class="sportsMode ? 'text-slate-400' : 'text-gray-600'" for="seat-quantity">
                                Espacios nuevos
                            </label>
                            <div class="mt-2 flex items-center gap-2">
                                <input id="seat-quantity" v-model.number="seatQuantity" class="form-input w-24" type="number" min="1" max="50" />
                                <button type="button" class="btn btn-primary flex-1" @click="startSeatPurchase">Comprar</button>
                            </div>
                            <p class="mt-2 text-[11px] leading-relaxed" :class="sportsMode ? 'text-slate-500' : 'text-gray-500'">
                                Total: {{ money(seatTotal) }}. Se paga con tarjeta o Yape en la sección de pago.
                            </p>
                        </template>
                        <p v-else class="mt-4 text-xs" :class="sportsMode ? 'text-slate-400' : 'text-gray-500'">
                            Esta organización no tiene un límite de usuarios configurado.
                        </p>
                    </aside>

                    <div class="space-y-3">
                        <article
                            v-for="member in members"
                            :key="member.id"
                            class="rounded-xl border p-4"
                            :class="sportsMode ? 'border-[#234052] bg-[#0f2634]' : 'border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900/40'"
                        >
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="font-semibold" :class="sportsMode ? 'text-white' : 'text-gray-900 dark:text-white'">
                                        {{ member.name }}
                                        <span v-if="member.is_owner" class="ml-2 rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold uppercase text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300">Propietario</span>
                                        <span v-else-if="member.status !== 'active'" class="ml-2 rounded-full bg-gray-200 px-2 py-0.5 text-[10px] font-bold uppercase text-gray-600 dark:bg-gray-700 dark:text-gray-300">Inactivo</span>
                                    </p>
                                    <p class="text-xs" :class="sportsMode ? 'text-slate-400' : 'text-gray-500'">{{ member.email }}</p>
                                    <p class="mt-1 text-xs" :class="sportsMode ? 'text-slate-300' : 'text-gray-600'">
                                        {{ member.is_owner ? 'Acceso completo' : (memberCapabilities(member).join(' · ') || 'Sin tareas') }}
                                    </p>
                                </div>
                                <div v-if="!member.is_owner" class="flex flex-wrap gap-2">
                                    <button v-if="member.status === 'active'" type="button" class="btn btn-sm btn-outline-danger" @click="deactivateMember(member)">Desactivar</button>
                                    <button v-else type="button" class="btn btn-sm btn-outline-success" @click="reactivateMember(member)">Reactivar</button>
                                </div>
                            </div>

                            <details v-if="!member.is_owner" class="mt-3">
                                <summary class="cursor-pointer text-xs font-bold uppercase tracking-wide" :class="sportsMode ? 'text-lime-300' : 'text-primary'">
                                    Cambiar tareas
                                </summary>
                                <div class="mt-2 grid gap-2 sm:grid-cols-2">
                                    <label v-for="capability in capabilities" :key="capability.key" class="flex items-start gap-2 rounded-lg border p-2 text-xs" :class="sportsMode ? 'border-[#234052]' : 'border-gray-200 dark:border-gray-700'">
                                        <input v-model="memberFormFor(member).capabilities" type="checkbox" :value="capability.key" class="mt-0.5" />
                                        <span><strong class="block">{{ capability.label }}</strong>{{ capability.description }}</span>
                                    </label>
                                </div>
                                <button type="button" class="btn btn-sm btn-primary mt-2" :disabled="memberFormFor(member).processing" @click="saveMember(member)">Guardar tareas</button>
                                <p v-if="Object.keys(memberFormFor(member).errors).length" class="mt-1 text-xs text-red-500">{{ Object.values(memberFormFor(member).errors).join(' ') }}</p>
                            </details>
                        </article>
                        <p v-if="!members.length" class="text-sm" :class="sportsMode ? 'text-slate-400' : 'text-gray-500'">Todavía no hay usuarios asociados.</p>
                    </div>
                </div>

                <details class="mt-4 rounded-xl border" :class="sportsMode ? 'border-[#234052] p-4' : 'border-gray-200 p-4 dark:border-gray-700'">
                    <summary class="cursor-pointer text-sm font-semibold" :class="sportsMode ? 'text-lime-300' : 'text-primary'">Agregar una persona al equipo</summary>
                    <form class="mt-3 grid gap-3 md:grid-cols-3" @submit.prevent="addMember">
                        <label class="space-y-1 text-sm">Nombre *<input v-model="memberForm.name" class="form-input" required /></label>
                        <label class="space-y-1 text-sm">Correo *<input v-model="memberForm.email" class="form-input" type="email" required /></label>
                        <label class="space-y-1 text-sm">Contraseña inicial *<input v-model="memberForm.password" class="form-input" type="password" minlength="8" autocomplete="new-password" required /></label>
                        <label class="space-y-1 text-sm">Documento *<input v-model="memberForm.numero_identidad" class="form-input" required /></label>
                        <label class="space-y-1 text-sm">Teléfono<input v-model="memberForm.telefono" class="form-input" /></label>
                        <label class="space-y-1 text-sm">Tipo de documento
                            <select v-model="memberForm.tipo_identidad" class="form-select">
                                <option value="1">DNI</option>
                                <option value="0">Sin documento</option>
                                <option value="6">RUC</option>
                            </select>
                        </label>
                        <div class="space-y-2 md:col-span-3">
                            <p class="text-sm font-semibold">Tareas que podrá hacer</p>
                            <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                <label v-for="capability in capabilities" :key="capability.key" class="flex items-start gap-2 rounded-lg border p-2 text-xs" :class="sportsMode ? 'border-[#234052]' : 'border-gray-200 dark:border-gray-700'">
                                    <input v-model="memberForm.capabilities" type="checkbox" :value="capability.key" class="mt-0.5" />
                                    <span><strong class="block">{{ capability.label }}</strong>{{ capability.description }}</span>
                                </label>
                            </div>
                        </div>
                        <div class="md:col-span-3">
                            <button class="btn btn-primary" :disabled="memberForm.processing || !memberForm.capabilities.length">Crear usuario</button>
                        </div>
                        <p v-if="Object.keys(memberForm.errors).length" class="text-sm text-red-500 md:col-span-3">{{ Object.values(memberForm.errors).join(' ') }}</p>
                        <p class="text-xs md:col-span-3" :class="sportsMode ? 'text-slate-500' : 'text-gray-500'">
                            Consume un espacio de los {{ seats.limit === null ? 'ilimitados' : seats.limit }}. Nunca recibe acceso al panel de administración.
                        </p>
                    </form>
                </details>
            </section>

            <section
                id="topup-payment"
                class="rounded-2xl border p-5 sm:p-6"
                :class="sportsMode ? 'border-[#234052] bg-[#0b1f2b] text-slate-100 shadow-[0_26px_70px_rgba(0,0,0,0.38)]' : 'panel space-y-4'"
                :style="sportsMode ? sportsControlVars : null"
            >
                <header class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold" :class="sportsMode ? 'text-white' : 'text-gray-900 dark:text-white-light'">
                            {{ paymentTarget === 'seats' ? 'Comprar espacios de usuario' : 'Recargar saldo' }}
                        </h2>
                        <p class="mt-1 max-w-xl text-sm" :class="sportsMode ? 'text-slate-400' : 'text-gray-500'">
                            {{ paymentTarget === 'seats'
                                ? 'Cada espacio permite dar acceso a una persona más de tu organización con las tareas que tú elijas.'
                                : 'El saldo prepago es lo que permite registrar partidos; recargarlo no cambia la fecha de vencimiento del acceso.' }}
                        </p>
                    </div>
                    <span
                        class="inline-flex shrink-0 items-center gap-2 rounded-full border px-3 py-1 text-xs font-bold"
                        :class="sportsMode ? 'border-[#2b4b5d] bg-[#122c3a] text-lime-200' : 'border-gray-200 bg-gray-50 text-gray-700'"
                    >
                        {{ paymentTarget === 'seats' ? `${seatQuantity} espacio(s)` : metodoEtiqueta }}
                    </span>
                </header>

                <div
                    v-if="paymentTarget === 'seats'"
                    class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border p-3 text-sm"
                    :class="sportsMode ? 'border-lime-300/30 bg-lime-400/10 text-lime-100' : 'border-primary/20 bg-primary/5 text-primary'">
                    <p>
                        Total a pagar: <strong>{{ money(seatTotal) }}</strong>
                        ({{ seatQuantity }} × {{ money(seatPrice) }}).
                    </p>
                    <button type="button" class="text-xs font-bold uppercase underline" @click="backToTopUp">Volver a recargar saldo</button>
                </div>

                <div class="mt-5 grid gap-6 lg:grid-cols-[minmax(0,1fr)_17rem]">
                    <div class="space-y-5">
                        <!-- Monto -->
                        <div v-if="paymentTarget === 'topup'">
                            <label for="topup-amount" class="block text-xs font-bold uppercase tracking-wide" :class="sportsMode ? 'text-slate-400' : 'text-gray-600'">
                                Monto a recargar (soles)
                            </label>
                            <div
                                class="mt-2 flex max-w-sm items-center overflow-hidden rounded-xl border"
                                :class="sportsMode ? 'border-[#2b4b5d] bg-[#0e2532]' : 'border-gray-300 bg-white dark:border-gray-600 dark:bg-gray-800'"
                            >
                                <span class="pl-4 text-sm font-black" :class="sportsMode ? 'text-lime-300' : 'text-gray-500'">S/</span>
                                <input
                                    id="topup-amount"
                                    v-model.number="amount"
                                    type="number"
                                    min="10"
                                    max="999999.99"
                                    step="0.01"
                                    required
                                    aria-describedby="topup-amount-help"
                                    class="w-full border-0 bg-transparent px-3 py-3 text-lg font-black focus:ring-0"
                                    :class="sportsMode ? 'text-white' : 'text-gray-900 dark:text-white'"
                                />
                            </div>
                            <p id="topup-amount-help" class="mt-2 text-xs" :class="sportsMode ? 'text-slate-400' : 'text-gray-500'">
                                Mínimo {{ money(minimumAmount) }}. Se acredita en tu saldo apenas Mercado Pago confirma el pago.
                            </p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <button
                                    v-for="monto in atajos"
                                    :key="monto"
                                    type="button"
                                    class="rounded-lg border px-3 py-1.5 text-xs font-bold transition"
                                    :class="Number(amount) === monto
                                        ? (sportsMode ? 'border-lime-300/60 bg-lime-400/15 text-lime-200' : 'border-primary bg-primary/10 text-primary')
                                        : (sportsMode ? 'border-[#2b4b5d] text-slate-300 hover:border-lime-300/50 hover:text-lime-200' : 'border-gray-300 text-gray-600 hover:border-primary dark:border-gray-600 dark:text-gray-300')"
                                    @click="setAmount(monto)"
                                >
                                    {{ money(monto) }}
                                </button>
                            </div>
                        </div>

                        <!-- Cantidad de espacios -->
                        <div v-else>
                            <label for="topup-seat-quantity" class="block text-xs font-bold uppercase tracking-wide" :class="sportsMode ? 'text-slate-400' : 'text-gray-600'">
                                Espacios a comprar
                            </label>
                            <div class="mt-2 flex max-w-sm items-center gap-3">
                                <input
                                    id="topup-seat-quantity"
                                    v-model.number="seatQuantity"
                                    type="number"
                                    min="1"
                                    max="50"
                                    class="form-input w-28"
                                />
                                <p class="text-sm" :class="sportsMode ? 'text-slate-300' : 'text-gray-600'">
                                    × {{ money(seatPrice) }} = <strong>{{ money(seatTotal) }}</strong>
                                </p>
                            </div>
                            <p class="mt-2 text-xs" :class="sportsMode ? 'text-slate-400' : 'text-gray-500'">
                                Los espacios se suman al límite de tu organización apenas se confirma el pago.
                            </p>
                        </div>

                        <!-- Medio de pago -->
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide" :class="sportsMode ? 'text-slate-400' : 'text-gray-600'">Medio de pago</p>
                            <div
                                class="mt-2 inline-flex rounded-xl border p-1"
                                :class="sportsMode ? 'border-[#2b4b5d] bg-[#0e2532]' : 'border-gray-300 bg-gray-100 dark:border-gray-600 dark:bg-gray-800'"
                                role="group"
                                aria-label="Medio de pago"
                            >
                                <button
                                    type="button"
                                    class="rounded-lg px-4 py-2 text-xs font-bold uppercase tracking-wide transition"
                                    :class="method === 'card'
                                        ? (sportsMode ? 'bg-[#c8f169] text-[#08251a]' : 'bg-white text-gray-900 shadow')
                                        : (sportsMode ? 'text-slate-300 hover:text-lime-200' : 'text-gray-600 dark:text-gray-300')"
                                    :aria-pressed="method === 'card'"
                                    @click="selectMethod('card')"
                                >
                                    💳 Tarjeta
                                </button>
                                <button
                                    type="button"
                                    class="rounded-lg px-4 py-2 text-xs font-bold uppercase tracking-wide transition"
                                    :class="method === 'yape'
                                        ? (sportsMode ? 'bg-[#c8f169] text-[#08251a]' : 'bg-white text-gray-900 shadow')
                                        : (sportsMode ? 'text-slate-300 hover:text-lime-200' : 'text-gray-600 dark:text-gray-300')"
                                    :aria-pressed="method === 'yape'"
                                    @click="selectMethod('yape')"
                                >
                                    📱 Yape
                                </button>
                            </div>
                        </div>

                        <!-- Tarjeta -->
                        <form v-show="method === 'card'" id="topup-card-form" class="space-y-4" @submit.prevent>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="sm:col-span-2">
                                    <p class="mb-1 text-xs font-bold uppercase tracking-wide" :class="sportsMode ? 'text-slate-400' : 'text-gray-600'">Número de tarjeta</p>
                                    <div
                                        id="topup-card-number"
                                        :class="sportsMode ? 'topup-card-slot overflow-hidden rounded-xl border border-[#2b4b5d] bg-[#0e2532]' : 'form-input'"
                                    ></div>
                                </div>
                                <div>
                                    <p class="mb-1 text-xs font-bold uppercase tracking-wide" :class="sportsMode ? 'text-slate-400' : 'text-gray-600'">Vencimiento</p>
                                    <div
                                        id="topup-card-expiration"
                                        :class="sportsMode ? 'topup-card-slot overflow-hidden rounded-xl border border-[#2b4b5d] bg-[#0e2532]' : 'form-input'"
                                    ></div>
                                </div>
                                <div>
                                    <p class="mb-1 text-xs font-bold uppercase tracking-wide" :class="sportsMode ? 'text-slate-400' : 'text-gray-600'">CVV</p>
                                    <div
                                        id="topup-card-cvv"
                                        :class="sportsMode ? 'topup-card-slot overflow-hidden rounded-xl border border-[#2b4b5d] bg-[#0e2532]' : 'form-input'"
                                    ></div>
                                </div>
                                <div>
                                    <label for="topup-card-name" class="mb-1 block text-xs font-bold uppercase tracking-wide" :class="sportsMode ? 'text-slate-400' : 'text-gray-600'">Titular de la tarjeta</label>
                                    <input id="topup-card-name" class="form-input w-full" placeholder="Como figura en la tarjeta" />
                                </div>
                                <div>
                                    <label for="topup-card-issuer" class="mb-1 block text-xs font-bold uppercase tracking-wide" :class="sportsMode ? 'text-slate-400' : 'text-gray-600'">Banco emisor</label>
                                    <select id="topup-card-issuer" class="form-select w-full"><option value="">Banco emisor</option></select>
                                </div>
                                <div>
                                    <label for="topup-card-installments" class="mb-1 block text-xs font-bold uppercase tracking-wide" :class="sportsMode ? 'text-slate-400' : 'text-gray-600'">Cuotas</label>
                                    <select id="topup-card-installments" class="form-select w-full"><option value="">Cuotas</option></select>
                                </div>
                                <div>
                                    <label for="topup-card-document-type" class="mb-1 block text-xs font-bold uppercase tracking-wide" :class="sportsMode ? 'text-slate-400' : 'text-gray-600'">Tipo de documento</label>
                                    <select id="topup-card-document-type" class="form-select w-full"><option value="DNI">DNI</option><option value="CE">Carnet de extranjería</option><option value="RUC">RUC</option></select>
                                </div>
                                <div>
                                    <label for="topup-card-document-number" class="mb-1 block text-xs font-bold uppercase tracking-wide" :class="sportsMode ? 'text-slate-400' : 'text-gray-600'">Número de documento</label>
                                    <input id="topup-card-document-number" class="form-input w-full" placeholder="Número de documento" />
                                </div>
                                <div>
                                    <label for="topup-card-email" class="mb-1 block text-xs font-bold uppercase tracking-wide" :class="sportsMode ? 'text-slate-400' : 'text-gray-600'">Correo electrónico</label>
                                    <input id="topup-card-email" class="form-input w-full" type="email" placeholder="Para enviarte el comprobante" />
                                </div>
                            </div>
                            <button
                                id="form-checkout__submit"
                                type="submit"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-xl px-5 py-3 text-sm font-bold uppercase tracking-wider transition disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto"
                                :class="sportsMode ? 'bg-[#c8f169] text-[#08251a] hover:bg-[#d9fb8d]' : 'btn btn-success'"
                                :disabled="loading || !canPay"
                            >
                                <IconLoader v-if="loading" class="h-4 w-4 animate-spin" />
                                Pagar {{ money(paymentAmount) }} con tarjeta
                            </button>
                            <p class="text-[11px]" :class="sportsMode ? 'text-slate-500' : 'text-gray-500'">
                                El cobro lo procesa Mercado Pago. No almacenamos los datos de tu tarjeta.
                            </p>
                        </form>

                        <!-- Yape -->
                        <form v-show="method === 'yape'" class="space-y-4" @submit.prevent="payYape">
                            <p class="text-sm" :class="sportsMode ? 'text-slate-400' : 'text-gray-600'">
                                Ingresa el celular afiliado a Yape y el código de verificación generado desde la app.
                            </p>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="topup-yape-email" class="mb-1 block text-xs font-bold uppercase tracking-wide" :class="sportsMode ? 'text-slate-400' : 'text-gray-600'">Correo electrónico</label>
                                    <input id="topup-yape-email" v-model="email" class="form-input w-full" type="email" placeholder="tucorreo@ejemplo.com" required />
                                </div>
                                <div>
                                    <label for="topup-yape-phone" class="mb-1 block text-xs font-bold uppercase tracking-wide" :class="sportsMode ? 'text-slate-400' : 'text-gray-600'">Celular Yape</label>
                                    <input id="topup-yape-phone" v-model="phoneNumber" class="form-input w-full" type="tel" inputmode="numeric" placeholder="9 dígitos" maxlength="9" required />
                                </div>
                                <div>
                                    <label for="topup-yape-document-type" class="mb-1 block text-xs font-bold uppercase tracking-wide" :class="sportsMode ? 'text-slate-400' : 'text-gray-600'">Tipo de documento</label>
                                    <select id="topup-yape-document-type" v-model="documentType" class="form-select w-full"><option value="DNI">DNI</option><option value="CE">Carnet de extranjería</option></select>
                                </div>
                                <div>
                                    <label for="topup-yape-document-number" class="mb-1 block text-xs font-bold uppercase tracking-wide" :class="sportsMode ? 'text-slate-400' : 'text-gray-600'">Número de documento</label>
                                    <input id="topup-yape-document-number" v-model="documentNumber" class="form-input w-full" placeholder="Opcional" />
                                </div>
                                <div class="sm:col-span-2">
                                    <label for="topup-yape-otp" class="mb-1 block text-xs font-bold uppercase tracking-wide" :class="sportsMode ? 'text-slate-400' : 'text-gray-600'">Código OTP de Yape</label>
                                    <input id="topup-yape-otp" v-model="otp" class="form-input w-full" inputmode="numeric" autocomplete="one-time-code" placeholder="Código de 6 dígitos" required />
                                </div>
                            </div>
                            <button
                                type="submit"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-xl px-5 py-3 text-sm font-bold uppercase tracking-wider transition disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto"
                                :class="sportsMode ? 'bg-[#c8f169] text-[#08251a] hover:bg-[#d9fb8d]' : 'btn btn-success'"
                                :disabled="loading || !canPay"
                            >
                                <IconLoader v-if="loading" class="h-4 w-4 animate-spin" />
                                Pagar {{ money(paymentAmount) }} con Yape
                            </button>
                        </form>

                        <p
                            v-if="error"
                            role="alert"
                            class="rounded-xl border p-3 text-sm"
                            :class="sportsMode ? 'border-rose-300/25 bg-rose-400/10 text-rose-100' : 'border-red-300 bg-red-50 text-red-700'"
                        >{{ error }}</p>
                        <p v-if="!publicKey" class="text-sm" :class="sportsMode ? 'text-rose-300' : 'text-red-600'" role="alert">
                            No hay una clave pública configurada para Mercado Pago.
                        </p>
                    </div>

                    <!-- Resumen de la recarga -->
                    <aside
                        class="h-fit rounded-xl border p-4"
                        :class="sportsMode ? 'border-[#234052] bg-[#122c3a]' : 'border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800'"
                        aria-label="Resumen de la recarga"
                    >
                        <p class="text-[11px] font-bold uppercase tracking-[0.18em]" :class="sportsMode ? 'text-lime-300' : 'text-gray-500'">
                            {{ paymentTarget === 'seats' ? 'Resumen de la compra' : 'Resumen de la recarga' }}
                        </p>
                        <dl class="mt-3 space-y-2 text-sm">
                            <div class="flex items-center justify-between gap-3">
                                <dt :class="sportsMode ? 'text-slate-400' : 'text-gray-500'">Monto</dt>
                                <dd class="font-bold" :class="sportsMode ? 'text-white' : 'text-gray-900 dark:text-white'">{{ money(paymentAmount) }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt :class="sportsMode ? 'text-slate-400' : 'text-gray-500'">Medio</dt>
                                <dd class="font-semibold" :class="sportsMode ? 'text-slate-200' : 'text-gray-800 dark:text-gray-200'">{{ metodoEtiqueta }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt :class="sportsMode ? 'text-slate-400' : 'text-gray-500'">{{ paymentTarget === 'seats' ? 'Espacios' : 'Saldo actual' }}</dt>
                                <dd :class="sportsMode ? 'text-slate-200' : 'text-gray-800 dark:text-gray-200'">{{ paymentTarget === 'seats' ? `${seatQuantity} × ${money(seatPrice)}` : money(balance) }}</dd>
                            </div>
                        </dl>
                        <div class="mt-3 border-t pt-3" :class="sportsMode ? 'border-[#2b4b5d]' : 'border-gray-200 dark:border-gray-700'">
                            <p class="text-[11px] uppercase" :class="sportsMode ? 'text-slate-400' : 'text-gray-500'">
                                {{ paymentTarget === 'seats' ? 'Nuevo límite de usuarios' : 'Saldo después de la recarga' }}
                            </p>
                            <p class="text-xl font-black" :class="sportsMode ? 'text-lime-300' : 'text-gray-900 dark:text-white'">
                                {{ paymentTarget === 'seats'
                                    ? (seats.limit === null ? 'Sin límite' : Number(seats.limit) + Number(seatQuantity || 0))
                                    : money(saldoDespues) }}
                            </p>
                            <p v-if="paymentTarget === 'topup' && partidosConSaldo !== null" class="mt-1 text-[11px]" :class="sportsMode ? 'text-slate-400' : 'text-gray-500'">
                                Alcanza para {{ Math.max(partidosConSaldo, 0) }} partido(s) más.
                            </p>
                        </div>
                        <p class="mt-3 text-[11px] leading-relaxed" :class="sportsMode ? 'text-slate-500' : 'text-gray-500'">
                            {{ paymentTarget === 'seats'
                                ? 'La compra de espacios no cambia la vigencia ni el saldo para partidos.'
                                : 'Recargar no cambia la fecha de vencimiento del acceso; solo suma saldo para generar partidos.' }}
                        </p>
                    </aside>
                </div>
            </section>

            <!-- Comprobantes de las compras ya acreditadas. -->
            <section
                class="rounded-2xl border p-5 sm:p-6"
                :class="sportsMode ? 'border-[#234052] bg-[#0b1f2b] text-slate-100 shadow-[0_26px_70px_rgba(0,0,0,0.38)]' : 'panel space-y-4'"
            >
                <header>
                    <h2 class="text-lg font-semibold" :class="sportsMode ? 'text-white' : 'text-gray-900 dark:text-white-light'">Comprobantes</h2>
                    <p class="mt-1 text-sm" :class="sportsMode ? 'text-slate-400' : 'text-gray-500'">
                        Pide tu boleta, factura o nota de venta de una compra acreditada. El administrador la emite y puede enviártela por correo o WhatsApp.
                    </p>
                </header>

                <div class="mt-4 overflow-x-auto rounded-xl border" :class="sportsMode ? 'border-[#234052]' : 'border-gray-200 dark:border-gray-700'">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs font-bold uppercase tracking-wide" :class="sportsMode ? 'bg-[#122c3a] text-lime-200' : 'bg-gray-50 text-gray-600 dark:bg-gray-800 dark:text-gray-300'">
                                <th class="px-4 py-3">Compra</th>
                                <th class="px-4 py-3">Fecha</th>
                                <th class="px-4 py-3 text-right">Monto</th>
                                <th class="px-4 py-3">Comprobante</th>
                            </tr>
                        </thead>
                        <tbody :class="sportsMode ? 'divide-y divide-[#1b3544]' : 'divide-y divide-gray-200 dark:divide-gray-700'">
                            <tr v-for="purchase in purchases" :key="purchase.key">
                                <td class="px-4 py-3">{{ purchase.label }}</td>
                                <td class="px-4 py-3" :class="sportsMode ? 'text-slate-300' : ''">{{ fecha(purchase.created_at) }}</td>
                                <td class="px-4 py-3 text-right font-bold">{{ money(purchase.amount) }}</td>
                                <td class="px-4 py-3">
                                    <span v-if="purchase.requested" class="text-xs" :class="sportsMode ? 'text-slate-400' : 'text-gray-500'">Solicitado</span>
                                    <button v-else type="button" class="btn btn-sm btn-outline-primary" @click="openDocumentRequest(purchase)">Solicitar</button>
                                </td>
                            </tr>
                            <tr v-if="!purchases.length">
                                <td colspan="4" class="px-4 py-8 text-center" :class="sportsMode ? 'text-slate-400' : 'text-gray-500'">Todavía no hay compras acreditadas.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <form v-if="documentTarget" class="mt-4 grid gap-3 rounded-xl border p-4 md:grid-cols-3" :class="sportsMode ? 'border-[#234052] bg-[#122c3a]' : 'border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800'" @submit.prevent="sendDocumentRequest">
                    <p class="text-sm font-semibold md:col-span-3">
                        Comprobante para {{ documentTarget.label }} · {{ money(documentTarget.amount) }}
                    </p>
                    <label class="space-y-1 text-sm">Tipo de comprobante
                        <select v-model="documentForm.document_type_id" class="form-select" required @change="documentTypeChanged">
                            <option v-for="type in documentTypes" :key="type.id" :value="type.id">
                                {{ type.sunat_id === '01' ? 'Factura electrónica' : (type.sunat_id === '03' ? 'Boleta electrónica' : 'Nota de venta') }}
                            </option>
                        </select>
                    </label>
                    <label class="space-y-1 text-sm">Nombre o razón social *
                        <input v-model="documentForm.billing_data.business_name" class="form-input" maxlength="255" required />
                    </label>
                    <label class="space-y-1 text-sm">RUC (solo factura)
                        <input v-model="documentForm.billing_data.ruc" class="form-input" maxlength="11" inputmode="numeric" />
                    </label>
                    <label class="space-y-1 text-sm">Documento de identidad
                        <input v-model="documentForm.billing_data.document_number" class="form-input" maxlength="20" />
                    </label>
                    <label class="space-y-1 text-sm">Dirección fiscal
                        <input v-model="documentForm.billing_data.address" class="form-input" maxlength="255" />
                    </label>
                    <label class="space-y-1 text-sm">Ubigeo (6 dígitos)
                        <input v-model="documentForm.billing_data.ubigeo" class="form-input" maxlength="6" inputmode="numeric" />
                    </label>
                    <label class="space-y-1 text-sm">Correo de envío
                        <input v-model="documentForm.billing_data.email" class="form-input" type="email" />
                    </label>
                    <label class="space-y-1 text-sm">WhatsApp de envío
                        <input v-model="documentForm.billing_data.phone" class="form-input" maxlength="20" />
                    </label>
                    <div class="flex flex-col justify-end gap-1 text-xs md:col-span-3">
                        <label><input v-model="documentForm.channels" type="checkbox" value="email" /> Enviar por correo</label>
                        <label><input v-model="documentForm.channels" type="checkbox" value="whatsapp" /> Enviar por WhatsApp</label>
                    </div>
                    <div class="flex items-center gap-2 md:col-span-3">
                        <button class="btn btn-primary" :disabled="documentForm.processing">Enviar solicitud</button>
                        <button type="button" class="btn btn-outline-danger" @click="documentTarget = null">Cancelar</button>
                    </div>
                    <p v-if="Object.keys(documentForm.errors).length" class="text-sm text-red-500 md:col-span-3">{{ Object.values(documentForm.errors).join(' ') }}</p>
                </form>

                <div v-if="documentRequests.length" class="mt-4 space-y-2">
                    <p class="text-xs font-bold uppercase tracking-wide" :class="sportsMode ? 'text-slate-400' : 'text-gray-500'">Solicitudes</p>
                    <article v-for="request in documentRequests" :key="request.id" class="flex flex-wrap items-center justify-between gap-2 rounded-lg border p-3 text-sm" :class="sportsMode ? 'border-[#234052]' : 'border-gray-200 dark:border-gray-700'">
                        <div>
                            <p class="font-semibold">{{ request.document_type || 'Comprobante' }} <span class="text-xs text-gray-500">· {{ request.number || 'En revisión' }}</span></p>
                            <p class="text-xs" :class="sportsMode ? 'text-slate-400' : 'text-gray-500'">
                                {{ request.status === 'issued' ? 'Emitido' : (request.status === 'rejected' ? 'Rechazado' : 'Solicitado') }} · {{ fecha(request.created_at) }}
                            </p>
                            <p v-if="request.rejection_reason" class="text-xs text-red-500">Motivo: {{ request.rejection_reason }}</p>
                        </div>
                        <a v-if="request.can_download" :href="route('even_organizations.documents.download', request.id)" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener">Descargar</a>
                    </article>
                </div>
            </section>

            <section
                class="rounded-2xl border p-5 sm:p-6"
                :class="sportsMode ? 'border-[#234052] bg-[#0b1f2b] text-slate-100 shadow-[0_26px_70px_rgba(0,0,0,0.38)]' : 'panel space-y-4'"
            >
                <header class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold" :class="sportsMode ? 'text-white' : 'text-gray-900 dark:text-white-light'">Historial de pagos</h2>
                        <p class="text-xs" :class="sportsMode ? 'text-slate-400' : 'text-gray-500'">Últimos {{ payments.length }} movimientos</p>
                    </div>
                    <Link
                        :href="route('even_ediciones_listado')"
                        class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-xs font-bold uppercase tracking-wider transition"
                        :class="sportsMode
                            ? 'border border-[#2b4b5d] text-slate-200 hover:border-lime-300/60 hover:text-lime-200'
                            : 'btn btn-outline-primary text-xs'"
                    >
                        Volver a ediciones
                    </Link>
                </header>

                <div
                    class="mt-4 overflow-x-auto rounded-xl border"
                    :class="sportsMode ? 'border-[#234052]' : 'border-gray-200 dark:border-gray-700'"
                >
                    <table class="w-full text-sm">
                        <thead>
                            <tr
                                class="text-left text-xs font-bold uppercase tracking-wide"
                                :class="sportsMode ? 'bg-[#122c3a] text-lime-200' : 'bg-gray-50 text-gray-600 dark:bg-gray-800 dark:text-gray-300'"
                            >
                                <th class="px-4 py-3">Fecha</th>
                                <th class="px-4 py-3">Medio</th>
                                <th class="px-4 py-3 text-right">Monto</th>
                                <th class="px-4 py-3">Estado</th>
                                <th class="px-4 py-3">Referencia</th>
                            </tr>
                        </thead>
                        <tbody :class="sportsMode ? 'divide-y divide-[#1b3544]' : 'divide-y divide-gray-200 dark:divide-gray-700'">
                            <tr
                                v-for="pago in payments"
                                :key="pago.id"
                                class="transition-colors"
                                :class="sportsMode ? 'hover:bg-[#122c3a]' : 'hover:bg-gray-50 dark:hover:bg-gray-800/60'"
                            >
                                <td class="whitespace-nowrap px-4 py-3" :class="sportsMode ? 'text-slate-300' : ''">{{ pago.created_at }}</td>
                                <td class="px-4 py-3" :class="sportsMode ? 'text-slate-300' : ''">{{ pago.method }}</td>
                                <td
                                    class="whitespace-nowrap px-4 py-3 text-right font-bold"
                                    :class="pagoAcreditado(pago) ? (sportsMode ? 'text-lime-300' : 'text-success') : (sportsMode ? 'text-slate-300' : 'text-gray-700')"
                                >{{ money(pago.amount) }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-bold" :class="estadoPagoClase(pago)">
                                        {{ pago.status_label }}
                                    </span>
                                </td>
                                <td class="max-w-[16rem] truncate px-4 py-3 font-mono text-xs" :class="sportsMode ? 'text-slate-400' : 'text-gray-500'" :title="pago.reference">
                                    {{ pago.reference }}
                                </td>
                            </tr>
                            <tr v-if="!payments.length">
                                <td colspan="5" class="px-4 py-12 text-center">
                                    <div
                                        class="mx-auto grid h-12 w-12 place-items-center rounded-full text-xl"
                                        :class="sportsMode ? 'border border-[#2b4b5d] bg-[#122c3a]' : 'bg-gray-100 dark:bg-gray-800'"
                                        aria-hidden="true"
                                    >🧾</div>
                                    <p class="mt-3 text-sm font-semibold" :class="sportsMode ? 'text-slate-200' : 'text-gray-700 dark:text-gray-200'">
                                        Todavía no registraste recargas ni pagos.
                                    </p>
                                    <p class="mt-1 text-xs" :class="sportsMode ? 'text-slate-400' : 'text-gray-500'">
                                        Cuando recargues saldo, el movimiento aparecerá aquí con su estado y referencia.
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </AppLayout>
</template>

<style scoped>
    /* Los iframes de Mercado Pago se montan dentro del slot: el borde y el
       fondo los pone el contenedor para integrarse con la tarjeta navy. */
    .topup-card-slot :deep(iframe) {
        display: block;
        width: 100%;
    }
</style>
