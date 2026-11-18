<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import { Head, router, useForm } from '@inertiajs/vue3';
    import ModalMedium from '@/Components/ModalMedium.vue';
    import InputLabel from '@/Components/InputLabel.vue';
    import InputError from '@/Components/InputError.vue';
    import PrimaryButton from '@/Components/PrimaryButton.vue';
    import { computed, ref } from 'vue';
    import { faFilter, faCircleCheck, faLock, faPlus, faTrash, faCircleInfo, faFileImport } from '@fortawesome/free-solid-svg-icons';
    import StatementImportModal from 'Modules/Treasury/Resources/assets/js/Components/StatementImportModal.vue';

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
        canImport: { type: Boolean, default: false },
        imports: { type: Array, default: () => [] },
        comparison: { type: Object, default: null },
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

    // Monto con su signo real: los egresos son negativos, los ingresos positivos.
    const signedAmount = (line) => Math.abs(Number(line.amount || 0)) * (line.type === 'income' ? 1 : -1);

    // La hora llega como HH:MM:SS; en pantalla basta HH:MM.
    const horaTexto = (value) => (value ? String(value).slice(0, 5) : '');

    // Las líneas del extracto llegan como Y-m-d; los avisos usan d/m/Y.
    const fechaTexto = (value) => {
        const [year, month, day] = String(value || '').split('-');

        return year && month && day ? `${day}/${month}/${year}` : value || '';
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

    // Importación de extracto: se revisa el mapeo y luego se aplica.
    const importModal = ref(false);

    const abrirImportacion = () => {
        if (!props.selectedAccountId) {
            window.alertaSistema('Falta la cuenta', 'Elige la cuenta a conciliar antes de importar el extracto.', 'warning');

            return;
        }

        importModal.value = true;
    };

    const importacionAplicada = (result) => {
        router.reload({
            preserveScroll: true,
            onSuccess: () => window.alertaSistema(
                'Extracto importado',
                `${result.rows_total} línea(s): ${result.rows_matched} emparejada(s) y ${result.rows_created} partida(s) nueva(s). Revisa las líneas y cierra la conciliación cuando cuadre.`,
                'success'
            ),
        });
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
            `Se cerrará el día ${props.until} con saldo de banco ${money(statement.value)}. Las ${props.unreconciledCount} líneas pendientes quedarán bloqueadas para siempre y se guardará quién cerró.`,
            'warning',
            { duration: 0, showCancelButton: true, confirmButtonText: 'Sí, cerrar', cancelButtonText: 'Cancelar' }
        );

        if (!result?.isConfirmed) return;

        router.post(route('treasury_reconciliation_close'), {
            treasury_account_id: props.selectedAccountId,
            until: props.until,
            statement_balance: statement.value,
        }, {
            preserveScroll: true,
            onSuccess: notifyFlash,
            onError: () => window.alertaSistema('No se pudo cerrar', 'Revisa el saldo y las partidas del extracto.', 'error'),
        });
    };

    // Línea del extracto sin par: se registra como movimiento del libro.
    const registrarMovimiento = async (line) => {
        const result = await window.alertaSistema(
            'Registrar movimiento',
            `Se registrará en la cuenta un ${line.type === 'income' ? 'ingreso' : 'egreso'} de ${money(signedAmount(line))} con fecha ${fechaTexto(line.date)}${line.time ? ` a las ${horaTexto(line.time)}` : ''}. La partida de ajuste se retira porque el movimiento pasa al libro.`,
            'warning',
            { duration: 0, showCancelButton: true, confirmButtonText: 'Sí, registrar', cancelButtonText: 'Cancelar' }
        );

        if (!result?.isConfirmed) return;

        router.post(route('treasury_reconciliation_statement_movement', props.comparison.import_id), {
            line_index: line.index,
        }, {
            preserveScroll: true,
            onSuccess: (page) => {
                selectedLines.value = selectedLines.value.filter((value) => value !== line.index);
                notifyFlash(page);
            },
        });
    };

    // El par recomendado no siempre es el mismo movimiento: el encargado decide.
    const marcarDiferentes = async (line) => {
        const bankTime = line.time ? ` a las ${horaTexto(line.time)}` : '';
        const bookTime = line.transaction?.time ? ` a las ${horaTexto(line.transaction.time)}` : '';

        const result = await window.alertaSistema(
            'Son movimientos diferentes',
            `El movimiento del libro (${line.transaction?.date || ''}${bookTime} · ${money(Math.abs(Number(line.transaction?.amount || 0)))} ${line.type === 'income' ? 'ingreso' : 'egreso'}) se queda como está. `
            + `La línea del banco (${fechaTexto(line.date)}${bankTime} · ${money(signedAmount(line))}) pasará a «Sin par» para que la registres como movimiento nuevo. ¿Continuar?`,
            'warning',
            { duration: 0, showCancelButton: true, confirmButtonText: 'Sí, son diferentes', cancelButtonText: 'Cancelar' }
        );

        if (!result?.isConfirmed) return;

        router.post(
            route('treasury_reconciliation_statement_unlink', [props.comparison.import_id, line.index]),
            {},
            { preserveScroll: true, onSuccess: notifyFlash }
        );
    };

    // Varias líneas sin par: se seleccionan y se registran de una sola vez.
    const selectedLines = ref([]);

    const pendingLines = computed(() => (props.comparison?.lines || []).filter((line) => line.status === 'pending'));

    const allLinesSelected = computed(() => pendingLines.value.length > 0
        && selectedLines.value.length === pendingLines.value.length);

    const toggleLine = (index) => {
        selectedLines.value = selectedLines.value.includes(index)
            ? selectedLines.value.filter((value) => value !== index)
            : [...selectedLines.value, index];
    };

    const toggleAllLines = (event) => {
        selectedLines.value = event.target.checked ? pendingLines.value.map((line) => line.index) : [];
    };

    const registrarSeleccionados = async () => {
        if (!selectedLines.value.length) return;

        const lines = pendingLines.value.filter((line) => selectedLines.value.includes(line.index));
        const incomes = lines.filter((line) => line.type === 'income');
        const expenses = lines.filter((line) => line.type !== 'income');
        const incomeTotal = incomes.reduce((acumulado, line) => acumulado + signedAmount(line), 0);
        const expenseTotal = expenses.reduce((acumulado, line) => acumulado + signedAmount(line), 0);

        const partes = [];
        if (incomes.length) partes.push(`${incomes.length} ingreso(s) por ${money(incomeTotal)}`);
        if (expenses.length) partes.push(`${expenses.length} egreso(s) por ${money(expenseTotal)}`);

        // El neto solo se menciona cuando hay ambos tipos, para no confundir
        // un total de ingresos con un total de egresos.
        const neto = incomes.length && expenses.length
            ? ` El neto es ${money(incomeTotal + expenseTotal)}.`
            : '';

        const result = await window.alertaSistema(
            'Registrar movimientos',
            `Se registrarán ${lines.length} movimiento(s) del extracto: ${partes.join(' y ')}.${neto} Las partidas de ajuste correspondientes se retiran porque el movimiento pasa al libro.`,
            'warning',
            { duration: 0, showCancelButton: true, confirmButtonText: 'Sí, registrar', cancelButtonText: 'Cancelar' }
        );

        if (!result?.isConfirmed) return;

        router.post(route('treasury_reconciliation_statement_movements', props.comparison.import_id), {
            line_indexes: selectedLines.value,
        }, {
            preserveScroll: true,
            onSuccess: (page) => {
                selectedLines.value = [];
                notifyFlash(page);
            },
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

                <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Saldo real del banco ({{ accountCurrency }})</label>
                        <input
                            v-model="realBalance"
                            type="number"
                            step="0.01"
                            class="form-input w-full sm:w-48"
                            placeholder="Monto según extracto…"
                        />
                    </div>
                    <button
                        v-if="canImport"
                        type="button"
                        class="btn btn-outline-primary whitespace-nowrap disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="!selectedAccountId"
                        title="Leer un extracto CSV, Excel u OFX y emparejar por monto, fecha y referencia"
                        @click="abrirImportacion"
                    >
                        <font-awesome-icon :icon="faFileImport" class="mr-1" /> Importar extracto
                    </button>
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

            <!-- Comparación del extracto contra el libro -->
            <div v-if="comparison" class="panel overflow-hidden p-0">
                <div class="flex flex-col gap-3 border-b border-gray-200 p-5 sm:flex-row sm:items-start sm:justify-between dark:border-gray-700">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Comparación con el extracto</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {{ comparison.file_name }} · {{ comparison.imported_at }} · movimientos del libro
                            del {{ comparison.from }} al {{ comparison.to }} (±{{ comparison.margin_days }} días del extracto).
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 text-xs">
                        <button
                            v-if="canImport && selectedLines.length"
                            type="button"
                            class="btn btn-sm btn-primary whitespace-nowrap"
                            :title="`Registrar ${selectedLines.length} línea(s) seleccionada(s) en el libro`"
                            @click="registrarSeleccionados"
                        >
                            <font-awesome-icon :icon="faPlus" class="mr-1" />
                            Registrar seleccionados ({{ selectedLines.length }})
                        </button>
                        <span class="rounded-full bg-success/10 px-2.5 py-1 font-medium text-success">
                            {{ comparison.accepted }} aceptada(s)
                        </span>
                        <span class="rounded-full bg-warning/10 px-2.5 py-1 font-medium text-warning">
                            {{ comparison.pending }} sin par
                        </span>
                        <span v-if="comparison.created" class="rounded-full bg-info/10 px-2.5 py-1 font-medium text-info">
                            {{ comparison.created }} creada(s)
                        </span>
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
                                        :checked="allLinesSelected"
                                        :disabled="pendingLines.length === 0"
                                        title="Seleccionar todas las líneas sin par"
                                        @change="toggleAllLines"
                                    />
                                </th>
                                <th>Fecha banco</th>
                                <th class="min-w-[180px]">Descripción del banco</th>
                                <th class="text-right">Monto</th>
                                <th class="min-w-[200px]">Movimiento del libro</th>
                                <th>Estado</th>
                                <th class="text-right">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="line in comparison.lines" :key="line.index">
                                <td class="text-center">
                                    <input
                                        v-if="line.status === 'pending' && canImport"
                                        type="checkbox"
                                        class="form-checkbox"
                                        :checked="selectedLines.includes(line.index)"
                                        @change="toggleLine(line.index)"
                                    />
                                </td>
                                <td class="whitespace-nowrap text-sm">
                                    {{ line.date }}<span v-if="line.time" class="text-xs text-gray-500"> {{ horaTexto(line.time) }}</span>
                                </td>
                                <td class="text-sm">
                                    {{ line.description }}
                                    <p v-if="line.reference" class="text-xs text-gray-500">Ref. {{ line.reference }}</p>
                                    <p v-if="line.operation_number" class="text-xs text-gray-500">Op. {{ line.operation_number }}</p>
                                </td>
                                <td
                                    class="whitespace-nowrap text-right font-semibold"
                                    :class="line.type === 'income' ? 'text-success' : 'text-danger'"
                                >
                                    {{ line.type === 'income' ? '+' : '−' }} {{ money(line.amount) }}
                                </td>
                                <td class="text-sm">
                                    <template v-if="line.transaction">
                                        {{ line.transaction.date }}<span v-if="line.transaction.time" class="text-xs text-gray-500"> {{ horaTexto(line.transaction.time) }}</span>
                                        · {{ line.transaction.description }}
                                        <p v-if="line.transaction.operation_number" class="text-xs text-gray-500">
                                            Op. {{ line.transaction.operation_number }}
                                        </p>
                                        <p v-if="line.operation_match" class="text-xs font-medium text-success">
                                            Mismo N° de operación: es este movimiento
                                        </p>
                                    </template>
                                    <span v-else class="text-gray-400">Sin movimiento en el libro</span>
                                    <p v-if="line.time && line.transaction?.time && horaTexto(line.time) !== horaTexto(line.transaction.time)" class="text-xs font-medium text-warning">
                                        Hora distinta a la del libro
                                    </p>
                                </td>
                                <td class="whitespace-nowrap">
                                    <span v-if="line.status === 'accepted'" class="inline-flex items-center gap-1 rounded-md bg-success/10 px-2 py-1 text-xs font-medium text-success">
                                        <font-awesome-icon :icon="faCircleCheck" /> Aceptado
                                    </span>
                                    <span v-else-if="line.status === 'created'" class="rounded-md bg-info/10 px-2 py-1 text-xs font-medium text-info">
                                        Movimiento creado
                                    </span>
                                    <span v-else class="rounded-md bg-warning/10 px-2 py-1 text-xs font-medium text-warning">
                                        Sin par
                                    </span>
                                </td>
                                <td class="space-y-1 text-right">
                                    <button
                                        v-if="line.status === 'accepted' && canImport && !line.operation_match"
                                        type="button"
                                        class="whitespace-nowrap text-xs font-medium text-danger hover:underline"
                                        title="El movimiento del libro se queda; esta línea del banco se registrará aparte"
                                        @click="marcarDiferentes(line)"
                                    >
                                        Son movimientos diferentes
                                    </button>
                                    <button
                                        v-if="line.status === 'pending' && canImport"
                                        type="button"
                                        class="btn btn-sm btn-outline-primary whitespace-nowrap"
                                        :title="`Registrar ${line.type === 'income' ? 'ingreso' : 'egreso'} en el libro con la fecha del banco`"
                                        @click="registrarMovimiento(line)"
                                    >
                                        <font-awesome-icon :icon="faPlus" class="mr-1" /> Registrar movimiento
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="comparison.book_unmatched.length" class="border-t border-gray-200 p-5 dark:border-gray-700">
                    <p class="text-xs font-semibold uppercase text-gray-500">
                        Movimientos del libro que el extracto no trae ({{ comparison.book_unmatched.length }})
                    </p>
                    <ul class="mt-2 divide-y divide-gray-100 dark:divide-gray-700">
                        <li v-for="row in comparison.book_unmatched" :key="row.id" class="flex items-center justify-between gap-3 py-2 text-sm">
                            <span class="truncate">
                                {{ row.date }} · {{ row.description }}
                                <span v-if="row.operation_number" class="text-gray-400">· Op. {{ row.operation_number }}</span>
                            </span>
                            <span class="whitespace-nowrap font-semibold" :class="row.type === 'income' ? 'text-success' : 'text-danger'">
                                {{ row.type === 'income' ? '+' : '−' }} {{ money(row.amount) }}
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
            <div v-else-if="selectedAccountId" class="panel p-5 text-sm text-gray-500 dark:text-gray-400">
                Importa un extracto para comparar los movimientos del banco con los del libro en el mismo rango de fechas.
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
                                Son movimientos del libro pendientes de conciliar. Al cerrar la conciliación, todos los de
                                esta lista hasta la fecha de corte quedan bloqueados para siempre y se guarda quién cerró.
                            </p>
                        </div>
                        <button
                            v-if="canClose"
                            type="button"
                            class="btn btn-primary shrink-0 whitespace-nowrap disabled:cursor-not-allowed disabled:opacity-50"
                            :disabled="statement === null || !isBalanced"
                            :title="statement === null ? 'Ingresa el saldo del banco para cerrar' : (!isBalanced ? 'La conciliación debe cuadrar para cerrar' : 'Cerrar y bloquear las líneas')"
                            @click="closeReconciliation"
                        >
                            Cerrar conciliación
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="min-w-full">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-800/60">
                                    <th>Fecha</th>
                                    <th>Descripción</th>
                                    <th>Categoría</th>
                                    <th class="text-right">Monto</th>
                                    <th class="w-10"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="unreconciled.length === 0">
                                    <td colspan="5" class="py-8 text-center text-sm text-gray-500">No hay líneas pendientes a esta fecha.</td>
                                </tr>
                                <tr v-for="row in unreconciled" :key="row.id">
                                    <td class="whitespace-nowrap text-sm">{{ row.date }}</td>
                                    <td class="text-sm">
                                        {{ row.description || row.reference || '—' }}
                                        <p v-if="row.operation_number" class="text-xs text-gray-500">Op. {{ row.operation_number }}</p>
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

                    <!-- Trazabilidad de los extractos leídos -->
                    <div v-if="imports.length" class="mt-5 border-t border-gray-200 pt-4 dark:border-gray-700">
                        <p class="text-xs font-semibold uppercase text-gray-500">Extractos importados</p>
                        <ul class="mt-2 space-y-2">
                            <li v-for="row in imports" :key="row.id" class="text-xs text-gray-600 dark:text-gray-300">
                                <p class="truncate font-medium">{{ row.file_name }}</p>
                                <p class="text-gray-500">
                                    {{ row.format }} · {{ row.rows_total }} línea(s) · {{ row.rows_matched }} emparejada(s)
                                    · {{ row.rows_created }} partida(s) · {{ row.created_at }}
                                </p>
                            </li>
                        </ul>
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

        <StatementImportModal
            :show="importModal"
            :account-id="selectedAccountId"
            :until="until"
            :currency="accountCurrency"
            @close="importModal = false"
            @applied="importacionAplicada"
        />
    </AppLayout>
</template>
