<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import { Head, useForm, router } from '@inertiajs/vue3';
    import { Dropdown, Menu, MenuItem } from 'ant-design-vue';
    import Swal2 from 'sweetalert2';
    import ModalLarge from '@/Components/ModalLarge.vue';
    import InputLabel from '@/Components/InputLabel.vue';
    import InputError from '@/Components/InputError.vue';
    import PrimaryButton from '@/Components/PrimaryButton.vue';
    import { ref, computed } from 'vue';
    import { faPlus, faFileArrowUp, faFilter } from '@fortawesome/free-solid-svg-icons';

    const props = defineProps({
        accounts: { type: Array, default: () => [] },
        categories: { type: Array, default: () => [] },
        selectedAccountId: { type: Number, default: null },
        from: { type: String, default: null },
        to: { type: String, default: null },
        transactions: { type: Array, default: () => [] },
        openingBalance: { type: Number, default: 0 },
        runningBalance: { type: Number, default: 0 },
        calculatedBalance: { type: Number, default: 0 },
        paymentMethods: { type: Array, default: () => [] },
    });

    const currency = (value) =>
        new Intl.NumberFormat('es-PE', { style: 'currency', currency: 'PEN' }).format(value || 0);

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
        type: 'expense',
        treasury_category_id: null,
        amount: '',
        reference: '',
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
        form.type = type;
        showModal.value = true;
    };

    const openEdit = (tx) => {
        editingId.value = tx.id;
        form.treasury_account_id = filters.value.account_id;
        form.transaction_date = tx.date;
        form.type = tx.type;
        form.amount = tx.amount;
        form.reference = tx.reference;
        form.description = tx.description;
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
            onSuccess: () => {
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

    const canEdit = (tx) => tx.source === 'manual' && !tx.voided;

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
                    <p class="text-sm text-gray-500 dark:text-gray-400">Saldo del período</p>
                    <p
                        class="mt-2 text-2xl font-bold"
                        :class="runningBalance >= 0 ? 'text-gray-800 dark:text-gray-100' : 'text-danger'"
                    >
                        {{ currency(runningBalance) }}
                    </p>
                </div>
                <div class="panel bg-gradient-to-br from-primary to-primary-dark p-5 text-white">
                    <p class="text-sm opacity-80">Saldo actual en el sistema</p>
                    <p class="mt-2 text-2xl font-bold">{{ currency(calculatedBalance) }}</p>
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

                <div class="flex gap-2">
                    <button type="button" class="btn btn-success" @click="openCreate('income')">
                        <font-awesome-icon :icon="faPlus" class="mr-1" /> Ingreso
                    </button>
                    <button type="button" class="btn btn-danger" @click="openCreate('expense')">
                        <font-awesome-icon :icon="faPlus" class="mr-1" /> Egreso
                    </button>
                </div>
            </div>

            <!-- Libro de movimientos -->
            <div class="panel overflow-hidden p-0">
                <div class="table-responsive">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800/60">
                                <th class="w-16 text-center">Acciones</th>
                                <th class="w-28">Fecha</th>
                                <th class="min-w-[240px]">Descripción</th>
                                <th class="min-w-[160px]">Categoría</th>
                                <th class="w-32">Referencia</th>
                                <th class="w-28">Origen</th>
                                <th class="w-36 text-right">Monto</th>
                                <th class="w-40 text-right">Saldo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="transactions.length === 0">
                                <td colspan="8" class="py-8 text-center text-sm text-gray-500">
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
                                        <span class="h-2 w-2 rounded-full" :style="{ backgroundColor: tx.category_color || '#94a3b8' }"></span>
                                        {{ tx.category || 'Sin categoría' }}
                                    </span>
                                </td>
                                <td class="text-gray-600 dark:text-gray-400">{{ tx.reference || '—' }}</td>
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
                                    :class="tx.running_balance >= 0 ? 'text-gray-800 dark:text-gray-100' : 'text-danger'"
                                >
                                    {{ currency(tx.running_balance) }}
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
                    <div>
                        <InputLabel value="Fecha *" class="mb-1.5" />
                        <input v-model="form.transaction_date" type="date" class="form-input w-full" />
                        <InputError :message="form.errors.transaction_date" class="mt-2" />
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

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
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
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel value="Referencia / N° operación" class="mb-1.5" />
                        <input v-model="form.reference" type="text" class="form-input w-full" />
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
    </AppLayout>
</template>
