<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import { Head, Link, useForm, router } from '@inertiajs/vue3';
    import { Dropdown, Menu, MenuItem } from 'ant-design-vue';
    import Swal2 from 'sweetalert2';
    import ModalLarge from '@/Components/ModalLarge.vue';
    import OriginDetailModal from '../../Components/OriginDetailModal.vue';
    import InputLabel from '@/Components/InputLabel.vue';
    import InputError from '@/Components/InputError.vue';
    import PrimaryButton from '@/Components/PrimaryButton.vue';
    import { ref, computed } from 'vue';
    import { faPlus, faFileArrowUp, faFilter, faCircleInfo, faTriangleExclamation } from '@fortawesome/free-solid-svg-icons';

    const props = defineProps({
        accounts: { type: Array, default: () => [] },
        categories: { type: Array, default: () => [] },
        selectedAccountId: { type: Number, default: null },
        from: { type: String, default: null },
        to: { type: String, default: null },
        transactions: { type: Array, default: () => [] },
        openingBalance: { type: Number, default: 0 },
        runningBalance: { type: Number, default: 0 },
        beforeStartCount: { type: Number, default: 0 },
        calculatedBalance: { type: Number, default: 0 },
        hidesBalance: { type: Boolean, default: false },
        walletTransferDays: { type: Number, default: 0 },
        walletBank: { type: String, default: null },
        cashOpen: { type: Object, default: () => ({ count: 0, balance: 0 }) },
        paymentMethods: { type: Array, default: () => [] },
        accountCurrency: { type: String, default: 'PEN' },
        costCenters: { type: Array, default: () => [] },
    });

    const currency = (value, code = props.accountCurrency) => {
        try {
            return new Intl.NumberFormat('es-PE', { style: 'currency', currency: code || 'PEN' }).format(value || 0);
        } catch (e) {
            return `${code || 'PEN'} ${Number(value || 0).toFixed(2)}`;
        }
    };

    // La fecha del libro llega en ISO para el input; en la tabla se muestra dd/mm/aaaa.
    const fecha = (value) => {
        if (!value) return '—';

        const [year, month, day] = String(value).split('-');

        return year && month && day ? `${day}/${month}/${year}` : value;
    };

    // Hora local en HH:MM para el input type="time".
    const horaActual = () => {
        const ahora = new Date();
        return `${String(ahora.getHours()).padStart(2, '0')}:${String(ahora.getMinutes()).padStart(2, '0')}`;
    };

    // Toast de éxito (convención del proyecto)
    const toast = Swal2.mixin({
        toast: true,
        position: 'bottom-end',
        showConfirmButton: false,
        timer: 3000,
        padding: '2em',
        customClass: { container: 'toast' },
    });

    // Filtros
    const filters = ref({
        account_id: props.selectedAccountId,
        from: props.from,
        to: props.to,
    });

    const applyFilters = () => {
        router.get(route('treasury_transactions_index'), filters.value, {
            preserveScroll: true,
            preserveState: true,
        });
    };

    // Registro manual de movimientos
    const showModal = ref(false);
    const editingId = ref(null);
    const attachmentFile = ref(null);

    const form = useForm({
        treasury_account_id: props.selectedAccountId,
        transaction_date: new Date().toISOString().slice(0, 10),
        transaction_time: horaActual(),
        type: 'expense',
        treasury_category_id: null,
        cost_center_id: null,
        amount: '',
        reference: '',
        operation_number: '',
        description: '',
        payment_method_id: null,
        attachment: null,
    });

    const availableCategories = computed(() =>
        props.categories.filter(
            (cat) => cat.applies_to === 'both' || cat.applies_to === form.type
        )
    );

    const openCreate = (type = 'expense') => {
        editingId.value = null;
        form.reset();
        form.treasury_account_id = filters.value.account_id;
        form.transaction_date = new Date().toISOString().slice(0, 10);
        form.transaction_time = horaActual();
        form.type = type;
        showModal.value = true;
    };

    const openEdit = (tx) => {
        editingId.value = tx.id;
        form.treasury_account_id = filters.value.account_id;
        form.transaction_date = tx.date;
        form.transaction_time = tx.time || '';
        form.type = tx.type;
        form.amount = tx.amount;
        form.reference = tx.reference;
        form.operation_number = tx.operation_number || '';
        form.description = tx.description;
        form.cost_center_id = tx.cost_center_id ?? null;
        showModal.value = true;
    };

    const onFileChange = (event) => {
        form.attachment = event.target.files[0];
    };

    const save = () => {
        const url = editingId.value
            ? route('treasury_transactions_update', editingId.value)
            : route('treasury_transactions_store');

        form.post(url, {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: (page) => {
                // El servidor rechaza, por ejemplo, un N° de operación repetido
                // en la cuenta: el mensaje debe verse y el modal seguir abierto.
                const error = page?.props?.flash?.error;

                if (error) {
                    window.alertaSistema('No se pudo guardar el movimiento', error, 'error');
                    return;
                }

                toast.fire({ icon: 'success', title: 'Movimiento guardado' });
                showModal.value = false;
                attachmentFile.value = null;
            },
            onError: () => Swal2.fire({ title: 'Error', text: 'Revisa los datos del formulario', icon: 'error', padding: '2em', customClass: 'sweet-alerts' }),
        });
    };

    // Cierre del modal del núcleo (reset form)
    const closeModal = () => {
        showModal.value = false;
        form.reset();
        editingId.value = null;
        attachmentFile.value = null;
    };

    // Anulación
    const remove = (tx) => {
        Swal2.fire({
            title: '¿Anular movimiento?',
            text: 'El movimiento no se borra: se marca como anulado para mantener la trazabilidad.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: '¡Sí, Anular!',
            cancelButtonText: 'Cancelar',
            padding: '2em',
            customClass: 'sweet-alerts',
            allowOutsideClick: () => !Swal2.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                router.delete(route('treasury_transactions_destroy', tx.id), {
                    preserveScroll: true,
                    onSuccess: () => toast.fire({ icon: 'success', title: 'Movimiento anulado' }),
                });
            }
        });
    };

    // La cifra del saldo es la misma de la pestaña Cuentas; la nota explica
    // cuándo el dinero de una billetera todavía no es del banco.
    const balanceLabel = computed(() => {
        if (props.hidesBalance) return 'Saldo propio (su dinero está en el banco)';
        if (props.walletTransferDays > 0) return 'Saldo propio (aún en la billetera)';

        return 'Saldo actual (igual que Cuentas)';
    });

    const balanceNote = computed(() => {
        if (props.hidesBalance) {
            return `Lo cobrado por esta billetera se muestra sumado en ${props.walletBank || 'su banco vinculado'}.`;
        }

        if (props.walletTransferDays > 0) {
            const dias = props.walletTransferDays === 1 ? '1 día hábil' : `${props.walletTransferDays} días hábiles`;

            return `Se transferirá a ${props.walletBank || 'su banco'} en ${dias}.`;
        }

        return null;
    });

    // La cuenta elegida puede ser la caja: entonces se cruza el efectivo de las
    // sesiones abiertas, que no vive en el libro de bancos.
    const selectedAccount = computed(() =>
        props.accounts.find((account) => account.id === props.selectedAccountId) || null
    );

    const isCashAccount = computed(() => selectedAccount.value?.type === 'cash');

    const canEdit = (tx) => tx.source === 'manual' && !tx.voided;

    // Detalle del origen (comprobante, cuota, curso)
    const detailModal = ref({ show: false, detail: null });

    const openDetail = (tx) => {
        detailModal.value = { show: true, detail: tx.detail };
    };

    const closeDetail = () => {
        detailModal.value = { show: false, detail: null };
    };

    // Auto-detracción de una factura desde su cobro.
    const autoDetraction = async (tx) => {
        const detail = tx.detail || {};
        const monto = currency(detail.detraccion || 0, detail.moneda || props.accountCurrency);

        const result = await window.alertaSistema(
            'Hacer auto-detracción',
            `Se transferirá ${monto} de esta cuenta a la cuenta de Detracción por la factura ${detail.comprobante || ''}. ¿Continuar?`,
            'warning',
            {
                duration: 0,
                showCancelButton: true,
                confirmButtonText: 'Sí, transferir',
                cancelButtonText: 'Cancelar',
            }
        );

        if (!result?.isConfirmed) return;

        router.post(route('treasury_transactions_auto_detraction', tx.id), {}, {
            preserveScroll: true,
            onSuccess: (page) => {
                const success = page.props.flash?.success;
                const error = page.props.flash?.error;

                if (success) {
                    window.alertaSistema('Auto-detracción registrada', success, 'success');
                } else if (error) {
                    window.alertaSistema('No se pudo hacer la auto-detracción', error, 'error');
                }
            },
            onError: () => window.alertaSistema('No se pudo hacer la auto-detracción', 'Revisa el movimiento e inténtalo de nuevo.', 'error'),
        });
    };

    const sourceLabels = {
        auto: 'Automático',
        manual: 'Manual',
        backfill: 'Backfill',
    };

    const sourceColors = {
        auto: 'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300',
        manual: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
        backfill: 'bg-orange-100 text-orange-700 dark:bg-orange-500/10 dark:text-orange-300',
    };
