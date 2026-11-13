<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import ModalLarge from '@/Components/ModalLarge.vue';
    import InputLabel from '@/Components/InputLabel.vue';
    import InputError from '@/Components/InputError.vue';
    import PrimaryButton from '@/Components/PrimaryButton.vue';
    import { Head, router, useForm } from '@inertiajs/vue3';
    import { ref } from 'vue';
    import { faFilter } from '@fortawesome/free-solid-svg-icons';

    const props = defineProps({
        documents: { type: Array, default: () => [] },
        accounts: { type: Array, default: () => [] },
        paymentMethods: { type: Array, default: () => [] },
        from: { type: String, default: null },
        to: { type: String, default: null },
    });

    const currency = (value) =>
        new Intl.NumberFormat('es-PE', { style: 'currency', currency: 'PEN' }).format(value || 0);

    const filters = ref({ from: props.from, to: props.to });

    const applyFilters = () => {
        router.get(route('treasury_collections_index'), filters.value, {
            preserveScroll: true,
            preserveState: true,
        });
    };

    const showModal = ref(false);
    const selected = ref(null);

    const form = useForm({
        sale_document_id: null,
        treasury_account_id: null,
        payment_method_id: null,
        amount: null,
        date: null,
        reference: null,
    });

    const openModal = (document) => {
        selected.value = document;
        form.reset();
        form.clearErrors();
        form.sale_document_id = document.id;
        form.treasury_account_id = props.accounts.length ? props.accounts[0].id : null;
        form.amount = document.pending_amount;
        form.date = document.date;
        showModal.value = true;
    };

    const closeModal = () => {
        showModal.value = false;
        selected.value = null;
        form.reset();
        form.clearErrors();
    };

    const submit = async () => {
        if (form.processing || !selected.value) {
            return;
        }

        const result = await window.alertaSistema(
            'Vincular cobro',
            `Se registrará ${currency(form.amount)} del comprobante ${selected.value.number} en la cuenta seleccionada. ¿Continuar?`,
            'question',
            {
                duration: 0,
                showCancelButton: true,
                confirmButtonText: 'Sí, vincular',
                cancelButtonText: 'Cancelar',
            }
        );

        if (!result?.isConfirmed) {
            return;
        }

        form.post(route('treasury_collections_link'), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false;
                selected.value = null;
                window.alertaSistema('Cobro vinculado', 'El cobro se registró en el libro de tesorería.', 'success');
            },
            onError: (errors) => {
                const first = Object.values(errors || {})[0];
                window.alertaSistema('No se pudo vincular', first || 'Revisa los datos del formulario.', 'error');
            },
        });
    };

    const stateLabels = {
        ignorado: 'Ignorado',
        fallido: 'Fallido',
        sin_registro: 'Sin registro',
    };

    const stateStyles = {
        ignorado: 'bg-warning/10 text-warning',
        fallido: 'bg-danger/10 text-danger',
        sin_registro: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
    };
</script>

