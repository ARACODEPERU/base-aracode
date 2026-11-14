<script setup>
import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
import Navigation from "@/Components/vristo/layout/Navigation.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import InputLabel from "@/Components/InputLabel.vue";
import ModalLargeX from "@/Components/ModalLargeX.vue";
import ModalSmall from "@/Components/ModalSmall.vue";
import { Head, Link } from "@inertiajs/vue3";
import { computed, onMounted, reactive, ref, watch } from "vue";
import { faChevronDown, faMagnifyingGlass, faPlus, faXmark } from "@fortawesome/free-solid-svg-icons";

const props = defineProps({
    payments: { type: Array, default: () => [] },
    saleDocumentTypes: { type: Array, default: () => [] },
    company: { type: Object, default: () => ({}) },
    type_operation: { type: String, default: null },
    taxes: { type: Object, default: () => ({ igv: 18, icbper: 0 }) },
});

const estadoCuota = {
    pending: "Pendiente",
    partial: "Parcial",
    amortized: "Amortizado",
    overdue: "Vencido",
    paid: "Pagado",
    cancelled: "Anulado",
};

const moneda = (value) => `S/ ${Number(value || 0).toFixed(2)}`;

const form = reactive({
    sale_documenttype_id: props.saleDocumentTypes[0]?.id ?? 1,
    serie: null,
    date_issue: new Date().toISOString().slice(0, 10),
    date_end: new Date().toISOString().slice(0, 10),
    additional_description: "",
    forma_pago: "Contado",
    // Un comprobante puede cobrarse con varios medios de pago, igual que en
    // facturacion electronica. Arranca vacio: el usuario elige el medio.
    payments: [{ type: null, reference: null, amount: null }],
});

const client = ref(null);
const contracts = ref([]);
const items = ref([]);
const series = ref([]);
const loadingContracts = ref(false);
const saving = ref(false);
const errorMessage = ref("");
// Si el usuario escribio el monto a mano, no se pisa al cambiar el total.
const montoEditado = ref(false);

// Seleccion de cuotas: modal con contratos desplegables.
const showContracts = ref(false);
const expandedContracts = ref([]);

// Venta al credito: cuotas programadas del comprobante (no del contrato).
const showQuotas = ref(false);
const cuotas = reactive({
    number: 1,
    days: 15,
    end_month: false,
    amounts: [],
});

const search = reactive({
    q: "",
    results: [],
    loading: false,
});

let searchTimer = null;

const igvRate = computed(() => Number(props.taxes?.igv ?? 18) / 100);

const totals = computed(() => {
    const total = items.value.reduce((sum, item) => sum + Number(item.amount || 0), 0);
    const base = total / (1 + igvRate.value);

    return {
        total: total.toFixed(2),
        base: base.toFixed(2),
        igv: (total - base).toFixed(2),
    };
});

const cuotasTotal = computed(() =>
    cuotas.amounts.reduce((sum, quota) => sum + Number(quota.amount || 0), 0).toFixed(2)
);

const diferenciaCuotas = computed(() =>
    (Number(totals.value.total) - Number(cuotasTotal.value)).toFixed(2)
);

const pagosTotal = computed(() =>
    form.payments
        .reduce((sum, payment) => sum + Number(payment.amount || 0), 0)
        .toFixed(2)
);

const diferenciaPagos = computed(() =>
    (Number(totals.value.total) - Number(pagosTotal.value)).toFixed(2)
);

const medioSinCuenta = (id) => {
    const metodo = props.payments.find((payment) => payment.id === id);

    return Boolean(metodo) && !metodo.has_treasury_account;
};

const addPayment = () => {
    form.payments.push({ type: null, reference: null, amount: null });
};

const removePayment = (index) => {
    if (form.payments.length > 1) {
        form.payments.splice(index, 1);
    }
};

const reiniciarPagos = () => {
    montoEditado.value = false;
    form.payments = [{ type: null, reference: null, amount: null }];
};

const yaEnBandeja = (quotaId) => items.value.some((item) => item.quota_id === quotaId);

