<script setup>
import { computed, reactive, ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import Pagination from '@/Components/Pagination.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import IconCashBanknotes from '@/Components/vristo/icon/icon-cash-banknotes.vue';
import IconCalendarDay from '@/Components/vristo/icon/icon-calendar-day.vue';
import IconCreditCard from '@/Components/vristo/icon/icon-credit-card.vue';
import IconPencil from '@/Components/vristo/icon/icon-pencil.vue';
import IconPlus from '@/Components/vristo/icon/icon-plus.vue';
import IconSearch from '@/Components/vristo/icon/icon-search.vue';
import IconTooth from '@/Components/vristo/icon/icon-tooth.vue';
import Swal from 'sweetalert2';
import PendingSignatureReminder from '../../Components/PendingSignatureReminder.vue';

const props = defineProps({
    charges: {
        type: Object,
        default: () => ({}),
    },
    procedures: {
        type: Array,
        default: () => [],
    },
    currencies: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    pendingSignatures: {
        type: Array,
        default: () => [],
    },
});

const activeTab = ref('charges');
const selectedChargeIds = ref([]);
const editingProcedure = ref(null);
const reviewingSales = ref(false);
const salesReviewModal = reactive({
    show: false,
    patient: null,
    charges: [],
    selectedIds: [],
});

const filters = useForm({
    search: props.filters.search || '',
    status: props.filters.status || '',
    only_pending: props.filters.only_pending ?? true,
});

const procedureForm = useForm({
    name: '',
    category: '',
    description: '',
    default_price: 0,
    currency_type_id: 'PEN',
    is_consultation: false,
    is_active: true,
});

const chargeForm = useForm({
    patient_id: null,
    attention_id: null,
    charged_at: new Date().toISOString().slice(0, 16),
    charges: [],
});

const quickCharge = reactive({
    procedure_id: null,
    price: 0,
    quantity: 1,
    charged_at: new Date().toISOString().slice(0, 16),
    notes: '',
});

const patientSearch = reactive({
    term: '',
    loading: false,
    options: [],
    selected: null,
});

const attentionSearch = reactive({
    loading: false,
    options: [],
    selected: null,
});

const activeProcedures = computed(() => props.procedures.filter((procedure) => procedure.is_active));
const selectedProcedure = computed(() => props.procedures.find((procedure) => Number(procedure.id) === Number(quickCharge.procedure_id)));

const pendingVisibleCharges = computed(() => (props.charges.data || []).filter((charge) => charge.status === 'pending'));
const groupedCharges = computed(() => {
    const groups = new Map();

    (props.charges.data || []).forEach((charge) => {
        const patientId = charge.patient_id || charge.patient?.id || 'sin-paciente';

        if (!groups.has(patientId)) {
            groups.set(patientId, {
                patient_id: patientId,
                patient: charge.patient,
                charges: [],
            });
        }

        groups.get(patientId).charges.push(charge);
    });

    return Array.from(groups.values());
});

const currencySymbol = (currencyId = 'PEN') => props.currencies.find((currency) => currency.id === currencyId)?.symbol || 'S/';

const formatMoney = (value, currencyId = 'PEN') => `${currencySymbol(currencyId)} ${Number(value || 0).toFixed(2)}`;

const formatDate = (value) => {
    if (!value) {
        return '';
    }

    return new Intl.DateTimeFormat('es-PE', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
};

const attentionLabel = (charge) => {
    if (!charge.attention) {
        return 'Sin atención vinculada';
    }

    const doctorName = charge.doctor?.person?.full_name || charge.attention?.doctor?.person?.full_name || 'Sin doctor';

    return `${formatDate(charge.attention.attention_at || charge.charged_at)} - ${doctorName}`;
};

const statusLabel = (status) => ({
    pending: 'Pendiente',
    billed: 'Facturado',
    paid: 'Pagado',
    cancelled: 'Anulado',
}[status] || status);

const statusClass = (status) => ({
    pending: 'bg-warning',
    billed: 'bg-info',
    paid: 'bg-success',
    cancelled: 'bg-danger',
}[status] || 'bg-secondary');

const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (character) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;',
}[character]));

