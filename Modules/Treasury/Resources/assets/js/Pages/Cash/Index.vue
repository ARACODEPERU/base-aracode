<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import ModalMedium from '@/Components/ModalMedium.vue';
    import InputLabel from '@/Components/InputLabel.vue';
    import PrimaryButton from '@/Components/PrimaryButton.vue';
    import { Head, Link, router, useForm } from '@inertiajs/vue3';
    import { computed, ref } from 'vue';
    import { faFilter, faCashRegister } from '@fortawesome/free-solid-svg-icons';

    const props = defineProps({
        from: { type: String, default: null },
        to: { type: String, default: null },
        movements: { type: Array, default: () => [] },
        sessions: { type: Array, default: () => [] },
        unsessioned: { type: Array, default: () => [] },
        cashMethods: { type: Array, default: () => [] },
        totals: { type: Object, default: () => ({}) },
        counts: { type: Array, default: () => [] },
        denominations: { type: Array, default: () => [200, 100, 50, 20, 10, 5, 2, 1, 0.5, 0.2, 0.1, 0.05] },
        canCount: { type: Boolean, default: false },
        open: { type: Object, default: () => ({ count: 0, balance: 0, sessions: [] }) },
        ledger: { type: Object, default: null },
    });

    const currency = (value) =>
        new Intl.NumberFormat('es-PE', { style: 'currency', currency: 'PEN' }).format(value || 0);

    const filters = ref({ from: props.from, to: props.to });

    const applyFilters = () => {
        router.get(route('treasury_cash_index'), filters.value, {
            preserveScroll: true,
            preserveState: true,
        });
    };

    const kindLabels = {
        venta: 'Venta',
        cuota: 'Abono de cuota',
        egreso: 'Egreso',
    };

    const kindStyles = {
        venta: 'bg-success/10 text-success',
        cuota: 'bg-primary/10 text-primary',
        egreso: 'bg-danger/10 text-danger',
    };

    // ---------------- Arqueo de caja ----------------
    const countModal = ref(false);
    const countSession = ref(null);
    const countForm = useForm({
        petty_cash_id: null,
        denominations: {},
        notes: '',
        close_session: false,
    });

    const openCount = (session) => {
        const base = {};

        props.denominations.forEach((denomination) => {
            base[String(denomination)] = 0;
        });

        countSession.value = session;
        countForm.clearErrors();
        countForm.petty_cash_id = session.id;
        countForm.denominations = base;
        countForm.notes = '';
        countForm.close_session = !!session.is_open;
        countModal.value = true;
    };

    // Lo contado se calcula en el navegador; el servidor lo vuelve a calcular.
    const countedTotal = computed(() =>
        Math.round(
            props.denominations.reduce(
                (total, denomination) =>
                    total + Number(denomination) * Number(countForm.denominations[String(denomination)] || 0),
                0
            ) * 100
        ) / 100
    );

    const expectedTotal = computed(() => Number(countSession.value?.balance || 0));

    const difference = computed(() => Math.round((countedTotal.value - expectedTotal.value) * 100) / 100);

    const differenceClass = computed(() => {
        if (Math.abs(difference.value) < 0.01) return 'text-success';
        return difference.value < 0 ? 'text-danger' : 'text-warning';
    });

    const saveCount = () => {
        countForm.post(route('treasury_cash_count'), {
            preserveScroll: true,
            onSuccess: (page) => {
                const flash = page?.props?.flash || {};
                countModal.value = false;

                if (flash.error) {
                    window.alertaSistema('No se pudo registrar el arqueo', flash.error, 'error');
                } else if (flash.success) {
                    window.alertaSistema('Arqueo registrado', flash.success, 'success');
                }
            },
            onError: () => window.alertaSistema('Revisa el arqueo', 'Completa las cantidades y revisa la diferencia.', 'warning'),
        });
    };
</script>

