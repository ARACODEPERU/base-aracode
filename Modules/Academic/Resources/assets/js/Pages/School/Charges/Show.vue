<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import { ref, reactive, computed, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import Swal2 from 'sweetalert2';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import ModalLargeXX from '@/Components/ModalLargeXX.vue';
import ModalLarge from '@/Components/ModalLarge.vue';
import iconLoader from '@/Components/vristo/icon/icon-loader.vue';
import { faMoneyBillWave, faTriangleExclamation, faFileInvoiceDollar, faFilePdf, faMagnifyingGlass } from '@fortawesome/free-solid-svg-icons';

const props = defineProps({
    enrollment: { type: Object, required: true },
    schoolType: { type: String, default: 'privado' },
    concepts: { type: Array, default: () => [] },
    schedules: { type: Array, default: () => [] },
    charges: { type: Array, default: () => [] },
    paymentMethods: { type: Object, default: () => ({}) },
    matriculaCharge: { type: Object, default: null },
    personCandidates: { type: Array, default: () => [] },
    identityDocuments: { type: Array, default: () => [] },
    documentTypes: { type: Array, default: () => [] },
    salePaymentMethods: { type: Array, default: () => [] },
    taxes: { type: Object, default: () => ({ igv: 18, icbper: 0 }) },
});

const isPrivate = computed(() => props.schoolType === 'privado');
const student = computed(() => props.enrollment.student ?? {});
const person = computed(() => student.value.person ?? {});

/* ----------------- Compromisos de pago ----------------- */

const matriculaRow = computed(() => {
    if (! props.matriculaCharge) return null;
    const c = props.matriculaCharge;
    return {
        key: 'm' + c.id,
        kind: 'charge',
        id: c.id,
        label: c.description,
        due: props.enrollment.enrollment_date,
        amount: c.amount,
        status: c.status,
        comprobante: c.sale_document ? c.sale_document.invoice_serie + '-' + c.sale_document.number : null,
        pdf_url: c.pdf_url,
        method: c.payment_method,
        reference: c.reference,
    };
});

const cuotaRows = computed(() => props.schedules.map((s) => {
    const c = s.charge;
    return {
        key: 's' + s.id,
        kind: 'schedule',
        id: s.id,
        label: (s.fee_type?.name ?? 'Mensualidad') + ' - Cuota ' + s.installment,
        due: s.due_date,
        amount: s.amount,
        status: c ? c.status : 'pendiente',
        comprobante: c?.sale_document ? c.sale_document.invoice_serie + '-' + c.sale_document.number : null,
        pdf_url: c?.pdf_url ?? null,
        method: c?.payment_method ?? null,
        reference: c?.reference ?? null,
    };
}));

const compromisos = computed(() => {
    const rows = matriculaRow.value ? [matriculaRow.value] : [];
    return rows.concat(cuotaRows.value);
});

// Estados de plazo de pago: "por vencer" desde 5 dias antes de la fecha
// limite y "vencido" cuando la fecha ya paso sin pago.
const DEADLINE_WARNING_DAYS = 5;

const daysUntil = (due) => {
    if (! due) return null;
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const dueDate = new Date(String(due).slice(0, 10) + 'T00:00:00');
    return Math.round((dueDate - today) / 86400000);
};

const deadlineOf = (row) => {
    if (row.status !== 'pendiente') return null;
    const days = daysUntil(row.due);
    if (days === null || days > DEADLINE_WARNING_DAYS) return null;
    return { kind: days < 0 ? 'vencido' : 'por_vencer', days };
};

const deadlineRows = computed(() => compromisos.value
    .map((row) => ({ row, deadline: deadlineOf(row) }))
    .filter((item) => item.deadline));

const porVencer = computed(() => deadlineRows.value.filter((item) => item.deadline.kind === 'por_vencer'));
const vencidos = computed(() => deadlineRows.value.filter((item) => item.deadline.kind === 'vencido'));

const showDeadlineModal = ref(false);

const money = (value) => 'S/ ' + (parseFloat(value) || 0).toFixed(2);

const deadlineTotal = (list) => list.reduce((acc, item) => acc + (parseFloat(item.row.amount) || 0), 0);

const monthlyConcept = computed(() => props.concepts.find((c) => c.is_recurring) ?? null);

const canGenerateCompromisos = computed(() =>
    isPrivate.value
    && ! props.matriculaCharge
    && props.schedules.length === 0
    && monthlyConcept.value
    && monthlyConcept.value.amount !== null
);

/* ----------------- Modal crear cobro / comprobante ----------------- */

const showDocModal = ref(false);
const savingDoc = ref(false);
const consultingDoc = ref(false);
const consultInfo = ref('');
const consultError = ref('');

const docForm = reactive({
    document_type: '80',
    payment_method_id: null,
    reference: '',
    paid_at: new Date().toISOString().slice(0, 10),
    client: {
        person_id: null,
        document_type_id: 1,
        number: '',
        full_name: '',
        address: '',
        email: '',
        telephone: '',
    },
});

const docItems = ref([]);

const extraConcepts = computed(() => props.concepts.filter((c) =>
    c.code !== 'mensualidad'
    && ! (c.code === 'matricula' && matriculaRow.value && matriculaRow.value.status !== 'anulado')
));

const buildDocItems = (preKey = null) => {
    const items = [];

    compromisos.value
        .filter((r) => r.status === 'pendiente')
        .forEach((r) => {
            items.push({
                key: r.key,
                type: r.kind,
                id: r.id,
                fee_type_id: null,
                label: r.label,
                amount: r.amount,
                detail: '',
                editable: r.kind === 'charge',
                selected: preKey ? r.key === preKey : true,
            });
        });

    extraConcepts.value.forEach((c) => {
        items.push({
            key: 'x' + c.id,
            type: 'concept',
            id: null,
            fee_type_id: c.id,
            label: c.name,
            amount: c.amount ?? '',
            detail: '',
            editable: true,
            selected: preKey ? 'x' + c.id === preKey : false,
        });
    });

    docItems.value = items;
};

const fillClient = (candidate) => {
    docForm.client.person_id = candidate.id;
    docForm.client.document_type_id = candidate.document_type_id ?? 1;
    docForm.client.number = candidate.number ?? '';
    docForm.client.full_name = candidate.full_name ?? '';
    docForm.client.address = candidate.address ?? '';
    docForm.client.email = candidate.email ?? '';
    docForm.client.telephone = candidate.telephone ?? '';
};

const selectClient = (event) => {
    const candidate = props.personCandidates.find((p) => p.id === parseInt(event.target.value));
    if (candidate) {
        fillClient(candidate);
    }
};

// La factura exige RUC: al elegirla limpiamos los datos de documento que
// no corresponden (DNI) para que el usuario ingrese y consulte el RUC.
watch(() => docForm.document_type, (tipo) => {
    consultInfo.value = '';
    consultError.value = '';
    if (tipo === '01' && String(docForm.client.document_type_id) !== '6') {
        docForm.client.document_type_id = 6;
        docForm.client.number = '';
        docForm.client.full_name = '';
        docForm.client.address = '';
    }
});

// Consulta el RUC/DNI del cliente (base de datos primero, luego la API de
// SUNAT migo.pe) y autocompleta razon social y direccion.
const consultClientDocument = () => {
    const number = String(docForm.client.number || '').trim();
    if (! number) {
        consultError.value = 'Ingresa el número de documento a consultar.';
        return;
    }

    consultingDoc.value = true;
    consultError.value = '';
    consultInfo.value = '';

    axios.post(route('aca_school_charges_consult_document'), {
        document_type_id: docForm.client.document_type_id,
        number: number,
    }).then(({ data }) => {
        if (data.success && data.person) {
            docForm.client.number = data.person.numero_documento ?? number;
            if (data.person.razon_social) docForm.client.full_name = data.person.razon_social;
            if (data.person.direccion) docForm.client.address = data.person.direccion;
            consultInfo.value = [
                data.source === 'database' ? 'Datos de la base de datos' : (data.source === 'sunat' ? 'Datos SUNAT (migo.pe)' : 'Datos RENIEC (migo.pe)'),
                data.person.estado ? 'Estado: ' + data.person.estado : null,
                data.person.condicion ? 'Condición: ' + data.person.condicion : null,
            ].filter(Boolean).join(' · ');
        } else {
            consultError.value = data.error ?? 'No se encontraron datos para el documento consultado.';
        }
    }).catch((error) => {
        consultError.value = error.response?.data?.message ?? 'No se pudo consultar el documento.';
    }).finally(() => {
        consultingDoc.value = false;
    });
};

const openDoc = (preKey = null) => {
    buildDocItems(preKey);

    docForm.document_type = '80';
    docForm.reference = '';
    docForm.paid_at = new Date().toISOString().slice(0, 10);

    if (! docForm.payment_method_id) {
        docForm.payment_method_id = props.salePaymentMethods[0]?.id ?? null;
    }

    if (! docForm.client.person_id && props.personCandidates.length > 0) {
        fillClient(props.personCandidates[0]);
    }

    showDocModal.value = true;
};

const closeDocModal = () => {
    showDocModal.value = false;
};

const openDocForConcept = (concept) => {
    if (concept.code === 'matricula' && matriculaRow.value && matriculaRow.value.status === 'pendiente') {
        openDoc(matriculaRow.value.key);
        return;
    }
    openDoc('x' + concept.id);
};

const selectedItems = computed(() => docItems.value.filter((i) => i.selected && parseFloat(i.amount) > 0));

const selectedTotal = computed(() =>
    selectedItems.value.reduce((acc, i) => acc + (parseFloat(i.amount) || 0), 0)
);

const totalsPreview = computed(() => {
    const total = selectedTotal.value;
    if (docForm.document_type === '01' || docForm.document_type === '03') {
        const rate = parseFloat(props.taxes.igv) || 18;
        const base = total / (1 + rate / 100);
        return { base: base.toFixed(2), igv: (total - base).toFixed(2), total: total.toFixed(2) };
    }
    return { base: null, igv: null, total: total.toFixed(2) };
});

const submitDoc = () => {
    if (selectedItems.value.length === 0) {
        Swal2.fire({
            icon: 'warning',
            text: 'Selecciona al menos un concepto con monto mayor a 0.',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
        return;
    }

    if (docForm.document_type) {
        if (docForm.document_type === '01' && (String(docForm.client.document_type_id) !== '6' || String(docForm.client.number).length !== 11)) {
            Swal2.fire({
                icon: 'warning',
                text: 'Para emitir una factura el cliente debe tener RUC (11 dígitos).',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
            return;
        }
        if (docForm.document_type === '03' && ! docForm.client.number) {
            Swal2.fire({
                icon: 'warning',
                text: 'Indica el número de documento del cliente para la boleta.',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
            return;
        }
        if (! docForm.client.full_name) {
            Swal2.fire({
                icon: 'warning',
                text: 'Indica el nombre o razón social del cliente.',
                padding: '2em',
                customClass: 'sweet-alerts',
            });
            return;
        }
    }

    savingDoc.value = true;

    axios.post(route('aca_school_charges_comprobante'), {
        enrollment_id: props.enrollment.id,
        items: selectedItems.value.map((i) => ({
            type: i.type,
            id: i.type === 'concept' ? null : i.id,
            fee_type_id: i.type === 'concept' ? i.fee_type_id : null,
            amount: i.amount,
            detail: i.detail || null,
        })),
        document_type: docForm.document_type || null,
        person_id: docForm.client.person_id,
        client_document_type_id: docForm.client.document_type_id,
        client_number: docForm.client.number,
        client_full_name: docForm.client.full_name,
        client_address: docForm.client.address,
        client_email: docForm.client.email,
        client_telephone: docForm.client.telephone,
        payment_method_id: docForm.payment_method_id,
        reference: docForm.reference || null,
        paid_at: docForm.paid_at,
    }).then((res) => {
        showDocModal.value = false;
        router.reload({ only: ['concepts', 'schedules', 'charges', 'matriculaCharge'], preserveScroll: true });
        Swal2.fire({
            title: 'Enhorabuena',
            text: res.data.message ?? 'Cobro registrado correctamente',
            icon: 'success',
            confirmButtonText: res.data.pdf_a4_url ? 'Ver PDF' : 'Cerrar',
            showCancelButton: !! res.data.pdf_a4_url,
            cancelButtonText: 'Cerrar',
            padding: '2em',
            customClass: 'sweet-alerts',
        }).then((result) => {
            if (result.isConfirmed && res.data.pdf_a4_url) {
                window.open(res.data.pdf_a4_url, '_blank');
            }
        });
    }).catch((error) => {
        Swal2.fire({
            icon: 'error',
            text: error.response?.data?.message ?? 'No se pudo registrar el cobro',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    }).finally(() => {
        savingDoc.value = false;
    });
};

const generateCompromisos = () => {
    axios.post(route('aca_school_charges_generate_schedule'), {
        enrollment_id: props.enrollment.id,
    }).then((res) => {
        Swal2.fire({
            title: 'Enhorabuena',
            text: res.data.message,
            icon: 'success',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
        router.reload({ only: ['concepts', 'schedules', 'charges', 'matriculaCharge'], preserveScroll: true });
    }).catch((error) => {
        Swal2.fire({
            icon: 'error',
            text: error.response?.data?.message ?? 'No se pudieron generar los compromisos',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    });
};

const annulCharge = (chargeId) => {
    const row = compromisos.value.find((r) => r.charge_id === chargeId) ?? props.charges.find((c) => c.id === chargeId);
    const name = row ? row.description ?? row.label : 'el cobro';
    Swal2.fire({
        title: '¿Anular cobro?',
        text: `${name}. La cuota vinculada (si existe) volverá a pendiente. El comprobante emitido no se anula automáticamente.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, anular',
        cancelButtonText: 'Cancelar',
        padding: '2em',
        customClass: 'sweet-alerts',
    }).then((result) => {
        if (result.isConfirmed) {
            // Deshabilita los botones mientras se procesa la anulacion.
            Swal2.showLoading();
            axios
                .post(route('aca_school_charges_annul', chargeId))
                .then((res) => {
                    router.reload({ only: ['concepts', 'schedules', 'charges', 'matriculaCharge'], preserveScroll: true });
                    // Reemplaza el dialogo de confirmacion por el aviso de exito.
                    Swal2.fire({
                        icon: 'success',
                        title: res.data?.message ?? 'Cobro anulado correctamente',
                        confirmButtonText: 'Aceptar',
                        padding: '2em',
                        customClass: 'sweet-alerts',
                    });
                })
                .catch((error) => {
                    Swal2.fire({
                        icon: 'error',
                        title: 'No se pudo anular',
                        text: error.response?.data?.message ?? 'Ocurrió un error inesperado',
                        confirmButtonText: 'Aceptar',
                        padding: '2em',
                        customClass: 'sweet-alerts',
                    });
                });
        }
    });
};

const formatDate = (value) => {
    if (! value) return '-';
    return value.slice(0, 10).split('-').reverse().join('/');
};

const methodLabel = (value) => props.paymentMethods[value] ?? value;
</script>

<template>
    <AppLayout title="Cobros del Alumno">
        <Navigation :routeModule="route('aca_dashboard')" :titleModule="'Académico'"
            :data="[
                { route: route('aca_school_enrollments_list'), title: 'Matrículas' },
                { title: 'Cobros' },
            ]"
        />
        <div class="pt-5">
            <div class="panel p-6">
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div>
                        <h3 class="text-lg font-semibold">{{ person.full_name }}</h3>
                        <p class="text-sm text-white-dark mt-1">
                            DNI {{ person.number }} · Código {{ student.student_code }} ·
                            {{ enrollment.year?.year }} ·
                            {{ enrollment.section ? 'Sección ' + enrollment.section.name : 'Sin sección' }}
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span v-if="isPrivate" class="badge bg-primary">Colegio Privado</span>
                        <span v-else class="badge bg-secondary">Colegio Nacional</span>
                        <button type="button" class="btn btn-primary" @click="openDoc()">
                            <font-awesome-icon :icon="faFileInvoiceDollar" class="mr-1" />
                            Crear cobro / Comprobante
                        </button>
                        <Link :href="route('aca_school_enrollments_list')" class="btn btn-outline-secondary">Ir al listado</Link>
                    </div>
                </div>
                <p v-if="! isPrivate" class="text-sm text-warning mt-3 flex items-center gap-2">
                    <font-awesome-icon :icon="faTriangleExclamation" />
                    Colegio nacional: todos los cobros son voluntarios (no obligatorios).
                </p>
            </div>

            <div class="panel mt-5">
                <div class="flex items-center justify-between p-5 border-b border-[#ebedf2] dark:border-[#191e3a]">
                    <h3 class="text-lg font-semibold">Compromisos de Pago</h3>
                    <PrimaryButton v-if="canGenerateCompromisos" type="button" @click="generateCompromisos">
                        Generar compromisos
                    </PrimaryButton>
                </div>
                <div class="table-responsive p-5">
                    <div v-if="deadlineRows.length" class="mb-4 border rounded-md p-3 flex flex-wrap items-center gap-3 border-warning">
                        <font-awesome-icon :icon="faTriangleExclamation" class="text-warning" />
                        <div class="flex-1 text-sm">
                            <span v-if="porVencer.length" class="text-warning">
                                {{ porVencer.length }} pago(s) por vencer en los próximos {{ DEADLINE_WARNING_DAYS }} días ({{ money(deadlineTotal(porVencer)) }})
                            </span>
                            <span v-if="vencidos.length" class="text-danger font-semibold">
                                {{ porVencer.length ? ' · ' : '' }}{{ vencidos.length }} pago(s) vencido(s) ({{ money(deadlineTotal(vencidos)) }})
                            </span>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-warning" @click="showDeadlineModal = true">
                            Ver detalle
                        </button>
                    </div>
                    <p v-if="compromisos.length === 0" class="text-sm text-white-dark">
                        Sin compromisos. Se crean automáticamente al matricular si hay tarifas configuradas, o con el botón "Generar compromisos" (colegio privado con tarifa de mensualidad).
                    </p>
                    <table v-else class="table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Concepto</th>
                                <th>Vencimiento</th>
                                <th>Monto</th>
                                <th class="!text-center">Estado</th>
                                <th class="!text-center">Comprobante</th>
                                <th class="!text-center">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in compromisos" :key="row.key">
                                <td>{{ row.label }}</td>
                                <td>{{ formatDate(row.due) }}</td>
                                <td class="font-semibold">S/ {{ row.amount }}</td>
                                <td class="text-center">
                                    <span v-if="row.status === 'pagado'" class="badge bg-success">Pagado</span>
                                    <span v-else-if="row.status === 'anulado'" class="badge bg-secondary">Anulado</span>
                                    <span v-else-if="deadlineOf(row)?.kind === 'vencido'" class="badge bg-danger">
                                        Vencido ({{ -deadlineOf(row).days }} d)
                                    </span>
                                    <span v-else-if="deadlineOf(row)" class="badge bg-warning">
                                        {{ deadlineOf(row).days === 0 ? 'Vence hoy' : 'Por vencer (' + deadlineOf(row).days + ' d)' }}
                                    </span>
                                    <span v-else class="badge bg-warning">Pendiente</span>
                                </td>
                                <td class="text-center">
                                    <a
                                        v-if="row.pdf_url"
                                        :href="row.pdf_url"
                                        target="_blank"
                                        class="text-primary inline-flex items-center gap-1 hover:underline"
                                    >
                                        <font-awesome-icon :icon="faFilePdf" />
                                        {{ row.comprobante }}
                                    </a>
                                    <span v-else class="text-xs text-white-dark">-</span>
                                </td>
                                <td class="text-center">
                                    <button
                                        v-if="row.status === 'pendiente' || row.status === 'anulado'"
                                        type="button"
                                        class="btn btn-sm btn-outline-success"
                                        @click="openDoc(row.key)"
                                    >
                                        <font-awesome-icon :icon="faMoneyBillWave" class="mr-1" />
                                        Cobrar
                                    </button>
                                    <span v-else class="text-xs text-white-dark">{{ methodLabel(row.method) }}{{ row.reference ? ' · ' + row.reference : '' }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="panel mt-5">
                <div class="p-5 border-b border-[#ebedf2] dark:border-[#191e3a]">
                    <h3 class="text-lg font-semibold">Conceptos de Cobro</h3>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 p-5">
                    <div
                        v-for="concept in concepts"
                        :key="concept.id"
                        class="border border-[#ebedf2] dark:border-[#191e3a] rounded-md p-4"
                    >
                        <div class="flex items-center justify-between">
                            <span class="font-semibold">{{ concept.name }}</span>
                            <span
                                class="text-xs px-2 py-1 rounded"
                                :class="isPrivate && concept.is_recurring ? 'bg-danger text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300'"
                            >
                                {{ isPrivate && concept.is_recurring ? 'Obligatorio' : 'Opcional' }}
                            </span>
                        </div>
                        <div class="mt-2 text-2xl font-bold">
                            {{ concept.amount !== null ? 'S/ ' + concept.amount : 'Sin tarifa' }}
                        </div>
                        <div class="text-xs text-white-dark mt-1">
                            Cobrado: S/ {{ concept.paid_amount }}
                        </div>
                        <button
                            type="button"
                            class="btn btn-primary w-full mt-3"
                            @click="openDocForConcept(concept)"
                        >
                            <font-awesome-icon :icon="faMoneyBillWave" class="mr-1" />
                            Cobrar
                        </button>
                    </div>
                </div>
            </div>

            <div class="panel mt-5">
                <div class="p-5 border-b border-[#ebedf2] dark:border-[#191e3a]">
                    <h3 class="text-lg font-semibold">Historial de Cobros</h3>
                </div>
                <div class="table-responsive p-5">
                    <p v-if="charges.length === 0" class="text-sm text-white-dark">Sin cobros registrados.</p>
                    <table v-else class="table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Concepto</th>
                                <th>Monto</th>
                                <th>Método</th>
                                <th>Referencia</th>
                                <th class="!text-center">Estado</th>
                                <th class="!text-center">Comprobante</th>
                                <th class="!text-center">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="charge in charges" :key="charge.id">
                                <td>{{ formatDate(charge.paid_at) }}</td>
                                <td>{{ charge.description }}</td>
                                <td class="font-semibold">S/ {{ charge.amount }}</td>
                                <td>{{ methodLabel(charge.payment_method) }}</td>
                                <td>{{ charge.reference ?? '-' }}</td>
                                <td class="text-center">
                                    <span v-if="charge.status === 'pagado'" class="badge bg-success">Pagado</span>
                                    <span v-else-if="charge.status === 'anulado'" class="badge bg-danger">Anulado</span>
                                    <span v-else class="badge bg-warning">Pendiente</span>
                                </td>
                                <td class="text-center">
                                    <a
                                        v-if="charge.pdf_url"
                                        :href="charge.pdf_url"
                                        target="_blank"
                                        class="text-primary inline-flex items-center gap-1 hover:underline"
                                    >
                                        <font-awesome-icon :icon="faFilePdf" />
                                        {{ charge.sale_document ? charge.sale_document.invoice_serie + '-' + charge.sale_document.number : '' }}
                                    </a>
                                    <span v-else class="text-xs text-white-dark">-</span>
                                </td>
                                <td class="text-center">
                                    <button
                                        v-if="charge.status !== 'anulado'"
                                        type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        @click="annulCharge(charge.id)"
                                    >
                                        Anular
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <ModalLargeXX :show="showDocModal" :onClose="closeDocModal" :icon="'/img/comunidad.png'">
            <template #title>
                Crear cobro / Comprobante
            </template>
            <template #message>
                {{ person.full_name }} · {{ enrollment.year?.year }}
            </template>
            <template #content>
                <div class="grid grid-cols-1 lg:grid-cols-5 gap-5">
                    <div class="lg:col-span-2">
                        <label class="form-label font-semibold">1. Conceptos a cobrar</label>
                        <div class="space-y-2 max-h-[420px] overflow-y-auto mt-1 pr-1">
                            <label
                                v-for="item in docItems"
                                :key="item.key"
                                class="flex items-center gap-2 border rounded-md px-3 py-2 cursor-pointer"
                                :class="item.selected ? 'border-primary bg-primary/5' : 'border-[#ebedf2] dark:border-[#1b2e4b]'"
                            >
                                <input type="checkbox" v-model="item.selected" class="w-4 h-4" />
                                <span class="flex-1 text-sm">{{ item.label }}</span>
                                <input
                                    v-if="item.type === 'concept'"
                                    v-model="item.detail"
                                    type="text"
                                    placeholder="Detalle (opcional)"
                                    maxlength="100"
                                    class="form-input form-input-sm w-44 hidden sm:block"
                                />
                                <input
                                    v-model="item.amount"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="form-input form-input-sm w-28 text-right"
                                    :disabled="! item.editable"
                                />
                            </label>
                            <p v-if="docItems.length === 0" class="text-sm text-white-dark">
                                No hay compromisos pendientes ni conceptos configurados.
                            </p>
                        </div>
                    </div>

                    <div class="lg:col-span-3 space-y-4">
                        <div class="grid sm:grid-cols-2 gap-3">
                            <div>
                                <label class="form-label font-semibold">2. Comprobante</label>
                                <select v-model="docForm.document_type" class="form-select">
                                    <option value="">Sin comprobante (solo cobro)</option>
                                    <option v-for="t in documentTypes" :key="t.value" :value="t.value">{{ t.label }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Fecha de pago *</label>
                                <input v-model="docForm.paid_at" type="date" class="form-input" />
                            </div>
                        </div>

                        <div v-if="docForm.document_type" class="border rounded-md p-3 space-y-3 border-[#ebedf2] dark:border-[#1b2e4b]">
                            <div class="flex items-center justify-between flex-wrap gap-2">
                                <span class="text-sm font-semibold">3. Cliente del comprobante</span>
                                <select class="form-select form-select-sm max-w-[260px]" @change="selectClient($event)">
                                    <option value="">— Seleccionar persona —</option>
                                    <option v-for="cand in personCandidates" :key="cand.id" :value="cand.id">
                                        {{ cand.label + ': ' + cand.full_name }}
                                    </option>
                                </select>
                            </div>
                            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                <div>
                                    <label class="form-label">Tipo documento</label>
                                    <select v-model="docForm.client.document_type_id" class="form-select form-select-sm" :disabled="docForm.document_type === '01'">
                                        <option v-for="d in identityDocuments" :key="d.id" :value="d.id">{{ d.description }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label">Número</label>
                                    <div class="flex gap-2">
                                        <input v-model="docForm.client.number" @input="consultError = ''; consultInfo = ''" type="text" class="form-input form-input-sm" maxlength="15" />
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-primary whitespace-nowrap"
                                            @click="consultClientDocument"
                                            :disabled="consultingDoc || !docForm.client.number"
                                        >
                                            <icon-loader v-if="consultingDoc" class="w-3 h-3 mr-1" />
                                            <font-awesome-icon v-else :icon="faMagnifyingGlass" class="w-3 h-3 mr-1" />
                                            Consultar
                                        </button>
                                    </div>
                                </div>
                                <div>
                                    <label class="form-label">Nombre / Razón social</label>
                                    <input v-model="docForm.client.full_name" type="text" class="form-input form-input-sm" maxlength="300" />
                                </div>
                                <div>
                                    <label class="form-label">Dirección</label>
                                    <input v-model="docForm.client.address" type="text" class="form-input form-input-sm" maxlength="300" />
                                </div>
                                <div>
                                    <label class="form-label">Email</label>
                                    <input v-model="docForm.client.email" type="email" class="form-input form-input-sm" maxlength="150" />
                                </div>
                                <div>
                                    <label class="form-label">Teléfono</label>
                                    <input v-model="docForm.client.telephone" type="text" class="form-input form-input-sm" maxlength="30" />
                                </div>
                            </div>
                            <p v-if="consultInfo" class="text-xs text-success">{{ consultInfo }}</p>
                            <p v-if="consultError" class="text-xs text-danger">{{ consultError }}</p>
                            <p v-if="docForm.document_type === '01'" class="text-xs text-warning">
                                La factura exige RUC (11 dígitos) como número de documento del cliente. Ingresa el RUC y presiona "Consultar" para autocompletar los datos.
                            </p>
                        </div>

                        <div class="grid sm:grid-cols-2 gap-3">
                            <div>
                                <label class="form-label font-semibold">4. Medio de pago *</label>
                                <select v-model="docForm.payment_method_id" class="form-select">
                                    <option v-for="pm in salePaymentMethods" :key="pm.id" :value="pm.id">{{ pm.description }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">N° de operación / referencia</label>
                                <input v-model="docForm.reference" type="text" class="form-input" maxlength="50" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-6 border-t pt-3 mt-4 border-[#ebedf2] dark:border-[#1b2e4b]">
                    <template v-if="totalsPreview.base !== null">
                        <div class="text-right">
                            <div class="text-xs text-white-dark">Base imponible</div>
                            <div class="font-semibold">S/ {{ totalsPreview.base }}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-xs text-white-dark">IGV</div>
                            <div class="font-semibold">S/ {{ totalsPreview.igv }}</div>
                        </div>
                    </template>
                    <div class="text-right">
                        <div class="text-xs text-white-dark">Total</div>
                        <div class="text-xl font-bold text-primary">S/ {{ totalsPreview.total }}</div>
                    </div>
                </div>
            </template>
            <template #buttons>
                <PrimaryButton type="button" @click="submitDoc" :disabled="savingDoc">
                    <icon-loader v-if="savingDoc" class="w-4 h-4 mr-1" />
                    {{ savingDoc ? 'Procesando...' : 'Registrar cobro' }}
                </PrimaryButton>
            </template>
        </ModalLargeXX>

        <ModalLarge :show="showDeadlineModal" :on-close="() => (showDeadlineModal = false)" :icon="'/img/cuenta-bancaria.png'">
            <template #title>
                Detalle de pagos por vencer y vencidos
            </template>
            <template #message>
                {{ person.full_name }} · {{ enrollment.year?.year }}
            </template>
            <template #content>
                <div class="space-y-4">
                    <div>
                        <h4 class="text-sm font-semibold mb-2 text-warning">Por vencer (próximos {{ DEADLINE_WARNING_DAYS }} días)</h4>
                        <table v-if="porVencer.length" class="table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Concepto</th>
                                    <th>Vence</th>
                                    <th>Días</th>
                                    <th>Monto</th>
                                    <th class="!text-center">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in porVencer" :key="item.row.key">
                                    <td>{{ item.row.label }}</td>
                                    <td>{{ formatDate(item.row.due) }}</td>
                                    <td>{{ item.deadline.days === 0 ? 'Hoy' : 'En ' + item.deadline.days + ' día(s)' }}</td>
                                    <td class="font-semibold">{{ money(item.row.amount) }}</td>
                                    <td class="text-center"><span class="badge bg-warning">Por vencer</span></td>
                                </tr>
                            </tbody>
                        </table>
                        <p v-else class="text-sm text-white-dark">Sin pagos próximos a vencer.</p>
                    </div>

                    <div>
                        <h4 class="text-sm font-semibold mb-2 text-danger">Vencidos</h4>
                        <table v-if="vencidos.length" class="table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Concepto</th>
                                    <th>Venció</th>
                                    <th>Atraso</th>
                                    <th>Monto</th>
                                    <th class="!text-center">Sanción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in vencidos" :key="item.row.key">
                                    <td>{{ item.row.label }}</td>
                                    <td>{{ formatDate(item.row.due) }}</td>
                                    <td>{{ -item.deadline.days }} día(s)</td>
                                    <td class="font-semibold">{{ money(item.row.amount) }}</td>
                                    <td class="text-center"><span class="badge bg-danger">Vencido</span></td>
                                </tr>
                            </tbody>
                        </table>
                        <p v-else class="text-sm text-white-dark">Sin pagos vencidos.</p>
                    </div>

                    <div class="border-t pt-3 space-y-1 text-sm border-[#ebedf2] dark:border-[#1b2e4b]">
                        <div class="flex justify-between">
                            <span>Total por vencer</span>
                            <span class="font-semibold">{{ money(deadlineTotal(porVencer)) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Total vencido</span>
                            <span class="font-semibold text-danger">{{ money(deadlineTotal(vencidos)) }}</span>
                        </div>
                    </div>

                    <p class="text-xs text-white-dark">
                        La sanción por mora de los pagos vencidos se aplicará según la política del colegio (aún no hay recargo configurado en el sistema, el monto mostrado es el de la cuota).
                    </p>
                </div>
            </template>
            <template #buttons>
                <PrimaryButton type="button" @click="showDeadlineModal = false">
                    Cerrar
                </PrimaryButton>
            </template>
        </ModalLarge>
    </AppLayout>
</template>
