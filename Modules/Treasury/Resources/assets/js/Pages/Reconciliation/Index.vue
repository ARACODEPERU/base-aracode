<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import { Head, router, useForm } from '@inertiajs/vue3';
    import ModalMedium from '@/Components/ModalMedium.vue';
    import InputLabel from '@/Components/InputLabel.vue';
    import InputError from '@/Components/InputError.vue';
    import PrimaryButton from '@/Components/PrimaryButton.vue';
    import { computed, ref } from 'vue';
    import { faFilter, faCircleCheck, faLock, faPlus, faTrash, faCircleInfo } from '@fortawesome/free-solid-svg-icons';

    const props = defineProps({
        accounts: { type: Array, default: () => [] },
        selectedAccountId: { type: Number, default: null },
        until: { type: String, default: null },
        unreconciled: { type: Array, default: () => [] },
        unreconciledCount: { type: Number, default: 0 },
        items: { type: Array, default: () => [] },
        systemBalance: { type: Number, default: 0 },
        adjustedSystemBalance: { type: Number, default: 0 },
        history: { type: Array, default: () => [] },
        tolerance: { type: Number, default: 0.01 },
        canClose: { type: Boolean, default: false },
    });

    const accountCurrency = computed(() => {
        const account = props.accounts.find((a) => a.id === props.selectedAccountId);

        return account?.currency || 'PEN';
    });

    const money = (value, currency = accountCurrency.value) => {
        try {
            return new Intl.NumberFormat('es-PE', { style: 'currency', currency }).format(value || 0);
        } catch (e) {
            return `${currency} ${Number(value || 0).toFixed(2)}`;
        }
    };

    const filters = ref({
        account_id: props.selectedAccountId,
        until: props.until,
    });

    const applyFilters = () => {
        router.get(route('treasury_reconciliation_index'), filters.value, {
            preserveScroll: true,
            preserveState: true,
        });
    };

    const notifyFlash = (page) => {
        const flash = page?.props?.flash || {};
        if (flash.error) window.alertaSistema('No se pudo completar', flash.error, 'error');
        else if (flash.success) window.alertaSistema('Listo', flash.success, 'success');
    };

    const realBalance = ref('');
    const statement = computed(() => (realBalance.value === '' ? null : Number(realBalance.value)));

    const difference = computed(() => {
        if (statement.value === null) return null;

        return Math.round((props.adjustedSystemBalance - statement.value) * 100) / 100;
    });

    const isBalanced = computed(() => difference.value !== null && Math.abs(difference.value) <= props.tolerance);

    const selected = ref([]);

    const toggleAll = (event) => {
        selected.value = event.target.checked ? props.unreconciled.map((row) => row.id) : [];
    };

    const toggle = (id) => {
        selected.value = selected.value.includes(id)
            ? selected.value.filter((value) => value !== id)
            : [...selected.value, id];
    };

    // Partidas del extracto
    const itemModal = ref(false);
    const itemForm = useForm({
        treasury_account_id: props.selectedAccountId,
        cutoff_date: props.until,
        description: '',
        amount: '',
        type: 'expense',
    });

    const openItem = () => {
        itemForm.clearErrors();
        itemForm.treasury_account_id = props.selectedAccountId;
        itemForm.cutoff_date = props.until;
        itemForm.description = '';
        itemForm.amount = '';
        itemForm.type = 'expense';
        itemModal.value = true;
    };

    const saveItem = () => {
        itemForm.post(route('treasury_reconciliation_items_store'), {
            preserveScroll: true,
            onSuccess: (page) => {
                notifyFlash(page);
                itemModal.value = false;
            },
            onError: () => window.alertaSistema('Revisa la partida', 'La descripción y el monto son obligatorios.', 'warning'),
        });
    };

    const removeItem = async (item) => {
        const result = await window.alertaSistema(
            'Quitar partida',
            `Se quitará «${item.description}» de esta conciliación.`,
            'warning',
            { duration: 0, showCancelButton: true, confirmButtonText: 'Sí, quitar', cancelButtonText: 'Cancelar' }
        );

        if (!result?.isConfirmed) return;

        router.delete(route('treasury_reconciliation_items_destroy', item.id), {
            preserveScroll: true,
            onSuccess: notifyFlash,
        });
    };

    const closeReconciliation = async () => {
        if (statement.value === null) {
            window.alertaSistema('Falta el saldo del banco', 'Ingresa el saldo del extracto para cerrar la conciliación.', 'warning');
            return;
        }

        if (!isBalanced.value) {
            window.alertaSistema(
                'La conciliación no cuadra',
                `Queda una diferencia de ${money(difference.value)}. Registra las partidas del extracto que faltan o revisa el saldo.`,
                'error'
            );
            return;
        }

        const result = await window.alertaSistema(
            'Cerrar conciliación',
            `Se cerrará el día ${props.until} con saldo de banco ${money(statement.value)}. Las ${selected.value.length || props.unreconciledCount} líneas quedarán bloqueadas para siempre y se guardará quién cerró.`,
            'warning',
            { duration: 0, showCancelButton: true, confirmButtonText: 'Sí, cerrar', cancelButtonText: 'Cancelar' }
        );

        if (!result?.isConfirmed) return;

        router.post(route('treasury_reconciliation_close'), {
            treasury_account_id: props.selectedAccountId,
            until: props.until,
            statement_balance: statement.value,
            transaction_ids: selected.value,
        }, {
            preserveScroll: true,
            onSuccess: notifyFlash,
            onError: () => window.alertaSistema('No se pudo cerrar', 'Revisa el saldo y las líneas seleccionadas.', 'error'),
        });
    };

    const openDetail = (row) => {
        const detail = row.detail;

        if (!detail) return;

        const lines = [
            detail.tipo && `Tipo: ${detail.tipo}`,
            detail.comprobante && `Comprobante: ${detail.comprobante}`,
            detail.cliente && `Cliente: ${detail.cliente}`,
            detail.vencimiento && `Vencimiento: ${detail.vencimiento}`,
            detail.concepto && `Concepto: ${detail.concepto}`,
            detail.metodo && `Método: ${detail.metodo}`,
        ].filter(Boolean);

        window.alertaSistema('Detalle del origen', lines.join('\n'), 'info', { duration: 0 });
    };