const etiquetaCuota = (quota) =>
    `Cuota ${quota.payment_number}${quota.due_date ? ` · vence ${quota.due_date}` : ""}`;

const getSeriesByDocumentType = async () => {
    series.value = [];
    form.serie = null;

    try {
        const { data } = await axios.get(route("sale_document_series", form.sale_documenttype_id));

        if (data.status) {
            series.value = data.series;
            form.serie = series.value[0]?.id ?? null;
        } else {
            window.alertaSistema(
                "Sin serie",
                "No existe una serie para este local y tipo de documento. Crea la serie antes de emitir el comprobante.",
                "warning"
            );
        }
    } catch (error) {
        window.alertaSistema("Error", "No se pudieron cargar las series del comprobante.", "error");
    }
};

const searchClients = async () => {
    if (search.q.trim() === "") {
        search.results = [];
        return;
    }

    search.loading = true;

    try {
        const { data } = await axios.post(route("comm_sales_documents_clients_search"), { q: search.q });
        search.results = data.clients || [];
    } catch (error) {
        search.results = [];
    } finally {
        search.loading = false;
    }
};

const queueClientSearch = () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(searchClients, 350);
};

const loadContracts = async (abrirModal = true) => {
    if (!client.value) {
        contracts.value = [];
        return;
    }

    loadingContracts.value = true;

    try {
        const { data } = await axios.get(
            route("comm_sales_documents_client_contracts", client.value.id)
        );
        contracts.value = data.contracts || [];

        if (abrirModal) {
            abrirContratos();
        }
    } catch (error) {
        contracts.value = [];
        window.alertaSistema("Error", "No se pudieron cargar los contratos del cliente.", "error");
    } finally {
        loadingContracts.value = false;
    }
};

const abrirContratos = () => {
    if (!contracts.value.length) {
        return;
    }

    expandedContracts.value = contracts.value.length === 1 ? [contracts.value[0].id] : [];
    showContracts.value = true;
};

const cerrarContratos = () => {
    showContracts.value = false;
};

const toggleContract = (id) => {
    expandedContracts.value = expandedContracts.value.includes(id)
        ? expandedContracts.value.filter((item) => item !== id)
        : [...expandedContracts.value, id];
};

const cambiarCliente = () => {
    client.value = null;
    contracts.value = [];
    items.value = [];
    search.q = "";
    search.results = [];
    expandedContracts.value = [];
    showContracts.value = false;
    errorMessage.value = "";
};

const selectClient = (selected) => {
    client.value = selected;
    search.results = [];
    search.q = selected.full_name;
    items.value = [];
    errorMessage.value = "";
    expandedContracts.value = [];
    showContracts.value = false;
    loadContracts();
};

const agregarCuota = (contract, quota) => {
    if (yaEnBandeja(quota.id)) {
        return;
    }

    items.value.push({
        quota_id: quota.id,
        contract_id: contract.id,
        contract_title: contract.title,
        payment_number: quota.payment_number,
        due_date: quota.due_date,
        balance_amount: Number(quota.balance_amount || 0),
        description: `${contract.service?.description || "Servicio"} - Cuota ${quota.payment_number}`,
        amount: Number(quota.balance_amount || 0).toFixed(2),
    });

    errorMessage.value = "";
};

const quitarItem = (index) => {
    items.value.splice(index, 1);
};

/**
 * Reparte el total en cuotas exactas (sin perder centimos) y calcula los
 * vencimientos igual que facturacion electronica: cada X dias o fin de mes.
 */
const calcularCuotas = () => {
    const totalCents = Math.round(Number(totals.value.total) * 100);
    const numero = Math.max(1, Number(cuotas.number) || 1);
    const base = Math.floor(totalCents / numero);
    let resto = totalCents - base * numero;
    const lista = [];

    for (let index = 0; index < numero; index += 1) {
        let cents = base;

        if (resto > 0) {
            cents += 1;
            resto -= 1;
        }

        const vencimiento = new Date(`${form.date_issue}T00:00:00`);

        if (cuotas.end_month) {
            vencimiento.setMonth(vencimiento.getMonth() + index + 1);
            vencimiento.setDate(0);
        } else {
            vencimiento.setDate(vencimiento.getDate() + Number(cuotas.days) * (index + 1));
        }

        lista.push({
            amount: (cents / 100).toFixed(2),
            dueDate: vencimiento.toISOString().slice(0, 10),
        });
    }

    cuotas.amounts = lista;
};

