<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import { Head, router, useForm } from '@inertiajs/vue3';
    import ModalLarge from '@/Components/ModalLarge.vue';
    import ModalMedium from '@/Components/ModalMedium.vue';
    import InputLabel from '@/Components/InputLabel.vue';
    import InputError from '@/Components/InputError.vue';
    import PrimaryButton from '@/Components/PrimaryButton.vue';
    import { ref, computed } from 'vue';
    import { faPlus, faCheck, faMoneyBillTransfer, faBan, faRotateRight, faPen, faFileExport } from '@fortawesome/free-solid-svg-icons';

    const props = defineProps({
        orders: { type: Array, default: () => [] },
        suppliers: { type: Array, default: () => [] },
        recurring: { type: Array, default: () => [] },
        categories: { type: Array, default: () => [] },
        accounts: { type: Array, default: () => [] },
        costCenters: { type: Array, default: () => [] },
        paymentMethods: { type: Array, default: () => [] },
        bankAccounts: { type: Array, default: () => [] },
        options: { type: Object, default: () => ({}) },
        bulk: { type: Object, default: () => ({ candidates: [], formats: [] }) },
        can: { type: Object, default: () => ({}) },
    });

    const tab = ref('orders');
    const today = () => new Date().toISOString().slice(0, 10);

    const money = (value, currency = 'PEN') => {
        try {
            return new Intl.NumberFormat('es-PE', { style: 'currency', currency }).format(value || 0);
        } catch (e) {
            return `${currency} ${Number(value || 0).toFixed(2)}`;
        }
    };

    const statusClass = (status) => ({
        draft: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
        pending_approval: 'bg-warning/10 text-warning',
        approved: 'bg-primary/10 text-primary',
        scheduled: 'bg-info/10 text-info',
        paid: 'bg-success/10 text-success',
        rejected: 'bg-danger/10 text-danger',
        voided: 'bg-danger/10 text-danger',
    }[status] || 'bg-gray-100 text-gray-600');

    // Mensaje del backend (success/error) por el puente de alertas.
    const notifyFlash = (page) => {
        const flash = page?.props?.flash || {};
        if (flash.error) {
            window.alertaSistema('No se pudo completar', flash.error, 'error');
        } else if (flash.success) {
            window.alertaSistema('Listo', flash.success, 'success');
        }
    };

    // ---------------- Órdenes ----------------
    // crypto.randomUUID solo existe en contextos seguros (https o localhost).
    // El ERP en red local se sirve por http, así que se usa un respaldo UUID v4.
    const claveIdempotencia = () => {
        if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
            return crypto.randomUUID();
        }

        const bytes = new Uint8Array(16);
        crypto.getRandomValues(bytes);
        bytes[6] = (bytes[6] & 0x0f) | 0x40;
        bytes[8] = (bytes[8] & 0x3f) | 0x80;

        const hex = Array.from(bytes, (b) => b.toString(16).padStart(2, '0')).join('');

        return `${hex.slice(0, 8)}-${hex.slice(8, 12)}-${hex.slice(12, 16)}-${hex.slice(16, 20)}-${hex.slice(20)}`;
    };

    const orderModal = ref(false);
    const emptyOrder = () => ({
        idempotency_key: claveIdempotencia(),
        type: 'purchase',
        supplier_id: null,
        cost_center_id: null,
        description: '',
        treasury_category_id: null,
        currency: 'PEN',
        amount: '',
        issue_date: today(),
        due_date: '',
        scheduled_date: '',
        treasury_account_id: null,
        payment_method_id: null,
        notes: '',
        attachment: null,
    });

    const orderForm = useForm(emptyOrder());

    const openOrder = () => {
        orderForm.defaults(emptyOrder());
        orderForm.reset();
        orderModal.value = true;
    };

    const saveOrder = () => {
        orderForm.post(route('treasury_payments_store'), {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: (page) => {
                notifyFlash(page);
                orderModal.value = false;
                orderForm.reset();
            },
            onError: () => window.alertaSistema('Revisa el formulario', 'Hay campos obligatorios sin completar.', 'warning'),
        });
    };

    const confirmAndAct = async (title, text, url, payload = {}) => {
        const result = await window.alertaSistema(title, text, 'warning', {
            duration: 0,
            showCancelButton: true,
            confirmButtonText: 'Sí, continuar',
            cancelButtonText: 'Cancelar',
        });

        if (!result?.isConfirmed) return;

        router.post(url, payload, {
            preserveScroll: true,
            onSuccess: notifyFlash,
            onError: () => window.alertaSistema('No se pudo completar', 'Revisa los datos e inténtalo otra vez.', 'error'),
        });
    };

    const approve = (order) => confirmAndAct(
        'Aprobar orden',
        `Se aprobará ${order.code} por ${money(order.amount, order.currency)}.`,
        route('treasury_payments_approve', order.id)
    );

    const pay = (order) => confirmAndAct(
        'Pagar orden',
        `Se registrará el egreso de ${money(order.amount, order.currency)} en el libro de tesorería.`,
        route('treasury_payments_pay', order.id),
        { date: today() }
    );

    const voidOrder = (order) => confirmAndAct(
        'Anular orden',
        `La orden ${order.code} no se borrará: quedará anulada y, si estaba pagada, se revertirá el egreso.`,
        route('treasury_payments_void', order.id),
        { reason: 'Anulación manual desde la pantalla de pagos' }
    );

    // ---------------- Pago masivo ----------------
    const bulkSelected = ref([]);
    const bulkFormat = ref('csv');
    const bulkDate = ref(today());
    const bulkProcessing = ref(false);
    const bulkResult = ref(null);

    const bulkOrders = computed(() => props.bulk?.candidates || []);
    const bulkSelectedOrders = computed(() => bulkOrders.value.filter((order) => bulkSelected.value.includes(order.id)));
    const bulkTotal = computed(() =>
        Math.round(bulkSelectedOrders.value.reduce((total, order) => total + Number(order.amount || 0), 0) * 100) / 100
    );
    const bulkCurrency = computed(() => bulkSelectedOrders.value[0]?.currency || 'PEN');

    const toggleBulk = (id) => {
        bulkSelected.value = bulkSelected.value.includes(id)
            ? bulkSelected.value.filter((item) => item !== id)
            : [...bulkSelected.value, id];
    };

    const allBulkSelected = computed(
        () => bulkOrders.value.length > 0 && bulkSelected.value.length === bulkOrders.value.length
    );

    const toggleAllBulk = () => {
        bulkSelected.value = allBulkSelected.value ? [] : bulkOrders.value.map((order) => order.id);
    };

    // Descarga el archivo del lote y muestra el resumen que devuelve el servidor.
    const generateBulk = async () => {
        if (bulkSelected.value.length === 0) {
            window.alertaSistema('Sin órdenes', 'Selecciona al menos una orden aprobada para el lote.', 'warning');
            return;
        }

        bulkProcessing.value = true;
        bulkResult.value = null;

        try {
            const response = await window.axios.post(
                route('treasury_payments_bulk_file'),
                {
                    orders: bulkSelected.value,
                    format: bulkFormat.value,
                    date: bulkDate.value,
                },
                { responseType: 'blob' }
            );

            const extension = ['csv', 'bcp'].includes(bulkFormat.value) ? 'csv' : 'txt';
            const blob = new Blob([response.data], {
                type: response.headers['content-type'] || 'application/octet-stream',
            });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');

            link.href = url;
            link.download = `pago-masivo-${bulkFormat.value}-${bulkDate.value}.${extension}`;
            document.body.appendChild(link);
            link.click();
            link.remove();
            URL.revokeObjectURL(url);

            const batch = response.headers['x-treasury-batch'] || '';
            const count = Number(response.headers['x-treasury-count'] || bulkSelected.value.length);
            const total = Number(response.headers['x-treasury-total'] || bulkTotal.value);
            const warnings = decodeURIComponent(response.headers['x-treasury-warnings'] || '');

            bulkResult.value = { batch, count, total, warnings };

            window.alertaSistema(
                'Archivo del lote generado',
                `Lote ${batch} con ${count} orden(es) por ${money(total, bulkCurrency.value)}.` +
                    (warnings ? ` Avisos: ${warnings}` : ''),
                warnings ? 'warning' : 'success'
            );

            bulkSelected.value = [];
            router.reload({ only: ['bulk', 'orders'] });
        } catch (error) {
            let message = 'No se pudo generar el archivo del banco.';
            const data = error?.response?.data;

            if (data instanceof Blob) {
                try {
                    const parsed = JSON.parse(await data.text());
                    message = parsed.message || message;
                } catch (e) {
                    // El cuerpo no era JSON: se mantiene el mensaje genérico.
                }
            } else if (data?.message) {
                message = data.message;
            }

            window.alertaSistema('No se pudo generar el archivo', message, 'error');
        } finally {
            bulkProcessing.value = false;
        }
    };

    // ---------------- Proveedores ----------------
    const supplierModal = ref(false);
    const supplierEditing = ref(null);
    const supplierForm = useForm({
        doc_type: '6',
        doc_number: '',
        name: '',
        trade_name: '',
        email: '',
        phone: '',
        payment_terms_days: 0,
        bank_account_id: null,
        notes: '',
        status: true,
    });

    const openSupplier = (supplier = null) => {
        supplierEditing.value = supplier?.id || null;
        supplierForm.clearErrors();

        if (supplier) {
            supplierForm.doc_type = supplier.doc_type || '6';
            supplierForm.doc_number = supplier.doc_number || '';
            supplierForm.name = supplier.name;
            supplierForm.trade_name = supplier.trade_name || '';
            supplierForm.email = supplier.email || '';
            supplierForm.phone = supplier.phone || '';
            supplierForm.payment_terms_days = supplier.payment_terms_days || 0;
            supplierForm.bank_account_id = supplier.bank_account_id || null;
            supplierForm.status = !!supplier.status;
        } else {
            supplierForm.reset();
            supplierForm.doc_type = '6';
            supplierForm.status = true;
        }

        supplierModal.value = true;
    };

    const saveSupplier = () => {
        const url = supplierEditing.value
            ? route('treasury_suppliers_update', supplierEditing.value)
            : route('treasury_suppliers_store');

        supplierForm.post(url, {
            preserveScroll: true,
            onSuccess: (page) => {
                notifyFlash(page);
                supplierModal.value = false;
            },
            onError: () => window.alertaSistema('Revisa el formulario', 'Hay datos del proveedor que no son válidos.', 'warning'),
        });
    };

    const removeSupplier = (supplier) => confirmAndAct(
        'Desactivar proveedor',
        `Si ${supplier.name} tiene órdenes o gastos, se desactivará en lugar de eliminarse.`,
        route('treasury_suppliers_destroy', supplier.id)
    );

    // ---------------- Recurrentes ----------------
    const recurringModal = ref(false);
    const recurringEditing = ref(null);
    const recurringForm = useForm({
        concept: '',
        type: 'service',
        supplier_id: null,
        treasury_category_id: null,
        currency: 'PEN',
        amount: '',
        treasury_account_id: null,
        payment_method_id: null,
        frequency: 'monthly',
        day_of_month: 1,
        start_date: today(),
        end_date: '',
        auto_pay: false,
        status: true,
        notes: '',
    });

    const openRecurring = (expense = null) => {
        recurringEditing.value = expense?.id || null;
        recurringForm.clearErrors();

        if (expense) {
            recurringForm.concept = expense.concept;
            recurringForm.type = expense.type;
            recurringForm.amount = expense.amount;
            recurringForm.currency = expense.currency;
            recurringForm.frequency = expense.frequency;
            recurringForm.auto_pay = !!expense.auto_pay;
            recurringForm.status = !!expense.status;
        } else {
            recurringForm.reset();
            recurringForm.frequency = 'monthly';
            recurringForm.currency = 'PEN';
            recurringForm.day_of_month = 1;
            recurringForm.start_date = today();
            recurringForm.status = true;
        }

        recurringModal.value = true;
    };

    const saveRecurring = () => {
        const url = recurringEditing.value
            ? route('treasury_recurring_update', recurringEditing.value)
            : route('treasury_recurring_store');

        recurringForm.post(url, {
            preserveScroll: true,
            onSuccess: (page) => {
                notifyFlash(page);
                recurringModal.value = false;
            },
            onError: () => window.alertaSistema('Revisa el formulario', 'Hay datos del gasto recurrente que no son válidos.', 'warning'),
        });
    };

    const runRecurring = (expense) => confirmAndAct(
        'Ejecutar ahora',
        `Se generará la orden de «${expense.concept}» y, si tiene pago automático, se pagará.`,
        route('treasury_recurring_run', expense.id)
    );

    const removeRecurring = (expense) => confirmAndAct(
        'Desactivar gasto recurrente',
        `Si «${expense.concept}» ya tiene ejecuciones, se desactivará en lugar de eliminarse.`,
        route('treasury_recurring_destroy', expense.id)
    );

    const pendingApproval = computed(() => props.orders.filter((o) => o.status === 'pending_approval').length);
    const openOrders = computed(() => props.orders.filter((o) => ['approved', 'scheduled'].includes(o.status)).length);