const saleDocumentLabel = (document) => {
    if (!document) {
        return null;
    }

    const type = document.invoice_type_doc === '01'
        ? 'Factura'
        : document.invoice_type_doc === '03'
            ? 'Boleta'
            : 'Documento';
    const serie = document.invoice_serie || '';
    const number = document.number || String(document.invoice_correlative || '').padStart(8, '0');
    const documentNumber = [serie, number].filter(Boolean).join('-');

    return documentNumber ? `${type} ${documentNumber}` : type;
};

const saleDocumentWarning = (charge) => {
    const document = charge.sale_document;

    if (!document) {
        return '';
    }

    const label = saleDocumentLabel(document);

    if (document.invoice_type_doc === '01') {
        return `Este cobro ya generó la ${label}. Después de anular el cobro, también debes anular la factura en Sales generando su nota de crédito.`;
    }

    if (document.invoice_type_doc === '03') {
        return `Este cobro ya generó la ${label}. Después de anular el cobro, también debes anular la boleta en Sales para comunicarla correctamente.`;
    }

    return `Este cobro ya generó ${label}. Después de anular el cobro, revisa el documento en Sales y realiza la anulación correspondiente.`;
};

const search = () => {
    filters.get(route('heal_procedure_charges_list'), {
        preserveState: true,
        replace: true,
    });
};

const toggleOnlyPending = () => {
    if (filters.only_pending) {
        filters.status = '';
    }

    search();
};

const searchPatients = () => {
    if (!patientSearch.term) {
        return;
    }

    patientSearch.loading = true;
    axios.post(route('heal_patients_search'), { search: patientSearch.term })
        .then((response) => {
            patientSearch.options = response.data.success ? response.data.patients : [];
        })
        .finally(() => {
            patientSearch.loading = false;
        });
};

const selectPatient = (patient) => {
    patientSearch.selected = patient;
    patientSearch.term = patient.person.full_name;
    patientSearch.options = [];
    chargeForm.patient_id = patient.id;
    chargeForm.attention_id = null;
    chargeForm.charges = [];
    attentionSearch.selected = null;
    loadPatientAttentions();
};

const loadPatientAttentions = () => {
    if (!chargeForm.patient_id) {
        attentionSearch.options = [];
        return;
    }

    attentionSearch.loading = true;
    axios.get(route('heal_procedure_charges_patient_attentions', chargeForm.patient_id))
        .then((response) => {
            attentionSearch.options = response.data.success ? response.data.attentions : [];
        })
        .finally(() => {
            attentionSearch.loading = false;
        });
};

const selectAttention = () => {
    attentionSearch.selected = attentionSearch.options.find((attention) => Number(attention.id) === Number(chargeForm.attention_id)) || null;
};

const syncProcedurePrice = () => {
    if (!selectedProcedure.value) {
        return;
    }

    quickCharge.price = selectedProcedure.value.default_price;
};

const addChargeRow = (procedure, data = {}) => {
    if (!procedure) {
        return;
    }

    chargeForm.charges.push({
        procedure_id: procedure.id,
        name: procedure.name,
        price: data.price ?? procedure.default_price,
        quantity: data.quantity ?? 1,
        notes: data.notes || '',
        source_treatment_id: data.source_treatment_id || null,
    });
};

const addManualChargeRow = () => {
    if (!selectedProcedure.value) {
        Swal.fire({ icon: 'info', title: 'Selecciona procedimiento', text: 'Elige un procedimiento para agregarlo al cobro.', customClass: 'sweet-alerts', padding: '2em' });
        return;
    }

    addChargeRow(selectedProcedure.value, {
        price: quickCharge.price,
        quantity: quickCharge.quantity,
        notes: quickCharge.notes,
    });

    quickCharge.procedure_id = null;
    quickCharge.price = 0;
    quickCharge.quantity = 1;
    quickCharge.notes = '';
};

const removeChargeRow = (index) => {
    chargeForm.charges.splice(index, 1);
};

const procedureById = (procedureId) => props.procedures.find((procedure) => Number(procedure.id) === Number(procedureId));

const addTreatmentAsCharge = (treatment) => {
    const procedure = procedureById(treatment.suggested_procedure_id);

    if (!procedure) {
        Swal.fire({ icon: 'info', title: 'Sin procedimiento sugerido', text: 'Elige manualmente un procedimiento del catálogo para este tratamiento.', customClass: 'sweet-alerts', padding: '2em' });
        return;
    }

    addChargeRow(procedure, {
        notes: treatment.suggested_notes,
        source_treatment_id: treatment.id,
    });
};