<template>
    <AppLayout title="Cobros sin cuenta">
        <Head title="Tesorería · Cobros sin cuenta">
            <meta name="aracodito-view" content="new" />
        </Head>
        <Navigation>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Tesorería</span>
            </li>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Cobros sin cuenta</span>
            </li>
        </Navigation>

        <div class="mt-5 space-y-5">
            <div class="panel p-5">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Comprobantes sin registrar en tesorería</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Comprobantes cuyo dinero no llegó al libro porque el método de pago no tiene cuenta asignada
                    (efectivo, giro) o porque la venta se creó sin medio de pago. Vincúlalos a la cuenta donde
                    realmente ingresó el dinero; el comprobante no se modifica y el movimiento queda como manual.
                </p>
            </div>

            <div class="panel flex flex-col gap-4 p-5 sm:flex-row sm:items-center">
                <input v-model="filters.from" type="date" class="form-input sm:w-40" title="Desde" />
                <input v-model="filters.to" type="date" class="form-input sm:w-40" title="Hasta" />
                <button type="button" class="btn btn-outline-primary" @click="applyFilters">
                    <font-awesome-icon :icon="faFilter" class="mr-1" /> Filtrar
                </button>
            </div>

            <div class="panel overflow-hidden p-0">
                <div class="table-responsive">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800/60">
                                <th class="w-28">Fecha</th>
                                <th class="w-32">Comprobante</th>
                                <th class="min-w-[220px]">Cliente</th>
                                <th class="w-28">Moneda</th>
                                <th class="w-32">Estado</th>
                                <th class="min-w-[180px]">Motivo</th>
                                <th class="w-36 text-right">Monto</th>
                                <th class="w-32 text-center">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="documents.length === 0">
                                <td colspan="8" class="py-8 text-center text-sm text-gray-500">
                                    No hay comprobantes pendientes de vincular en el período.
                                </td>
                            </tr>
                            <tr
                                v-for="document in documents"
                                :key="document.id"
                                class="hover:bg-gray-50/70 dark:hover:bg-gray-800/30"
                            >
                                <td class="text-gray-600 dark:text-gray-400">{{ document.date || '—' }}</td>
                                <td class="font-medium text-gray-800 dark:text-gray-100">{{ document.number || '—' }}</td>
                                <td class="text-gray-700 dark:text-gray-300">{{ document.client || '—' }}</td>
                                <td class="text-gray-600 dark:text-gray-400">{{ document.currency }}</td>
                                <td>
                                    <span class="rounded-md px-2 py-1 text-xs font-medium" :class="stateStyles[document.state]">
                                        {{ stateLabels[document.state] || document.state }}
                                    </span>
                                </td>
                                <td class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ document.reason || (document.method ? `Método: ${document.method}` : 'Venta sin medio de pago') }}
                                </td>
                                <td class="text-right font-semibold text-gray-800 dark:text-gray-100">
                                    {{ currency(document.pending_amount) }}
                                </td>
                                <td class="text-center">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-primary"
                                        :disabled="accounts.length === 0"
                                        @click="openModal(document)"
                                    >
                                        Vincular
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <ModalLarge :show="showModal" :on-close="closeModal" :icon="'/img/cuenta-bancaria.png'">
            <template #title>Vincular cobro a una cuenta</template>
            <template #message>
                <span>{{ selected?.number }} · {{ selected?.client }}</span>
            </template>
            <template #content>
                <div class="space-y-4">
                    <div>
                        <InputLabel value="Cuenta de tesorería *" class="mb-1.5" />
                        <select v-model="form.treasury_account_id" class="form-select w-full">
                            <option :value="null" disabled>Seleccionar…</option>
                            <option v-for="account in accounts" :key="account.id" :value="account.id">
                                {{ account.label }} ({{ account.currency }})
                            </option>
                        </select>
                        <InputError :message="form.errors.treasury_account_id" class="mt-2" />
                        <InputError :message="form.errors.sale_document_id" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel value="Monto *" class="mb-1.5" />
                            <input v-model="form.amount" type="number" step="0.01" min="0.01" class="form-input w-full" />
                            <InputError :message="form.errors.amount" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel value="Fecha del cobro" class="mb-1.5" />
                            <input v-model="form.date" type="date" class="form-input w-full" />
                            <InputError :message="form.errors.date" class="mt-2" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel value="Método de pago" class="mb-1.5" />
                            <select v-model="form.payment_method_id" class="form-select w-full">
                                <option :value="null">Sin especificar</option>
                                <option v-for="method in paymentMethods" :key="method.id" :value="method.id">
                                    {{ method.description }}
                                </option>
                            </select>
                            <InputError :message="form.errors.payment_method_id" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel value="Referencia / N° operación" class="mb-1.5" />
                            <input v-model="form.reference" type="text" class="form-input w-full" />
                            <InputError :message="form.errors.reference" class="mt-2" />
                        </div>
                    </div>
                </div>
            </template>
            <template #buttons>
                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing" @click="submit">
                    Vincular cobro
                </PrimaryButton>
            </template>
        </ModalLarge>
    </AppLayout>
</template>
