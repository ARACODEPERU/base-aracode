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
    import { ref, reactive } from 'vue';
    import { faLandmark, faWallet, faPencil, faTrash } from '@fortawesome/free-solid-svg-icons';

    const props = defineProps({
        accounts: { type: Array, default: () => [] },
        bankAccounts: { type: Array, default: () => [] },
        wallets: { type: Array, default: () => [] },
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

    const showModal = ref(false);
    const editingId = ref(null);

    // Cierre del modal del núcleo (reset form)
    const closeModal = () => {
        showModal.value = false;
        form.reset();
        editingId.value = null;
    };

    const form = useForm({
        type: 'bank',
        bank_account_id: null,
        company_billetera_id: null,
        label: '',
        currency_type_id: 'PEN',
        opening_balance: 0,
        opening_balance_date: null,
        status: true,
    });

    const openCreate = () => {
        editingId.value = null;
        form.reset();
        form.type = 'bank';
        form.currency_type_id = 'PEN';
        form.status = true;
        showModal.value = true;
    };

    const openEdit = (account) => {
        editingId.value = account.id;
        form.type = account.type;
        form.bank_account_id = account.bank_account_id;
        form.company_billetera_id = account.company_billetera_id;
        form.label = account.custom_label ?? '';
        form.currency_type_id = account.currency || 'PEN';
        form.opening_balance = account.opening_balance;
        form.opening_balance_date = account.opening_balance_date;
        form.status = account.status;
        showModal.value = true;
    };

    const onTypeChange = () => {
        form.bank_account_id = null;
        form.company_billetera_id = null;
    };

    const save = () => {
        const routeName = editingId.value ? 'treasury_accounts_update' : 'treasury_accounts_store';
        const url = editingId.value ? route(routeName, editingId.value) : route(routeName);

        form.post(url, {
            preserveScroll: true,
            onSuccess: () => {
                toast.fire({ icon: 'success', title: 'Cuenta guardada' });
                showModal.value = false;
            },
        });
    };

    const remove = (account) => {
        Swal2.fire({
            title: '¿Eliminar cuenta de tesorería?',
            text: 'Si la cuenta tiene movimientos, se desactivará en lugar de eliminarse.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: '¡Sí, Eliminar!',
            cancelButtonText: 'Cancelar',
            padding: '2em',
            customClass: 'sweet-alerts',
            allowOutsideClick: () => !Swal2.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                router.delete(route('treasury_accounts_destroy', account.id), {
                    preserveScroll: true,
                    onSuccess: () => toast.fire({ icon: 'success', title: 'Cuenta eliminada' }),
                });
            }
        });
    };

    // Asignación de método de pago -> cuenta
    const assignForm = useForm({
        payment_method_id: null,
        bank_account_id: null,
    });

    const selectedMethod = reactive({});

    const saveAssignment = () => {
        if (! assignForm.payment_method_id) {
            Swal2.fire({ title: 'Atención', text: 'Selecciona un método de pago', icon: 'warning', padding: '2em', customClass: 'sweet-alerts' });
            return;
        }

        assignForm.post(route('treasury_accounts_assign_payment_method'), {
            preserveScroll: true,
            onSuccess: () => toast.fire({ icon: 'success', title: 'Método de pago asignado' }),
        });
    };

    const sourceLabels = {
        bank: 'Banco',
        wallet: 'Billetera',
    };
</script>

<template>
    <AppLayout title="Cuentas de Tesorería">
        <Head title="Tesorería · Cuentas" />
        <Navigation>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Tesorería</span>
            </li>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Cuentas</span>
            </li>
        </Navigation>

        <div class="mt-5 space-y-5">
            <div class="panel flex flex-col gap-4 p-5 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Cuentas de tesorería</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Bancos y billeteras digitales que componen el libro de tesorería
                    </p>
                </div>
                <button type="button" class="btn btn-primary" @click="openCreate">Nueva cuenta</button>
            </div>

            <div class="panel overflow-hidden p-0">
                <div class="table-responsive">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800/60">
                                <th class="w-16 text-center">Acciones</th>
                                <th class="w-28">Tipo</th>
                                <th class="min-w-[220px]">Cuenta</th>
                                <th class="w-24">Moneda</th>
                                <th class="w-40 text-right">Saldo inicial</th>
                                <th class="w-40 text-right">Saldo actual</th>
                                <th class="w-24 text-center">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="accounts.length === 0">
                                <td colspan="7" class="py-8 text-center text-sm text-gray-500">
                                    No hay cuentas registradas.
                                </td>
                            </tr>
                            <tr
                                v-for="account in accounts"
                                :key="account.id"
                                class="hover:bg-gray-50/70 dark:hover:bg-gray-800/30"
                            >
                                <td class="text-center">
                                    <Dropdown placement="bottomLeft" arrow>
                                        <button
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700"
                                            type="button"
                                        >
                                            <font-awesome-icon :icon="faPencil" />
                                        </button>
                                        <template #overlay>
                                            <Menu>
                                                <MenuItem key="edit" @click="openEdit(account)">Editar</MenuItem>
                                                <MenuItem key="delete" danger @click="remove(account)">
                                                    <span class="inline-flex items-center gap-2">
                                                        <font-awesome-icon :icon="faTrash" /> Eliminar
                                                    </span>
                                                </MenuItem>
                                            </Menu>
                                        </template>
                                    </Dropdown>
                                </td>
                                <td>
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-md px-2 py-1 text-xs font-medium"
                                        :class="account.type === 'bank'
                                            ? 'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300'
                                            : 'bg-purple-100 text-purple-700 dark:bg-purple-500/10 dark:text-purple-300'"
                                    >
                                        <font-awesome-icon :icon="account.type === 'bank' ? faLandmark : faWallet" />
                                        {{ sourceLabels[account.type] }}
                                    </span>
                                </td>
                                <td class="font-medium text-gray-800 dark:text-gray-100">{{ account.label }}</td>
                                <td class="text-gray-600 dark:text-gray-400">{{ account.currency }}</td>
                                <td class="text-right text-gray-600 dark:text-gray-400">
                                    {{ currency(account.opening_balance) }}
                                    <p v-if="account.opening_balance_date" class="text-xs text-gray-400">
                                        al {{ account.opening_balance_date }}
                                    </p>
                                </td>
                                <td
                                    class="text-right font-bold"
                                    :class="account.balance >= 0 ? 'text-gray-800 dark:text-gray-100' : 'text-danger'"
                                >
                                    {{ currency(account.balance) }}
                                </td>
                                <td class="text-center">
                                    <span
                                        class="rounded-md px-2 py-1 text-xs font-medium"
                                        :class="account.status
                                            ? 'bg-success/10 text-success'
                                            : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-300'"
                                    >
                                        {{ account.status ? 'Activa' : 'Inactiva' }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Asignación método de pago -> cuenta -->
            <div class="panel p-5">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Asignación de métodos de pago</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Indica en qué cuenta cae el dinero de cada método de pago (Yape, Plin, transferencias, etc.).
                    Así los cobros de ventas se registran automáticamente en el libro de tesorería.
                </p>

                <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-3">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Método de pago</label>
                        <select v-model="assignForm.payment_method_id" class="form-select w-full">
                            <option :value="null" disabled>Seleccionar…</option>
                            <option v-for="method in paymentMethods" :key="method.id" :value="method.id">
                                {{ method.description }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Cuenta bancaria destino</label>
                        <select v-model="assignForm.bank_account_id" class="form-select w-full">
                            <option :value="null">Sin asignar</option>
                            <option v-for="bank in bankAccounts" :key="bank.id" :value="bank.id">
                                {{ bank.label }}
                            </option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">
                            Los métodos de billetera (Yape, Plin, MercadoPago) se detectan por nombre; los demás requieren esta asignación.
                        </p>
                    </div>
                    <div class="flex items-end">
                        <button
                            type="button"
                            class="btn btn-primary w-full"
                            :disabled="assignForm.processing"
                            @click="saveAssignment"
                        >
                            {{ assignForm.processing ? 'Guardando…' : 'Guardar asignación' }}
                        </button>
                    </div>
                </div>

                <div class="mt-4 overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800/60">
                                <th class="min-w-[200px]">Método de pago</th>
                                <th class="min-w-[220px]">Cuenta asignada</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="method in paymentMethods" :key="method.id">
                                <td class="text-gray-800 dark:text-gray-100">{{ method.description }}</td>
                                <td>
                                    <span
                                        v-if="method.bank_account_id"
                                        class="rounded-md bg-success/10 px-2 py-1 text-xs font-medium text-success"
                                    >
                                        {{ bankAccounts.find((b) => b.id === method.bank_account_id)?.label || `Cuenta #${method.bank_account_id}` }}
                                    </span>
                                    <span v-else class="text-xs text-gray-400">Automático (por nombre de billetera) o sin asignar</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal crear/editar cuenta (componente del núcleo, patrón Sales) -->
        <ModalLarge :show="showModal" :on-close="closeModal" :icon="'/img/cuenta-bancaria.png'">
            <template #title>
                {{ editingId ? 'Editar cuenta' : 'Nueva cuenta de tesorería' }}
            </template>
            <template #message>
                <span>Bancos y billeteras del libro de tesorería</span>
            </template>
            <template #content>
            <div class="space-y-4">
                <div>
                    <InputLabel value="Tipo de cuenta" class="mb-1.5" />
                    <div class="grid grid-cols-2 gap-3">
                        <button
                            type="button"
                            class="flex items-center justify-center gap-2 rounded-lg border-2 p-3 text-sm font-medium transition"
                            :class="form.type === 'bank' ? 'border-primary bg-primary/5 text-primary' : 'border-gray-200 dark:border-gray-700'"
                            @click="form.type = 'bank'; onTypeChange()"
                        >
                            <font-awesome-icon :icon="faLandmark" /> Banco
                        </button>
                        <button
                            type="button"
                            class="flex items-center justify-center gap-2 rounded-lg border-2 p-3 text-sm font-medium transition"
                            :class="form.type === 'wallet' ? 'border-primary bg-primary/5 text-primary' : 'border-gray-200 dark:border-gray-700'"
                            @click="form.type = 'wallet'; onTypeChange()"
                        >
                            <font-awesome-icon :icon="faWallet" /> Billetera digital
                        </button>
                    </div>
                </div>

                <div v-if="form.type === 'bank'">
                    <InputLabel value="Cuenta bancaria *" class="mb-1.5" />
                    <select v-model="form.bank_account_id" class="form-select w-full">
                        <option :value="null" disabled>Seleccionar…</option>
                        <option v-for="bank in bankAccounts" :key="bank.id" :value="bank.id">
                            {{ bank.label }} ({{ bank.currency }})
                        </option>
                    </select>
                    <InputError :message="form.errors.bank_account_id" class="mt-2" />
                </div>

                <div v-else>
                    <InputLabel value="Billetera de empresa *" class="mb-1.5" />
                    <select v-model="form.company_billetera_id" class="form-select w-full">
                        <option :value="null" disabled>Seleccionar…</option>
                        <option v-for="wallet in wallets" :key="wallet.id" :value="wallet.id">
                            {{ wallet.label }}
                        </option>
                    </select>
                    <InputError :message="form.errors.company_billetera_id" class="mt-2" />
                </div>

                <div>
                    <InputLabel value="Nombre visible (opcional)" class="mb-1.5" />
                    <input v-model="form.label" type="text" class="form-input w-full" placeholder="Ej: Cuenta principal BCP" />
                    <InputError :message="form.errors.label" class="mt-2" />
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <InputLabel value="Moneda" class="mb-1.5" />
                        <select v-model="form.currency_type_id" class="form-select w-full">
                            <option value="PEN">Soles (PEN)</option>
                            <option value="USD">Dólares (USD)</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel value="Saldo inicial" class="mb-1.5" />
                        <input v-model="form.opening_balance" type="number" step="0.01" class="form-input w-full" />
                    </div>
                    <div>
                        <InputLabel value="Fecha de corte" class="mb-1.5" />
                        <input v-model="form.opening_balance_date" type="date" class="form-input w-full" />
                    </div>
                </div>
                <p class="text-xs text-gray-500">
                    El saldo inicial es el dinero que había en la cuenta antes de empezar a registrar movimientos en el sistema.
                </p>

                <label class="flex cursor-pointer items-center gap-2">
                    <input v-model="form.status" type="checkbox" class="form-checkbox" />
                    <span class="text-sm">Cuenta activa</span>
                </label>
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
