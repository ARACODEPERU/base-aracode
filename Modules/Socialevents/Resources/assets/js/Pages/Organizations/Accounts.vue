<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import { useForm } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, reactive, ref, watch } from 'vue';
import Swal2 from 'sweetalert2';

const props = defineProps({
    organizations: { type: Array, default: () => [] },
    districts: { type: Array, default: () => [] },
    documentTypes: { type: Array, default: () => [] },
    paymentMethods: { type: Array, default: () => [] },
    identityTypes: { type: Array, default: () => [] },
    alerts: { type: Object, default: () => ({}) },
    recentAlerts: { type: Array, default: () => [] },
    // Catálogo de tareas que el administrador puede asignar a un usuario.
    capabilities: { type: Array, default: () => [] },
    seatPrice: { type: Number, default: 1 },
});

const statusLabels = {
    trial: 'Prueba',
    active: 'Activo',
    sponsored: 'Cortesía / patrocinio',
    suspended: 'Bloqueado',
};

const statusClasses = {
    trial: 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300',
    active: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300',
    sponsored: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-300',
    suspended: 'bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-300',
};

const documentLabels = { 80: 'Nota de venta', '01': 'Factura electrónica', '03': 'Boleta electrónica' };
const documentStatusLabels = { requested: 'Solicitado', issued: 'Emitido', rejected: 'Rechazado' };
const documentStatusClasses = {
    requested: 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300',
    issued: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300',
    rejected: 'bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-300',
};

const capabilityLabel = (key) => props.capabilities.find((item) => item.key === key)?.label || key;
const memberCapabilities = (member) => {
    const raw = member?.capabilities;
    const values = Array.isArray(raw) ? raw : (() => {
        try { return JSON.parse(raw || '[]'); } catch { return []; }
    })();

    return values.length ? values.map(capabilityLabel) : ['Sin tareas asignadas'];
};

const fullName = (person) => [person.name, person.apellido_paterno, person.apellido_materno].filter(Boolean).join(' ');
const money = (value) => Number(value || 0).toFixed(2);
const prettyDate = (value) => {
    if (!value) return '—';
    const [year, month, day] = String(value).slice(0, 10).split('-');
    return `${day}/${month}/${year}`;
};

// El historial guarda el id del medio de pago (o "manual" en los ajustes de saldo).
const methodLabel = (value) => {
    const method = props.paymentMethods.find((item) => String(item.id) === String(value));
    if (method) return method.description;

    return value === 'manual' ? 'Ajuste manual' : (value || '—');
};

const search = ref('');
const activeModal = ref(null);
const selectedId = ref(null);
const editTab = ref('datos');
const showAlertsPanel = ref(false);
const showRecentAlerts = ref(false);

const today = new Date().toISOString().slice(0, 10);

const selectedOrganization = computed(() =>
    props.organizations.find((organization) => organization.id === selectedId.value) || null
);

const filteredOrganizations = computed(() => {
    const term = search.value.trim().toLowerCase();
    if (!term) return props.organizations;

    return props.organizations.filter((organization) => [organization.name, organization.owner_name, organization.owner_email, organization.ruc]
        .filter(Boolean)
        .join(' ')
        .toLowerCase()
        .includes(term));
});

// Listado global de compras: junta las compras de espacios y las recargas de
// cada organización para que el superadministrador las revise sin entrar una
// por una.
const purchaseSearch = ref('');
const allPurchases = computed(() => {
    const rows = props.organizations.flatMap((organization) => {
        const seats = (organization.seat_purchases || []).map((purchase) => ({
            key: `seat-${organization.id}-${purchase.id}`,
            organization_id: organization.id,
            organization: organization.name,
            type: 'Compra de espacios',
            date: purchase.created_at,
            amount: Number(purchase.total || 0),
            method: purchase.payment_method === 'admin' ? 'Cortesía' : purchase.payment_method,
            status: purchase.status,
            reference: purchase.reference,
            purchase_type: 'seat_purchase',
            purchase_id: purchase.id,
        }));
        const topups = (organization.payments || []).map((payment) => ({
            key: `topup-${organization.id}-${payment.id ?? payment.created_at}`,
            organization_id: organization.id,
            organization: organization.name,
            type: 'Recarga de saldo',
            date: payment.created_at,
            amount: Number(payment.amount || 0),
            method: methodLabel(payment.payment_method),
            status: 'credited',
            reference: payment.reference,
            purchase_type: 'topup',
            purchase_id: payment.id,
        }));

        return [...seats, ...topups];
    });

    return rows.sort((a, b) => String(b.date).localeCompare(String(a.date)));
});
const filteredPurchases = computed(() => {
    const term = purchaseSearch.value.trim().toLowerCase();
    if (!term) return allPurchases.value;

    return allPurchases.value.filter((purchase) => [purchase.organization, purchase.type, purchase.method, purchase.reference]
        .filter(Boolean)
        .join(' ')
        .toLowerCase()
        .includes(term));
});

const purchaseDocument = (purchase) => {
    const organization = props.organizations.find((item) => Number(item.id) === Number(purchase.organization_id));

    return (organization?.document_requests || []).find((request) => request.purchase_type === purchase.purchase_type
        && Number(request.purchase_id) === Number(purchase.purchase_id));
};
const openOrganizationDocuments = (organizationId) => {
    const organization = props.organizations.find((item) => Number(item.id) === Number(organizationId));
    if (organization) openEdit(organization, 'comprobantes');
};

const stats = computed(() => ({
    total: props.organizations.length,
    active: props.organizations.filter((organization) => organization.status === 'active').length,
    blocked: props.organizations.filter((organization) => organization.status === 'suspended').length,
    balance: props.organizations.reduce((total, organization) => total + Number(organization.prepaid_balance || 0), 0),
}));

const newOrganization = useForm({
    name: '', tipo_organizacion: 'empresa', ruc: '', address: '', ubigeo: '', match_rate: 5,
    owner: { person_id: null, name: '', email: '', password: '', telefono: '', tipo_identidad: '1', numero_identidad: '' },
});
const ownerSearch = ref('');
const ownerResults = ref([]);
const ownerSearchLoading = ref(false);
const selectedOwner = ref(null);
let ownerSearchTimeout;
let ownerSearchController;

const searchOwner = () => {
    const term = ownerSearch.value.trim();
    if (selectedOwner.value && term === selectedOwner.value.name) {
        clearTimeout(ownerSearchTimeout);
        ownerSearchController?.abort();
        ownerSearchController = null;
        ownerSearchLoading.value = false;
        ownerResults.value = [];
        return;
    }
    if (selectedOwner.value) clearOwnerSelection();
    clearTimeout(ownerSearchTimeout);
    ownerSearchController?.abort();
    ownerSearchController = null;
    ownerResults.value = [];
    if (term.length < 2) {
        ownerSearchLoading.value = false;
        return;
    }

    ownerSearchLoading.value = true;
    ownerSearchTimeout = setTimeout(async () => {
        const controller = new AbortController();
        ownerSearchController = controller;
        try {
            const response = await fetch(`${route('even_organizations.owner-search')}?search=${encodeURIComponent(term)}`, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                signal: controller.signal,
            });
            if (!response.ok) throw new Error('No se pudo buscar al titular.');
            const result = await response.json();
            if (!controller.signal.aborted && ownerSearch.value.trim() === term) {
                ownerResults.value = result.people || [];
            }
        } catch (error) {
            if (error.name !== 'AbortError') ownerResults.value = [];
        } finally {
            if (ownerSearchController === controller) {
                ownerSearchLoading.value = false;
                ownerSearchController = null;
            }
        }
    }, 250);
};