watch(
    () => [totals.value.total, form.date_issue, cuotas.number, cuotas.days, cuotas.end_month, form.forma_pago],
    () => {
        if (form.forma_pago === "Credito") {
            calcularCuotas();
        }
    },
    { immediate: true }
);

// Al volver al contado se reinicia el cobro; con un solo medio de pago el
// monto sigue al total mientras el usuario no lo escriba a mano.
watch(
    () => [form.forma_pago, totals.value.total],
    ([formaPago, total], previos) => {
        if (formaPago !== previos?.[0]) {
            reiniciarPagos();
        }

        if (
            formaPago === "Contado" &&
            form.payments.length === 1 &&
            !montoEditado.value &&
            Number(total) > 0
        ) {
            form.payments[0].amount = total;
        }
    },
    { immediate: true }
);

const validar = () => {
    if (!client.value) {
        return "Selecciona el cliente del comprobante.";
    }

    if (!items.value.length) {
        return "Agrega al menos una cuota pendiente de pago.";
    }

    if (!form.serie) {
        return "Selecciona la serie del comprobante.";
    }

    const tipo = Number(form.sale_documenttype_id);

    if (tipo === 1 && Number(client.value.document_type_id) !== 6) {
        return "Para emitir una factura el cliente debe tener RUC.";
    }

    if (items.value.some((item) => Number(item.amount) <= 0)) {
        return "El monto de cada cobro debe ser mayor a cero.";
    }

    if (items.value.some((item) => Number(item.amount) > Number(item.balance_amount) + 0.01)) {
        return "El monto de una cuota no puede ser mayor a su saldo pendiente.";
    }

    if (form.forma_pago === "Contado") {
        if (form.payments.some((payment) => !payment.type)) {
            return "Selecciona el medio de pago de cada cobro.";
        }

        if (form.payments.some((payment) => Number(payment.amount) <= 0)) {
            return "El monto de cada medio de pago debe ser mayor a cero.";
        }

        if (Math.abs(Number(diferenciaPagos.value)) > 0.01) {
            return "La suma de los medios de pago debe ser igual al total del comprobante.";
        }
    }

    if (form.forma_pago === "Credito") {
        if (!cuotas.amounts.length) {
            return "Programa al menos una cuota para la venta al crédito.";
        }

        if (Math.abs(Number(diferenciaCuotas.value)) > 0.01) {
            return "Las cuotas programadas deben sumar el total del comprobante.";
        }
    }

    return null;
};

const limpiarBandeja = () => {
    items.value = [];
    errorMessage.value = "";
    form.additional_description = "";
    reiniciarPagos();
    calcularCuotas();

    if (client.value) {
        loadContracts(false);
    }
};

const preguntaSiguientePaso = async (label) => {
    const mensaje = `El comprobante ${label} se registró correctamente. ¿Qué deseas hacer?`;

    if (window.alertaSistema.enAracodito()) {
        const result = await window.alertaSistema("Comprobante registrado", mensaje, "success", {
            duration: 0,
            buttons: [
                { label: "Enviar a SUNAT", value: "send", variant: "primary" },
                { label: "Imprimir", value: "print" },
                { label: "Seguir creando", value: "continue" },
            ],
        });

        const eleccion = result?.value ?? result?.action;

        return ["send", "print", "continue"].includes(eleccion) ? eleccion : "continue";
    }

    const result = await window.alertaSistema("Comprobante registrado", mensaje, "success", {
        showCancelButton: true,
        showDenyButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#5E5A5A",
        denyButtonColor: "#5E5A5A",
        confirmButtonText: "Enviar a SUNAT",
        denyButtonText: "Imprimir",
        cancelButtonText: "Seguir creando",
    });

    if (result.isConfirmed) return "send";
    if (result.isDenied) return "print";

    return "continue";
};

