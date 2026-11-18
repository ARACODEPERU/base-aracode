<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import { Head, router, useForm } from '@inertiajs/vue3';
    import ModalMedium from '@/Components/ModalMedium.vue';
    import InputLabel from '@/Components/InputLabel.vue';
    import InputError from '@/Components/InputError.vue';
    import PrimaryButton from '@/Components/PrimaryButton.vue';
    import { computed, ref } from 'vue';
    import { faVault, faTriangleExclamation, faLock, faArrowRightFromBracket, faPenToSquare } from '@fortawesome/free-solid-svg-icons';

    const props = defineProps({
        summary: { type: Object, required: true },
        igv: { type: Object, default: () => ({ period: '', amount: 0, due_date: '' }) },
        destinations: { type: Array, default: () => [] },
        threshold: { type: Number, default: 700 },
        percent: { type: Number, default: 12 },
        canManage: { type: Boolean, default: false },
    });

    const money = (value, currency = 'PEN') => {
        try {
            return new Intl.NumberFormat('es-PE', { style: 'currency', currency }).format(value || 0);
        } catch (e) {
            return `${currency} ${Number(value || 0).toFixed(2)}`;
        }
    };

    const fecha = (value) => {
        if (!value) return '—';

        const [year, month, day] = String(value).split('-');

        return year && month && day ? `${day}/${month}/${year}` : value;
    };

    const notifyFlash = (page) => {
        const flash = page?.props?.flash || {};
        if (flash.error) window.alertaSistema('No se pudo completar', flash.error, 'error');
        else if (flash.success) window.alertaSistema('Movimiento registrado', flash.success, 'success');
    };

    const hoy = new Date().toISOString().slice(0, 10);

    // Liberación del saldo detraído a una cuenta de la empresa.
    const modalLiberar = ref(false);
    const liberar = useForm({
        password: '',
        treasury_account_id: null,
        amount: 0,
        date: hoy,
        reference: '',
    });

    const abrirLiberar = () => {
        liberar.clearErrors();
        liberar.password = '';
        liberar.treasury_account_id = props.destinations[0]?.id ?? null;
        liberar.amount = Math.max(0, Number(props.summary.balance || 0));
        liberar.date = hoy;
        liberar.reference = '';
        modalLiberar.value = true;
    };

    // Ajuste manual: el único camino para modificar el saldo fuera de IGV o liberación.
    const modalAjuste = ref(false);
    const ajuste = useForm({
        password: '',
        type: 'income',
        amount: 0,
        date: hoy,
        reason: '',
    });

    const abrirAjuste = () => {
        ajuste.clearErrors();
        ajuste.password = '';
        ajuste.type = 'income';
        ajuste.amount = 0;
        ajuste.date = hoy;
        ajuste.reason = '';
        modalAjuste.value = true;
    };

    const confirmarLiberar = async () => {
        const resultado = await window.alertaSistema(
            'Liberar detracción',
            `Se moverán ${money(liberar.amount, props.summary.currency)} de la cuenta de detracción a una cuenta de la empresa. Esta acción queda auditada y no se puede deshacer sin un ajuste crítico.`,
            'warning',
            { duration: 0, showCancelButton: true, confirmButtonText: 'Sí, liberar', cancelButtonText: 'Cancelar' }
        );

        if (!resultado?.isConfirmed) return;

        liberar.post(route('treasury_detraction_release'), {
            preserveScroll: true,
            onSuccess: (page) => {
                notifyFlash(page);
                modalLiberar.value = false;
            },
            onError: () => window.alertaSistema('Revisa el formulario', 'Faltan datos o la contraseña.', 'warning'),
        });
    };

    const confirmarAjuste = async () => {
        const resultado = await window.alertaSistema(
            'Ajuste crítico de detracción',
            'Vas a modificar el saldo de una cuenta restringida. Quedará registrado con tu usuario, la fecha y el motivo. ¿Continuar?',
            'warning',
            { duration: 0, showCancelButton: true, confirmButtonText: 'Sí, ajustar', cancelButtonText: 'Cancelar' }
        );

        if (!resultado?.isConfirmed) return;

        ajuste.post(route('treasury_detraction_adjust'), {
            preserveScroll: true,
            onSuccess: (page) => {
                notifyFlash(page);
                modalAjuste.value = false;
            },
            onError: () => window.alertaSistema('Revisa el formulario', 'Revisa el motivo y la contraseña.', 'warning'),
        });
    };

    const puedeLiberar = computed(() => Number(props.summary.balance || 0) > 0 && props.destinations.length > 0);

    // Banco y número de cuenta donde el cliente deposita la detracción.
    const modalCuenta = ref(false);
    const cuentaForm = useForm({
        bank_name: props.summary.bank_name || 'Banco de la Nación',
        account_number: props.summary.account_number || '',
    });

    const abrirCuenta = () => {
        cuentaForm.clearErrors();
        cuentaForm.bank_name = props.summary.bank_name || 'Banco de la Nación';
        cuentaForm.account_number = props.summary.account_number || '';
        modalCuenta.value = true;
    };

    const guardarCuenta = () => {
        cuentaForm.post(route('treasury_detraction_account_update'), {
            preserveScroll: true,
            onSuccess: (page) => {
                notifyFlash(page);
                modalCuenta.value = false;
            },
            onError: () => window.alertaSistema('Revisa la cuenta', 'El banco y el número no deben superar el largo permitido.', 'warning'),
        });
    };

    // Lleva a Impuestos con el mes y la cuenta de detracción ya elegidos.
    const irAPagarIgv = () => {
        router.visit(route('treasury_taxes_index', {
            month: props.igv.period,
            account_id: props.summary.account_id,
        }));
    };
