<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import { Head } from '@inertiajs/vue3';
    import { computed } from 'vue';
    import { faWallet, faLandmark, faArrowTrendUp, faArrowTrendDown, faFileInvoiceDollar, faTriangleExclamation, faCircleInfo, faBell, faCashRegister } from '@fortawesome/free-solid-svg-icons';

    const props = defineProps({
        accounts: { type: Array, default: () => [] },
        groups: { type: Array, default: () => [] },
        consolidated: { type: Number, default: 0 },
        detraction: { type: Number, default: 0 },
        detractionAccount: { type: Object, default: null },
        rates: { type: Array, default: () => [] },
        ratesIncomplete: { type: Boolean, default: false },
        baseCurrency: { type: String, default: 'PEN' },
        monthIncomes: { type: Number, default: 0 },
        monthExpenses: { type: Number, default: 0 },
        monthByCurrency: { type: Array, default: () => [] },
        byCategory: { type: Array, default: () => [] },
        recent: { type: Array, default: () => [] },
        pendingOrders: { type: Object, default: () => ({ count: 0, total_base: 0 }) },
        walletTransfers: { type: Object, default: () => ({ count: 0, total: 0, rows: [], groups: [], next_date: null }) },
        cashOpen: { type: Object, default: () => ({ count: 0, balance: 0 }) },
        settings: { type: Object, default: () => ({ enabled: true, starts_on: null }) },
        alerts: { type: Array, default: () => [] },
    });

    const alertStyles = {
        danger: 'border-danger/40 bg-danger/5 text-danger',
        warning: 'border-warning/40 bg-warning/5 text-warning',
        info: 'border-info/40 bg-info/5 text-info',
    };

    // Fecha de corte del módulo en formato dd/mm/aaaa.
    const fechaCorte = (value) => {
        if (!value) return null;
        const [year, month, day] = String(value).split('-');
        return year && month && day ? `${day}/${month}/${year}` : value;
    };

    // Fecha dd/mm/aaaa para la cola de traslados de billetera.
    const fecha = (value) => {
        if (!value) return '—';
        const [year, month, day] = String(value).split('-');
        return year && month && day ? `${day}/${month}/${year}` : value;
    };

    // Si la fecha de inicio está en el futuro, el módulo aún no muestra saldos.
    const corteFuturo = computed(() => {
        const hoy = new Date().toISOString().slice(0, 10);
        return Boolean(props.settings.starts_on) && props.settings.starts_on > hoy;
    });

    const money = (value, currency = 'PEN') => {
        try {
            return new Intl.NumberFormat('es-PE', { style: 'currency', currency }).format(value || 0);
        } catch (e) {
            return `${currency} ${Number(value || 0).toFixed(2)}`;
        }
    };

    const accountIcon = (type) => (type === 'bank' ? faLandmark : faWallet);

    const maxCategoryTotal = Math.max(...props.byCategory.map((c) => Math.abs(c.total_base)), 1);

    const sourceLabels = {
        auto: 'Automático',
        manual: 'Manual',
        backfill: 'Backfill',
    };
</script>