const enviarSunat = async (documentData) => {
    if (documentData.invoice_type_doc !== "01") {
        window.alertaSistema(
            "Las boletas se envían mediante un resumen",
            "La boleta quedó registrada. El envío a SUNAT se hace con el resumen diario, igual que en facturación electrónica.",
            "info"
        );
        return;
    }

    try {
        const { data } = await axios.get(
            route("saledocuments_send", [documentData.id, documentData.invoice_type_doc])
        );

        window.alertaSistema(
            data.success ? "Respuesta de SUNAT" : "SUNAT rechazó el comprobante",
            data.success
                ? `${data.message}${data.queued ? " El envío quedó en la cola de reintento." : ""}`
                : `Código ${data.code}: ${data.message}`,
            data.success ? "success" : "error"
        );
    } catch (error) {
        window.alertaSistema(
            "No se pudo enviar a SUNAT",
            error?.response?.data?.message || "Revisa el comprobante e inténtalo nuevamente.",
            "error"
        );
    }
};

const imprimir = (documentData) => {
    const url = route("saledocuments_download", [
        documentData.id,
        documentData.invoice_type_doc,
        "PDF",
    ]);

    window.open(url, "_blank");
};

const save = async () => {
    if (saving.value) {
        return;
    }

    const error = validar();

    if (error) {
        window.alertaSistema("Revisa el comprobante", error, "warning");
        return;
    }

    saving.value = true;
    errorMessage.value = "";

    const payload = {
        client_id: client.value.id,
        serie: form.serie,
        sale_documenttype_id: form.sale_documenttype_id,
        date_issue: form.date_issue,
        date_end: form.date_end,
        forma_pago: form.forma_pago,
        additional_description: form.additional_description || null,
        items: items.value.map((item) => ({
            quota_id: item.quota_id,
            amount: Number(item.amount),
            description: item.description,
        })),
        payments: [],
        quotas: { amounts: [] },
    };

    if (form.forma_pago === "Contado") {
        payload.payments = form.payments.map((payment) => ({
            type: payment.type,
            amount: Number(payment.amount),
            reference: payment.reference || null,
        }));
    } else {
        payload.quotas.amounts = cuotas.amounts.map((quota) => ({
            amount: Number(quota.amount),
            dueDate: quota.dueDate,
        }));
    }

    try {
        const { data } = await axios.post(route("comm_sales_documents_store"), payload);

        if (data?.success === false) {
            throw { response: { data } };
        }

        const documentData = data.document;
        const label = `${documentData.invoice_serie}-${documentData.invoice_correlative}`;

        limpiarBandeja();

        const eleccion = await preguntaSiguientePaso(label);

        if (eleccion === "send") {
            await enviarSunat(documentData);
        }

        if (eleccion === "print") {
            imprimir(documentData);
        }
    } catch (error) {
        const respuesta = error?.response?.data;
        const validaciones = respuesta?.errors ? Object.values(respuesta.errors).flat()[0] : null;
        const mensaje = respuesta?.message || validaciones || "No se pudo registrar el comprobante.";
        errorMessage.value = mensaje;
        window.alertaSistema("No se pudo registrar", mensaje, "error");
    } finally {
        saving.value = false;
    }
};

watch(
    () => form.sale_documenttype_id,
    () => getSeriesByDocumentType()
);

onMounted(() => {
    getSeriesByDocumentType();
});
</script>