const clearOwnerSelection = () => {
    selectedOwner.value = null;
    newOrganization.owner.person_id = null;
};

const resetOwnerSearch = () => {
    clearTimeout(ownerSearchTimeout);
    ownerSearchController?.abort();
    ownerSearchController = null;
    ownerSearchLoading.value = false;
    ownerResults.value = [];
    ownerSearch.value = '';
    selectedOwner.value = null;
};

const selectOwner = (person) => {
    if (!person.available) return;
    selectedOwner.value = person;
    newOrganization.owner.person_id = person.id;
    newOrganization.owner.name = person.name;
    newOrganization.owner.email = person.email || '';
    newOrganization.owner.telefono = person.telefono || '';
    newOrganization.owner.tipo_identidad = String(person.tipo_identidad || '1');
    newOrganization.owner.numero_identidad = person.numero_identidad || '';
    ownerSearch.value = person.name;
    ownerResults.value = [];
};

watch(ownerSearch, searchOwner);
onBeforeUnmount(() => {
    clearTimeout(ownerSearchTimeout);
    ownerSearchController?.abort();
});

const openCreate = () => {
    resetOwnerSearch();
    newOrganization.reset();
    newOrganization.owner.person_id = null;
    newOrganization.owner.tipo_identidad = '1';
    newOrganization.match_rate = 5;
    activeModal.value = 'create';
    selectedId.value = null;
};

const createOrganization = () => newOrganization.post(route('even_organizations.store'), {
    preserveScroll: true,
    onSuccess: () => {
        newOrganization.reset();
        resetOwnerSearch();
        activeModal.value = null;
    },
});

const alertsForm = useForm({ ...props.alerts });
const saveAlerts = () => alertsForm.put(route('even_organizations.alerts.update'), { preserveScroll: true });

// Un formulario por organización para cada acción, así los errores y los mensajes no se mezclan.
// Se llenan con un watch porque Inertia reutiliza el componente al volver del
// servidor: después de crear una organización hay que registrar sus formularios.
const organizationForms = reactive({});
const forms = reactive({});

const buildOrganizationForms = (organization) => ({
    profile: useForm({
        name: organization.name,
        tipo_organizacion: organization.tipo_organizacion || 'empresa',
        ruc: organization.ruc || '',
        address: organization.address || '',
        ubigeo: organization.ubigeo || '',
        reason: '',
    }),
    payment: useForm({
        amount: null,
        document_type_id: props.documentTypes.find((type) => type.sunat_id === '80')?.id || '',
        payment_method_id: props.paymentMethods[0]?.id || '',
        paid_at: today,
        reference: '',
        ruc: organization.ruc || '',
        address: organization.address || '',
        ubigeo: organization.ubigeo || '',
    }),
    alert: useForm({ type: 'custom', message: '', send_email: true, send_system: true }),
    user: useForm({
        name: '', email: '', password: '', telefono: '', tipo_identidad: '1', numero_identidad: '',
        capabilities: ['full'],
    }),
    seats: useForm({ quantity: 1, reason: '' }),
});

const buildSettingsForms = (organization) => ({
    settings: useForm({
        match_rate: organization.match_rate,
        status: organization.status,
        trial_days: organization.trial_days || 30,
        trial_ends_at: organization.trial_ends_at ? String(organization.trial_ends_at).slice(0, 10) : '',
        access_until: organization.access_until?.slice(0, 10) || '',
        credit_amount: null,
        reason: '',
    }),
    block: useForm({ reason: '' }),
    unblock: useForm({ reason: '', access_until: '' }),
});

watch(
    () => props.organizations,
    (organizations) => {
        organizations.forEach((organization) => {
            if (!organizationForms[organization.id]) organizationForms[organization.id] = buildOrganizationForms(organization);
            if (!forms[organization.id]) forms[organization.id] = buildSettingsForms(organization);
        });
    },
    { immediate: true }
);

const openEdit = (organization, tab = 'datos') => {
    selectedId.value = organization.id;
    editTab.value = tab;
    activeModal.value = 'edit';
};

const openPayments = (organization) => {
    const payment = organizationForms[organization.id]?.payment;
    if (payment) {
        payment.clearErrors();
        payment.ruc = organization.ruc || '';
        payment.address = organization.address || '';
        payment.ubigeo = organization.ubigeo || '';
    }
    selectedId.value = organization.id;
    activeModal.value = 'pay';
};

const closeModal = () => {
    activeModal.value = null;
    selectedId.value = null;
};

const selectedDocumentType = computed(() => {
    if (!selectedOrganization.value) return null;
    const payment = organizationForms[selectedOrganization.value.id].payment;

    return props.documentTypes.find((type) => type.id === payment.document_type_id) || null;
});
const requiresBillingData = computed(() => ['01', '03'].includes(String(selectedDocumentType.value?.sunat_id)));
const requiresRuc = computed(() => String(selectedDocumentType.value?.sunat_id) === '01');

const saveProfile = (id) => organizationForms[id].profile.put(route('even_organizations.profile.update', id), { preserveScroll: true });
const registerPayment = (id) => organizationForms[id].payment.post(route('even_organizations.payments.store', id), {
    preserveScroll: true,
    onSuccess: () => {
        organizationForms[id].payment.reset('amount', 'reference');
        closeModal();
    },
});
const sendAlert = (id) => organizationForms[id].alert.post(route('even_organizations.alerts.send', id), {
    preserveScroll: true,
    onSuccess: () => organizationForms[id].alert.reset('message'),
});
const addOrganizationUser = (id) => organizationForms[id].user.post(route('even_organizations.users.store', id), {
    preserveScroll: true,
    onSuccess: () => {
        organizationForms[id].user.reset();
        organizationForms[id].user.capabilities = ['full'];
    },
});
const grantSeats = (id) => organizationForms[id].seats.post(route('even_organizations.seats.grant', id), {
    preserveScroll: true,
    onSuccess: () => organizationForms[id].seats.reset('quantity', 'reason'),
});

// Acciones sobre las solicitudes de comprobante que llegan del organizador.
const documentForms = reactive({});
const buildDocumentForm = (request) => useForm({
    channels: request.delivery_channels?.length ? [...request.delivery_channels] : ['email'],
    email: request.billing_data?.email || '',
    phone: request.billing_data?.phone || '',
    reason: '',
});
const documentFormFor = (request) => {
    if (!documentForms[request.id]) documentForms[request.id] = buildDocumentForm(request);

    return documentForms[request.id];
};
const issueDocument = (id) => documentFormFor(id).post(route('even_organizations.documents.issue', id), { preserveScroll: true });
const rejectDocument = (id) => documentFormFor(id).post(route('even_organizations.documents.reject', id), { preserveScroll: true });
const markAlertRead = (id) => useForm({}).post(route('even_organizations.alerts.read', id), { preserveScroll: true });