<template>
    <AppLayout title="Tesorería">
        <Head title="Tesorería">
            <meta name="aracodito-view" content="new" />
        </Head>
        <Navigation>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Tesorería</span>
            </li>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Dashboard</span>
            </li>
        </Navigation>

        <div class="mt-5 space-y-5">
            <!-- Desde cuándo cuenta Tesorería -->
            <div v-if="fechaCorte(settings.starts_on)" class="panel border-l-4 border-info p-4 text-sm text-gray-600 dark:text-gray-300">
                <font-awesome-icon :icon="faCircleInfo" class="mr-1 text-info" />
                <template v-if="corteFuturo">
                    Tesorería empezará a contar el <strong>{{ fechaCorte(settings.starts_on) }}</strong>:
                    hasta ese día no se muestran saldos ni movimientos.
                </template>
                <template v-else>
                    Tesorería cuenta desde el <strong>{{ fechaCorte(settings.starts_on) }}</strong>: los movimientos anteriores
                    no suman en saldos, proyecciones ni reportes.
                </template>
            </div>

            <!-- Dinero restringido: la detracción no es caja libre -->
            <div v-if="detraction || detractionAccount" class="panel flex flex-col gap-3 border-l-4 border-warning p-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-start gap-3">
                    <font-awesome-icon :icon="faLandmark" class="mt-1 text-warning" />
                    <div>
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-100">
                            Detracción (solo IGV): {{ money(detraction, baseCurrency) }}
                        </p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-300">
                            No suma al consolidado porque solo puede pagar IGV.
                            <template v-if="detractionAccount?.account_number">
                                Cuenta {{ detractionAccount.bank_name || 'Banco de la Nación' }} ·
                                <span class="font-mono">{{ detractionAccount.account_number }}</span>
                            </template>
                        </p>
                    </div>
                </div>
                <a :href="route('treasury_detraction_index')" class="btn btn-sm btn-outline-warning whitespace-nowrap">
                    Ver detracción
                </a>
            </div>

            <!-- Alertas automáticas -->
            <div v-if="alerts.length" class="panel overflow-hidden p-0">
                <div class="flex items-center justify-between border-b border-gray-200 p-5 dark:border-gray-700">
                    <div class="flex items-center gap-2">
                        <font-awesome-icon :icon="faBell" class="text-warning" />
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Alertas de Tesorería</h2>
                    </div>
                    <span class="rounded-md bg-warning/10 px-2 py-1 text-xs font-medium text-warning">{{ alerts.length }}</span>
                </div>
                <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                    <li
                        v-for="alert in alerts"
                        :key="alert.code"
                        class="flex flex-col gap-2 border-l-4 p-4 sm:flex-row sm:items-center sm:justify-between"
                        :class="alertStyles[alert.severity] || alertStyles.info"
                    >
                        <div class="flex items-start gap-3">
                            <font-awesome-icon :icon="faTriangleExclamation" class="mt-1 shrink-0" />
                            <div>
                                <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ alert.title }}</p>
                                <p class="mt-0.5 text-xs text-gray-600 dark:text-gray-300">{{ alert.message }}</p>
                            </div>
                        </div>
                        <a
                            v-if="alert.route"
                            :href="route(alert.route)"
                            class="btn btn-sm btn-outline-primary shrink-0 whitespace-nowrap"
                        >
                            Revisar
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Resumen principal -->
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
                <div class="panel bg-gradient-to-br from-primary to-primary-dark text-white">
                    <div class="flex items-start justify-between p-5">
                        <div>
                            <p class="text-sm opacity-80">Consolidado en {{ baseCurrency }}</p>
                            <p class="mt-2 text-3xl font-bold">{{ money(consolidated, baseCurrency) }}</p>
                            <p v-if="ratesIncomplete" class="mt-1 text-xs text-warning">Faltan tipos de cambio</p>
                        </div>
                        <div class="rounded-full bg-white/20 p-3">
                            <font-awesome-icon :icon="faWallet" class="text-xl" />
                        </div>
                    </div>
                </div>

                <div class="panel">
                    <div class="flex items-start justify-between p-5">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Ingresos del mes (S/)</p>
                            <p class="mt-2 text-2xl font-bold text-success">{{ money(monthIncomes, baseCurrency) }}</p>
                        </div>
                        <div class="rounded-full bg-success/10 p-3 text-success">
                            <font-awesome-icon :icon="faArrowTrendUp" class="text-xl" />
                        </div>
                    </div>
                </div>

                <div class="panel">
                    <div class="flex items-start justify-between p-5">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Egresos del mes (S/)</p>
                            <p class="mt-2 text-2xl font-bold text-danger">{{ money(monthExpenses, baseCurrency) }}</p>
                        </div>
                        <div class="rounded-full bg-danger/10 p-3 text-danger">
                            <font-awesome-icon :icon="faArrowTrendDown" class="text-xl" />
                        </div>
                    </div>
                </div>

                <div class="panel">
                    <div class="flex items-start justify-between p-5">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Aprobadas por pagar</p>
                            <p class="mt-2 text-2xl font-bold text-gray-800 dark:text-gray-100">{{ pendingOrders.count }}</p>
                            <p class="mt-1 text-xs text-gray-500">{{ money(pendingOrders.total_base, baseCurrency) }}</p>
                        </div>
                        <div class="rounded-full bg-primary/10 p-3 text-primary">
                            <font-awesome-icon :icon="faFileInvoiceDollar" class="text-xl" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Saldos por moneda -->
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
                <div v-for="group in groups" :key="group.currency" class="panel p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Saldo en {{ group.currency }}</p>
                    <p class="mt-2 text-2xl font-bold text-gray-800 dark:text-gray-100">{{ money(group.total, group.currency) }}</p>
                    <p class="mt-1 text-xs text-gray-500">{{ group.accounts }} cuenta(s)</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
                <!-- Saldos por cuenta -->
                <div class="panel p-5">
                    <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white">Saldos por cuenta</h2>
                    <div v-if="accounts.length === 0" class="py-8 text-center text-sm text-gray-500">
                        No hay cuentas registradas. Crea cuentas en la sección Cuentas.
                    </div>
                    <ul v-else class="space-y-3">
                        <li
                            v-for="account in accounts"
                            :key="account.id"
                            class="flex items-center justify-between rounded-lg border border-gray-200 p-3 dark:border-gray-700"
                        >
                            <div class="flex items-center gap-3">
                                <div class="rounded-lg bg-primary/10 p-2 text-primary">
                                    <font-awesome-icon :icon="accountIcon(account.type)" />
                                </div>
                                <div>
                                    <p class="font-medium text-gray-800 dark:text-gray-100">{{ account.label }}</p>
                                    <p class="text-xs text-gray-500">{{ account.currency }}</p>
                                </div>
                            </div>
                            <p v-if="account.hides_balance" class="text-right text-xs text-gray-400">
                                Saldo en su banco vinculado
                            </p>
                            <p
                                v-else
                                class="font-bold"
                                :class="account.balance >= 0 ? 'text-gray-800 dark:text-gray-100' : 'text-danger'"
                            >
                                {{ money(account.balance, account.currency) }}
                            </p>
                        </li>
                    </ul>

                    <div v-if="rates.length" class="mt-4 border-t border-gray-200 pt-3 text-xs text-gray-500 dark:border-gray-700">
                        <p v-for="rate in rates" :key="rate.currency" class="flex items-center gap-1">
                            <font-awesome-icon v-if="rate.is_stale" :icon="faTriangleExclamation" class="text-warning" />
                            TC {{ rate.currency }}: {{ rate.rate }} ({{ rate.date }})
                        </p>
                    </div>
                </div>

                <!-- Movimientos por categoría -->
                <div class="panel p-5">
                    <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white">Movimientos del mes por categoría</h2>

                    <div v-if="byCategory.length === 0" class="py-8 text-center text-sm text-gray-500">
                        Sin movimientos este mes.
                    </div>

                    <ul v-else class="space-y-4">
                        <li v-for="category in byCategory" :key="category.name + category.type">
                            <div class="mb-1 flex items-center justify-between text-sm">
                                <span class="flex items-center gap-2">
                                    <span class="inline-block h-3 w-3 rounded-full" :style="{ backgroundColor: category.color || '#94a3b8' }"></span>
                                    {{ category.name }}
                                    <span class="text-xs text-gray-400">{{ category.currency }}</span>
                                </span>
                                <span :class="category.type === 'income' ? 'text-success' : 'text-danger'">
                                    {{ money(category.total, category.currency) }}
                                </span>
                            </div>
                            <div class="h-2 w-full rounded bg-gray-100 dark:bg-gray-700">
                                <div
                                    class="h-2 rounded"
                                    :class="category.type === 'income' ? 'bg-success' : 'bg-danger'"
                                    :style="{ width: Math.round((Math.abs(category.total_base) / maxCategoryTotal) * 100) + '%' }"
                                ></div>
                            </div>
                        </li>
                    </ul>

                    <p class="mt-4 text-xs text-gray-500 dark:text-gray-400">
                        Las barras comparan el equivalente en {{ baseCurrency }}; el importe se muestra en su moneda.
                    </p>
                </div>
            </div>

            <!-- Efectivo esperado en cajas abiertas (no forma parte del libro de bancos) -->
            <div
                v-if="cashOpen.count"
                class="panel flex flex-col gap-3 border-l-4 border-success p-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex items-start gap-3">
                    <font-awesome-icon :icon="faCashRegister" class="mt-1 text-success" />
                    <div>
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-100">
                            Efectivo en cajas abiertas: {{ money(cashOpen.balance, baseCurrency) }}
                        </p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-300">
                            {{ cashOpen.count }} caja{{ cashOpen.count === 1 ? '' : 's' }} abierta{{ cashOpen.count === 1 ? '' : 's' }}.
                            El efectivo se controla con la caja chica y no entra al libro de bancos.
                        </p>
                    </div>
                </div>
                <a :href="route('treasury_cash_index')" class="btn btn-sm btn-outline-success whitespace-nowrap">
                    Ver efectivo
                </a>
            </div>

            <!-- Dinero cobrado en billeteras que todavía no llegó al banco -->
            <div v-if="walletTransfers.count" class="panel overflow-hidden p-0">
                <div class="flex flex-col gap-2 border-b border-gray-200 p-5 dark:border-gray-700 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Billeteras en camino al banco</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Cobros que ya están en la billetera y se transferirán a su banco en la fecha indicada.
                            Este dinero ya está contado en el saldo de la billetera.
                        </p>
                    </div>
                    <a :href="route('treasury_accounts_index')" class="btn btn-sm btn-outline-primary whitespace-nowrap">
                        Ver cuentas
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800/60">
                                <th>Traslado</th>
                                <th>Billetera</th>
                                <th>Banco destino</th>
                                <th class="text-right">Monto</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in walletTransfers.rows" :key="item.id">
                                <td class="whitespace-nowrap text-sm">
                                    {{ fecha(item.scheduled_date) }}
                                    <span v-if="item.overdue" class="ml-1 text-xs font-medium text-warning">vencido</span>
                                    <span v-else-if="item.days === 0" class="ml-1 text-xs text-success">hoy</span>
                                </td>
                                <td class="text-sm">{{ item.wallet || '—' }}</td>
                                <td class="text-sm">{{ item.bank || '—' }}</td>
                                <td class="text-right text-sm font-semibold">{{ money(item.amount, item.currency) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Últimos movimientos -->
            <div class="panel overflow-hidden p-0">
                <div class="border-b border-gray-200 p-5 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Últimos movimientos</h2>
                </div>
                <div class="table-responsive">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800/60">
                                <th>Fecha</th>
                                <th>Descripción</th>
                                <th>Categoría</th>
                                <th>Cuenta</th>
                                <th>N° operación</th>
                                <th>Origen</th>
                                <th class="text-right">Monto</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="recent.length === 0">
                                <td colspan="7" class="py-8 text-center text-sm text-gray-500">Sin movimientos registrados.</td>
                            </tr>
                            <tr v-for="tx in recent" :key="tx.id">
                                <td class="whitespace-nowrap text-sm">{{ tx.date }}</td>
                                <td class="text-sm">{{ tx.description || '—' }}</td>
                                <td class="text-sm">
                                    <span v-if="tx.category" class="inline-flex items-center gap-1">
                                        <span class="inline-block h-2.5 w-2.5 rounded-full" :style="{ backgroundColor: tx.category_color || '#94a3b8' }"></span>
                                        {{ tx.category }}
                                    </span>
                                    <span v-else class="text-gray-400">—</span>
                                </td>
                                <td class="text-sm">{{ tx.account }}</td>
                                <td class="whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">{{ tx.operation_number || '—' }}</td>
                                <td class="text-xs text-gray-500">{{ sourceLabels[tx.source] || tx.source }}</td>
                                <td class="text-right font-semibold" :class="tx.type === 'income' ? 'text-success' : 'text-danger'">
                                    {{ tx.type === 'income' ? '+' : '−' }} {{ money(tx.amount, tx.currency) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
