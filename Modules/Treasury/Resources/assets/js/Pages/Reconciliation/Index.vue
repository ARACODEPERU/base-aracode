<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import { Head, router } from '@inertiajs/vue3';
    import Swal2 from 'sweetalert2';
    import { ref } from 'vue';
    import { faCircleCheck, faFilter } from '@fortawesome/free-solid-svg-icons';

    const props = defineProps({
        accounts: { type: Array, default: () => [] },
        selectedAccountId: { type: Number, default: null },
        until: { type: String, default: null },
        systemBalance: { type: Number, default: 0 },
        unreconciled: { type: Array, default: () => [] },
        unreconciledCount: { type: Number, default: 0 },
    });

    const currency = (value) =>
        new Intl.NumberFormat('es-PE', { style: 'currency', currency: 'PEN' }).format(value || 0);

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

    const selected = ref([]);

    const selectedTotal = (direction) =>
        props.unreconciled
            .filter((tx) => selected.value.includes(tx.id))
            .reduce((sum, tx) => sum + (tx.type === direction ? tx.amount : 0), 0);

    const isSelected = (tx) => selected.value.includes(tx.id);

    const toggle = (tx) => {
        if (isSelected(tx)) {
            selected.value = selected.value.filter((id) => id !== tx.id);
        } else {
            selected.value.push(tx.id);
        }
    };

    const selectAll = () => {
        selected.value = selected.value.length === props.unreconciled.length
            ? []
            : props.unreconciled.map((tx) => tx.id);
    };

    const realBalance = ref(null);

    // Toast de éxito (convención del proyecto)
    const toast = Swal2.mixin({
        toast: true,
        position: 'bottom-end',
        showConfirmButton: false,
        timer: 3000,
        padding: '2em',
        customClass: { container: 'toast' },
    });

    const reconcile = () => {
        if (selected.value.length === 0) {
            Swal2.fire({ title: 'Atención', text: 'Selecciona al menos una transacción', icon: 'warning', padding: '2em', customClass: 'sweet-alerts' });
            return;
        }

        router.post(
            route('treasury_reconciliation_reconcile'),
            { transaction_ids: selected.value },
            {
                preserveScroll: true,
                onSuccess: () => {
                    toast.fire({ icon: 'success', title: 'Transacciones conciliadas' });
                    selected.value = [];
                    realBalance.value = null;
                },
            }
        );
    };

    const sourceLabels = {
        auto: 'Automático',
        manual: 'Manual',
        backfill: 'Backfill',
    };
</script>

<template>
    <AppLayout title="Conciliación bancaria">
        <Head title="Tesorería · Conciliación" />
        <Navigation>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Tesorería</span>
            </li>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Conciliación</span>
            </li>
        </Navigation>

        <div class="mt-5 space-y-5">
            <!-- Filtros -->
            <div class="panel flex flex-col gap-4 p-5 lg:flex-row lg:items-end lg:justify-between">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Cuenta</label>
                        <select v-model="filters.account_id" class="form-select w-full sm:w-56">
                            <option v-for="account in accounts" :key="account.id" :value="account.id">
                                {{ account.label }}
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
                    <label class="mb-1.5 block text-sm font-medium">Saldo real del banco</label>
                    <input
                        v-model="realBalance"
                        type="number"
                        step="0.01"
                        class="form-input w-full sm:w-56"
                        placeholder="Monto según extracto…"
                    />
                </div>
            </div>

            <!-- Comparación de saldos -->
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                <div class="panel p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Saldo en el sistema</p>
                    <p class="mt-2 text-2xl font-bold text-gray-800 dark:text-gray-100">{{ currency(systemBalance) }}</p>
                </div>
                <div class="panel p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Saldo real del banco</p>
                    <p class="mt-2 text-2xl font-bold text-gray-800 dark:text-gray-100">
                        {{ realBalance !== null && realBalance !== '' ? currency(realBalance) : '—' }}
                    </p>
                </div>
                <div class="panel p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Diferencia</p>
                    <p
                        class="mt-2 text-2xl font-bold"
                        :class="realBalance !== null && realBalance !== '' && Math.abs(systemBalance - realBalance) > 0.01
                            ? 'text-danger'
                            : 'text-success'"
                    >
                        <template v-if="realBalance !== null && realBalance !== ''">
                            {{ currency(systemBalance - realBalance) }}
                        </template>
                        <template v-else>—</template>
                    </p>
                    <p
                        v-if="realBalance !== null && realBalance !== '' && Math.abs(systemBalance - realBalance) <= 0.01"
                        class="mt-1 flex items-center gap-1 text-xs font-medium text-success"
                    >
                        <font-awesome-icon :icon="faCircleCheck" /> ¡Cuadrado!
                    </p>
                </div>
            </div>

            <!-- Transacciones sin conciliar -->
            <div class="panel overflow-hidden p-0">
                <div class="flex flex-col gap-3 border-b border-gray-200 p-5 sm:flex-row sm:items-center sm:justify-between dark:border-gray-700">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">
                            Transacciones sin conciliar
                            <span class="ml-2 rounded-full bg-primary/10 px-2.5 py-0.5 text-sm text-primary">{{ unreconciledCount }}</span>
                        </h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Marca las que ya aparecen en el extracto del banco
                            <template v-if="realBalance !== null && realBalance !== ''">
                                — seleccionadas: <strong>{{ currency(selectedTotal('income') - selectedTotal('expense')) }}</strong>
                            </template>
                        </p>
                    </div>
                    <button
                        type="button"
                        class="btn btn-primary"
                        :disabled="selected.length === 0"
                        @click="reconcile"
                    >
                        Conciliar seleccionadas ({{ selected.length }})
                    </button>
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
                                        @change="selectAll"
                                    />
                                </th>
                                <th class="w-28">Fecha</th>
                                <th class="min-w-[240px]">Descripción</th>
                                <th class="min-w-[160px]">Categoría</th>
                                <th class="w-28">Origen</th>
                                <th class="w-36 text-right">Monto</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="unreconciled.length === 0">
                                <td colspan="6" class="py-8 text-center text-sm text-gray-500">
                                    ¡Todo conciliado! No hay transacciones pendientes.
                                </td>
                            </tr>
                            <tr
                                v-for="tx in unreconciled"
                                :key="tx.id"
                                class="cursor-pointer hover:bg-gray-50/70 dark:hover:bg-gray-800/30"
                                :class="isSelected(tx) ? 'bg-primary/5' : ''"
                                @click="toggle(tx)"
                            >
                                <td class="text-center" @click.stop>
                                    <input
                                        v-model="selected"
                                        type="checkbox"
                                        class="form-checkbox"
                                        :value="tx.id"
                                    />
                                </td>
                                <td class="text-gray-600 dark:text-gray-400">{{ tx.date }}</td>
                                <td>
                                    <p class="text-gray-800 dark:text-gray-100">{{ tx.description || '—' }}</p>
                                    <p v-if="tx.reference" class="text-xs text-gray-400">Ref: {{ tx.reference }}</p>
                                </td>
                                <td>
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-md px-2 py-1 text-xs"
                                        :style="{ backgroundColor: (tx.category_color || '#94a3b8') + '20', color: tx.category_color || '#64748b' }"
                                    >
                                        {{ tx.category || 'Sin categoría' }}
                                    </span>
                                </td>
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