const saveSettings = (id) => forms[id].settings.put(route('even_organizations.update', id), { preserveScroll: true });
const blockAccount = (id) => forms[id].block.post(route('even_organizations.block', id), {
    preserveScroll: true,
    onSuccess: () => forms[id].block.reset(),
});
const unblockAccount = (id) => forms[id].unblock.post(route('even_organizations.unblock', id), {
    preserveScroll: true,
    onSuccess: () => forms[id].unblock.reset(),
});

const deleteOrganization = async (organization) => {
    const result = await Swal2.fire({
        title: '¿Eliminar organización?',
        text: 'Se bloqueará su acceso y se conservará el historial de pagos y auditoría.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Eliminar',
        cancelButtonText: 'Cancelar',
    });
    if (!result.isConfirmed) return;

    const form = useForm({ reason: 'Eliminación manual por superadministrador.' });
    form.delete(route('even_organizations.destroy', organization.id), { preserveScroll: true });
};
</script>

<template>
    <AppLayout title="Cuentas de organizaciones">
        <Navigation :routeModule="route('even_dashboard')" titleModule="GESTIÓN DEPORTIVA" :data="[{ title: 'Cuentas' }]" />

        <div class="relative z-0 mt-4 w-full space-y-6">
            <!-- Encabezado -->
            <header class="panel overflow-hidden border-0 bg-gradient-to-r from-primary/10 via-primary/5 to-transparent">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <span class="grid h-12 w-12 shrink-0 place-content-center rounded-2xl bg-primary/15 text-primary">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V8l7-4 7 4v13M9 21v-5h6v5M9 10h.01M15 10h.01" />
                            </svg>
                        </span>
                        <div>
                            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Organizaciones</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Administración, pagos y avisos para cada cuenta.</p>
                        </div>
                    </div>
                    <button class="btn btn-primary" type="button" @click="openCreate">Nueva organización</button>
                </div>
            </header>

            <!-- Resumen -->
            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <article class="panel">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Organizaciones</p>
                    <p class="mt-1 text-2xl font-bold">{{ stats.total }}</p>
                </article>
                <article class="panel">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Cuentas activas</p>
                    <p class="mt-1 text-2xl font-bold text-emerald-600">{{ stats.active }}</p>
                </article>
                <article class="panel">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Bloqueadas</p>
                    <p class="mt-1 text-2xl font-bold text-red-600">{{ stats.blocked }}</p>
                </article>
                <article class="panel">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Saldo acumulado</p>
                    <p class="mt-1 text-2xl font-bold text-primary">S/ {{ money(stats.balance) }}</p>
                </article>
            </section>

            <!-- Compras globales de todas las organizaciones -->
            <section class="panel space-y-3">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold">Compras de organizaciones</h2>
                        <p class="text-xs text-gray-500">Compras de espacios y recargas de saldo, con su comprobante.</p>
                    </div>
                    <input v-model="purchaseSearch" type="search" class="form-input w-full sm:w-72" placeholder="Buscar organización, tipo o referencia" />
                </div>
                <div class="overflow-x-auto rounded-lg border">
                    <table class="table-hover w-full text-sm">
                        <thead>
                            <tr>
                                <th class="text-left">Organización</th>
                                <th class="text-left">Concepto</th>
                                <th class="text-left">Fecha</th>
                                <th class="text-right">Monto</th>
                                <th class="text-left">Medio / estado</th>
                                <th class="text-left">Comprobante</th>
                                <th class="text-right"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="purchase in filteredPurchases.slice(0, 20)" :key="purchase.key">
                                <td class="font-semibold">{{ purchase.organization }}</td>
                                <td>{{ purchase.type }}</td>
                                <td class="whitespace-nowrap">{{ prettyDate(purchase.date) }}</td>
                                <td class="text-right font-semibold">S/ {{ money(purchase.amount) }}</td>
                                <td>
                                    <p>{{ purchase.method || '—' }}</p>
                                    <p class="text-xs text-gray-500">{{ purchase.status }}</p>
                                </td>
                                <td class="text-xs">
                                    <span v-if="purchaseDocument(purchase)" class="rounded-full px-2 py-0.5 font-bold" :class="documentStatusClasses[purchaseDocument(purchase).status] || 'bg-gray-100 text-gray-600'">
                                        {{ documentStatusLabels[purchaseDocument(purchase).status] || purchaseDocument(purchase).status }}
                                    </span>
                                    <span v-else class="text-gray-500">Sin solicitud</span>
                                </td>
                                <td class="text-right">
                                    <button type="button" class="btn btn-sm btn-outline-primary" @click="openOrganizationDocuments(purchase.organization_id)">Revisar</button>
                                </td>
                            </tr>
                            <tr v-if="!filteredPurchases.length">
                                <td colspan="7" class="py-6 text-center text-gray-500">Todavía no hay compras registradas.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Listado -->
            <section class="panel organization-accounts-list p-0">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200/70 p-4 dark:border-gray-700">
                    <h2 class="text-base font-semibold">Listado de organizaciones</h2>
                    <input v-model="search" type="search" class="form-input w-full sm:w-72" placeholder="Buscar por organización, RUC o titular" />
                </div>

                <div class="overflow-x-auto">
                    <table class="table-hover w-full text-sm">
                        <thead>
                            <tr>
                                <th class="text-left">Organización</th>
                                <th class="text-left">Titular</th>
                                <th class="text-left">Estado</th>
                                <th class="text-right">Saldo</th>
                                <th class="text-left">Vigencia</th>
                                <th class="text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="organization in filteredOrganizations" :key="organization.id">
                                <td>
                                    <p class="font-semibold text-gray-900 dark:text-white">{{ organization.name }}</p>
                                    <p class="text-xs text-gray-500">
                                        {{ organization.tipo_organizacion === 'persona_natural' ? 'Persona natural' : 'Empresa' }}
                                        <span v-if="organization.ruc"> · RUC {{ organization.ruc }}</span>
                                        <span v-else class="text-amber-600"> · Sin RUC</span>
                                    </p>
                                </td>
                                <td>
                                    <p class="font-medium">{{ organization.owner_name || 'Sin titular' }}</p>
                                    <p class="text-xs text-gray-500">{{ organization.owner_email || '—' }}</p>
                                    <p v-if="organization.owner_phone" class="text-xs text-gray-500">Tel. {{ organization.owner_phone }}</p>
                                </td>
                                <td>
                                    <span class="rounded-full px-3 py-1 text-xs font-semibold" :class="statusClasses[organization.status] || 'bg-gray-100 text-gray-600'">
                                        {{ statusLabels[organization.status] || organization.status }}
                                    </span>
                                </td>
                                <td class="text-right font-semibold">S/ {{ money(organization.prepaid_balance) }}</td>
                                <td>
                                    <span v-if="organization.access_until" class="text-xs">Hasta {{ prettyDate(organization.access_until) }}</span>
                                    <span v-else-if="organization.trial_ends_at" class="text-xs">Prueba hasta {{ prettyDate(organization.trial_ends_at) }}</span>
                                    <span v-else class="text-xs text-gray-500">Sin fecha</span>
                                </td>
                                <td>
                                    <div class="flex flex-wrap items-center justify-end gap-1.5">
                                        <button type="button" class="btn btn-sm btn-outline-primary" title="Editar datos" @click="openEdit(organization, 'datos')">Editar</button>
                                        <button type="button" class="btn btn-sm btn-success" title="Registrar pago" @click="openPayments(organization)">Cobros</button>
                                        <button type="button" class="btn btn-sm btn-outline-primary" title="Avisos de pago" @click="openEdit(organization, 'avisos')">Avisos</button>
                                        <button v-if="organization.status !== 'suspended'" type="button" class="btn btn-sm btn-outline-danger" @click="openEdit(organization, 'acceso')">Bloquear</button>
                                        <button v-else type="button" class="btn btn-sm btn-outline-success" @click="openEdit(organization, 'acceso')">Activar</button>
                                        <button type="button" class="btn btn-sm btn-danger" @click="deleteOrganization(organization)">Eliminar</button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!filteredOrganizations.length">
                                <td colspan="6" class="py-8 text-center text-gray-500">
                                    {{ organizations.length ? 'Ninguna organización coincide con la búsqueda.' : 'Todavía no hay organizaciones registradas.' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Avisos recientes -->
            <section class="panel space-y-3">
                <button class="flex w-full items-center justify-between gap-2 text-left" type="button" @click="showRecentAlerts = !showRecentAlerts">
                    <span class="font-semibold">Avisos recientes de pago <span class="ml-2 text-xs font-normal text-gray-500">({{ recentAlerts.length }})</span></span>
                    <span class="text-xs text-primary">{{ showRecentAlerts ? 'Ocultar' : 'Mostrar' }}</span>
                </button>
                <template v-if="showRecentAlerts">
                    <article v-for="alert in recentAlerts" :key="alert.id" class="flex flex-wrap items-start justify-between gap-3 rounded-lg border p-3 text-sm">
                        <div>
                            <p class="font-semibold">{{ alert.organization_name }} · {{ alert.type }}</p>
                            <p>{{ alert.message }}</p>
                            <p class="text-xs text-gray-500">{{ alert.user_name }} · {{ alert.created_at }}</p>
                        </div>
                        <button v-if="!alert.read_at" class="btn btn-sm btn-outline-primary" type="button" @click="markAlertRead(alert.id)">Marcar como leída</button>
                        <span v-else class="text-xs text-gray-500">Leída</span>
                    </article>
                    <p v-if="!recentAlerts.length" class="text-sm text-gray-500">Sin avisos registrados.</p>
                </template>
            </section>

            <!-- Configuración global de avisos -->
            <section class="panel space-y-4">
                <button class="flex w-full items-center justify-between gap-2 text-left" type="button" @click="showAlertsPanel = !showAlertsPanel">
                    <span class="font-semibold">Programación global de alertas de pago</span>
                    <span class="text-xs text-primary">{{ showAlertsPanel ? 'Ocultar' : 'Configurar' }}</span>
                </button>
                <template v-if="showAlertsPanel">
                    <form class="grid gap-4 md:grid-cols-4" @submit.prevent="saveAlerts">
                        <label class="flex items-center gap-2 text-sm"><input v-model="alertsForm.alerts_enabled" type="checkbox" /> Activar avisos automáticos</label>
                        <label class="flex items-center gap-2 text-sm"><input v-model="alertsForm.email_enabled" type="checkbox" /> Enviar correo</label>
                        <label class="flex items-center gap-2 text-sm"><input v-model="alertsForm.system_enabled" type="checkbox" /> Aviso en sistema</label>
                        <label class="space-y-1 text-sm">Avisar días antes<input v-model.number="alertsForm.days_before" class="form-input" type="number" min="1" max="60" /></label>
                        <label class="space-y-1 text-sm">Días de atraso<input v-model.number="alertsForm.overdue_after" class="form-input" type="number" min="0" max="90" /></label>
                        <label class="space-y-1 text-sm">Repetir cada (días; 0 = una vez)<input v-model.number="alertsForm.repeat_every" class="form-input" type="number" min="0" max="30" /></label>
                        <label class="space-y-1 text-sm">Hora de aviso<input v-model="alertsForm.time" class="form-input" type="time" /></label>
                        <button class="btn btn-primary self-end" :disabled="alertsForm.processing">Guardar configuración</button>
                    </form>
                    <p class="text-xs text-gray-500">Los avisos se procesan con el scheduler de Laravel, a las {{ alertsForm.time }} en la zona horaria configurada.</p>
                    <p v-if="Object.keys(alertsForm.errors).length" class="text-sm text-red-600">{{ Object.values(alertsForm.errors).join(' ') }}</p>
                </template>
            </section>
        </div>

        <!-- Lista compartida de ubigeos para los formularios -->
        <datalist id="organizations-ubigeo-list">
            <option v-for="district in districts" :key="district.id" :value="district.id">{{ district.name }}</option>
        </datalist>

        <!-- Modal: nueva organización -->
        <div v-if="activeModal === 'create'" class="fixed inset-0 z-[60] overflow-y-auto bg-black/50 p-4" @click.self="closeModal">
            <div class="mx-auto w-full max-w-4xl rounded-xl bg-white p-6 shadow-2xl dark:bg-[#0e1726]">
                <div class="mb-4 flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-bold">Nueva organización</h2>
                        <p class="text-sm text-gray-500">Crea la cuenta y su usuario organizador. Los datos de facturación son opcionales.</p>
                    </div>
                    <button type="button" class="text-2xl leading-none text-gray-400 hover:text-gray-700" @click="closeModal">&times;</button>
                </div>

                <form class="space-y-5" @submit.prevent="createOrganization">
                    <div class="grid gap-4 md:grid-cols-3">
                        <label class="space-y-1 text-sm">Organización *<input v-model="newOrganization.name" class="form-input" maxlength="180" required /></label>
                        <label class="space-y-1 text-sm">Tipo
                            <select v-model="newOrganization.tipo_organizacion" class="form-select">
                                <option value="empresa">Empresa</option>
                                <option value="persona_natural">Persona natural</option>
                            </select>
                        </label>
                        <label class="space-y-1 text-sm">RUC (opcional)
                            <input v-model="newOrganization.ruc" class="form-input" inputmode="numeric" maxlength="11" placeholder="Se puede ingresar al cobrar" />
                            <span class="block text-xs text-gray-500">Solo se pide al emitir factura.</span>
                        </label>
                        <label class="space-y-1 text-sm">Tarifa por equipo/partido (S/) *
                            <input v-model.number="newOrganization.match_rate" class="form-input" type="number" min="0" step="0.01" required />
                        </label>
                        <label class="space-y-1 text-sm">Dirección fiscal (opcional)
                            <input v-model="newOrganization.address" class="form-input" maxlength="255" />
                        </label>
                        <label class="space-y-1 text-sm">Ubigeo SUNAT (opcional)
                            <input v-model="newOrganization.ubigeo" class="form-input" list="organizations-ubigeo-list" inputmode="numeric" maxlength="6" placeholder="Escribe el código o distrito" />
                        </label>
                    </div>

                    <div class="rounded-lg border p-4">
                        <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">Titular de la cuenta</h3>
                        <div class="mb-4">
                            <label for="organization-owner-search" class="mb-1 block text-sm">Buscar persona existente</label>
                            <input id="organization-owner-search" v-model="ownerSearch" class="form-input" autocomplete="off" placeholder="Nombre, correo o documento (mínimo 2 caracteres)" />
                            <p v-if="ownerSearchLoading" class="mt-1 text-xs text-gray-500">Buscando personas…</p>
                            <div v-else-if="ownerResults.length" class="mt-2 max-h-48 overflow-y-auto rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-[#0e1726]">
                                <button v-for="person in ownerResults" :key="person.id" type="button" class="flex w-full items-center justify-between gap-3 border-b border-gray-100 px-3 py-2 text-left text-sm last:border-b-0 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-700 dark:hover:bg-gray-800" :disabled="!person.available" @click="selectOwner(person)">
                                    <span><span class="block font-medium">{{ person.name }}</span><span class="block text-xs text-gray-500">{{ person.email }} · Doc. {{ person.numero_identidad || '—' }}</span></span>
                                    <span class="shrink-0 text-xs" :class="person.available ? 'text-emerald-600' : 'text-amber-600'">{{ person.available ? 'Seleccionar' : 'Ya tiene organización' }}</span>
                                </button>
                            </div>
                            <p v-else-if="ownerSearch.trim().length >= 2 && !ownerSearchLoading" class="mt-1 text-xs text-gray-500">No se encontraron personas.</p>
                            <p v-if="selectedOwner" class="mt-2 text-xs text-emerald-700">Persona seleccionada: {{ selectedOwner.name }}. Sus datos se actualizarán al guardar.</p>
                        </div>
                        <div class="grid gap-4 md:grid-cols-3">
                            <label class="space-y-1 text-sm">Nombre del titular *<input v-model="newOrganization.owner.name" class="form-input" required @input="clearOwnerSelection" /></label>
                            <label class="space-y-1 text-sm">Correo de acceso *<input v-model="newOrganization.owner.email" class="form-input" type="email" required @input="clearOwnerSelection" /></label>
                            <label class="space-y-1 text-sm">Contraseña inicial {{ selectedOwner?.user_id ? '(opcional si reutilizas la cuenta)' : '*' }}
                                <input v-model="newOrganization.owner.password" class="form-input" type="password" minlength="8" autocomplete="new-password" :required="!selectedOwner?.user_id" />
                                <span class="block text-xs text-gray-500">Mínimo 8 caracteres. Si la persona ya tiene cuenta y dejas vacío, conserva su contraseña.</span>
                            </label>
                            <label class="space-y-1 text-sm">Tipo de documento
                                <select v-model="newOrganization.owner.tipo_identidad" class="form-select" @change="clearOwnerSelection">
                                    <option v-for="type in identityTypes" :key="type.id" :value="String(type.id)">{{ type.description }}</option>
                                </select>
                            </label>
                            <label class="space-y-1 text-sm">N.º documento del titular *<input v-model="newOrganization.owner.numero_identidad" class="form-input" required @input="clearOwnerSelection" /></label>
                            <label class="space-y-1 text-sm">Teléfono<input v-model="newOrganization.owner.telefono" class="form-input" @input="clearOwnerSelection" /></label>
                        </div>
                    </div>

                    <p v-if="Object.keys(newOrganization.errors).length" class="text-sm text-red-600">{{ Object.values(newOrganization.errors).join(' ') }}</p>

                    <div class="flex justify-end gap-2">
                        <button class="btn btn-outline-primary" type="button" @click="closeModal">Cancelar</button>
                        <button class="btn btn-primary" :disabled="newOrganization.processing">Crear organización y usuario</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal: editar organización -->
        <div v-if="activeModal === 'edit' && selectedOrganization" class="fixed inset-0 z-[60] overflow-y-auto bg-black/50 p-4" @click.self="closeModal">
            <div class="mx-auto w-full max-w-5xl rounded-xl bg-white shadow-2xl dark:bg-[#0e1726]">
                <div class="flex flex-wrap items-start justify-between gap-3 border-b border-gray-200 p-5 dark:border-gray-700">
                    <div>
                        <h2 class="text-lg font-bold">{{ selectedOrganization.name }}</h2>
                        <p class="text-sm text-gray-500">
                            Saldo S/ {{ money(selectedOrganization.prepaid_balance) }}
                            · {{ statusLabels[selectedOrganization.status] || selectedOrganization.status }}
                            <span v-if="selectedOrganization.owner_email"> · {{ selectedOrganization.owner_email }}</span>
                        </p>
                    </div>
                    <button type="button" class="text-2xl leading-none text-gray-400 hover:text-gray-700" @click="closeModal">&times;</button>
                </div>

                <div class="flex flex-wrap gap-1 border-b border-gray-200 px-5 pt-3 dark:border-gray-700">
                    <button v-for="tab in [{ key: 'datos', label: 'Datos' }, { key: 'acceso', label: 'Acceso y bloqueo' }, { key: 'usuarios', label: 'Usuarios' }, { key: 'comprobantes', label: 'Comprobantes' }, { key: 'avisos', label: 'Avisos' }]"
                        :key="tab.key" type="button"
                        class="rounded-t-lg px-4 py-2 text-sm font-medium"
                        :class="editTab === tab.key ? 'bg-primary text-white' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'"
                        @click="editTab = tab.key">
                        {{ tab.label }}
                    </button>
                </div>

                <div class="max-h-[70vh] overflow-y-auto p-5">
                    <!-- Datos -->
                    <form v-if="editTab === 'datos'" class="grid gap-4 md:grid-cols-3" @submit.prevent="saveProfile(selectedOrganization.id)">
                        <label class="space-y-1 text-sm">Nombre *<input v-model="organizationForms[selectedOrganization.id].profile.name" class="form-input" required /></label>
                        <label class="space-y-1 text-sm">Tipo
                            <select v-model="organizationForms[selectedOrganization.id].profile.tipo_organizacion" class="form-select">
                                <option value="empresa">Empresa</option>
                                <option value="persona_natural">Persona natural</option>
                            </select>
                        </label>
                        <label class="space-y-1 text-sm">RUC (opcional)
                            <input v-model="organizationForms[selectedOrganization.id].profile.ruc" class="form-input" inputmode="numeric" maxlength="11" />
                        </label>
                        <label class="space-y-1 text-sm">Dirección fiscal (opcional)
                            <input v-model="organizationForms[selectedOrganization.id].profile.address" class="form-input" maxlength="255" />
                        </label>
                        <label class="space-y-1 text-sm">Ubigeo SUNAT (opcional)
                            <input v-model="organizationForms[selectedOrganization.id].profile.ubigeo" class="form-input" list="organizations-ubigeo-list" inputmode="numeric" maxlength="6" />
                        </label>
                        <label class="space-y-1 text-sm">Motivo de auditoría *<input v-model="organizationForms[selectedOrganization.id].profile.reason" class="form-input" required /></label>
                        <p v-if="Object.keys(organizationForms[selectedOrganization.id].profile.errors).length" class="text-sm text-red-600 md:col-span-3">{{ Object.values(organizationForms[selectedOrganization.id].profile.errors).join(' ') }}</p>
                        <div class="flex items-end md:col-span-3">
                            <button class="btn btn-primary" :disabled="organizationForms[selectedOrganization.id].profile.processing">Guardar datos</button>
                        </div>
                    </form>

                    <!-- Acceso -->
                    <div v-else-if="editTab === 'acceso'" class="space-y-5">
                        <form class="grid gap-4 md:grid-cols-3" @submit.prevent="saveSettings(selectedOrganization.id)">
                            <label class="space-y-1 text-sm">Estado
                                <select v-model="forms[selectedOrganization.id].settings.status" class="form-select">
                                    <option value="trial">Prueba</option>
                                    <option value="active">Activo</option>
                                    <option value="sponsored">Cortesía / patrocinio</option>
                                    <option value="suspended">Suspendido</option>
                                </select>
                            </label>
                            <label class="space-y-1 text-sm">Tarifa por equipo y partido (S/)
                                <input v-model.number="forms[selectedOrganization.id].settings.match_rate" class="form-input" type="number" min="0" step="0.01" required />
                            </label>
                            <label class="space-y-1 text-sm">Días de prueba
                                <input v-model.number="forms[selectedOrganization.id].settings.trial_days" class="form-input" type="number" min="1" max="3660" required />
                                <span class="block text-xs text-gray-500">Solo se usa cuando no indicas una fecha de fin.</span>
                            </label>
                            <label class="space-y-1 text-sm">Fin de prueba
                                <input v-model="forms[selectedOrganization.id].settings.trial_ends_at" class="form-input" type="date" />
                                <span class="block text-xs text-gray-500">Vencida la fecha, la cuenta queda en solo lectura.</span>
                            </label>
                            <label class="space-y-1 text-sm">Acceso pagado hasta
                                <input v-model="forms[selectedOrganization.id].settings.access_until" class="form-input" type="date" />
                            </label>
                            <label class="space-y-1 text-sm">Crédito manual (S/)
                                <input v-model.number="forms[selectedOrganization.id].settings.credit_amount" class="form-input" type="number" min="0" step="0.01" placeholder="0.00" />
                            </label>
                            <label class="space-y-1 text-sm md:col-span-2">Motivo para auditoría *
                                <input v-model="forms[selectedOrganization.id].settings.reason" class="form-input" maxlength="1000" required />
                            </label>
                            <div class="flex items-end">
                                <button class="btn btn-primary" :disabled="forms[selectedOrganization.id].settings.processing">Guardar y auditar</button>
                            </div>
                            <p v-if="forms[selectedOrganization.id].settings.recentlySuccessful" class="text-sm text-green-600 md:col-span-3">Organización actualizada y auditada.</p>
                            <p v-if="Object.keys(forms[selectedOrganization.id].settings.errors).length" class="text-sm text-red-600 md:col-span-3">{{ Object.values(forms[selectedOrganization.id].settings.errors).join(' ') }}</p>
                        </form>

                        <form v-if="selectedOrganization.status !== 'suspended'" class="space-y-2 rounded-lg border border-red-200 p-4" @submit.prevent="blockAccount(selectedOrganization.id)">
                            <p class="text-sm font-semibold text-red-700">Bloquear cuenta (por ejemplo, falta de pago)</p>
                            <p class="text-xs text-gray-500">La organización queda en modo solo lectura hasta que se desbloquee.</p>
                            <input v-model="forms[selectedOrganization.id].block.reason" class="form-input" maxlength="1000" placeholder="Motivo obligatorio" required />
                            <button class="btn btn-danger" :disabled="forms[selectedOrganization.id].block.processing">Bloquear</button>
                            <p v-if="Object.keys(forms[selectedOrganization.id].block.errors).length" class="text-sm text-red-600">{{ Object.values(forms[selectedOrganization.id].block.errors).join(' ') }}</p>
                        </form>

                        <form v-else class="space-y-2 rounded-lg border border-green-200 p-4" @submit.prevent="unblockAccount(selectedOrganization.id)">
                            <p class="text-sm font-semibold text-green-700">Desbloquear cuenta</p>
                            <label class="space-y-1 text-sm">Acceso hasta (si ya no está en prueba)
                                <input v-model="forms[selectedOrganization.id].unblock.access_until" class="form-input" type="date" />
                            </label>
                            <input v-model="forms[selectedOrganization.id].unblock.reason" class="form-input" maxlength="1000" placeholder="Motivo obligatorio" required />
                            <button class="btn btn-success" :disabled="forms[selectedOrganization.id].unblock.processing">Desbloquear</button>
                            <p v-if="Object.keys(forms[selectedOrganization.id].unblock.errors).length" class="text-sm text-red-600">{{ Object.values(forms[selectedOrganization.id].unblock.errors).join(' ') }}</p>
                        </form>
                    </div>

                    <!-- Usuarios -->
                    <div v-else-if="editTab === 'usuarios'" class="space-y-5">
                        <section class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-primary/20 bg-primary/5 p-4">
                            <div>
                                <p class="text-sm font-semibold">Espacios de usuario</p>
                                <p class="text-xs text-gray-500">
                                    {{ selectedOrganization.seats_used || 0 }} en uso de
                                    {{ selectedOrganization.seat_limit === null ? 'espacios ilimitados' : selectedOrganization.seat_limit }}
                                    <span v-if="selectedOrganization.seats_available !== null"> · {{ selectedOrganization.seats_available }} disponible(s)</span>
                                </p>
                            </div>
                            <form class="flex flex-wrap items-end gap-2" @submit.prevent="grantSeats(selectedOrganization.id)">
                                <label class="space-y-1 text-xs">Espacios a regalar
                                    <input v-model.number="organizationForms[selectedOrganization.id].seats.quantity" class="form-input w-24" type="number" min="1" max="100" required />
                                </label>
                                <label class="space-y-1 text-xs">Motivo (opcional)
                                    <input v-model="organizationForms[selectedOrganization.id].seats.reason" class="form-input w-56" maxlength="255" placeholder="Cortesía, compensación..." />
                                </label>
                                <button class="btn btn-outline-primary" :disabled="organizationForms[selectedOrganization.id].seats.processing">Agregar espacios</button>
                            </form>
                            <p v-if="Object.keys(organizationForms[selectedOrganization.id].seats.errors).length" class="w-full text-sm text-red-600">{{ Object.values(organizationForms[selectedOrganization.id].seats.errors).join(' ') }}</p>
                        </section>

                        <div class="overflow-x-auto rounded-lg border">
                            <table class="table-hover w-full text-sm">
                                <thead>
                                    <tr>
                                        <th class="text-left">Usuario</th>
                                        <th class="text-left">Rol / tareas</th>
                                        <th class="text-left">Correo</th>
                                        <th class="text-left">Teléfono</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(member, index) in selectedOrganization.members" :key="index">
                                        <td class="font-semibold">
                                            {{ fullName(member) }}
                                            <span v-if="member.status !== 'active'" class="ml-2 rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-bold uppercase text-gray-500 dark:bg-gray-700 dark:text-gray-300">Inactivo</span>
                                        </td>
                                        <td>
                                            <p class="font-medium">{{ member.role === 'owner' ? 'Propietario' : 'Usuario' }}</p>
                                            <p class="text-xs text-gray-500">{{ memberCapabilities(member).join(' · ') }}</p>
                                        </td>
                                        <td>{{ member.email }}</td>
                                        <td>
                                            {{ member.telefono || '—' }}
                                            <span v-if="member.numero_identidad" class="block text-xs text-gray-500">{{ member.tipo_identidad }} · {{ member.numero_identidad }}</span>
                                        </td>
                                    </tr>
                                    <tr v-if="!selectedOrganization.members.length">
                                        <td colspan="4" class="text-center text-gray-500">Sin usuarios asociados.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <section class="space-y-3 rounded-lg border p-4">
                            <h3 class="font-semibold">Crear usuario para esta organización</h3>
                            <form class="grid gap-3 md:grid-cols-3" @submit.prevent="addOrganizationUser(selectedOrganization.id)">
                                <label class="space-y-1 text-sm">Nombre *<input v-model="organizationForms[selectedOrganization.id].user.name" class="form-input" required /></label>
                                <label class="space-y-1 text-sm">Correo de acceso *<input v-model="organizationForms[selectedOrganization.id].user.email" class="form-input" type="email" required /></label>
                                <label class="space-y-1 text-sm">Contraseña inicial *
                                    <input v-model="organizationForms[selectedOrganization.id].user.password" class="form-input" type="password" minlength="8" autocomplete="new-password" required />
                                    <span class="block text-xs text-gray-500">Mínimo 8 caracteres.</span>
                                </label>
                                <label class="space-y-1 text-sm">Tipo de documento
                                    <select v-model="organizationForms[selectedOrganization.id].user.tipo_identidad" class="form-select">
                                        <option v-for="type in identityTypes" :key="type.id" :value="String(type.id)">{{ type.description }}</option>
                                    </select>
                                </label>
                                <label class="space-y-1 text-sm">N.º documento *<input v-model="organizationForms[selectedOrganization.id].user.numero_identidad" class="form-input" required /></label>
                                <label class="space-y-1 text-sm">Teléfono<input v-model="organizationForms[selectedOrganization.id].user.telefono" class="form-input" /></label>
                                <div class="space-y-2 md:col-span-3">
                                    <p class="text-sm font-medium">Tareas permitidas</p>
                                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                        <label v-for="capability in capabilities" :key="capability.key" class="flex items-start gap-2 rounded-lg border p-2 text-xs">
                                            <input v-model="organizationForms[selectedOrganization.id].user.capabilities" type="checkbox" :value="capability.key" class="mt-0.5" />
                                            <span><strong class="block">{{ capability.label }}</strong>{{ capability.description }}</span>
                                        </label>
                                    </div>
                                    <p class="text-xs text-gray-500">Solo se otorgan los permisos de las tareas marcadas; nunca el panel de administración.</p>
                                </div>
                                <div class="flex items-end md:col-span-3">
                                    <button class="btn btn-outline-primary" :disabled="organizationForms[selectedOrganization.id].user.processing">Agregar usuario</button>
                                </div>
                                <p v-if="Object.keys(organizationForms[selectedOrganization.id].user.errors).length" class="text-sm text-red-600 md:col-span-3">{{ Object.values(organizationForms[selectedOrganization.id].user.errors).join(' ') }}</p>
                            </form>
                        </section>
                    </div>

                    <!-- Comprobantes -->
                    <div v-else-if="editTab === 'comprobantes'" class="space-y-5">
                        <section class="space-y-2">
                            <h3 class="font-semibold">Compras de espacios</h3>
                            <div class="overflow-x-auto rounded-lg border">
                                <table class="table-hover w-full text-sm">
                                    <thead>
                                        <tr>
                                            <th class="text-left">Fecha</th>
                                            <th class="text-right">Espacios</th>
                                            <th class="text-right">Total</th>
                                            <th class="text-left">Medio</th>
                                            <th class="text-left">Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="purchase in selectedOrganization.seat_purchases || []" :key="purchase.id">
                                            <td>{{ prettyDate(purchase.created_at) }}</td>
                                            <td class="text-right">{{ purchase.quantity }}</td>
                                            <td class="text-right font-semibold">S/ {{ money(purchase.total) }}</td>
                                            <td>{{ purchase.payment_method === 'admin' ? 'Cortesía' : purchase.payment_method }}</td>
                                            <td>{{ purchase.status }}</td>
                                        </tr>
                                        <tr v-if="!selectedOrganization.seat_purchases?.length">
                                            <td colspan="5" class="text-center text-gray-500">Sin compras de espacios registradas.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </section>

                        <section class="space-y-2">
                            <h3 class="font-semibold">Solicitudes de boleta, factura o nota de venta</h3>
                            <div v-for="request in selectedOrganization.document_requests || []" :key="request.id" class="rounded-lg border p-4">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <p class="font-semibold">
                                            {{ documentLabels[request.sunat_id] || request.document_type || 'Comprobante' }}
                                            <span class="ml-2 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase" :class="documentStatusClasses[request.status] || 'bg-gray-100 text-gray-600'">
                                                {{ documentStatusLabels[request.status] || request.status }}
                                            </span>
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            {{ request.purchase_type === 'seat_purchase' ? 'Compra de espacios' : 'Recarga de saldo' }} #{{ request.purchase_id }}
                                            · solicitado {{ prettyDate(request.created_at) }}
                                            <span v-if="request.number"> · {{ request.number }}</span>
                                        </p>
                                        <p v-if="request.billing_data?.business_name" class="text-xs text-gray-500">
                                            {{ request.billing_data.business_name }}
                                            <span v-if="request.billing_data.ruc"> · RUC {{ request.billing_data.ruc }}</span>
                                            <span v-if="request.billing_data.document_number"> · Doc. {{ request.billing_data.document_number }}</span>
                                        </p>
                                    </div>
                                    <a v-if="request.can_download" :href="route('even_organizations.documents.download', request.id)" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener">Ver comprobante</a>
                                </div>

                                <form v-if="request.status === 'requested'" class="mt-3 grid gap-3 md:grid-cols-4" @submit.prevent="issueDocument(request.id)">
                                    <label class="space-y-1 text-xs">Correo
                                        <input v-model="documentFormFor(request).email" class="form-input" type="email" placeholder="Correo de envío" />
                                    </label>
                                    <label class="space-y-1 text-xs">WhatsApp
                                        <input v-model="documentFormFor(request).phone" class="form-input" placeholder="Número con 9 dígitos" />
                                    </label>
                                    <div class="flex flex-col justify-end gap-1 text-xs">
                                        <label><input v-model="documentFormFor(request).channels" type="checkbox" value="email" /> Enviar por correo</label>
                                        <label><input v-model="documentFormFor(request).channels" type="checkbox" value="whatsapp" /> Enviar por WhatsApp</label>
                                    </div>
                                    <div class="flex items-end">
                                        <button class="btn btn-success w-full" :disabled="documentFormFor(request).processing">Emitir y enviar</button>
                                    </div>
                                    <p v-if="Object.keys(documentFormFor(request).errors).length" class="text-sm text-red-600 md:col-span-4">{{ Object.values(documentFormFor(request).errors).join(' ') }}</p>
                                </form>

                                <form v-if="request.status === 'requested'" class="mt-2 flex flex-wrap items-end gap-2" @submit.prevent="rejectDocument(request.id)">
                                    <label class="space-y-1 text-xs">Motivo de rechazo
                                        <input v-model="documentFormFor(request).reason" class="form-input w-72" maxlength="1000" required />
                                    </label>
                                    <button class="btn btn-outline-danger" :disabled="documentFormFor(request).processing">Rechazar solicitud</button>
                                </form>
                                <p v-if="request.rejection_reason" class="mt-2 text-xs text-red-600">Motivo: {{ request.rejection_reason }}</p>
                            </div>
                            <p v-if="!selectedOrganization.document_requests?.length" class="text-sm text-gray-500">Todavía no hay solicitudes de comprobante.</p>
                        </section>
                    </div>

                    <!-- Avisos -->
                    <section v-else-if="editTab === 'avisos'" class="space-y-3">
                        <h3 class="font-semibold">Enviar aviso a esta organización</h3>
                        <p class="text-sm text-gray-500">El aviso aparece en el sistema y, si se marca, también por correo a todos los usuarios de la cuenta.</p>
                        <form class="grid gap-3 md:grid-cols-4" @submit.prevent="sendAlert(selectedOrganization.id)">
                            <label class="space-y-1 text-sm">Tipo
                                <select v-model="organizationForms[selectedOrganization.id].alert.type" class="form-select">
                                    <option value="upcoming">Pago próximo</option>
                                    <option value="due">Pago vence hoy</option>
                                    <option value="overdue">Pago vencido</option>
                                    <option value="custom">Personalizado</option>
                                </select>
                            </label>
                            <label class="space-y-1 text-sm md:col-span-3">Mensaje
                                <textarea v-model="organizationForms[selectedOrganization.id].alert.message" class="form-textarea" maxlength="1000" required></textarea>
                            </label>
                            <div class="flex flex-col gap-2 text-sm">
                                <label><input v-model="organizationForms[selectedOrganization.id].alert.send_email" type="checkbox" /> Correo</label>
                                <label><input v-model="organizationForms[selectedOrganization.id].alert.send_system" type="checkbox" /> Sistema</label>
                            </div>
                            <div class="flex items-end md:col-span-3">
                                <button class="btn btn-outline-primary" :disabled="organizationForms[selectedOrganization.id].alert.processing">Enviar aviso</button>
                            </div>
                            <p v-if="Object.keys(organizationForms[selectedOrganization.id].alert.errors).length" class="text-sm text-red-600 md:col-span-4">{{ Object.values(organizationForms[selectedOrganization.id].alert.errors).join(' ') }}</p>
                        </form>
                    </section>
                </div>
            </div>
        </div>

        <!-- Modal: cobros -->
        <div v-if="activeModal === 'pay' && selectedOrganization" class="fixed inset-0 z-[60] overflow-y-auto bg-black/50 p-4" @click.self="closeModal">
            <div class="mx-auto w-full max-w-4xl rounded-xl bg-white shadow-2xl dark:bg-[#0e1726]">
                <div class="flex flex-wrap items-start justify-between gap-3 border-b border-gray-200 p-5 dark:border-gray-700">
                    <div>
                        <h2 class="text-lg font-bold">Cobros · {{ selectedOrganization.name }}</h2>
                        <p class="text-sm text-gray-500">Saldo actual S/ {{ money(selectedOrganization.prepaid_balance) }} · {{ statusLabels[selectedOrganization.status] || selectedOrganization.status }}</p>
                    </div>
                    <button type="button" class="text-2xl leading-none text-gray-400 hover:text-gray-700" @click="closeModal">&times;</button>
                </div>

                <div class="max-h-[70vh] space-y-5 overflow-y-auto p-5">
                    <form class="grid gap-4 md:grid-cols-3" @submit.prevent="registerPayment(selectedOrganization.id)">
                        <label class="space-y-1 text-sm">Monto (S/) *
                            <input v-model.number="organizationForms[selectedOrganization.id].payment.amount" class="form-input" type="number" min="0.01" step="0.01" required />
                        </label>
                        <label class="space-y-1 text-sm">Comprobante *
                            <select v-model="organizationForms[selectedOrganization.id].payment.document_type_id" class="form-select" required>
                                <option v-for="type in documentTypes" :key="type.id" :value="type.id">{{ documentLabels[type.sunat_id] || type.description }}</option>
                            </select>
                        </label>
                        <label class="space-y-1 text-sm">Medio de pago *
                            <select v-model="organizationForms[selectedOrganization.id].payment.payment_method_id" class="form-select" required>
                                <option v-for="method in paymentMethods" :key="method.id" :value="method.id">{{ method.description }}</option>
                            </select>
                        </label>
                        <label class="space-y-1 text-sm">Fecha *<input v-model="organizationForms[selectedOrganization.id].payment.paid_at" class="form-input" type="date" required /></label>
                        <label class="space-y-1 text-sm">Referencia<input v-model="organizationForms[selectedOrganization.id].payment.reference" class="form-input" maxlength="180" placeholder="Operación, voucher, etc." /></label>

                        <div v-if="requiresBillingData" class="grid gap-4 rounded-lg border border-amber-200 bg-amber-50/60 p-4 md:col-span-3 md:grid-cols-3 dark:border-amber-500/30 dark:bg-amber-500/10">
                            <p class="text-sm font-semibold text-amber-700 md:col-span-3 dark:text-amber-300">Datos de facturación para el comprobante electrónico</p>
                            <label class="space-y-1 text-sm">RUC {{ requiresRuc ? '*' : '(opcional)' }}
                                <input v-model="organizationForms[selectedOrganization.id].payment.ruc" class="form-input" inputmode="numeric" maxlength="11" :required="requiresRuc" />
                            </label>
                            <label class="space-y-1 text-sm">Dirección fiscal {{ requiresRuc ? '*' : '(opcional)' }}
                                <input v-model="organizationForms[selectedOrganization.id].payment.address" class="form-input" maxlength="255" :required="requiresRuc" />
                            </label>
                            <label class="space-y-1 text-sm">Ubigeo SUNAT {{ requiresRuc ? '*' : '(opcional)' }}
                                <input v-model="organizationForms[selectedOrganization.id].payment.ubigeo" class="form-input" list="organizations-ubigeo-list" inputmode="numeric" maxlength="6" :required="requiresRuc" />
                            </label>
                            <p class="text-xs text-amber-700 md:col-span-3 dark:text-amber-300">Lo que ingreses aquí se guarda en la organización para los próximos comprobantes.</p>
                        </div>

                        <p v-if="Object.keys(organizationForms[selectedOrganization.id].payment.errors).length" class="text-sm text-red-600 md:col-span-3">{{ Object.values(organizationForms[selectedOrganization.id].payment.errors).join(' ') }}</p>

                        <div class="flex justify-end gap-2 md:col-span-3">
                            <button class="btn btn-success" :disabled="organizationForms[selectedOrganization.id].payment.processing">Registrar pago y emitir comprobante</button>
                        </div>
                    </form>

                    <section class="space-y-2">
                        <h3 class="font-semibold">Últimos cobros</h3>
                        <div class="overflow-x-auto rounded-lg border">
                            <table class="table-hover w-full text-sm">
                                <thead>
                                    <tr>
                                        <th class="text-left">Fecha</th>
                                        <th class="text-left">Detalle</th>
                                        <th class="text-left">Medio</th>
                                        <th class="text-right">Monto</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(payment, index) in selectedOrganization.payments" :key="index">
                                        <td>{{ prettyDate(payment.created_at) }}</td>
                                        <td>{{ payment.description }}</td>
                                        <td>{{ methodLabel(payment.payment_method) }}</td>
                                        <td class="text-right font-semibold">S/ {{ money(payment.amount) }}</td>
                                    </tr>
                                    <tr v-if="!selectedOrganization.payments?.length">
                                        <td colspan="4" class="text-center text-gray-500">Todavía no hay cobros registrados.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
