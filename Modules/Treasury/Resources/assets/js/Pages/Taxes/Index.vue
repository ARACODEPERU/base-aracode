<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import { Head, router, useForm } from '@inertiajs/vue3';
    import ModalMedium from '@/Components/ModalMedium.vue';
    import InputLabel from '@/Components/InputLabel.vue';
    import InputError from '@/Components/InputError.vue';
    import PrimaryButton from '@/Components/PrimaryButton.vue';
    import { computed, ref } from 'vue';
    import { faReceipt, faTriangleExclamation } from '@fortawesome/free-solid-svg-icons';

    const props = defineProps({
        summary: { type: Object, required: true },
        payments: { type: Array, default: () => [] },
        accounts: { type: Array, default: () => [] },
        currencies: { type: Array, default: () => ['PEN'] },
        filters: { type: Object, default: () => ({}) },
        dueDate: { type: String, default: '' },
        igvRate: { type: Number, default: 18 },
        canPay: { type: Boolean, default: false },
        selectedAccountId: { type: Number, default: null },
    });

    const money = (value, currency = 'PEN') => {
        try {
            return new Intl.NumberFormat('es-PE', { style: 'currency', currency }).format(value || 0);
        } catch (e) {
            return `${currency} ${Number(value || 0).toFixed(2)}`;
        }
    };

    // Las fechas se muestran como en el resto del ERP: dd/mm/aaaa.
    const fecha = (value) => {
        if (!value) return '—';

        const [year, month, day] = String(value).split('-');

        return year && month && day ? `${day}/${month}/${year}` : value;
    };

    const mesLargo = (value) => {
        if (!value) return '';

        const [year, month] = String(value).split('-').map(Number);
        const texto = new Date(year, (month || 1) - 1, 1).toLocaleDateString('es-PE', {
            month: 'long',
            year: 'numeric',
        });

        return texto.charAt(0).toUpperCase() + texto.slice(1);
    };

    const query = ref({
        month: props.filters.month || '',
        currency: props.filters.currency || 'PEN',
    });

    const applyFilters = () => {
        router.get(route('treasury_taxes_index'), query.value, { preserveScroll: true, preserveState: true });
    };

    const cuentasDisponibles = computed(() =>
        props.accounts.filter((account) => account.currency === props.summary.currency)
    );

    const yaPagado = computed(() => Number(props.summary.paid || 0) > 0);

    const saldoPorPagar = computed(() =>
        Math.max(0, Math.round((Number(props.summary.net_igv || 0) - Number(props.summary.paid || 0)) * 100) / 100)
    );

    const notifyFlash = (page) => {
        const flash = page?.props?.flash || {};
        if (flash.error) window.alertaSistema('No se pudo completar', flash.error, 'error');
        else if (flash.success) window.alertaSistema('Pago registrado', flash.success, 'success');
    };

    const modal = ref(false);
    const form = useForm({
        treasury_account_id: null,
        period: props.summary.period || props.filters.month,
        date: new Date().toISOString().slice(0, 10),
        amount: 0,
        reference: '',
    });

    const abrirPago = () => {
        form.clearErrors();
        form.period = props.summary.period || props.filters.month;
        form.date = new Date().toISOString().slice(0, 10);
        form.amount = saldoPorPagar.value || Number(props.summary.net_igv || 0);
        form.reference = '';

        // Si venimos de la cuenta de detracción, se preselecciona esa cuenta.
        const sugerida = cuentasDisponibles.value.find((account) => account.id === props.selectedAccountId);
        form.treasury_account_id = sugerida?.id ?? cuentasDisponibles.value[0]?.id ?? null;
        modal.value = true;
    };

    const guardarPago = () => {
        form.post(route('treasury_taxes_pay'), {
            preserveScroll: true,
            onSuccess: (page) => {
                notifyFlash(page);
                modal.value = false;
            },
            onError: () => window.alertaSistema('Revisa el formulario', 'Hay datos que no son válidos.', 'warning'),
        });
    };
</script>