</script>

<template>
    <AppLayout title="Conciliación bancaria">
        <Head title="Tesorería · Conciliación">
            <meta name="aracodito-view" content="new" />
        </Head>
        <Navigation>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2"><span>Tesorería</span></li>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2"><span>Conciliación</span></li>
        </Navigation>

        <div class="mt-5 space-y-5">
            <!-- Filtros -->
            <div class="panel flex flex-col gap-4 p-5 lg:flex-row lg:items-end lg:justify-between">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Cuenta</label>
                        <select v-model="filters.account_id" class="form-select w-full sm:w-56">
                            <option v-for="account in accounts" :key="account.id" :value="account.id">
                                {{ account.label }} ({{ account.currency }})
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Hasta</label>
                        <input v-model="filters.until" type="date" class="form-input sm:w-40" />
                    </div>
                    <button type="button" class="btn btn-outline-primary" @click="applyFilters">
                        <font-awesome-icon :icon="faFilter" class="mr-1" /> Filtrar
                    </button>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium">Saldo real del banco ({{ accountCurrency }})</label>
                    <input
                        v-model="realBalance"
                        type="number"
                        step="0.01"
                        class="form-input w-full sm:w-56"
                        placeholder="Monto según extracto…"
                    />
                </div>
            </div>

            <!-- Comparación -->
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
                <div class="panel p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Saldo en el sistema</p>
                    <p class="mt-2 text-2xl font-bold text-gray-800 dark:text-gray-100">{{ money(systemBalance) }}</p>
                </div>
                <div class="panel p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Partidas del extracto</p>
                    <p class="mt-2 text-2xl font-bold text-gray-800 dark:text-gray-100">{{ money(adjustedSystemBalance - systemBalance) }}</p>
                    <p class="mt-1 text-xs text-gray-500">Ajustan el saldo del sistema</p>
                </div>
                <div class="panel p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Saldo real del banco</p>
                    <p class="mt-2 text-2xl font-bold text-gray-800 dark:text-gray-100">
                        {{ statement !== null ? money(statement) : '—' }}
                    </p>
                </div>
                <div class="panel p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Diferencia</p>
                    <p class="mt-2 text-2xl font-bold" :class="isBalanced ? 'text-success' : 'text-danger'">
                        {{ difference !== null ? money(difference) : '—' }}
                    </p>
                    <p v-if="isBalanced" class="mt-1 flex items-center gap-1 text-xs font-medium text-success">
                        <font-awesome-icon :icon="faCircleCheck" /> Cuadrado
                    </p>
                </div>
            </div>

            <!-- Cierre -->
            <div class="panel flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-start gap-3">
                    <div class="rounded-lg bg-primary/10 p-2 text-primary">
                        <font-awesome-icon :icon="faLock" class="text-lg" />
                    </div>
                    <div>
                        <p class="font-semibold text-gray-800 dark:text-white">Cierre inmutable</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Al cerrar se guarda quién concilió, el saldo del banco, la huella de las líneas y se bloquean para siempre.
                        </p>
                    </div>
                </div>
                <button
                    v-if="canClose"
                    type="button"
                    class="btn btn-primary disabled:cursor-not-allowed disabled:opacity-50"
                    :disabled="statement === null || !isBalanced"
                    :title="statement === null ? 'Ingresa el saldo del banco para cerrar' : (!isBalanced ? 'La conciliación debe cuadrar para cerrar' : 'Cerrar y bloquear las líneas')"
                    @click="closeReconciliation"
                >
                    Cerrar conciliación
                </button>
            </div>

            <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">
                <!-- Líneas sin conciliar -->
                <div class="panel overflow-hidden p-0 xl:col-span-2">
                    <div class="flex flex-col gap-3 border-b border-gray-200 p-5 sm:flex-row sm:items-center sm:justify-between dark:border-gray-700">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-800 dark:text-white">
                                Líneas sin conciliar
                                <span class="ml-2 rounded-full bg-primary/10 px-2.5 py-0.5 text-sm text-primary">{{ unreconciledCount }}</span>
                            </h2>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Seleccionadas: {{ selected.length }} · sin seleccionar: {{ unreconciledCount - selected.length }}
                            </p>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="min-w-full">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-800/60">
                                    <th class="w-12 text-center">
                                        <input
                                            type="checkbox"
                                            class="form-checkbox"
                                            :checked="unreconciled.length > 0 && selected.length === unreconciled.length"
                                            @change="toggleAll"
                                        />
                                    </th>
                                    <th>Fecha</th>
                                    <th>Descripción</th>
                                    <th>Categoría</th>
                                    <th class="text-right">Monto</th>
                                    <th class="w-10"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="unreconciled.length === 0">
                                    <td colspan="6" class="py-8 text-center text-sm text-gray-500">No hay líneas pendientes a esta fecha.</td>
                                </tr>
                                <tr v-for="row in unreconciled" :key="row.id">
                                    <td class="text-center">
                                        <input type="checkbox" class="form-checkbox" :checked="selected.includes(row.id)" @change="toggle(row.id)" />
                                    </td>
                                    <td class="whitespace-nowrap text-sm">{{ row.date }}</td>
                                    <td class="text-sm">
                                        {{ row.description || row.reference || '—' }}
                                        <p class="text-xs text-gray-500">{{ row.source }}</p>
                                    </td>
                                    <td class="text-sm">{{ row.category || '—' }}</td>
                                    <td class="text-right font-semibold" :class="row.type === 'income' ? 'text-success' : 'text-danger'">
                                        {{ row.type === 'income' ? '+' : '−' }} {{ money(row.amount, row.currency) }}
                                    </td>
                                    <td>
                                        <button v-if="row.detail" type="button" class="text-primary" @click="openDetail(row)">
                                            <font-awesome-icon :icon="faCircleInfo" />
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Partidas del extracto -->
                <div class="panel p-5">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Partidas del extracto</h2>
                        <button type="button" class="btn btn-sm btn-outline-primary" @click="openItem">
                            <font-awesome-icon :icon="faPlus" />
                        </button>
                    </div>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Cargos o abonos del banco que todavía no están en el libro.
                    </p>

                    <div class="mt-4 space-y-3">
                        <div v-for="item in items" :key="item.id" class="flex items-start justify-between rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                            <div>
                                <p class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ item.description }}</p>
                                <p class="text-xs text-gray-500">{{ item.type_label }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-semibold">{{ money(item.amount) }}</p>
                                <button type="button" class="text-xs text-danger" @click="removeItem(item)">
                                    <font-awesome-icon :icon="faTrash" /> Quitar
                                </button>
                            </div>
                        </div>
                        <p v-if="items.length === 0" class="py-6 text-center text-sm text-gray-500">
                            Sin partidas registradas.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Historial -->
            <div class="panel overflow-hidden p-0">
                <div class="border-b border-gray-200 p-5 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Actas de conciliación</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Cierres anteriores con su responsable y huella</p>
                </div>
                <div class="table-responsive">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800/60">
                                <th>Corte</th>
                                <th class="text-right">Extracto</th>
                                <th class="text-right">Sistema</th>
                                <th class="text-right">Diferencia</th>
                                <th class="text-center">Líneas</th>
                                <th>Cerró</th>
                                <th>Huella</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="history.length === 0">
                                <td colspan="7" class="py-6 text-center text-sm text-gray-500">Aún no hay conciliaciones cerradas.</td>
                            </tr>
                            <tr v-for="row in history" :key="row.id">
                                <td class="text-sm font-medium">{{ row.cutoff_date }}</td>
                                <td class="text-right text-sm">{{ money(row.statement_balance, row.currency) }}</td>
                                <td class="text-right text-sm">{{ money(row.system_balance, row.currency) }}</td>
                                <td class="text-right text-sm" :class="Math.abs(row.difference) > tolerance ? 'text-danger' : 'text-success'">
                                    {{ money(row.difference, row.currency) }}
                                </td>
                                <td class="text-center text-sm">{{ row.transactions_count }}</td>
                                <td class="text-sm">
                                    {{ row.closed_by || '—' }}
                                    <p class="text-xs text-gray-500">{{ row.closed_at }}</p>
                                </td>
                                <td class="font-mono text-[11px] text-gray-500">{{ (row.snapshot_hash || '').slice(0, 12) }}…</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <ModalMedium :show="itemModal" :on-close="() => (itemModal = false)" :icon="'/img/cuenta-bancaria.png'">
            <template #title>Nueva partida del extracto</template>
            <template #message><span>Cargo o abono que el banco ya aplicó y el libro aún no registra</span></template>
            <template #content>
                <div class="space-y-4">
                    <div>
                        <InputLabel value="Descripción *" class="mb-1.5" />
                        <input v-model="itemForm.description" type="text" class="form-input w-full" placeholder="Ej: Comisión de mantenimiento" />
                        <InputError :message="itemForm.errors.description" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Tipo" class="mb-1.5" />
                            <select v-model="itemForm.type" class="form-select w-full">
                                <option value="expense">Cargo del banco</option>
                                <option value="income">Abono del banco</option>
                            </select>
                        </div>
                        <div>
                            <InputLabel value="Monto *" class="mb-1.5" />
                            <input v-model="itemForm.amount" type="number" min="0.01" step="0.01" class="form-input w-full" />
                            <InputError :message="itemForm.errors.amount" />
                        </div>
                    </div>
                </div>
            </template>
            <template #buttons>
                <PrimaryButton :disabled="itemForm.processing" @click="saveItem">Agregar partida</PrimaryButton>
            </template>
        </ModalMedium>
    </AppLayout>
</template>