</script>

<template>
    <AppLayout title="Cuenta de detracción">
        <Head title="Tesorería · Detracción">
            <meta name="aracodito-view" content="new" />
        </Head>
        <Navigation>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2"><span>Tesorería</span></li>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2"><span>Detracción</span></li>
        </Navigation>

        <div class="mt-5 space-y-5">
            <!-- Regla de la cuenta -->
            <div class="panel border-l-4 border-primary p-5">
                <div class="flex items-start gap-3">
                    <font-awesome-icon :icon="faLock" class="mt-1 text-primary" />
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Cuenta restringida</h2>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                            Las facturas desde {{ money(threshold) }} están sujetas a detracción del {{ percent }} % del total.
                            El IGV sigue siendo 18 %: de cada S/ 100 del total facturado, S/ {{ percent }} llegan a esta
                            cuenta para pagarlo y el resto a la cuenta de la empresa. Liberar el saldo o ajustarlo exige
                            tu contraseña y queda en auditoría.
                        </p>

                        <!-- Dónde deposita el cliente -->
                        <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div class="rounded-lg border border-gray-200 px-3 py-2 dark:border-gray-700">
                                <p class="text-xs text-gray-500 dark:text-gray-400">Banco de la cuenta</p>
                                <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                                    {{ summary.bank_name || 'Banco de la Nación' }}
                                </p>
                            </div>
                            <div class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 px-3 py-2 dark:border-gray-700">
                                <div class="min-w-0">
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Número de cuenta de detracciones</p>
                                    <p class="truncate font-mono text-sm font-semibold text-gray-800 dark:text-gray-100">
                                        {{ summary.account_number || 'Sin registrar' }}
                                    </p>
                                </div>
                                <button v-if="canManage" type="button" class="btn btn-sm btn-outline-primary whitespace-nowrap" @click="abrirCuenta">
                                    Editar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Saldos -->
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
                <div class="panel bg-gradient-to-br from-primary to-primary-dark p-5 text-white">
                    <p class="text-sm opacity-80">Saldo en detracción</p>
                    <p class="mt-2 text-3xl font-bold">{{ money(summary.balance, summary.currency) }}</p>
                    <p class="mt-1 text-xs opacity-80">Disponible solo para IGV</p>
                </div>
                <div class="panel p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total detraído</p>
                    <p class="mt-2 text-2xl font-bold text-success">{{ money(summary.total_detracted, summary.currency) }}</p>
                    <p class="mt-1 text-xs text-gray-500">{{ summary.detractions.length }} detracción(es) registradas</p>
                </div>
                <div class="panel p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total liberado</p>
                    <p class="mt-2 text-2xl font-bold text-warning">{{ money(summary.total_released, summary.currency) }}</p>
                    <p class="mt-1 text-xs text-gray-500">Trasladado a cuentas de la empresa</p>
                </div>
                <div class="panel p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Ajustes manuales</p>
                    <p class="mt-2 text-2xl font-bold" :class="summary.total_adjustments < 0 ? 'text-danger' : 'text-gray-800 dark:text-gray-100'">
                        {{ money(summary.total_adjustments, summary.currency) }}
                    </p>
                    <p class="mt-1 text-xs text-gray-500">Neto de ajustes auditados</p>
                </div>
            </div>

            <!-- Acciones críticas -->
            <div class="panel flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-800 dark:text-gray-100">Movimientos fuera de IGV</p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-300">
                        Para retirar el saldo cuando SUNAT lo habilite o corregir un error con sustento.
                    </p>
                </div>
                <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                    <button type="button" class="btn btn-outline-danger w-full sm:w-auto" :disabled="!canManage" @click="abrirAjuste">
                        <font-awesome-icon :icon="faPenToSquare" class="mr-1" /> Ajuste manual
                    </button>
                    <button type="button" class="btn btn-primary w-full sm:w-auto" :disabled="!canManage || !puedeLiberar" @click="abrirLiberar">
                        <font-awesome-icon :icon="faArrowRightFromBracket" class="mr-1" /> Liberar a cuenta
                    </button>
                </div>
            </div>

            <!-- El uso natural del saldo: pagar el IGV del periodo -->
            <div v-if="igv.amount > 0" class="panel flex flex-col gap-3 border-l-4 border-success p-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-800 dark:text-gray-100">
                        IGV por pagar de {{ igv.period }}: {{ money(igv.amount, summary.currency) }}
                    </p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-300">
                        Vence el {{ fecha(igv.due_date) }} y se paga desde este saldo en la pantalla de Impuestos.
                        <span v-if="Number(summary.balance) < Number(igv.amount)" class="font-medium text-warning">
                            Esta cuenta tiene {{ money(summary.balance, summary.currency) }}: el pago se completa con la cuenta que elijas allí.
                        </span>
                    </p>
                </div>
                <button type="button" class="btn btn-primary w-full whitespace-nowrap sm:w-auto" :disabled="!canManage" @click="irAPagarIgv">
                    Pagar IGV
                </button>
            </div>

            <!-- Detracciones registradas -->
            <div class="panel overflow-hidden p-0">
                <div class="border-b border-gray-200 p-5 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Detracciones cobradas</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Se registran al cobrar la factura, no al emitirla
                    </p>
                </div>
                <div class="table-responsive max-h-[480px] overflow-y-auto">
                    <table class="min-w-full">
                        <thead class="sticky top-0 bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th>Cobrada</th>
                                <th>Factura</th>
                                <th>Emitida</th>
                                <th>Cliente</th>
                                <th>%</th>
                                <th>Tipo</th>
                                <th class="text-right">Total factura</th>
                                <th class="text-right">A la empresa</th>
                                <th class="text-right">Detraído</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in summary.detractions" :key="item.id">
                                <td class="whitespace-nowrap text-sm">{{ fecha(item.date) }}</td>
                                <td class="text-sm font-medium">{{ item.document }}</td>
                                <td class="text-sm">{{ fecha(item.document_date) }}</td>
                                <td class="text-sm">{{ item.client || '—' }}</td>
                                <td class="text-sm">{{ item.percent ?? '—' }} %</td>
                                <td class="text-sm">
                                    <span v-if="item.auto" class="rounded bg-warning/10 px-1.5 py-0.5 text-[11px] text-warning">Auto-detracción</span>
                                    <span v-else class="rounded bg-primary/10 px-1.5 py-0.5 text-[11px] text-primary">Detracción</span>
                                </td>
                                <td class="text-right text-sm text-gray-500">
                                    {{ item.invoice_total !== null ? money(item.invoice_total, summary.currency) : '—' }}
                                </td>
                                <td class="text-right text-sm text-gray-500">
                                    {{ item.net_to_company !== null ? money(item.net_to_company, summary.currency) : '—' }}
                                </td>
                                <td class="text-right text-sm font-semibold">{{ money(item.amount, summary.currency) }}</td>
                            </tr>
                            <tr v-if="summary.detractions.length === 0">
                                <td colspan="9" class="py-8 text-center text-sm text-gray-500">
                                    Todavía no hay detracciones registradas.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Movimientos de la cuenta -->
            <div class="panel overflow-hidden p-0">
                <div class="border-b border-gray-200 p-5 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Movimientos de la cuenta</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Cobros, pagos de IGV, liberaciones y ajustes</p>
                </div>
                <div class="table-responsive max-h-[480px] overflow-y-auto">
                    <table class="min-w-full">
                        <thead class="sticky top-0 bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th>Fecha</th>
                                <th>Descripción</th>
                                <th>Referencia</th>
                                <th>N° operación</th>
                                <th class="text-right">Ingreso</th>
                                <th class="text-right">Egreso</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="movement in summary.movements" :key="movement.id">
                                <td class="whitespace-nowrap text-sm">
                                    {{ fecha(movement.date) }}
                                    <span v-if="movement.time" class="block text-xs text-gray-500">{{ movement.time }}</span>
                                </td>
                                <td class="text-sm">{{ movement.description }}</td>
                                <td class="text-sm text-gray-500">{{ movement.reference || '—' }}</td>
                                <td class="whitespace-nowrap text-sm text-gray-500">{{ movement.operation_number || '—' }}</td>
                                <td class="text-right text-sm text-success">
                                    <template v-if="movement.type === 'income'">{{ money(movement.amount, summary.currency) }}</template>
                                    <span v-else class="text-gray-300">—</span>
                                </td>
                                <td class="text-right text-sm text-danger">
                                    <template v-if="movement.type === 'expense'">{{ money(movement.amount, summary.currency) }}</template>
                                    <span v-else class="text-gray-300">—</span>
                                </td>
                            </tr>
                            <tr v-if="summary.movements.length === 0">
                                <td colspan="6" class="py-8 text-center text-sm text-gray-500">Sin movimientos.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Datos de la cuenta de detracciones -->
        <ModalMedium :show="modalCuenta" :on-close="() => (modalCuenta = false)" :icon="'/img/cuenta-bancaria.png'">
            <template #title>Cuenta de detracciones</template>
            <template #message><span>Banco y número donde el cliente deposita el {{ percent }} %</span></template>
            <template #content>
                <div class="space-y-4">
                    <div>
                        <InputLabel value="Banco *" class="mb-1.5" />
                        <input v-model="cuentaForm.bank_name" type="text" class="form-input w-full" placeholder="Banco de la Nación" />
                        <InputError :message="cuentaForm.errors.bank_name" />
                    </div>
                    <div>
                        <InputLabel value="Número de cuenta *" class="mb-1.5" />
                        <input v-model="cuentaForm.account_number" type="text" class="form-input w-full font-mono" placeholder="00-000-000000" />
                        <InputError :message="cuentaForm.errors.account_number" />
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Este número es el que se informa al cliente en la factura. La cuenta debe estar a nombre de la empresa
                        en el Banco de la Nación.
                    </p>
                </div>
            </template>
            <template #buttons>
                <PrimaryButton :disabled="cuentaForm.processing" @click="guardarCuenta">Guardar cuenta</PrimaryButton>
            </template>
        </ModalMedium>

        <!-- Liberación crítica -->
        <ModalMedium :show="modalLiberar" :on-close="() => (modalLiberar = false)" :icon="'/img/cuenta-bancaria.png'">
            <template #title>Liberar saldo de detracción</template>
            <template #message><span>Movimiento crítico: requiere tu contraseña</span></template>
            <template #content>
                <div class="space-y-4">
                    <div class="rounded-lg border border-warning/40 bg-warning/10 p-3 text-sm text-warning">
                        <p class="flex items-center gap-2 font-semibold"><font-awesome-icon :icon="faTriangleExclamation" /> Advertencia</p>
                        <p class="mt-1">
                            Solo libera cuando SUNAT habilitó el retiro. Se crearán dos movimientos: egreso de detracción e
                            ingreso en la cuenta destino. Queda auditado con tu usuario.
                        </p>
                    </div>
                    <div>
                        <InputLabel value="Cuenta destino *" class="mb-1.5" />
                        <select v-model="liberar.treasury_account_id" class="form-select w-full">
                            <option :value="null" disabled>Seleccione</option>
                            <option v-for="account in destinations" :key="account.id" :value="account.id">
                                {{ account.label }} ({{ account.currency }})
                            </option>
                        </select>
                        <InputError :message="liberar.errors.treasury_account_id" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Importe *" class="mb-1.5" />
                            <input v-model="liberar.amount" type="number" min="0.01" step="0.01" class="form-input w-full" />
                            <InputError :message="liberar.errors.amount" />
                        </div>
                        <div>
                            <InputLabel value="Fecha *" class="mb-1.5" />
                            <input v-model="liberar.date" type="date" class="form-input w-full" />
                            <InputError :message="liberar.errors.date" />
                        </div>
                    </div>
                    <div>
                        <InputLabel value="Referencia" class="mb-1.5" />
                        <input v-model="liberar.reference" type="text" class="form-input w-full" placeholder="Constancia o número de operación" />
                        <InputError :message="liberar.errors.reference" />
                    </div>
                    <div>
                        <InputLabel value="Tu contraseña *" class="mb-1.5" />
                        <input v-model="liberar.password" type="password" class="form-input w-full" autocomplete="current-password" />
                        <InputError :message="liberar.errors.password" />
                    </div>
                </div>
            </template>
            <template #buttons>
                <PrimaryButton :disabled="liberar.processing" @click="confirmarLiberar">Liberar</PrimaryButton>
            </template>
        </ModalMedium>

        <!-- Ajuste crítico -->
        <ModalMedium :show="modalAjuste" :on-close="() => (modalAjuste = false)" :icon="'/img/cuenta-bancaria.png'">
            <template #title>Ajuste manual de detracción</template>
            <template #message><span>Modifica una cuenta restringida</span></template>
            <template #content>
                <div class="space-y-4">
                    <div class="rounded-lg border border-danger/40 bg-danger/10 p-3 text-sm text-danger">
                        <p class="flex items-center gap-2 font-semibold"><font-awesome-icon :icon="faTriangleExclamation" /> Operación crítica</p>
                        <p class="mt-1">
                            Un ajuste sin sustento descuadra el IGV. Explica el motivo; se guarda tu usuario, la fecha y la
                            hora junto al movimiento.
                        </p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Tipo *" class="mb-1.5" />
                            <select v-model="ajuste.type" class="form-select w-full">
                                <option value="income">Aumentar el saldo (ingreso)</option>
                                <option value="expense">Disminuir el saldo (egreso)</option>
                            </select>
                            <InputError :message="ajuste.errors.type" />
                        </div>
                        <div>
                            <InputLabel value="Importe *" class="mb-1.5" />
                            <input v-model="ajuste.amount" type="number" min="0.01" step="0.01" class="form-input w-full" />
                            <InputError :message="ajuste.errors.amount" />
                        </div>
                    </div>
                    <div>
                        <InputLabel value="Fecha *" class="mb-1.5" />
                        <input v-model="ajuste.date" type="date" class="form-input w-full" />
                        <InputError :message="ajuste.errors.date" />
                    </div>
                    <div>
                        <InputLabel value="Motivo * (mínimo 10 caracteres)" class="mb-1.5" />
                        <textarea v-model="ajuste.reason" rows="3" class="form-input w-full" placeholder="Detalla por qué se modifica el saldo"></textarea>
                        <InputError :message="ajuste.errors.reason" />
                    </div>
                    <div>
                        <InputLabel value="Tu contraseña *" class="mb-1.5" />
                        <input v-model="ajuste.password" type="password" class="form-input w-full" autocomplete="current-password" />
                        <InputError :message="ajuste.errors.password" />
                    </div>
                </div>
            </template>
            <template #buttons>
                <PrimaryButton :disabled="ajuste.processing" @click="confirmarAjuste">Registrar ajuste</PrimaryButton>
            </template>
        </ModalMedium>
    </AppLayout>
</template>