<template>
    <AppLayout title="Efectivo en Tesorería">
        <Head title="Tesorería · Efectivo">
            <meta name="aracodito-view" content="new" />
        </Head>
        <Navigation>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Tesorería</span>
            </li>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Efectivo</span>
            </li>
        </Navigation>

        <div class="mt-5 space-y-5">
            <div class="panel p-5">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Efectivo y caja chica</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    El efectivo no entra al libro de bancos: se controla con la caja chica para no duplicar saldos.
                    <span v-if="cashMethods.length">
                        Métodos considerados: <strong>{{ cashMethods.join(', ') }}</strong>.
                    </span>
                </p>
            </div>

            <!-- Cruce con el libro: efectivo esperado en cajas y saldo de su cuenta -->
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div class="panel p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Efectivo en cajas abiertas</p>
                    <p class="mt-2 text-2xl font-bold text-success">{{ currency(open.balance) }}</p>
                    <p class="mt-1 text-xs text-gray-400">
                        {{ open.count }} caja{{ open.count === 1 ? '' : 's' }} abierta{{ open.count === 1 ? '' : 's' }} ·
                        se calcula hasta hoy
                    </p>
                </div>
                <div class="panel p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Saldo en el libro de Tesorería</p>
                    <template v-if="ledger">
                        <p
                            class="mt-2 text-2xl font-bold"
                            :class="ledger.balance >= 0 ? 'text-gray-800 dark:text-gray-100' : 'text-danger'"
                        >
                            {{ currency(ledger.balance) }}
                        </p>
                        <p class="mt-1 text-xs text-gray-400">
                            {{ ledger.label }} · {{ ledger.movements_count }} movimiento{{ ledger.movements_count === 1 ? '' : 's' }} registrado{{ ledger.movements_count === 1 ? '' : 's' }}
                        </p>
                        <Link
                            :href="route('treasury_transactions_index', { account_id: ledger.account_id })"
                            class="btn btn-sm btn-outline-primary mt-3"
                        >
                            Ver movimientos
                        </Link>
                    </template>
                    <p v-else class="mt-2 text-sm text-gray-500">
                        No hay una cuenta de efectivo en el libro. Créala en Cuentas para registrar movimientos manuales.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
                <div class="panel p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Ventas en efectivo</p>
                    <p class="mt-2 text-2xl font-bold text-success">{{ currency(totals.sales) }}</p>
                    <p class="mt-1 text-xs text-gray-400">{{ totals.counts?.sales || 0 }} ventas</p>
                </div>
                <div class="panel p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Abonos de cuotas</p>
                    <p class="mt-2 text-2xl font-bold text-primary">{{ currency(totals.quotas) }}</p>
                    <p class="mt-1 text-xs text-gray-400">{{ totals.counts?.quotas || 0 }} abonos</p>
                </div>
                <div class="panel p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Egresos de caja</p>
                    <p class="mt-2 text-2xl font-bold text-danger">{{ currency(totals.expenses) }}</p>
                    <p class="mt-1 text-xs text-gray-400">{{ totals.counts?.expenses || 0 }} egresos</p>
                </div>
                <div class="panel bg-gradient-to-br from-primary to-primary-dark p-5 text-white">
                    <p class="text-sm opacity-80">Neto del período</p>
                    <p class="mt-2 text-2xl font-bold">{{ currency(totals.net) }}</p>
                    <p class="mt-1 text-xs opacity-70">{{ totals.sessions || 0 }} sesiones de caja</p>
                </div>
            </div>

            <div class="panel flex flex-col gap-4 p-5 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <input v-model="filters.from" type="date" class="form-input sm:w-40" title="Desde" />
                    <input v-model="filters.to" type="date" class="form-input sm:w-40" title="Hasta" />
                    <button type="button" class="btn btn-outline-primary" @click="applyFilters">
                        <font-awesome-icon :icon="faFilter" class="mr-1" /> Filtrar
                    </button>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Solo soles: las ventas en moneda extranjera no se suman a la caja.
                </p>
            </div>

            <div class="panel overflow-hidden p-0">
                <div class="table-responsive">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800/60">
                                <th class="w-28">Fecha</th>
                                <th class="w-40">Tipo</th>
                                <th class="min-w-[220px]">Comprobante</th>
                                <th class="min-w-[200px]">Cliente / Referencia</th>
                                <th class="w-32">Caja</th>
                                <th class="w-36 text-right">Monto</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="movements.length === 0">
                                <td colspan="6" class="py-8 text-center text-sm text-gray-500">
                                    Sin movimientos de efectivo en el período seleccionado.
                                </td>
                            </tr>
                            <tr
                                v-for="(row, index) in movements"
                                :key="`${row.kind}-${index}`"
                                class="hover:bg-gray-50/70 dark:hover:bg-gray-800/30"
                            >
                                <td class="text-gray-600 dark:text-gray-400">{{ row.date || '—' }}</td>
                                <td>
                                    <span class="rounded-md px-2 py-1 text-xs font-medium" :class="kindStyles[row.kind]">
                                        {{ kindLabels[row.kind] || row.label }}
                                    </span>
                                </td>
                                <td class="text-gray-800 dark:text-gray-100">{{ row.document || '—' }}</td>
                                <td>
                                    <p class="text-gray-700 dark:text-gray-300">{{ row.client || '—' }}</p>
                                    <p v-if="row.reference" class="text-xs text-gray-400">{{ row.reference }}</p>
                                </td>
                                <td class="text-gray-600 dark:text-gray-400">
                                    {{ row.petty_cash_id ? `#${row.petty_cash_id}` : 'Sin caja' }}
                                </td>
                                <td
                                    class="text-right font-semibold"
                                    :class="row.kind === 'egreso' ? 'text-danger' : 'text-success'"
                                >
                                    {{ row.kind === 'egreso' ? '−' : '+' }} {{ currency(row.amount) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-if="unsessioned.length" class="panel border-l-4 border-warning p-5">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white">
                    Efectivo sin sesión de caja ({{ totals.unsessioned_count }})
                </h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Estas ventas cobraron en efectivo pero no quedaron asociadas a una caja abierta:
                    total {{ currency(totals.unsessioned) }}. Revísalas con el cajero del turno.
                </p>
                <ul class="mt-3 space-y-1 text-sm text-gray-600 dark:text-gray-300">
                    <li v-for="row in unsessioned" :key="`u-${row.kind}-${row.reference}`">
                        {{ row.date }} · {{ row.document || row.reference }} · {{ currency(row.amount) }}
                    </li>
                </ul>
            </div>

            <div class="panel overflow-hidden p-0">
                <div class="border-b border-gray-200 p-5 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Sesiones de caja</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Saldo calculado: apertura + ventas + abonos − egresos del período. «Arquear» cuenta los
                        billetes y monedas y, si lo pides, cierra la sesión con el dinero contado.
                    </p>
                </div>
                <div class="table-responsive">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800/60">
                                <th class="w-24">Caja</th>
                                <th class="w-32">Apertura</th>
                                <th class="w-32">Cierre</th>
                                <th class="min-w-[160px]">Cajero</th>
                                <th class="w-32 text-right">Apertura S/</th>
                                <th class="w-32 text-right">Ventas</th>
                                <th class="w-32 text-right">Egresos</th>
                                <th class="w-32 text-right">Saldo</th>
                                <th class="w-28 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="sessions.length === 0">
                                <td colspan="9" class="py-8 text-center text-sm text-gray-500">
                                    Sin sesiones de caja en el período.
                                </td>
                            </tr>
                            <tr v-for="session in sessions" :key="session.id">
                                <td class="text-gray-600 dark:text-gray-400">
                                    #{{ session.id }}
                                    <span v-if="session.is_open" class="ml-1 rounded bg-success/10 px-1.5 py-0.5 text-xs text-success">Abierta</span>
                                </td>
                                <td class="text-gray-600 dark:text-gray-400">{{ session.opening || '—' }}</td>
                                <td class="text-gray-600 dark:text-gray-400">{{ session.closed || '—' }}</td>
                                <td class="text-gray-700 dark:text-gray-300">{{ session.seller || '—' }}</td>
                                <td class="text-right">{{ currency(session.beginning_balance) }}</td>
                                <td class="text-right text-success">{{ currency(session.sales + session.quotas) }}</td>
                                <td class="text-right text-danger">{{ currency(session.expenses) }}</td>
                                <td class="text-right font-bold">{{ currency(session.balance) }}</td>
                                <td class="text-right">
                                    <button
                                        v-if="canCount"
                                        type="button"
                                        class="btn btn-sm btn-outline-primary"
                                        title="Arquear caja"
                                        @click="openCount(session)"
                                    >
                                        <font-awesome-icon :icon="faCashRegister" />
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="panel overflow-hidden p-0">
                <div class="border-b border-gray-200 p-5 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Arqueos registrados</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Cada arqueo es inmutable. La diferencia es lo contado menos lo esperado por el sistema.
                    </p>
                </div>
                <div class="table-responsive">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800/60">
                                <th>Fecha</th>
                                <th>Sesión</th>
                                <th class="text-right">Esperado</th>
                                <th class="text-right">Contado</th>
                                <th class="text-right">Diferencia</th>
                                <th class="text-center">Cierre</th>
                                <th class="min-w-[180px]">Nota</th>
                                <th>Usuario</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="counts.length === 0">
                                <td colspan="8" class="py-8 text-center text-sm text-gray-500">
                                    Todavía no hay arqueos registrados.
                                </td>
                            </tr>
                            <tr v-for="count in counts" :key="count.id">
                                <td class="text-sm">{{ count.cutoff_date }}</td>
                                <td class="text-sm">{{ count.session }}</td>
                                <td class="text-right text-sm">{{ currency(count.expected_amount) }}</td>
                                <td class="text-right text-sm">{{ currency(count.counted_amount) }}</td>
                                <td
                                    class="text-right text-sm font-semibold"
                                    :class="Math.abs(count.difference) < 0.01 ? 'text-success' : 'text-danger'"
                                >
                                    {{ currency(count.difference) }}
                                </td>
                                <td class="text-center">
                                    <span
                                        class="rounded-md px-2 py-1 text-xs"
                                        :class="count.closed_session ? 'bg-success/10 text-success' : 'bg-gray-100 text-gray-500 dark:bg-gray-700'"
                                    >
                                        {{ count.closed_session ? 'Cerró caja' : 'Solo arqueo' }}
                                    </span>
                                </td>
                                <td class="text-xs text-gray-500">{{ count.notes || '—' }}</td>
                                <td class="text-sm">{{ count.created_by || '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <ModalMedium :show="countModal" :on-close="() => (countModal = false)" :icon="'/img/cuenta-bancaria.png'">
            <template #title>Arqueo de caja #{{ countSession?.id }}</template>
            <template #message>
                <span>Cuenta los billetes y monedas. Si hay diferencia, explica el motivo.</span>
            </template>
            <template #content>
                <div class="space-y-4">
                    <div class="flex items-center justify-between rounded-lg border border-gray-200 p-3 text-sm dark:border-gray-700">
                        <span class="text-gray-500 dark:text-gray-400">Saldo esperado del sistema</span>
                        <strong>{{ currency(expectedTotal) }}</strong>
                    </div>

                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                        <div v-for="denomination in denominations" :key="denomination">
                            <InputLabel :value="currency(denomination)" class="mb-1 text-xs" />
                            <input
                                v-model.number="countForm.denominations[String(denomination)]"
                                type="number"
                                min="0"
                                step="1"
                                class="form-input w-full text-right"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 rounded-lg bg-gray-50 p-3 text-sm dark:bg-gray-800/60">
                        <div>
                            <p class="text-gray-500 dark:text-gray-400">Total contado</p>
                            <p class="text-lg font-bold text-primary">{{ currency(countedTotal) }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500 dark:text-gray-400">Diferencia</p>
                            <p class="text-lg font-bold" :class="differenceClass">{{ currency(difference) }}</p>
                            <p class="text-xs text-gray-500">
                                {{ Math.abs(difference) < 0.01 ? 'Cuadra' : difference < 0 ? 'Faltante' : 'Sobrante' }}
                            </p>
                        </div>
                    </div>

                    <div>
                        <InputLabel value="Nota del arqueo" class="mb-1.5" />
                        <textarea
                            v-model="countForm.notes"
                            rows="2"
                            class="form-textarea w-full"
                            placeholder="Obligatoria si hay faltante o sobrante"
                        ></textarea>
                    </div>

                    <label v-if="countSession?.is_open" class="flex items-center gap-2 text-sm">
                        <input v-model="countForm.close_session" type="checkbox" class="form-checkbox" />
                        <span>Cerrar la sesión de caja con el dinero contado</span>
                    </label>
                </div>
            </template>
            <template #buttons>
                <PrimaryButton :disabled="countForm.processing" @click="saveCount">Registrar arqueo</PrimaryButton>
            </template>
        </ModalMedium>
    </AppLayout>
</template>