const addTreatmentsFromAttention = () => {
    if (!attentionSearch.selected?.treatments?.length) {
        Swal.fire({ icon: 'info', title: 'Sin tratamientos', text: 'La atención seleccionada no tiene tratamientos realizados para sugerir.', customClass: 'sweet-alerts', padding: '2em' });
        return;
    }

    attentionSearch.selected.treatments.forEach((treatment) => {
        if (!chargeForm.charges.some((charge) => Number(charge.source_treatment_id) === Number(treatment.id))) {
            addTreatmentAsCharge(treatment);
        }
    });
};

const resetProcedureForm = () => {
    editingProcedure.value = null;
    procedureForm.reset();
    procedureForm.currency_type_id = 'PEN';
    procedureForm.is_active = true;
    procedureForm.is_consultation = false;
};

const editProcedure = (procedure) => {
    editingProcedure.value = procedure;
    procedureForm.name = procedure.name;
    procedureForm.category = procedure.category;
    procedureForm.description = procedure.description;
    procedureForm.default_price = procedure.default_price;
    procedureForm.currency_type_id = procedure.currency_type_id || 'PEN';
    procedureForm.is_consultation = Boolean(procedure.is_consultation);
    procedureForm.is_active = Boolean(procedure.is_active);
    activeTab.value = 'catalog';
};

const saveProcedure = () => {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            resetProcedureForm();
            Swal.fire({ icon: 'success', title: 'Guardado', text: 'Procedimiento guardado correctamente.', customClass: 'sweet-alerts', padding: '2em' });
        },
    };

    if (editingProcedure.value) {
        procedureForm.put(route('heal_procedures_update', editingProcedure.value.id), options);
        return;
    }

    procedureForm.post(route('heal_procedures_store'), options);
};

const saveCharge = () => {
    if (!chargeForm.charges.length) {
        Swal.fire({ icon: 'info', title: 'Agrega procedimientos', text: 'Selecciona tratamientos de una atención o agrega procedimientos manualmente.', customClass: 'sweet-alerts', padding: '2em' });
        return;
    }

    chargeForm.charged_at = quickCharge.charged_at;
    chargeForm.post(route('heal_patient_charges_store_many'), {
        preserveScroll: true,
        onSuccess: () => {
            chargeForm.reset();
            chargeForm.charges = [];
            chargeForm.charged_at = new Date().toISOString().slice(0, 16);
            quickCharge.procedure_id = null;
            quickCharge.quantity = 1;
            quickCharge.price = 0;
            quickCharge.charged_at = new Date().toISOString().slice(0, 16);
            quickCharge.notes = '';
            patientSearch.selected = null;
            patientSearch.term = '';
            attentionSearch.selected = null;
            attentionSearch.options = [];
            Swal.fire({ icon: 'success', title: 'Cobro registrado', text: 'El procedimiento quedó pendiente de facturación.', customClass: 'sweet-alerts', padding: '2em' });
        },
    });
};

const changeStatus = (charge, status) => {
    if (status === 'cancelled') {
        confirmCancelCharge(charge);
        return;
    }

    router.put(route('heal_patient_charges_status', charge.id), { status }, {
        preserveScroll: true,
        onSuccess: () => router.reload({ only: ['charges'] }),
    });
};

