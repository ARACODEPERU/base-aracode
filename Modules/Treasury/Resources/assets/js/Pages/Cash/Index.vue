<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import { Head, router } from '@inertiajs/vue3';
    import { ref } from 'vue';
    import { faFilter } from '@fortawesome/free-solid-svg-icons';

    const props = defineProps({
        from: { type: String, default: null },
        to: { type: String, default: null },
        movements: { type: Array, default: () => [] },
        sessions: { type: Array, default: () => [] },
        unsessioned: { type: Array, default: () => [] },
        cashMethods: { type: Array, default: () => [] },
        totals: { type: Object, default: () => ({}) },
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
</script>

<template>
    <AppLayout title="Efectivo en Tesorería">
        <Head title="Tesorería · Efectivo" />
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
                        Saldo calculado: apertura + ventas + abonos − egresos del período.
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
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="sessions.length === 0">
                                <td colspan="8" class="py-8 text-center text-sm text-gray-500">
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
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