</script>

<template>
    <AppLayout title="Movimientos de Tesorería">
        <Head title="Tesorería · Movimientos" />
        <Navigation>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Tesorería</span>
            </li>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Movimientos</span>
            </li>
        </Navigation>

        <div class="mt-5 space-y-5">
            <!-- Resumen de la cuenta seleccionada -->
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                <div class="panel p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Saldo inicial</p>
                    <p class="mt-2 text-2xl font-bold text-gray-800 dark:text-gray-100">{{ currency(openingBalance) }}</p>
                </div>
                <div class="panel p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Saldo al cierre del período</p>
                    <p
                        class="mt-2 text-2xl font-bold"
                        :class="runningBalance >= 0 ? 'text-gray-800 dark:text-gray-100' : 'text-danger'"
                    >
                        {{ currency(runningBalance) }}
                    </p>
                </div>
                <div class="panel bg-gradient-to-br from-primary to-primary-dark p-5 text-white">
                    <p class="text-sm opacity-80">{{ balanceLabel }}</p>
                    <p class="mt-2 text-2xl font-bold">{{ currency(calculatedBalance) }}</p>
                    <p v-if="balanceNote" class="mt-1 text-xs opacity-80">{{ balanceNote }}</p>
                    <p v-if="isCashAccount" class="mt-1 text-xs opacity-80">
                        Cajas abiertas: {{ cashOpen.count }} · efectivo esperado {{ currency(cashOpen.balance) }}.
                        <Link :href="route('treasury_cash_index')" class="underline">Ver cajas</Link>
                    </p>
                </div>
            </div>

            <!-- Filtros y acciones -->
            <div class="panel flex flex-col gap-4 p-5 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <select v-model="filters.account_id" class="form-select w-full sm:w-56">
                        <option v-for="account in accounts" :key="account.id" :value="account.id">
                            {{ account.label }}
                        </option>
                    </select>
                    <input v-model="filters.from" type="date" class="form-input sm:w-40" title="Desde" />
                    <input v-model="filters.to" type="date" class="form-input sm:w-40" title="Hasta" />
                    <button type="button" class="btn btn-outline-primary" @click="applyFilters">
                        <font-awesome-icon :icon="faFilter" class="mr-1" /> Filtrar
                    </button>
                </div>

                <div class="flex flex-wrap gap-2">
                    <Link :href="route('treasury_accounts_index')" class="btn btn-outline-primary">
                        Ver cuentas
                    </Link>
                    <button type="button" class="btn btn-success" @click="openCreate('income')">
                        <font-awesome-icon :icon="faPlus" class="mr-1" /> Ingreso
                    </button>
                    <button type="button" class="btn btn-danger" @click="openCreate('expense')">
                        <font-awesome-icon :icon="faPlus" class="mr-1" /> Egreso
                    </button>
                </div>
            </div>

            <!-- Movimientos anteriores al inicio del módulo -->
            <div
                v-if="beforeStartCount"
                class="panel border-l-4 border-warning p-4 text-sm text-gray-600 dark:text-gray-300"
            >
                <font-awesome-icon :icon="faTriangleExclamation" class="mr-1 text-warning" />
                Hay {{ beforeStartCount }} movimiento(s) anteriores al inicio de Tesorería. Se muestran en la lista
                para que puedas revisarlos, pero no suman en saldos, proyecciones ni reportes. Si deben contar, cambia
                la fecha de inicio en
                <Link :href="route('treasury_configuration_index')" class="underline">Configuración</Link>.
            </div>

            <!-- Libro de movimientos -->
            <div class="panel overflow-hidden p-0">
                <div class="table-responsive">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800/60">
                                <th class="w-24 text-center">Acciones</th>
                                <th class="w-32">Fecha y hora</th>
                                <th class="min-w-[240px]">Descripción</th>
                                <th class="min-w-[160px]">Categoría</th>
                                <th class="w-32">Referencia</th>
                                <th class="w-32">N° operación</th>
                                <th class="w-28">Origen</th>
                                <th class="w-36 text-right">Monto</th>
                                <th class="w-40 text-right">Saldo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="transactions.length === 0">
                                <td colspan="9" class="py-8 text-center text-sm text-gray-500">
                                    Sin movimientos en el período seleccionado.
                                </td>
                            </tr>
                            <tr
                                v-for="tx in transactions"
                                :key="tx.id"
                                class="hover:bg-gray-50/70 dark:hover:bg-gray-800/30"
                                :class="tx.voided ? 'opacity-40' : ''"
                            >
                                <td class="text-center">
                                    <button
                                        v-if="tx.detail"
                                        type="button"
                                        class="mr-1 inline-flex h-9 w-9 items-center justify-center rounded-lg border text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700"
                                        title="Ver detalle del origen"
                                        @click="openDetail(tx)"
                                    >
                                        <font-awesome-icon :icon="faCircleInfo" />
                                    </button>
                                    <Dropdown v-if="!tx.voided" placement="bottomLeft" arrow>
                                        <button
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700"
                                            type="button"
                                        >
                                            <font-awesome-icon :icon="faFileArrowUp" />
                                        </button>
                                        <template #overlay>
                                            <Menu>
                                                <MenuItem v-if="canEdit(tx)" key="edit" @click="openEdit(tx)">Editar</MenuItem>
                                                <MenuItem key="void" danger @click="remove(tx)">Anular</MenuItem>
                                            </Menu>
                                        </template>
                                    </Dropdown>
                                    <span v-else class="text-xs font-medium text-danger">Anulado</span>
                                </td>
                                <td class="whitespace-nowrap text-gray-600 dark:text-gray-400">
                                    {{ fecha(tx.date) }}
                                    <span v-if="tx.time" class="block text-xs text-gray-400 dark:text-gray-500">{{ tx.time }}</span>
                                </td>
                                <td>
                                    <p class="text-gray-800 dark:text-gray-100">{{ tx.description || '—' }}</p>
                                    <p v-if="tx.reference" class="text-xs text-gray-400">Ref: {{ tx.reference }}</p>
                                    <p v-if="tx.operation_number" class="text-xs text-gray-400">Op: {{ tx.operation_number }}</p>
                                    <span
                                        v-if="tx.before_start"
                                        class="mt-1 inline-flex items-center gap-1 rounded-md bg-warning/10 px-2 py-0.5 text-xs font-medium text-warning"
                                    >
                                        Antes del inicio
                                    </span>
                                    <template v-if="tx.detail?.detraccion_pendiente">
                                        <span class="mt-1 inline-flex items-center gap-1 rounded-md bg-warning/10 px-2 py-0.5 text-xs font-medium text-warning">
                                            <font-awesome-icon :icon="faTriangleExclamation" /> Falta detracción
                                        </span>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-warning mt-1.5 w-full sm:w-auto"
                                            @click="autoDetraction(tx)"
                                        >
                                            Hacer auto-detracción
                                        </button>
                                    </template>
                                </td>
                                <td>
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-md px-2 py-1 text-xs"
                                        :style="{ backgroundColor: (tx.category_color || '#94a3b8') + '20', color: tx.category_color || '#64748b' }"
                                    >
                                        <span class="h-2 w-2 rounded-full" :style="{ backgroundColor: tx.category_color || '#94a3b8' }"></span>
                                        {{ tx.category || 'Sin categoría' }}
                                    </span>
                                </td>
                                <td class="text-gray-600 dark:text-gray-400">{{ tx.reference || '—' }}</td>
                                <td class="whitespace-nowrap text-gray-600 dark:text-gray-400">{{ tx.operation_number || '—' }}</td>
                                <td>
                                    <span class="rounded-md px-2 py-1 text-xs" :class="sourceColors[tx.source] || sourceColors.manual">
                                        {{ sourceLabels[tx.source] || tx.source }}
                                    </span>
                                </td>
                                <td
                                    class="text-right font-semibold"
                                    :class="tx.type === 'income' ? 'text-success' : 'text-danger'"
                                >
                                    {{ tx.type === 'income' ? '+' : '−' }} {{ currency(tx.amount) }}
                                </td>
                                <td
                                    class="text-right font-bold"
                                    :class="tx.before_start
                                        ? 'text-gray-400 dark:text-gray-500'
                                        : (tx.running_balance >= 0 ? 'text-gray-800 dark:text-gray-100' : 'text-danger')"
                                    :title="tx.before_start ? 'Anterior al inicio del módulo: no suma al saldo' : null"
                                >
                                    {{ tx.before_start ? '—' : currency(tx.running_balance) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal registro/edición (componente del núcleo, patrón Sales) -->
        <ModalLarge :show="showModal" :on-close="closeModal" :icon="'/img/cuenta-bancaria.png'">
            <template #title>
                {{ editingId ? 'Editar movimiento' : (form.type === 'income' ? 'Registrar ingreso' : 'Registrar egreso') }}
            </template>
            <template #message>
                <span>Movimientos del libro de tesorería</span>
            </template>
            <template #content>
            <div class="space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel value="Cuenta *" class="mb-1.5" />
                        <select v-model="form.treasury_account_id" class="form-select w-full">
                            <option v-for="account in accounts" :key="account.id" :value="account.id">
                                {{ account.label }}
                            </option>
                        </select>
                        <InputError :message="form.errors.treasury_account_id" class="mt-2" />
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <InputLabel value="Fecha *" class="mb-1.5" />
                            <input v-model="form.transaction_date" type="date" class="form-input w-full" />
                            <InputError :message="form.errors.transaction_date" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel value="Hora" class="mb-1.5" />
                            <input v-model="form.transaction_time" type="time" class="form-input w-full" />
                            <InputError :message="form.errors.transaction_time" class="mt-2" />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Se guarda con la fecha.</p>
                        </div>
                    </div>
                </div>

                <div v-if="!editingId" class="grid grid-cols-2 gap-3">
                    <button
                        type="button"
                        class="rounded-lg border-2 p-2.5 text-sm font-medium transition"
                        :class="form.type === 'income' ? 'border-success bg-success/5 text-success' : 'border-gray-200 dark:border-gray-700'"
                        @click="form.type = 'income'"
                    >
                        Ingreso
                    </button>
                    <button
                        type="button"
                        class="rounded-lg border-2 p-2.5 text-sm font-medium transition"
                        :class="form.type === 'expense' ? 'border-danger bg-danger/5 text-danger' : 'border-gray-200 dark:border-gray-700'"
                        @click="form.type = 'expense'"
                    >
                        Egreso
                    </button>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <InputLabel value="Monto *" class="mb-1.5" />
                        <input v-model="form.amount" type="number" step="0.01" min="0.01" class="form-input w-full" />
                        <InputError :message="form.errors.amount" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel value="Categoría" class="mb-1.5" />
                        <select v-model="form.treasury_category_id" class="form-select w-full">
                            <option :value="null">Sin categoría</option>
                            <option v-for="cat in availableCategories" :key="cat.id" :value="cat.id">
                                {{ cat.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <InputLabel value="Centro de costo" class="mb-1.5" />
                        <select v-model="form.cost_center_id" class="form-select w-full">
                            <option :value="null">Sin centro de costo</option>
                            <option v-for="center in costCenters" :key="center.id" :value="center.id">
                                {{ center.code }} · {{ center.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.cost_center_id" class="mt-2" />
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Clasifica el gasto para los reportes por área.
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel value="Referencia" class="mb-1.5" />
                        <input v-model="form.reference" type="text" class="form-input w-full" />
                    </div>
                    <div>
                        <InputLabel value="N° de operación del banco" class="mb-1.5" />
                        <input
                            v-model="form.operation_number"
                            type="text"
                            class="form-input w-full"
                            placeholder="El que trae el extracto"
                        />
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Si coincide con el del extracto, la conciliación los reconoce como el mismo movimiento.
                        </p>
                    </div>
                    <div>
                        <InputLabel value="Método de pago" class="mb-1.5" />
                        <select v-model="form.payment_method_id" class="form-select w-full">
                            <option :value="null">Ninguno</option>
                            <option v-for="method in paymentMethods" :key="method.id" :value="method.id">
                                {{ method.description }}
                            </option>
                        </select>
                    </div>
                </div>

                <div>
                    <InputLabel value="Descripción" class="mb-1.5" />
                    <textarea v-model="form.description" rows="2" class="form-textarea w-full" placeholder="Ej: Pago de planilla diciembre, comisión bancaria…"></textarea>
                    <InputError :message="form.errors.description" class="mt-2" />
                </div>

                <div v-if="!editingId">
                    <InputLabel value="Comprobante adjunto (opcional)" class="mb-1.5" />
                    <input ref="attachmentFile" type="file" class="form-input w-full" accept="image/*,.pdf" @change="onFileChange" />
                </div>
            </div>
            </template>
            <template #buttons>
                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing" @click="save">
                    Guardar
                </PrimaryButton>
            </template>
        </ModalLarge>

        <!-- Detalle del origen del movimiento -->
        <OriginDetailModal :show="detailModal.show" :detail="detailModal.detail" :on-close="closeDetail" />
    </AppLayout>
</template>