</script>

<template>
    <AppLayout title="Pagos y compras">
        <Head title="Tesorería · Pagos y compras">
            <meta name="aracodito-view" content="new" />
        </Head>
        <Navigation>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Tesorería</span>
            </li>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Pagos</span>
            </li>
        </Navigation>

        <div class="mt-5 space-y-5">
            <!-- Resumen -->
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                <div class="panel p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Pendientes de aprobación</p>
                    <p class="mt-2 text-2xl font-bold text-warning">{{ pendingApproval }}</p>
                    <p class="mt-1 text-xs text-gray-500">Umbral: {{ money(options.approval_threshold) }}</p>
                </div>
                <div class="panel p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Aprobadas por pagar</p>
                    <p class="mt-2 text-2xl font-bold text-primary">{{ openOrders }}</p>
                </div>
                <div class="panel p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Gastos recurrentes activos</p>
                    <p class="mt-2 text-2xl font-bold text-success">{{ recurring.filter((r) => r.status).length }}</p>
                </div>
            </div>

            <!-- Pestañas -->
            <div class="panel p-2">
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="item in [
                            { key: 'orders', label: 'Órdenes de pago' },
                            { key: 'bulk', label: 'Pago masivo' },
                            { key: 'suppliers', label: 'Proveedores' },
                            { key: 'recurring', label: 'Gastos recurrentes' },
                        ]"
                        :key="item.key"
                        type="button"
                        class="rounded-lg px-4 py-2 text-sm font-medium transition"
                        :class="tab === item.key
                            ? 'bg-primary text-white'
                            : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'"
                        @click="tab = item.key"
                    >
                        {{ item.label }}
                    </button>
                </div>
            </div>

            <!-- Órdenes -->
            <div v-if="tab === 'orders'" class="space-y-5">
                <div class="panel flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Órdenes de pago</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Compras, pagos a proveedores, gastos imprevistos y recurrentes. El egreso entra al libro recién al pagar.
                        </p>
                    </div>
                    <button v-if="can.create" type="button" class="btn btn-primary" @click="openOrder">
                        <font-awesome-icon :icon="faPlus" class="mr-1" /> Nueva orden
                    </button>
                </div>

                <div class="panel overflow-hidden p-0">
                    <div class="table-responsive">
                        <table class="min-w-full">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-800/60">
                                    <th>Código</th>
                                    <th>Tipo</th>
                                    <th class="min-w-[240px]">Descripción</th>
                                    <th>Vence</th>
                                    <th class="text-right">Monto</th>
                                    <th>Estado</th>
                                    <th class="text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="orders.length === 0">
                                    <td colspan="7" class="py-8 text-center text-sm text-gray-500">
                                        No hay órdenes. Crea la primera con «Nueva orden».
                                    </td>
                                </tr>
                                <tr v-for="order in orders" :key="order.id" class="hover:bg-gray-50/70 dark:hover:bg-gray-800/30">
                                    <td class="font-mono text-xs">{{ order.code }}</td>
                                    <td class="text-sm">{{ order.type_label }}</td>
                                    <td>
                                        <p class="font-medium text-gray-800 dark:text-gray-100">{{ order.description }}</p>
                                        <p class="text-xs text-gray-500">
                                            {{ order.supplier || 'Sin proveedor' }}
                                            <template v-if="order.cost_center"> · {{ order.cost_center }}</template>
                                        </p>
                                    </td>
                                    <td class="text-sm">{{ order.due_date || order.scheduled_date || order.issue_date }}</td>
                                    <td class="text-right font-semibold">{{ money(order.amount, order.currency) }}</td>
                                    <td>
                                        <span class="rounded-md px-2 py-1 text-xs font-medium" :class="statusClass(order.status)">
                                            {{ order.status_label }}
                                        </span>
                                    </td>
                                    <td class="text-right">
                                        <div class="flex justify-end gap-2">
                                            <button
                                                v-if="can.approve && order.can_approve"
                                                type="button"
                                                class="btn btn-sm btn-outline-primary"
                                                title="Aprobar"
                                                @click="approve(order)"
                                            >
                                                <font-awesome-icon :icon="faCheck" />
                                            </button>
                                            <button
                                                v-if="can.pay && order.can_pay"
                                                type="button"
                                                class="btn btn-sm btn-outline-success"
                                                title="Pagar"
                                                @click="pay(order)"
                                            >
                                                <font-awesome-icon :icon="faMoneyBillTransfer" />
                                            </button>
                                            <button
                                                v-if="can.void && order.can_void"
                                                type="button"
                                                class="btn btn-sm btn-outline-danger"
                                                title="Anular"
                                                @click="voidOrder(order)"
                                            >
                                                <font-awesome-icon :icon="faBan" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Proveedores -->
            <!-- Pago masivo: archivo para el banco -->
            <div v-if="tab === 'bulk'" class="space-y-5">
                <div class="panel p-5">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Pago masivo con archivo bancario</h2>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Arma el archivo con las órdenes aprobadas y súbelo al banco. Esto no ejecuta el pago ni mueve
                                el libro: cada orden conserva el lote y la fecha de exportación.
                            </p>
                            <p class="mt-2 text-xs text-warning">
                                Los layouts son referenciales (CSV, BCP, BBVA e Interbank). Valida el formato con tu banco antes de subirlo.
                            </p>
                        </div>
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium">Formato</label>
                                <select v-model="bulkFormat" class="form-select sm:w-44">
                                    <option v-for="item in bulk.formats" :key="item.code" :value="item.code">{{ item.label }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium">Fecha de proceso</label>
                                <input v-model="bulkDate" type="date" class="form-input sm:w-40" />
                            </div>
                            <button
                                v-if="can.bulk"
                                type="button"
                                class="btn btn-primary disabled:cursor-not-allowed disabled:opacity-50"
                                :disabled="bulkProcessing || bulkSelected.length === 0"
                                @click="generateBulk"
                            >
                                <font-awesome-icon :icon="faFileExport" class="mr-1" />
                                {{ bulkProcessing ? 'Generando…' : 'Generar archivo' }}
                            </button>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap items-center gap-4 rounded-lg bg-gray-50 p-3 text-sm dark:bg-gray-800/60">
                        <span>Seleccionadas: <strong>{{ bulkSelected.length }}</strong></span>
                        <span>Total: <strong>{{ money(bulkTotal, bulkCurrency) }}</strong></span>
                        <span v-if="bulkResult?.batch" class="text-xs text-gray-500">
                            Último lote: <span class="font-mono">{{ bulkResult.batch }}</span>
                        </span>
                    </div>

                    <div v-if="bulkResult?.warnings" class="mt-3 rounded-lg border border-warning/40 bg-warning/10 p-3 text-sm text-warning">
                        {{ bulkResult.warnings }}
                    </div>
                </div>

                <div class="panel overflow-hidden p-0">
                    <div class="table-responsive">
                        <table class="min-w-full">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-800/60">
                                    <th class="w-12">
                                        <input
                                            type="checkbox"
                                            class="form-checkbox"
                                            :checked="allBulkSelected"
                                            title="Seleccionar todas"
                                            @change="toggleAllBulk"
                                        />
                                    </th>
                                    <th>Código</th>
                                    <th class="min-w-[200px]">Proveedor</th>
                                    <th>Cuenta del proveedor</th>
                                    <th>Vence</th>
                                    <th class="text-right">Monto</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="bulkOrders.length === 0">
                                    <td colspan="6" class="py-8 text-center text-sm text-gray-500">
                                        No hay órdenes aprobadas o programadas para incluir en un lote.
                                    </td>
                                </tr>
                                <tr v-for="order in bulkOrders" :key="order.id">
                                    <td>
                                        <input
                                            type="checkbox"
                                            class="form-checkbox"
                                            :checked="bulkSelected.includes(order.id)"
                                            @change="toggleBulk(order.id)"
                                        />
                                    </td>
                                    <td class="font-mono text-xs">{{ order.code }}</td>
                                    <td>
                                        <p class="text-sm">{{ order.supplier }}</p>
                                        <p class="text-xs text-gray-500">{{ order.document || 'Sin documento' }}</p>
                                    </td>
                                    <td class="font-mono text-xs">{{ order.bank_account || 'Sin cuenta' }}</td>
                                    <td class="text-sm">{{ order.due_date || order.scheduled_date || '—' }}</td>
                                    <td class="text-right font-semibold">{{ money(order.amount, order.currency) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div v-if="tab === 'suppliers'" class="space-y-5">
                <div class="panel flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Proveedores</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">RUC o DNI único por proveedor. Con órdenes no se borran: se desactivan.</p>
                    </div>
                    <button v-if="can.suppliers" type="button" class="btn btn-primary" @click="openSupplier()">
                        <font-awesome-icon :icon="faPlus" class="mr-1" /> Nuevo proveedor
                    </button>
                </div>

                <div class="panel overflow-hidden p-0">
                    <div class="table-responsive">
                        <table class="min-w-full">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-800/60">
                                    <th>Documento</th>
                                    <th>Proveedor</th>
                                    <th>Contacto</th>
                                    <th class="text-center">Días de crédito</th>
                                    <th class="text-center">Órdenes</th>
                                    <th class="text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="suppliers.length === 0">
                                    <td colspan="6" class="py-8 text-center text-sm text-gray-500">Sin proveedores registrados.</td>
                                </tr>
                                <tr v-for="supplier in suppliers" :key="supplier.id">
                                    <td class="font-mono text-xs">{{ supplier.doc_type }}-{{ supplier.doc_number || '—' }}</td>
                                    <td>
                                        <p class="font-medium text-gray-800 dark:text-gray-100">{{ supplier.name }}</p>
                                        <p v-if="!supplier.status" class="text-xs text-danger">Inactivo</p>
                                    </td>
                                    <td class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ supplier.email || '—' }}<br />
                                        <span class="text-xs">{{ supplier.phone || '' }}</span>
                                    </td>
                                    <td class="text-center text-sm">{{ supplier.payment_terms_days }}</td>
                                    <td class="text-center text-sm">{{ supplier.orders_count }}</td>
                                    <td class="text-right">
                                        <div class="flex justify-end gap-2">
                                            <button v-if="can.suppliers" type="button" class="btn btn-sm btn-outline-primary" @click="openSupplier(supplier)">
                                                <font-awesome-icon :icon="faPen" />
                                            </button>
                                            <button v-if="can.suppliers" type="button" class="btn btn-sm btn-outline-danger" @click="removeSupplier(supplier)">
                                                <font-awesome-icon :icon="faBan" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Recurrentes -->
            <div v-if="tab === 'recurring'" class="space-y-5">
                <div class="panel flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Gastos recurrentes</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Planillas, servicios, insumos y alquileres. Se ejecutan por la cola (queue:work); el pago automático es opcional.
                        </p>
                    </div>
                    <button v-if="can.recurring" type="button" class="btn btn-primary" @click="openRecurring()">
                        <font-awesome-icon :icon="faPlus" class="mr-1" /> Nuevo recurrente
                    </button>
                </div>

                <div class="panel overflow-hidden p-0">
                    <div class="table-responsive">
                        <table class="min-w-full">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-800/60">
                                    <th>Concepto</th>
                                    <th>Frecuencia</th>
                                    <th>Próxima</th>
                                    <th class="text-right">Monto</th>
                                    <th class="text-center">Pago automático</th>
                                    <th>Última ejecución</th>
                                    <th class="text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="recurring.length === 0">
                                    <td colspan="7" class="py-8 text-center text-sm text-gray-500">Sin gastos recurrentes.</td>
                                </tr>
                                <tr v-for="expense in recurring" :key="expense.id">
                                    <td>
                                        <p class="font-medium text-gray-800 dark:text-gray-100">{{ expense.concept }}</p>
                                        <p class="text-xs text-gray-500">{{ expense.type_label }} · {{ expense.category || 'Sin categoría' }}</p>
                                        <p v-if="!expense.status" class="text-xs text-danger">Inactivo</p>
                                    </td>
                                    <td class="text-sm">{{ expense.frequency_label }}</td>
                                    <td class="text-sm">{{ expense.next_run_date }}</td>
                                    <td class="text-right font-semibold">{{ money(expense.amount, expense.currency) }}</td>
                                    <td class="text-center">
                                        <span class="rounded-md px-2 py-1 text-xs" :class="expense.auto_pay ? 'bg-success/10 text-success' : 'bg-gray-100 text-gray-600'">
                                            {{ expense.auto_pay ? 'Sí' : 'No' }}
                                        </span>
                                    </td>
                                    <td class="text-xs">
                                        <span v-if="expense.last_run_at">{{ expense.last_run_at }}</span>
                                        <span v-else class="text-gray-400">Aún no</span>
                                        <p v-if="expense.last_error" class="text-danger">{{ expense.last_error }}</p>
                                    </td>
                                    <td class="text-right">
                                        <div class="flex justify-end gap-2">
                                            <button v-if="can.recurring" type="button" class="btn btn-sm btn-outline-primary" title="Ejecutar ahora" @click="runRecurring(expense)">
                                                <font-awesome-icon :icon="faRotateRight" />
                                            </button>
                                            <button v-if="can.recurring" type="button" class="btn btn-sm btn-outline-primary" title="Editar" @click="openRecurring(expense)">
                                                <font-awesome-icon :icon="faPen" />
                                            </button>
                                            <button v-if="can.recurring" type="button" class="btn btn-sm btn-outline-danger" title="Desactivar" @click="removeRecurring(expense)">
                                                <font-awesome-icon :icon="faBan" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal orden -->
        <ModalLarge :show="orderModal" :on-close="() => (orderModal = false)" :icon="'/img/cuenta-bancaria.png'">
            <template #title>Nueva orden de pago</template>
            <template #message>
                <span>El dinero sale del libro recién al pagar. Sobre {{ money(options.approval_threshold) }} requiere aprobación.</span>
            </template>
            <template #content>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <InputLabel value="Tipo *" class="mb-1.5" />
                        <select v-model="orderForm.type" class="form-select w-full">
                            <option v-for="item in options.types" :key="item.code" :value="item.code">{{ item.label }}</option>
                        </select>
                        <InputError :message="orderForm.errors.type" />
                    </div>
                    <div>
                        <InputLabel value="Proveedor" class="mb-1.5" />
                        <select v-model="orderForm.supplier_id" class="form-select w-full">
                            <option :value="null">Sin proveedor</option>
                            <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">{{ supplier.name }}</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <InputLabel value="Descripción *" class="mb-1.5" />
                        <input v-model="orderForm.description" type="text" class="form-input w-full" placeholder="Ej: Compra de insumos de limpieza" />
                        <InputError :message="orderForm.errors.description" />
                    </div>
                    <div>
                        <InputLabel value="Categoría" class="mb-1.5" />
                        <select v-model="orderForm.treasury_category_id" class="form-select w-full">
                            <option :value="null">Sin categoría</option>
                            <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel value="Centro de costo" class="mb-1.5" />
                        <select v-model="orderForm.cost_center_id" class="form-select w-full">
                            <option :value="null">Sin centro de costo</option>
                            <option v-for="center in costCenters" :key="center.id" :value="center.id">{{ center.code }} · {{ center.name }}</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel value="Moneda" class="mb-1.5" />
                        <select v-model="orderForm.currency" class="form-select w-full">
                            <option value="PEN">Soles (PEN)</option>
                            <option value="USD">Dólares (USD)</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel value="Monto *" class="mb-1.5" />
                        <input v-model="orderForm.amount" type="number" min="0.01" step="0.01" class="form-input w-full" />
                        <InputError :message="orderForm.errors.amount" />
                    </div>
                    <div>
                        <InputLabel value="Fecha de emisión *" class="mb-1.5" />
                        <input v-model="orderForm.issue_date" type="date" class="form-input w-full" />
                        <InputError :message="orderForm.errors.issue_date" />
                    </div>
                    <div>
                        <InputLabel value="Vencimiento" class="mb-1.5" />
                        <input v-model="orderForm.due_date" type="date" class="form-input w-full" />
                    </div>
                    <div>
                        <InputLabel value="Fecha de pago prevista" class="mb-1.5" />
                        <input v-model="orderForm.scheduled_date" type="date" class="form-input w-full" />
                    </div>
                    <div>
                        <InputLabel value="Cuenta de pago" class="mb-1.5" />
                        <select v-model="orderForm.treasury_account_id" class="form-select w-full">
                            <option :value="null">Elegir al pagar</option>
                            <option v-for="account in accounts" :key="account.id" :value="account.id">{{ account.label }} ({{ account.currency }})</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel value="Medio de pago" class="mb-1.5" />
                        <select v-model="orderForm.payment_method_id" class="form-select w-full">
                            <option :value="null">Sin medio</option>
                            <option v-for="method in paymentMethods" :key="method.id" :value="method.id">{{ method.description }}</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <InputLabel value="Notas" class="mb-1.5" />
                        <textarea v-model="orderForm.notes" rows="2" class="form-textarea w-full"></textarea>
                    </div>
                    <div class="md:col-span-2">
                        <InputLabel value="Comprobante (opcional)" class="mb-1.5" />
                        <input type="file" class="form-input w-full" @input="orderForm.attachment = $event.target.files[0]" />
                    </div>
                </div>
            </template>
            <template #buttons>
                <PrimaryButton :disabled="orderForm.processing" @click="saveOrder">Guardar orden</PrimaryButton>
            </template>
        </ModalLarge>

        <!-- Modal proveedor -->
        <ModalMedium :show="supplierModal" :on-close="() => (supplierModal = false)" :icon="'/img/cuenta-bancaria.png'">
            <template #title>{{ supplierEditing ? 'Editar proveedor' : 'Nuevo proveedor' }}</template>
            <template #message><span>Datos fiscales y condiciones de pago</span></template>
            <template #content>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <InputLabel value="Tipo de documento" class="mb-1.5" />
                        <select v-model="supplierForm.doc_type" class="form-select w-full">
                            <option value="6">RUC</option>
                            <option value="1">DNI</option>
                            <option value="0">Sin documento</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel value="Número" class="mb-1.5" />
                        <input v-model="supplierForm.doc_number" type="text" class="form-input w-full" />
                        <InputError :message="supplierForm.errors.doc_number" />
                    </div>
                    <div class="md:col-span-2">
                        <InputLabel value="Razón social *" class="mb-1.5" />
                        <input v-model="supplierForm.name" type="text" class="form-input w-full" />
                        <InputError :message="supplierForm.errors.name" />
                    </div>
                    <div>
                        <InputLabel value="Nombre comercial" class="mb-1.5" />
                        <input v-model="supplierForm.trade_name" type="text" class="form-input w-full" />
                    </div>
                    <div>
                        <InputLabel value="Correo" class="mb-1.5" />
                        <input v-model="supplierForm.email" type="email" class="form-input w-full" />
                    </div>
                    <div>
                        <InputLabel value="Teléfono" class="mb-1.5" />
                        <input v-model="supplierForm.phone" type="text" class="form-input w-full" />
                    </div>
                    <div>
                        <InputLabel value="Días de crédito" class="mb-1.5" />
                        <input v-model="supplierForm.payment_terms_days" type="number" min="0" max="365" class="form-input w-full" />
                    </div>
                    <div class="md:col-span-2">
                        <InputLabel value="Cuenta bancaria" class="mb-1.5" />
                        <select v-model="supplierForm.bank_account_id" class="form-select w-full">
                            <option :value="null">Sin cuenta</option>
                            <option v-for="account in bankAccounts" :key="account.id" :value="account.id">{{ account.label }}</option>
                        </select>
                    </div>
                </div>
            </template>
            <template #buttons>
                <PrimaryButton :disabled="supplierForm.processing" @click="saveSupplier">Guardar</PrimaryButton>
            </template>
        </ModalMedium>

        <!-- Modal recurrente -->
        <ModalLarge :show="recurringModal" :on-close="() => (recurringModal = false)" :icon="'/img/cuenta-bancaria.png'">
            <template #title>{{ recurringEditing ? 'Editar gasto recurrente' : 'Nuevo gasto recurrente' }}</template>
            <template #message><span>Se ejecuta desde la cola; el pago automático es opcional.</span></template>
            <template #content>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <InputLabel value="Concepto *" class="mb-1.5" />
                        <input v-model="recurringForm.concept" type="text" class="form-input w-full" placeholder="Ej: Alquiler de oficina" />
                        <InputError :message="recurringForm.errors.concept" />
                    </div>
                    <div>
                        <InputLabel value="Tipo *" class="mb-1.5" />
                        <select v-model="recurringForm.type" class="form-select w-full">
                            <option v-for="item in options.recurring_types" :key="item.code" :value="item.code">{{ item.label }}</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel value="Frecuencia *" class="mb-1.5" />
                        <select v-model="recurringForm.frequency" class="form-select w-full">
                            <option v-for="item in options.frequencies" :key="item.code" :value="item.code">{{ item.label }}</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel value="Monto *" class="mb-1.5" />
                        <input v-model="recurringForm.amount" type="number" min="0.01" step="0.01" class="form-input w-full" />
                        <InputError :message="recurringForm.errors.amount" />
                    </div>
                    <div>
                        <InputLabel value="Moneda" class="mb-1.5" />
                        <select v-model="recurringForm.currency" class="form-select w-full">
                            <option value="PEN">Soles (PEN)</option>
                            <option value="USD">Dólares (USD)</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel value="Día del mes" class="mb-1.5" />
                        <input v-model="recurringForm.day_of_month" type="number" min="1" max="31" class="form-input w-full" />
                        <p class="mt-1 text-xs text-gray-500">Si el mes es corto se usa el último día.</p>
                    </div>
                    <div>
                        <InputLabel value="Inicio *" class="mb-1.5" />
                        <input v-model="recurringForm.start_date" type="date" class="form-input w-full" />
                        <InputError :message="recurringForm.errors.start_date" />
                    </div>
                    <div>
                        <InputLabel value="Fin (opcional)" class="mb-1.5" />
                        <input v-model="recurringForm.end_date" type="date" class="form-input w-full" />
                    </div>
                    <div>
                        <InputLabel value="Cuenta de pago" class="mb-1.5" />
                        <select v-model="recurringForm.treasury_account_id" class="form-select w-full">
                            <option :value="null">Elegir al pagar</option>
                            <option v-for="account in accounts" :key="account.id" :value="account.id">{{ account.label }} ({{ account.currency }})</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel value="Categoría" class="mb-1.5" />
                        <select v-model="recurringForm.treasury_category_id" class="form-select w-full">
                            <option :value="null">Sin categoría</option>
                            <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="flex items-center gap-2">
                            <input v-model="recurringForm.auto_pay" type="checkbox" class="form-checkbox" />
                            <span>Pagar automáticamente (sin aprobación) cada vez que venza</span>
                        </label>
                    </div>
                </div>
            </template>
            <template #buttons>
                <PrimaryButton :disabled="recurringForm.processing" @click="saveRecurring">Guardar</PrimaryButton>
            </template>
        </ModalLarge>
    </AppLayout>
</template>