<template>
    <AppLayout title="IGV de ventas">
        <Head title="Tesorería · Impuestos">
            <meta name="aracodito-view" content="new" />
        </Head>
        <Navigation>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2"><span>Tesorería</span></li>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2"><span>Impuestos</span></li>
        </Navigation>

        <div class="mt-5 space-y-5">
            <!-- Filtros del periodo -->
            <div class="panel flex flex-col gap-4 p-5 lg:flex-row lg:items-end lg:justify-between">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:flex-wrap">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Mes de emisión</label>
                        <input v-model="query.month" type="month" class="form-input sm:w-52" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Moneda</label>
                        <select v-model="query.currency" class="form-select sm:w-32">
                            <option v-for="code in currencies" :key="code" :value="code">{{ code }}</option>
                        </select>
                    </div>
                    <button type="button" class="btn btn-primary" @click="applyFilters">Consultar</button>
                </div>

                <p class="max-w-md text-xs text-gray-500 dark:text-gray-400">
                    Se calcula con boletas y facturas <strong>emitidas</strong> (también al crédito), no con lo cobrado.
                    Vence el {{ fecha(dueDate) }}.
                </p>
            </div>

            <!-- Resumen del periodo -->
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
                <div class="panel p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Ventas con comprobante</p>
                    <p class="mt-2 text-2xl font-bold text-gray-800 dark:text-gray-100">{{ money(summary.sales_total, summary.currency) }}</p>
                    <p class="mt-1 text-xs text-gray-500">{{ summary.documents_count }} comprobante(s) · {{ mesLargo(summary.period) }}</p>
                </div>
                <div class="panel p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">IGV trasladado ({{ igvRate }} %)</p>
                    <p class="mt-2 text-2xl font-bold text-success">{{ money(summary.sales_igv, summary.currency) }}</p>
                    <p class="mt-1 text-xs text-gray-500">Base {{ money(summary.taxable_base, summary.currency) }}</p>
                </div>
                <div class="panel p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Notas de crédito</p>
                    <p class="mt-2 text-2xl font-bold" :class="summary.notes_igv > 0 ? 'text-danger' : 'text-gray-400'">
                        <template v-if="summary.notes_igv > 0">-{{ money(summary.notes_igv, summary.currency) }}</template>
                        <template v-else>{{ money(0, summary.currency) }}</template>
                    </p>
                    <p class="mt-1 text-xs text-gray-500">{{ summary.notes_count }} nota(s) de crédito</p>
                </div>
                <div class="panel bg-gradient-to-br from-primary to-primary-dark p-5 text-white">
                    <p class="text-sm opacity-80">IGV por pagar</p>
                    <p class="mt-2 text-2xl font-bold">{{ money(summary.net_igv, summary.currency) }}</p>
                    <p class="mt-1 text-xs opacity-80">
                        Pagado: {{ money(summary.paid, summary.currency) }}
                        <span v-if="saldoPorPagar > 0 && !yaPagado"> · saldo {{ money(saldoPorPagar, summary.currency) }}</span>
                    </p>
                    <!-- Desglose: lo trasladado menos notas y lo ya adelantado por detracción. -->
                    <p class="mt-2 border-t border-white/20 pt-2 text-[11px] leading-relaxed opacity-90">
                        {{ money(summary.sales_igv, summary.currency) }} IGV
                        − {{ money(summary.notes_igv, summary.currency) }} notas
                        − {{ money(summary.detractions, summary.currency) }} detracción
                    </p>
                </div>
            </div>

            <!-- Acción y aviso -->
            <div class="panel flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-start gap-3">
                    <font-awesome-icon :icon="faReceipt" class="mt-1 text-primary" />
                    <div>
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-100">
                            El IGV de la venta no se descuenta del ingreso
                        </p>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-300">
                            Cada boleta o factura entra al libro por su total. Este pago es el egreso tributario del periodo.
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    class="btn btn-primary whitespace-nowrap"
                    :disabled="!canPay || summary.net_igv <= 0 || cuentasDisponibles.length === 0"
                    @click="abrirPago"
                >
                    Registrar pago de IGV
                </button>
            </div>

            <div v-if="summary.net_igv > 0 && cuentasDisponibles.length === 0" class="panel border-l-4 border-warning p-4 text-sm text-warning">
                <font-awesome-icon :icon="faTriangleExclamation" /> No hay cuentas activas en {{ summary.currency }} para pagar este IGV.
            </div>

            <!-- Detalle de comprobantes -->
            <div class="panel overflow-hidden p-0">
                <div class="border-b border-gray-200 p-5 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Boletas y facturas emitidas</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Del {{ fecha(summary.from) }} al {{ fecha(summary.to) }} · incluye ventas al crédito
                    </p>
                </div>
                <div class="table-responsive max-h-[520px] overflow-y-auto">
                    <table class="min-w-full">
                        <thead class="sticky top-0 bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th>Número</th>
                                <th>Cliente</th>
                                <th class="text-right">Total</th>
                                <th class="text-right">IGV</th>
                                <th>Estado SUNAT</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="document in summary.documents" :key="document.id">
                                <td class="whitespace-nowrap text-sm">{{ fecha(document.date) }}</td>
                                <td class="text-sm">{{ document.type_label }}</td>
                                <td class="text-sm font-medium">{{ document.number }}</td>
                                <td class="text-sm">
                                    {{ document.client }}
                                    <span v-if="document.credit" class="ml-1 rounded bg-warning/10 px-1.5 py-0.5 text-[11px] text-warning">Crédito</span>
                                </td>
                                <td class="text-right text-sm">{{ money(document.total, summary.currency) }}</td>
                                <td class="text-right text-sm font-semibold">{{ money(document.igv, summary.currency) }}</td>
                                <td class="text-sm text-gray-500">{{ document.status }}</td>
                            </tr>
                            <tr v-if="summary.documents.length === 0">
                                <td colspan="7" class="py-8 text-center text-sm text-gray-500">
                                    Sin boletas ni facturas emitidas en el periodo.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Notas de crédito -->
            <div v-if="summary.notes.length" class="panel overflow-hidden p-0">
                <div class="border-b border-gray-200 p-5 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Notas de crédito que restan IGV</h2>
                </div>
                <div class="table-responsive">
                    <table class="min-w-full">
                        <thead class="bg-gray-50 dark:bg-gray-800/60">
                            <tr>
                                <th>Fecha</th>
                                <th>Número</th>
                                <th>Cliente</th>
                                <th class="text-right">IGV</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="note in summary.notes" :key="note.id">
                                <td class="text-sm">{{ fecha(note.date) }}</td>
                                <td class="text-sm font-medium">{{ note.number }}</td>
                                <td class="text-sm">{{ note.client }}</td>
                                <td class="text-right text-sm text-danger">-{{ money(note.igv, summary.currency) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagos registrados -->
            <div class="panel overflow-hidden p-0">
                <div class="border-b border-gray-200 p-5 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Pagos de IGV registrados</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Egresos con categoría «IGV por pagar»</p>
                </div>
                <div class="table-responsive">
                    <table class="min-w-full">
                        <thead class="bg-gray-50 dark:bg-gray-800/60">
                            <tr>
                                <th>Fecha</th>
                                <th>Periodo</th>
                                <th>Cuenta</th>
                                <th>Referencia</th>
                                <th class="text-right">Monto</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="payment in payments" :key="payment.id" :class="payment.voided ? 'opacity-50' : ''">
                                <td class="text-sm">
                                    {{ fecha(payment.date) }}
                                    <span v-if="payment.time" class="block text-xs text-gray-500">{{ payment.time }}</span>
                                </td>
                                <td class="text-sm">{{ payment.period }}</td>
                                <td class="text-sm">{{ payment.account }}</td>
                                <td class="text-sm text-gray-500">{{ payment.reference || '—' }}</td>
                                <td class="text-right text-sm font-semibold">{{ money(payment.amount, payment.currency) }}</td>
                            </tr>
                            <tr v-if="payments.length === 0">
                                <td colspan="5" class="py-8 text-center text-sm text-gray-500">Sin pagos de IGV registrados.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <ModalMedium :show="modal" :on-close="() => (modal = false)" :icon="'/img/cuenta-bancaria.png'">
            <template #title>Registrar pago de IGV</template>
            <template #message><span>Se creará un egreso en la cuenta elegida</span></template>
            <template #content>
                <div class="space-y-4">
                    <div class="rounded-lg bg-gray-50 p-3 text-sm dark:bg-gray-800">
                        <p class="text-gray-600 dark:text-gray-300">
                            Periodo <strong>{{ mesLargo(form.period) }}</strong> ·
                            IGV por pagar <strong>{{ money(summary.net_igv, summary.currency) }}</strong>
                        </p>
                    </div>
                    <div>
                        <InputLabel value="Cuenta *" class="mb-1.5" />
                        <select v-model="form.treasury_account_id" class="form-select w-full">
                            <option :value="null" disabled>Seleccione</option>
                            <option v-for="account in cuentasDisponibles" :key="account.id" :value="account.id">
                                {{ account.label }} · {{ money(account.balance, account.currency) }}
                                {{ account.is_detraction ? ' (solo IGV)' : '' }}
                            </option>
                        </select>
                        <InputError :message="form.errors.treasury_account_id" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Fecha *" class="mb-1.5" />
                            <input v-model="form.date" type="date" class="form-input w-full" />
                            <InputError :message="form.errors.date" />
                        </div>
                        <div>
                            <InputLabel value="Monto *" class="mb-1.5" />
                            <input v-model="form.amount" type="number" min="0.01" step="0.01" class="form-input w-full" />
                            <InputError :message="form.errors.amount" />
                        </div>
                    </div>
                    <div>
                        <InputLabel value="Referencia" class="mb-1.5" />
                        <input v-model="form.reference" type="text" class="form-input w-full" placeholder="Número de operación o declaración" />
                        <InputError :message="form.errors.reference" />
                    </div>
                </div>
            </template>
            <template #buttons>
                <PrimaryButton :disabled="form.processing" @click="guardarPago">Registrar pago</PrimaryButton>
            </template>
        </ModalMedium>
    </AppLayout>
</template>