const confirmCancelCharge = (charge) => {
    const documentWarning = saleDocumentWarning(charge);
    const chargeName = escapeHtml(charge.name_snapshot || 'seleccionado');

    Swal.fire({
        title: '¿Anular procedimiento/cobro?',
        html: `
            <div class="flex flex-col items-center gap-3 text-center">
                <div class="flex h-20 w-20 items-center justify-center rounded-full bg-danger/10 text-danger">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 6h18" />
                        <path d="M8 6V4h8v2" />
                        <path d="M19 6l-1 14H6L5 6" />
                        <path d="M10 11v5" />
                        <path d="M14 11v5" />
                    </svg>
                </div>
                <div>
                    <p class="mb-2 text-sm">Se marcará como <strong>Anulado</strong> el cobro <strong>${chargeName}</strong>.</p>
                    ${documentWarning ? `<p class="rounded bg-warning/10 p-3 text-sm text-warning">${documentWarning}</p>` : '<p class="text-sm text-white-dark">Esta acción no elimina el registro, solo cambia su estado.</p>'}
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Sí, anular',
        cancelButtonText: 'Cancelar',
        padding: '2em',
        customClass: 'sweet-alerts',
    }).then((result) => {
        if (!result.isConfirmed) {
            return;
        }

        router.put(route('heal_patient_charges_status', charge.id), { status: 'cancelled' }, {
            preserveScroll: true,
            onSuccess: () => {
                router.reload({ only: ['charges'] });
                Swal.fire({
                    icon: documentWarning ? 'warning' : 'success',
                    title: 'Cobro anulado',
                    text: documentWarning || 'El procedimiento/cobro fue anulado correctamente.',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
            },
        });
    });
};

const openSalesReviewModal = (group) => {
    const pendingCharges = group.charges.filter((charge) => charge.status === 'pending');

    if (!pendingCharges.length) {
        Swal.fire({ icon: 'info', title: 'Sin cobros pendientes', text: 'Este paciente no tiene cobros pendientes para enviar a Sales.', customClass: 'sweet-alerts', padding: '2em' });
        return;
    }

    salesReviewModal.patient = group.patient;
    salesReviewModal.charges = pendingCharges;
    salesReviewModal.selectedIds = pendingCharges.map((charge) => charge.id);
    salesReviewModal.show = true;
};

const closeSalesReviewModal = () => {
    salesReviewModal.show = false;
    salesReviewModal.patient = null;
    salesReviewModal.charges = [];
    salesReviewModal.selectedIds = [];
};

const prepareSalesReview = (chargeIds = null) => {
    const idsToReview = chargeIds || (selectedChargeIds.value.length
        ? selectedChargeIds.value
        : pendingVisibleCharges.value.map((charge) => charge.id));
    const patientIds = pendingVisibleCharges.value
        .filter((charge) => idsToReview.includes(charge.id))
        .map((charge) => charge.patient_id);
    const uniquePatientIds = [...new Set(patientIds)];

    if (!idsToReview.length) {
        Swal.fire({ icon: 'info', title: 'Sin cobros pendientes', text: 'Registra o marca cobros pendientes antes de revisar en Sales.', customClass: 'sweet-alerts', padding: '2em' });
        return;
    }

    if (uniquePatientIds.length > 1) {
        Swal.fire({ icon: 'info', title: 'Elige un solo paciente', text: 'Marca solo los cobros pendientes del mismo paciente para enviarlos a Sales.', customClass: 'sweet-alerts', padding: '2em' });
        return;
    }

    router.post(route('heal_patient_charges_prepare_sales'), {
        charge_ids: idsToReview,
    }, {
        preserveScroll: true,
        onStart: () => {
            reviewingSales.value = true;
            Swal.fire({
                title: 'Preparando Sales',
                text: 'Estamos llevando los cobros al documento de venta.',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading(),
                customClass: 'sweet-alerts',
                padding: '2em',
            });
        },
        onError: (errors) => {
            reviewingSales.value = false;
            Swal.fire({
                icon: 'error',
                title: 'No se pudo revisar en Sales',
                text: errors.charges || errors.charge_ids || 'Revisa que los cobros esten pendientes y sean del mismo paciente.',
                customClass: 'sweet-alerts',
                padding: '2em',
            });
        },
        onSuccess: () => {
            reviewingSales.value = false;
            Swal.close();
            closeSalesReviewModal();
        },
        onFinish: () => {
            reviewingSales.value = false;
            Swal.close();
        },
    });
};

const prepareSelectedSalesReview = () => {
    prepareSalesReview(salesReviewModal.selectedIds);
};
</script>

<template>
    <AppLayout title="Procedimientos/cobros">
        <PendingSignatureReminder :items="pendingSignatures" />

        <Navigation :routeModule="route('health_dashboard')" :titleModule="'Salud'">
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Procedimientos/cobros</span>
            </li>
        </Navigation>

        <div class="pt-5 space-y-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl">Procedimientos/cobros</h2>
                    <p class="text-sm text-white-dark">Tratamientos efectuados, cobros pendientes y catálogo de precios.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button type="button" class="btn" :class="activeTab === 'charges' ? 'btn-primary' : 'btn-outline-primary'" @click="activeTab = 'charges'">
                        <IconCashBanknotes class="h-4 w-4 ltr:mr-2 rtl:ml-2" />
                        Cobros
                    </button>
                    <button type="button" class="btn" :class="activeTab === 'catalog' ? 'btn-primary' : 'btn-outline-primary'" @click="activeTab = 'catalog'">
                        <IconTooth class="h-4 w-4 ltr:mr-2 rtl:ml-2" />
                        Catálogo
                    </button>
                </div>
            </div>

            <template v-if="activeTab === 'charges'">
                <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
                    <div class="panel xl:col-span-1">
                        <div class="mb-4 flex items-center gap-2 font-semibold">
                            <IconPlus class="h-5 w-5 text-primary" />
                            Agregar procedimiento/cobro
                        </div>
                        <form class="space-y-4" @submit.prevent="saveCharge">
                            <div>
                                <InputLabel value="Paciente" />
                                <div class="relative">
                                    <TextInput v-model="patientSearch.term" type="text" class="form-input pr-10" placeholder="Buscar paciente" @keyup.enter.prevent="searchPatients" />
                                    <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-white-dark" @click="searchPatients">
                                        <IconSearch class="h-4 w-4" />
                                    </button>
                                </div>
                                <InputError :message="chargeForm.errors.patient_id" class="mt-1" />
                                <div v-if="patientSearch.options.length" class="mt-2 max-h-48 overflow-auto rounded border border-slate-200 dark:border-slate-700">
                                    <button
                                        v-for="patient in patientSearch.options"
                                        :key="patient.id"
                                        type="button"
                                        class="block w-full px-3 py-2 text-left text-sm hover:bg-primary/10"
                                        @click="selectPatient(patient)"
                                    >
                                        <div class="font-semibold">{{ patient.person.full_name }}</div>
                                        <div class="text-xs text-white-dark">{{ patient.person.number }}</div>
                                    </button>
                                </div>
                            </div>

                            <div>
                                <InputLabel value="Atención" />
                                <div class="flex gap-2">
                                    <select v-model="chargeForm.attention_id" class="form-select" :disabled="!chargeForm.patient_id || attentionSearch.loading" @change="selectAttention">
                                        <option :value="null">{{ attentionSearch.loading ? 'Cargando...' : 'Selecciona una atención' }}</option>
                                        <option v-for="attention in attentionSearch.options" :key="attention.id" :value="attention.id">
                                            {{ formatDate(attention.attention_at) }} - {{ attention.doctor_name || 'Sin doctor' }}
                                        </option>
                                    </select>
                                    <button type="button" class="btn btn-outline-primary shrink-0" :disabled="!chargeForm.patient_id" @click="loadPatientAttentions">
                                        <IconCalendarDay class="h-4 w-4" />
                                    </button>
                                </div>
                                <div v-if="attentionSearch.selected?.charges_count" class="mt-1 text-xs text-warning">
                                    Esta atención ya tiene {{ attentionSearch.selected.charges_count }} cobro(s) registrado(s).
                                </div>
                                <InputError :message="chargeForm.errors.attention_id" class="mt-1" />
                            </div>

                            <div v-if="attentionSearch.selected" class="rounded border border-slate-200 p-3 dark:border-slate-700">
                                <div class="mb-2 flex items-center justify-between gap-2">
                                    <div class="font-semibold">Tratamientos de la atención</div>
                                    <button type="button" class="btn btn-sm btn-outline-primary" @click="addTreatmentsFromAttention">Agregar todos</button>
                                </div>
                                <div v-if="attentionSearch.selected.treatments?.length" class="space-y-2">
                                    <div v-for="treatment in attentionSearch.selected.treatments" :key="treatment.id" class="rounded bg-slate-50 p-2 text-sm dark:bg-slate-800">
                                        <div class="flex items-start justify-between gap-2">
                                            <div>
                                                <div class="font-semibold">{{ treatment.name || treatment.treatment_type || 'Tratamiento' }}</div>
                                                <div class="text-xs text-white-dark line-clamp-2">{{ treatment.suggested_notes || 'Sin detalle' }}</div>
                                                <div class="mt-1 text-xs" :class="treatment.suggested_procedure_id ? 'text-success' : 'text-warning'">
                                                    {{ treatment.suggested_procedure_id ? `Sugerido: ${procedureById(treatment.suggested_procedure_id)?.name}` : 'Sin coincidencia en catálogo' }}
                                                </div>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-outline-success shrink-0" :disabled="!treatment.suggested_procedure_id" @click="addTreatmentAsCharge(treatment)">
                                                <IconPlus class="h-4 w-4" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-sm text-white-dark">Esta atención no tiene tratamientos registrados.</div>
                            </div>

                            <div class="rounded border border-dashed border-slate-300 p-3 dark:border-slate-700">
                                <div class="mb-3 font-semibold">Agregar procedimiento adicional</div>
                                <InputLabel value="Procedimiento" />
                                <select v-model="quickCharge.procedure_id" class="form-select" @change="syncProcedurePrice">
                                    <option :value="null">Selecciona</option>
                                    <option v-for="procedure in activeProcedures" :key="procedure.id" :value="procedure.id">
                                        {{ procedure.name }} - {{ formatMoney(procedure.default_price, procedure.currency_type_id) }}
                                    </option>
                                </select>

                                <div class="mt-3 grid grid-cols-2 gap-3">
                                    <div>
                                        <InputLabel value="Precio" />
                                        <TextInput v-model="quickCharge.price" type="number" step="0.01" min="0" class="form-input" />
                                    </div>
                                    <div>
                                        <InputLabel value="Cantidad" />
                                        <TextInput v-model="quickCharge.quantity" type="number" step="0.01" min="0.01" class="form-input" />
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <InputLabel value="Observación" />
                                    <textarea v-model="quickCharge.notes" class="form-textarea" rows="2"></textarea>
                                </div>
                                <button type="button" class="btn btn-outline-primary mt-3 w-full" @click="addManualChargeRow">
                                    <IconPlus class="h-4 w-4 ltr:mr-2 rtl:ml-2" />
                                    Agregar a la lista
                                </button>
                            </div>

                            <div>
                                <InputLabel value="Fecha" />
                                <TextInput v-model="quickCharge.charged_at" type="datetime-local" class="form-input" />
                            </div>

                            <div class="space-y-2">
                                <div class="font-semibold">Procedimientos a cobrar</div>
                                <div v-if="chargeForm.charges.length" class="space-y-2">
                                    <div v-for="(charge, index) in chargeForm.charges" :key="index" class="rounded border border-slate-200 p-3 dark:border-slate-700">
                                        <div class="mb-2 flex items-start justify-between gap-2">
                                            <div class="font-semibold">{{ charge.name }}</div>
                                            <button type="button" class="text-danger" @click="removeChargeRow(index)">Quitar</button>
                                        </div>
                                        <div class="grid grid-cols-2 gap-3">
                                            <div>
                                                <InputLabel value="Precio" />
                                                <TextInput v-model="charge.price" type="number" step="0.01" min="0" class="form-input" />
                                            </div>
                                            <div>
                                                <InputLabel value="Cantidad" />
                                                <TextInput v-model="charge.quantity" type="number" step="0.01" min="0.01" class="form-input" />
                                            </div>
                                        </div>
                                        <div class="mt-2">
                                            <InputLabel value="Observación" />
                                            <textarea v-model="charge.notes" class="form-textarea" rows="2"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="rounded border border-dashed border-slate-300 p-4 text-center text-sm text-white-dark dark:border-slate-700">
                                    Elige una atención y agrega sus tratamientos, o agrega procedimientos manualmente.
                                </div>
                                <InputError :message="chargeForm.errors.charges" class="mt-1" />
                            </div>

                            <button type="submit" class="btn btn-primary w-full" :disabled="chargeForm.processing">
                                Registrar cobros
                            </button>
                        </form>
                    </div>

                    <div class="panel xl:col-span-2 p-0 overflow-hidden">
                        <div class="flex flex-wrap items-center justify-between gap-3 p-5">
                            <div class="flex flex-wrap gap-2">
                                <label class="inline-flex items-center gap-2 rounded border border-slate-200 px-3 py-2 text-sm dark:border-slate-700">
                                    <input v-model="filters.only_pending" type="checkbox" class="form-checkbox" @change="toggleOnlyPending" />
                                    <span>Mostrar solo pendientes de pago</span>
                                </label>
                                <select v-model="filters.status" class="form-select w-40" :disabled="filters.only_pending" @change="search">
                                    <option value="">Todos</option>
                                    <option value="pending">Pendientes</option>
                                    <option value="billed">Facturados</option>
                                    <option value="paid">Pagados</option>
                                    <option value="cancelled">Anulados</option>
                                </select>
                                <div class="relative">
                                    <input v-model="filters.search" type="text" placeholder="Buscar paciente o tratamiento" class="form-input py-2 pr-10" @keyup.enter="search" />
                                    <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2" @click="search">
                                        <IconSearch class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                            <button type="button" class="btn btn-outline-success" :disabled="reviewingSales" @click="prepareSalesReview()">
                                <IconCreditCard class="h-4 w-4 ltr:mr-2 rtl:ml-2" />
                                {{ reviewingSales ? 'Preparando...' : 'Revisar en Sales' }}
                            </button>
                        </div>

                        <Pagination :data="charges">
                            <div class="space-y-4 p-5 pt-0">
                                <div
                                    v-for="group in groupedCharges"
                                    :key="group.patient_id"
                                    class="rounded border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900"
                                >
                                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 p-4 dark:border-slate-700">
                                        <div>
                                            <div class="font-semibold">{{ group.patient?.person?.full_name || 'Paciente sin nombre' }}</div>
                                            <div class="text-xs text-white-dark">{{ group.patient?.person?.number || 'Sin documento' }}</div>
                                        </div>
                                        <button
                                            type="button"
                                            class="btn btn-outline-success btn-sm"
                                            :disabled="reviewingSales || !group.charges.some((charge) => charge.status === 'pending')"
                                            @click="openSalesReviewModal(group)"
                                        >
                                            <IconCreditCard class="h-4 w-4 ltr:mr-2 rtl:ml-2" />
                                            Generar documento de venta
                                        </button>
                                    </div>

                                    <div class="divide-y divide-slate-100 dark:divide-slate-800">
                                        <div
                                            v-for="charge in group.charges"
                                            :key="charge.id"
                                            class="grid grid-cols-1 gap-3 p-4 md:grid-cols-12 md:items-center"
                                        >
                                            <div class="md:col-span-1">
                                                <input v-if="charge.status === 'pending'" v-model="selectedChargeIds" type="checkbox" class="form-checkbox" :value="charge.id" />
                                            </div>
                                            <div class="md:col-span-4">
                                                <div class="font-semibold">{{ charge.name_snapshot }}</div>
                                                <div class="text-xs text-white-dark">{{ attentionLabel(charge) }}</div>
                                            </div>
                                            <div class="md:col-span-2 text-sm">{{ formatDate(charge.charged_at) }}</div>
                                            <div class="md:col-span-2 font-semibold">{{ formatMoney(charge.total, charge.currency_type_id) }}</div>
                                            <div class="md:col-span-1">
                                                <span class="badge" :class="statusClass(charge.status)">{{ statusLabel(charge.status) }}</span>
                                            </div>
                                            <div class="md:col-span-2">
                                                <div class="flex flex-wrap justify-end gap-1">
                                                    <button v-if="charge.status === 'pending'" type="button" class="btn btn-sm btn-outline-success" @click="changeStatus(charge, 'paid')">Pagado</button>
                                                    <button v-if="charge.status !== 'cancelled'" type="button" class="btn btn-sm btn-outline-danger" @click="changeStatus(charge, 'cancelled')">Anular</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="!charges.data?.length" class="py-8 text-center text-white-dark">
                                    No hay procedimientos/cobros registrados.
                                </div>
                            </div>
                        </Pagination>
                    </div>
                </div>
            </template>

            <template v-else>
                <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
                    <div class="panel">
                        <div class="mb-4 flex items-center gap-2 font-semibold">
                            <IconPlus class="h-5 w-5 text-primary" />
                            {{ editingProcedure ? 'Editar procedimiento' : 'Nuevo procedimiento' }}
                        </div>
                        <form class="space-y-4" @submit.prevent="saveProcedure">
                            <div>
                                <InputLabel value="Nombre" />
                                <TextInput v-model="procedureForm.name" type="text" class="form-input" />
                                <InputError :message="procedureForm.errors.name" class="mt-1" />
                            </div>
                            <div>
                                <InputLabel value="Categoria" />
                                <TextInput v-model="procedureForm.category" type="text" class="form-input" />
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <InputLabel value="Precio default" />
                                    <TextInput v-model="procedureForm.default_price" type="number" min="0" step="0.01" class="form-input" />
                                </div>
                                <div>
                                    <InputLabel value="Moneda" />
                                    <select v-model="procedureForm.currency_type_id" class="form-select">
                                        <option v-for="currency in currencies" :key="currency.id" :value="currency.id">{{ currency.id }} - {{ currency.description }}</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <InputLabel value="Descripción" />
                                <textarea v-model="procedureForm.description" class="form-textarea" rows="3"></textarea>
                            </div>
                            <label class="flex items-center gap-2">
                                <input v-model="procedureForm.is_consultation" type="checkbox" class="form-checkbox" />
                                <span>Es consulta</span>
                            </label>
                            <label class="flex items-center gap-2">
                                <input v-model="procedureForm.is_active" type="checkbox" class="form-checkbox" />
                                <span>Activo</span>
                            </label>
                            <div class="flex gap-2">
                                <button type="submit" class="btn btn-primary" :disabled="procedureForm.processing">Guardar</button>
                                <button v-if="editingProcedure" type="button" class="btn btn-outline-dark" @click="resetProcedureForm">Cancelar</button>
                            </div>
                        </form>
                    </div>

                    <div class="panel xl:col-span-2 p-0 overflow-hidden">
                        <div class="table-responsive">
                            <table class="table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Categoria</th>
                                        <th>Precio default</th>
                                        <th>Estado</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="procedure in procedures" :key="procedure.id">
                                        <td>
                                            <div class="font-semibold">{{ procedure.name }}</div>
                                            <div v-if="procedure.is_consultation" class="text-xs text-primary">Consulta automática</div>
                                        </td>
                                        <td>{{ procedure.category || '-' }}</td>
                                        <td>{{ formatMoney(procedure.default_price, procedure.currency_type_id) }}</td>
                                        <td><span class="badge" :class="procedure.is_active ? 'bg-success' : 'bg-danger'">{{ procedure.is_active ? 'Activo' : 'Inactivo' }}</span></td>
                                        <td>
                                            <div class="flex justify-center">
                                                <button type="button" class="btn btn-sm btn-outline-primary" @click="editProcedure(procedure)">
                                                    <IconPencil class="h-4 w-4" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </template>

            <div v-if="salesReviewModal.show" class="fixed inset-0 z-[999] flex items-center justify-center bg-black/60 px-4 py-6">
                <div class="w-full max-w-4xl rounded bg-white shadow-xl dark:bg-[#0e1726]">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4 dark:border-slate-700">
                        <div>
                            <h3 class="text-lg font-semibold dark:text-white">Generar documento de venta</h3>
                            <p class="text-sm text-white-dark">
                                {{ salesReviewModal.patient?.person?.full_name || 'Paciente' }}
                            </p>
                        </div>
                        <button type="button" class="btn btn-outline-dark btn-sm" @click="closeSalesReviewModal">Cerrar</button>
                    </div>

                    <div class="max-h-[70vh] overflow-y-auto p-5">
                        <div class="mb-4 rounded border border-primary/20 bg-primary/5 p-3 text-sm text-primary">
                            Selecciona solo los tratamientos que deseas cobrar ahora. Los que no marques quedaran pendientes para generar boleta o factura en otro momento.
                        </div>

                        <div class="space-y-3">
                            <label
                                v-for="charge in salesReviewModal.charges"
                                :key="charge.id"
                                class="flex cursor-pointer items-start gap-3 rounded border border-slate-200 p-3 hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800"
                            >
                                <input v-model="salesReviewModal.selectedIds" type="checkbox" class="form-checkbox mt-1" :value="charge.id" />
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-start justify-between gap-2">
                                        <div>
                                            <div class="font-semibold">{{ charge.name_snapshot }}</div>
                                            <div class="text-xs text-white-dark">{{ attentionLabel(charge) }}</div>
                                        </div>
                                        <div class="font-semibold text-primary">{{ formatMoney(charge.total, charge.currency_type_id) }}</div>
                                    </div>
                                    <div v-if="charge.notes" class="mt-1 text-xs text-white-dark">{{ charge.notes }}</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="flex flex-wrap justify-end gap-2 border-t border-slate-200 px-5 py-4 dark:border-slate-700">
                        <button type="button" class="btn btn-outline-dark" @click="closeSalesReviewModal">Cancelar</button>
                        <button
                            type="button"
                            class="btn btn-success"
                            :disabled="reviewingSales || !salesReviewModal.selectedIds.length"
                            @click="prepareSelectedSalesReview"
                        >
                            <IconCreditCard class="h-4 w-4 ltr:mr-2 rtl:ml-2" />
                            {{ reviewingSales ? 'Preparando...' : 'Generar documento de venta' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
