<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import { Head } from '@inertiajs/vue3';
    import { faWallet, faLandmark, faArrowTrendUp, faArrowTrendDown } from '@fortawesome/free-solid-svg-icons';

    const props = defineProps({
        accounts: { type: Array, default: () => [] },
        totalBalance: { type: Number, default: 0 },
        monthIncomes: { type: Number, default: 0 },
        monthExpenses: { type: Number, default: 0 },
        byCategory: { type: Array, default: () => [] },
        recent: { type: Array, default: () => [] },
    });

    const currency = (value) =>
        new Intl.NumberFormat('es-PE', { style: 'currency', currency: 'PEN' }).format(value || 0);

    const accountIcon = (type) => (type === 'bank' ? faLandmark : faWallet);

    const maxCategoryTotal = Math.max(...props.byCategory.map((c) => Math.abs(c.total)), 1);

    const sourceLabels = {
        auto: 'Automático',
        manual: 'Manual',
        backfill: 'Backfill',
    };
</script>

<template>
    <AppLayout title="Tesorería">
        <Head title="Tesorería" />
        <Navigation>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Tesorería</span>
            </li>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Dashboard</span>
            </li>
        </Navigation>

        <div class="mt-5 space-y-5">
            <!-- Resumen principal -->
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
                <div class="panel bg-gradient-to-br from-primary to-primary-dark text-white">
                    <div class="flex items-start justify-between p-5">
                        <div>
                            <p class="text-sm opacity-80">Saldo total en cuentas</p>
                            <p class="mt-2 text-3xl font-bold">{{ currency(totalBalance) }}</p>
                        </div>
                        <div class="rounded-full bg-white/20 p-3">
                            <font-awesome-icon :icon="faWallet" class="text-xl" />
                        </div>
                    </div>
                </div>

                <div class="panel">
                    <div class="flex items-start justify-between p-5">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Ingresos del mes</p>
                            <p class="mt-2 text-2xl font-bold text-success">{{ currency(monthIncomes) }}</p>
                        </div>
                        <div class="rounded-full bg-success/10 p-3 text-success">
                            <font-awesome-icon :icon="faArrowTrendUp" class="text-xl" />
                        </div>
                    </div>
                </div>

                <div class="panel">
                    <div class="flex items-start justify-between p-5">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Egresos del mes</p>
                            <p class="mt-2 text-2xl font-bold text-danger">{{ currency(monthExpenses) }}</p>
                        </div>
                        <div class="rounded-full bg-danger/10 p-3 text-danger">
                            <font-awesome-icon :icon="faArrowTrendDown" class="text-xl" />
                        </div>
                    </div>
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
                            <p
                                class="font-bold"
                                :class="account.balance >= 0 ? 'text-gray-800 dark:text-gray-100' : 'text-danger'"
                            >
                                {{ currency(account.balance) }}
                            </p>
                        </li>
                    </ul>
                </div>

                <!-- Movimientos por categoría -->
                <div class="panel p-5">
                    <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white">Movimientos del mes por categoría</h2>
                    <div v-if="byCategory.length === 0" class="py-8 text-center text-sm text-gray-500">
                        Sin movimientos este mes.
                    </div>
                    <ul v-else class="space-y-3">
                        <li v-for="cat in byCategory" :key="`${cat.type}-${cat.name}`">
                            <div class="mb-1 flex items-center justify-between text-sm">
                                <span class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                                    <span class="h-2.5 w-2.5 rounded-full" :style="{ backgroundColor: cat.color || '#94a3b8' }"></span>
                                    {{ cat.name }}
                                </span>
                                <span :class="cat.type === 'income' ? 'text-success' : 'text-danger'">
                                    {{ cat.type === 'income' ? '+' : '−' }} {{ currency(Math.abs(cat.total)) }}
                                </span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700">
                                <div
                                    class="h-full rounded-full"
                                    :style="{
                                        width: `${(Math.abs(cat.total) / maxCategoryTotal) * 100}%`,
                                        backgroundColor: cat.color || '#94a3b8',
                                    }"
                                ></div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Últimos movimientos -->
            <div class="panel p-0">
                <div class="border-b border-gray-200 p-5 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Últimos movimientos</h2>
                </div>
                <div class="table-responsive">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800/60">
                                <th class="w-32">Fecha</th>
                                <th class="min-w-[220px]">Descripción</th>
                                <th class="min-w-[160px]">Categoría</th>
                                <th class="min-w-[180px]">Cuenta</th>
                                <th class="w-32">Origen</th>
                                <th class="w-36 text-right">Monto</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="recent.length === 0">
                                <td colspan="6" class="py-8 text-center text-sm text-gray-500">Sin movimientos registrados.</td>
                            </tr>
                            <tr
                                v-for="tx in recent"
                                :key="tx.id"
                                class="hover:bg-gray-50/70 dark:hover:bg-gray-800/30"
                            >
                                <td class="text-gray-600 dark:text-gray-400">{{ tx.date }}</td>
                                <td class="text-gray-800 dark:text-gray-100">{{ tx.description || '—' }}</td>
                                <td>
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-md px-2 py-1 text-xs"
                                        :style="{ backgroundColor: (tx.category_color || '#94a3b8') + '20', color: tx.category_color || '#64748b' }"
                                    >
                                        <span class="h-2 w-2 rounded-full" :style="{ backgroundColor: tx.category_color || '#94a3b8' }"></span>
                                        {{ tx.category || 'Sin categoría' }}
                                    </span>
                                </td>
                                <td class="text-gray-600 dark:text-gray-400">{{ tx.account || '—' }}</td>
                                <td>
                                    <span class="rounded-md bg-gray-100 px-2 py-1 text-xs text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                        {{ sourceLabels[tx.source] || tx.source }}
                                    </span>
                                </td>
                                <td
                                    class="text-right font-semibold"
                                    :class="tx.type === 'income' ? 'text-success' : 'text-danger'"
                                >
                                    {{ tx.type === 'income' ? '+' : '−' }} {{ currency(tx.amount) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