<template>
    <Head>
        <meta name="aracodito-view" content="new" />
    </Head>

    <AppLayout title="Documento de venta">
        <Navigation
            :routeModule="route('comm_dashboard')"
            titleModule="Comercial"
            :data="[{ title: 'Documento de venta' }]"
        />

        <div class="mt-5 panel">
            <div
                class="flex flex-col gap-4 border-b border-gray-200 pb-4 dark:border-gray-700 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h2 class="text-xl font-bold dark:text-white">Documento de venta</h2>
                    <p class="text-xs text-gray-500">
                        Factura o boleta con las cuotas pendientes de los contratos del cliente.
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div>
                        <InputLabel value="Comprobante" />
                        <select v-model="form.sale_documenttype_id" class="form-select form-select-sm">
                            <option v-for="type in saleDocumentTypes" :key="type.id" :value="type.id">
                                {{ type.description }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <InputLabel value="Serie" />
                        <select v-model="form.serie" class="form-select form-select-sm">
                            <option :value="null">Seleccionar</option>
                            <option v-for="serie in series" :key="serie.id" :value="serie.id">
                                {{ serie.description }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <InputLabel value="Emisión" />
                        <input v-model="form.date_issue" class="form-input form-input-sm" type="date" />
                    </div>
                    <div>
                        <InputLabel value="Vencimiento" />
                        <input v-model="form.date_end" class="form-input form-input-sm" type="date" />
                    </div>
                </div>
            </div>

            <!-- Cliente -->
            <div class="mt-4 grid sm:grid-cols-6 space-x-6">
                <div class="sm:col-span-4">
                    <InputLabel value="Cliente" />
                    <div class="relative">
                        <input
                            v-model="search.q"
                            class="form-input ltr:pl-10"
                            type="text"
                            placeholder="Buscar por nombre, razón social o número de documento"
                            @input="queueClientSearch"
                        />
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <font-awesome-icon :icon="faMagnifyingGlass" class="h-4 w-4 text-gray-400" />
                        </div>

                        <div
                            v-if="search.results.length"
                            class="absolute z-40 mt-1 max-h-60 w-full overflow-auto rounded-md border border-gray-300 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800"
                        >
                            <button
                                v-for="person in search.results"
                                :key="person.id"
                                type="button"
                                class="block w-full border-b border-gray-100 px-3 py-2 text-left last:border-b-0 hover:bg-gray-100 dark:border-gray-700 dark:hover:bg-gray-700"
                                @click="selectClient(person)"
                            >
                                <p class="text-sm font-semibold dark:text-white">{{ person.full_name }}</p>
                                <p class="text-xs text-gray-500">
                                    {{ person.number }} · {{ person.contracts_count }} contrato(s) vigente(s)
                                </p>
                            </button>
                        </div>
                    </div>

                    <p v-if="search.loading" class="mt-1 text-xs text-gray-500">Buscando...</p>

                    <div
                        v-if="client"
                        class="mt-2 flex flex-wrap items-center gap-3 rounded-md bg-gray-100 p-2 text-xs dark:bg-gray-800"
                    >
                        <span class="font-semibold dark:text-white">{{ client.full_name }}</span>
                        <span class="text-gray-500">{{ client.number }}</span>
                        <span class="text-gray-500">{{ client.email || "Sin correo" }}</span>
                        <button
                            v-if="contracts.length"
                            type="button"
                            class="ml-auto text-xs font-semibold text-blue-600"
                            @click="abrirContratos"
                        >
                            Ver cuotas
                        </button>
                        <button type="button" class="text-xs font-semibold text-rose-600" @click="cambiarCliente">
                            Cambiar cliente
                        </button>
                    </div>
                </div>
                <div class="sm:col-span-2">
                    <InputLabel value="Forma de pago" />
                    <select v-model="form.forma_pago" class="form-select">
                        <option value="Contado">Al contado</option>
                        <option value="Credito">Al crédito</option>
                    </select>
                </div>
            </div>

            <!-- Bandeja de cobros -->
            <div class="mt-5">
                <h3 class="mb-2 text-sm font-bold uppercase dark:text-white">Bandeja de items</h3>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 50px"></th>
                                <th>Contrato</th>
                                <th>Descripción</th>
                                <th class="text-right" style="width: 130px">Monto</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, index) in items" :key="item.quota_id">
                                <td class="text-center">
                                    <button type="button" class="btn btn-danger btn-sm" @click="quitarItem(index)">
                                        <font-awesome-icon :icon="faXmark" />
                                    </button>
                                </td>
                                <td>
                                    <p class="text-xs font-semibold dark:text-white">{{ item.contract_title }}</p>
                                    <p class="text-xs text-gray-500">
                                        Cuota {{ item.payment_number }} · saldo {{ moneda(item.balance_amount) }}
                                    </p>
                                </td>
                                <td>
                                    <input v-model="item.description" class="form-input form-input-sm" type="text" />
                                </td>
                                <td>
                                    <input
                                        v-model="item.amount"
                                        class="form-input form-input-sm text-right"
                                        type="number"
                                        min="0.01"
                                        step="0.01"
                                    />
                                </td>
                            </tr>
                            <tr v-if="!items.length">
                                <td colspan="4">
                                    <p class="p-2 text-center text-xs text-gray-500">
                                        Abre las cuotas del cliente y agrega las que estén pendientes o amortizadas.
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="dark:text-gray-300">
                            <tr>
                                <td colspan="2"></td>
                                <td class="pt-3 text-right text-xs uppercase"><b>Op. gravadas</b></td>
                                <td class="pt-3 text-right">{{ totals.base }}</td>
                            </tr>
                            <tr>
                                <td colspan="2"></td>
                                <td class="text-right text-xs uppercase"><b>IGV</b></td>
                                <td class="text-right">{{ totals.igv }}</td>
                            </tr>
                            <tr>
                                <td colspan="2"></td>
                                <td class="pb-3 text-right text-xs uppercase"><b>Total a cobrar</b></td>
                                <td class="pb-3 text-right font-bold">{{ totals.total }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <p v-if="errorMessage" class="mt-2 text-xs font-semibold text-rose-600">{{ errorMessage }}</p>

            <div class="mt-4 grid gap-4 sm:grid-cols-3">
                <div class="sm:col-span-3">
                    <InputLabel value="Descripción adicional (opcional)" />
                    <textarea
                        v-model="form.additional_description"
                        class="form-textarea"
                        rows="2"
                        maxlength="255"
                    ></textarea>
                </div>



                <div class="grid sm:grid-cols-3 sm:col-span-3 space-x-6">
                    <div v-if="form.forma_pago === 'Contado'" class="space-y-2">
                        <div class="flex items-center justify-between">
                            <InputLabel value="Medio de pago" />
                            <button
                                type="button"
                                class="text-xs font-semibold uppercase text-primary"
                                @click="addPayment"
                            >
                                Agregar (+)
                            </button>
                        </div>

                        <div
                            v-for="(payment, index) in form.payments"
                            :key="index"
                            class="rounded border border-gray-200 p-2 dark:border-gray-700"
                        >
                            <div class="flex items-start gap-2">
                                <select v-model="payment.type" class="form-select">
                                    <option :value="null" disabled>Seleccionar medio de pago</option>
                                    <option v-for="method in payments" :key="method.id" :value="method.id">
                                        {{ method.description }}
                                    </option>
                                </select>
                                <button
                                    v-if="form.payments.length > 1"
                                    type="button"
                                    class="btn btn-sm btn-outline-danger"
                                    @click="removePayment(index)"
                                >
                                    <font-awesome-icon :icon="faXmark" />
                                </button>
                            </div>

                            <div class="mt-2 flex gap-2">
                                <input
                                    v-model="payment.reference"
                                    type="text"
                                    class="form-input text-xs"
                                    maxlength="255"
                                    placeholder="Referencia"
                                />
                                <input
                                    v-model="payment.amount"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="form-input w-32 text-right text-xs"
                                    placeholder="Monto"
                                    @input="montoEditado = true"
                                />
                            </div>

                            <p v-if="medioSinCuenta(payment.type)" class="mt-1 text-xs text-amber-600">
                                Este medio no tiene cuenta de tesorería: el cobro quedará como ignorado en
                                Tesorería y no se sumará al libro.
                            </p>
                        </div>

                        <p
                            class="text-xs"
                            :class="Math.abs(Number(diferenciaPagos)) > 0.01 ? 'text-rose-600' : 'text-gray-500'"
                        >
                            Cobrado: {{ pagosTotal }} de {{ totals.total }}
                        </p>
                    </div>

                    <div v-else>
                        <InputLabel value="Cuotas del comprobante" />
                        <button type="button" class="btn btn-outline-primary w-full" @click="showQuotas = true">
                            Programar cuotas ({{ cuotas.amounts.length }})
                        </button>
                        <p class="mt-1 text-xs" :class="Math.abs(Number(diferenciaCuotas)) > 0.01 ? 'text-rose-600' : 'text-gray-500'">
                            Cuotas: {{ cuotasTotal }} de {{ totals.total }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:justify-end">
                <Link :href="route('comm_contracts')" class="btn btn-danger uppercase text-xs">
                    Cancelar
                </Link>
                <PrimaryButton
                    :class="{ 'opacity-50': saving }"
                    :disabled="saving"
                    @click="save"
                >
                    <svg
                        v-if="saving"
                        class="mr-2 inline h-4 w-4 animate-spin"
                        viewBox="0 0 100 101"
                        fill="none"
                        xmlns="http://www.w3.org/2000/svg"
                    >
                        <path
                            d="M100 50.59C100 78.2 77.61 100.59 50 100.59 22.39 100.59 0 78.2 0 50.59 0 22.98 22.39.59 50 .59c27.6 0 50 22.39 50 50Z"
                            fill="currentColor"
                            opacity=".25"
                        />
                        <path
                            d="M93.97 39.04c2.42-.64 3.89-3.13 3.04-5.49A45.5 45.5 0 0 0 72.94 7.41c-2.36-.83-4.8.35-5.44 2.77-.64 2.43.86 4.85 3.34 5.22 6.9 1.03 13.42 3.76 19.1 8.1 5.7 4.34 10.1 10.1 12.6 16.4.83 2 2.02 3.44 1.43-1Z"
                            fill="currentColor"
                        />
                    </svg>
                    {{ saving ? "Registrando..." : "Registrar documento" }}
                </PrimaryButton>
            </div>
        </div>

        <!-- Contratos: con uno solo se ven todas sus cuotas; con varios, cada
             contrato se despliega para ver y agregar sus cuotas. -->
        <ModalLargeX
            :show="showContracts"
            :onClose="cerrarContratos"
            icon="/img/pago.png"
        >
            <template #title>Cuotas por cobrar</template>
            <template #message>
                <span v-if="client">{{ client.full_name }} · {{ client.number }}</span>
                <span v-if="items.length"> · {{ items.length }} en la bandeja</span>
            </template>

            <template #content>
                <p v-if="loadingContracts" class="text-xs text-gray-500">Cargando contratos...</p>
                <p v-else-if="!contracts.length" class="text-xs text-gray-500">
                    El cliente no tiene contratos vigentes.
                </p>

                <div v-else class="space-y-3">
                    <div
                        v-for="contract in contracts"
                        :key="contract.id"
                        class="overflow-hidden rounded-md border border-gray-200 bg-white dark:border-gray-600 dark:bg-gray-800"
                    >
                        <button
                            v-if="contracts.length > 1"
                            type="button"
                            class="flex w-full items-center justify-between gap-3 px-3 py-2 text-left hover:bg-gray-50 dark:hover:bg-gray-700/60"
                            @click="toggleContract(contract.id)"
                        >
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold dark:text-white">{{ contract.title }}</span>
                                <span class="block text-xs text-gray-500">
                                    {{ contract.service?.description || "Sin servicio" }} · {{ contract.payments.length }} cuota(s)
                                </span>
                            </span>
                            <font-awesome-icon
                                :icon="faChevronDown"
                                class="h-3 w-3 flex-shrink-0 text-gray-500 transition-transform"
                                :class="{ 'rotate-180': expandedContracts.includes(contract.id) }"
                            />
                        </button>
                        <div v-else class="px-3 py-2">
                            <p class="text-sm font-semibold dark:text-white">{{ contract.title }}</p>
                            <p class="text-xs text-gray-500">{{ contract.service?.description || "Sin servicio" }}</p>
                        </div>

                        <div v-show="contracts.length === 1 || expandedContracts.includes(contract.id)">
                            <div class="table-responsive">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Cuota</th>
                                            <th>Vence</th>
                                            <th class="text-right">Monto</th>
                                            <th class="text-right">Saldo</th>
                                            <th class="text-center">Estado</th>
                                            <th class="text-center">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-if="!contract.payments.length">
                                            <td colspan="6" class="text-center text-xs text-gray-500">
                                                Sin cuotas pendientes de cobro en este contrato.
                                            </td>
                                        </tr>
                                        <tr v-for="quota in contract.payments" :key="quota.id">
                                            <td>{{ etiquetaCuota(quota) }}</td>
                                            <td>{{ quota.due_date || "Sin fecha" }}</td>
                                            <td class="text-right">{{ moneda(quota.total_amount) }}</td>
                                            <td class="text-right font-semibold">{{ moneda(quota.balance_amount) }}</td>
                                            <td class="text-center text-xs">{{ estadoCuota[quota.status] || quota.status }}</td>
                                            <td class="text-center">
                                                <button
                                                    type="button"
                                                    class="btn btn-sm"
                                                    :class="yaEnBandeja(quota.id) ? 'btn-outline-secondary' : 'btn-outline-primary'"
                                                    :disabled="yaEnBandeja(quota.id)"
                                                    @click="agregarCuota(contract, quota)"
                                                >
                                                    <font-awesome-icon :icon="faPlus" class="mr-1" />
                                                    {{ yaEnBandeja(quota.id) ? "Agregado" : "Agregar" }}
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <template #buttons>
                <button type="button" class="btn btn-primary" @click="cerrarContratos">
                    Listo ({{ items.length }})
                </button>
            </template>
        </ModalLargeX>

        <!-- Programacion de cuotas de una venta al credito. -->
        <ModalSmall :show="showQuotas" :on-close="() => (showQuotas = false)">
            <template #title>Programar cuotas</template>
            <template #message>Cuotas del comprobante, no del contrato.</template>

            <template #content>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <InputLabel value="Número de cuotas" />
                        <select v-model="cuotas.number" class="form-select">
                            <option v-for="n in 36" :key="n" :value="n">{{ n }}</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel value="Cada X días" />
                        <select v-model="cuotas.days" class="form-select" :disabled="cuotas.end_month">
                            <option v-for="n in 31" :key="n" :value="n">{{ n }}</option>
                        </select>
                        <label class="mt-1 flex items-center gap-2 text-xs text-gray-500">
                            <input v-model="cuotas.end_month" type="checkbox" class="form-checkbox" />
                            Cada fin de mes
                        </label>
                    </div>
                </div>

                <div class="mt-3 max-h-64 space-y-2 overflow-y-auto">
                    <div v-for="(quota, index) in cuotas.amounts" :key="index" class="grid grid-cols-2 gap-2">
                        <div>
                            <InputLabel :value="`Monto cuota ${index + 1}`" />
                            <input v-model="quota.amount" class="form-input form-input-sm" type="number" min="0.01" step="0.01" />
                        </div>
                        <div>
                            <InputLabel :value="`Vence cuota ${index + 1}`" />
                            <input v-model="quota.dueDate" class="form-input form-input-sm" type="date" />
                        </div>
                    </div>
                </div>

                <p class="mt-3 text-xs" :class="Math.abs(Number(diferenciaCuotas)) > 0.01 ? 'text-rose-600' : 'text-gray-500'">
                    Suma de cuotas: {{ cuotasTotal }} · Total del comprobante: {{ totals.total }}
                </p>
            </template>

            <template #buttons>
                <button type="button" class="btn btn-primary" @click="showQuotas = false">Hecho</button>
            </template>
        </ModalSmall>
    </AppLayout>
</template>
