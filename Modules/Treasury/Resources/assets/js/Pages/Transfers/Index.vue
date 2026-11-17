<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import { Head, useForm, router } from '@inertiajs/vue3';
    import { computed, watch } from 'vue';
    import InputLabel from '@/Components/InputLabel.vue';
    import InputError from '@/Components/InputError.vue';
    import PrimaryButton from '@/Components/PrimaryButton.vue';
    import { faBan, faTriangleExclamation } from '@fortawesome/free-solid-svg-icons';

    const props = defineProps({
        accounts: { type: Array, default: () => [] },
        transfers: { type: Array, default: () => [] },
        rates: { type: Object, default: () => ({}) },
        itfThreshold: { type: Number, default: 1000 },
        canVoid: { type: Boolean, default: false },
    });

    const money = (value, currency = 'PEN') => {
        try {
            return new Intl.NumberFormat('es-PE', { style: 'currency', currency }).format(value || 0);
        } catch (e) {
            return `${currency} ${Number(value || 0).toFixed(2)}`;
        }
    };

    const form = useForm({
        from_account_id: null,
        to_account_id: null,
        transfer_date: new Date().toISOString().slice(0, 10),
        amount: null,
        reference: '',
        to_fixed_fee: 0,
        to_percent_fee: 0,
        charge_origin_fee: false,
        from_fixed_fee: 0,
        from_percent_fee: 0,
        own_account_itf_free: false,
        exchange_rate: null,
    });

    const fromAccount = computed(() => props.accounts.find((a) => a.id === form.from_account_id));
    const toAccount = computed(() => props.accounts.find((a) => a.id === form.to_account_id));
    const crossCurrency = computed(() =>
        !!fromAccount.value && !!toAccount.value && fromAccount.value.currency !== toAccount.value.currency
    );

    const rateCode = computed(() => {
        if (!crossCurrency.value) return null;

        return fromAccount.value.currency === 'PEN' ? toAccount.value.currency : fromAccount.value.currency;
    });

    const rate = computed(() => {
        if (!crossCurrency.value) return 1;

        return Number(form.exchange_rate) || Number(props.rates?.[rateCode.value]?.rate) || 0;
    });

    // Monto que realmente se acredita en la cuenta destino, en su moneda.
    const destinationAmount = computed(() => {
        const amount = Number(form.amount) || 0;

        if (!crossCurrency.value) return amount;
        if (!rate.value) return 0;

        return fromAccount.value.currency === 'PEN' ? amount / rate.value : amount * rate.value;
    });

    const r2 = (value) => Math.round(value * 100) / 100;

    // Las comisiones de transferencia llevan IGV (18 %), igual que en el servidor.
    const preview = computed(() => {
        const amount = Number(form.amount) || 0;
        const destination = destinationAmount.value;
        const toFee = r2(Number(form.to_fixed_fee) + (destination * Number(form.to_percent_fee)) / 100);
        const fromFee = form.charge_origin_fee
            ? r2(Number(form.from_fixed_fee) + (amount * Number(form.from_percent_fee)) / 100)
            : 0;
        const toIgv = r2(toFee * 0.18);
        const fromIgv = r2(fromFee * 0.18);
        const itf = !form.own_account_itf_free && amount >= props.itfThreshold ? r2(amount * 0.00005) : 0;
        const net = r2(destination - toFee - toIgv);

        return {
            fromFee,
            fromIgv,
            toFee,
            toIgv,
            itf,
            destination: r2(destination),
            net: Math.max(0, net),
            expenses: r2(fromFee + fromIgv + itf + toFee + toIgv),
        };
    });

    // Al elegir el par, carga las comisiones e ITF guardados y el TC vigente.
    watch(
        () => [form.from_account_id, form.to_account_id],
        async ([from, to]) => {
            if (!from || !to || from === to) return;

            const params = new URLSearchParams({ from_account_id: from, to_account_id: to });
            const response = await fetch(route('treasury_transfers_profile') + '?' + params, {
                headers: { Accept: 'application/json' },
            });
            const profile = await response.json();

            if (profile) {
                form.to_fixed_fee = profile.to_fixed_fee;
                form.to_percent_fee = profile.to_percent_fee;
                form.from_fixed_fee = profile.from_fixed_fee;
                form.from_percent_fee = profile.from_percent_fee;
                form.own_account_itf_free = !!profile.own_account_itf_free;
            }

            form.exchange_rate = crossCurrency.value ? Number(props.rates?.[rateCode.value]?.rate) || null : null;
        }
    );

    const notifyFlash = (page) => {
        const flash = page?.props?.flash || {};
        if (flash.error) window.alertaSistema('No se pudo registrar', flash.error, 'error');
        else if (flash.success) window.alertaSistema('Transferencia registrada', flash.success, 'success');
    };

    const submit = () => {
        form.post(route('treasury_transfers_store'), {
            preserveScroll: true,
            onSuccess: (page) => {
                notifyFlash(page);
                form.reset('amount', 'reference');
            },
            onError: () => window.alertaSistema('Revisa el formulario', 'Hay datos que no son válidos.', 'warning'),
        });
    };

    const voidTransfer = async (transfer) => {
        const result = await window.alertaSistema(
            'Anular transferencia',
            `Se anularán los dos movimientos de ${money(transfer.amount, transfer.from_currency)}. Nada se borra: quedan marcados como anulados.`,
            'warning',
            { duration: 0, showCancelButton: true, confirmButtonText: 'Sí, anular', cancelButtonText: 'Cancelar' }
        );

        if (!result?.isConfirmed) return;

        router.post(route('treasury_transfers_void', transfer.id), {}, {
            preserveScroll: true,
            onSuccess: notifyFlash,
        });
    };
</script>

<template>
    <AppLayout title="Transferencias de tesorería">
        <Head title="Tesorería · Transferencias">
            <meta name="aracodito-view" content="new" />
        </Head>
        <Navigation>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2"><span>Tesorería</span></li>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2"><span>Transferencias</span></li>
        </Navigation>

        <div class="panel mt-6">
            <h2 class="mb-4 text-lg font-semibold">Nueva transferencia</h2>

            <form class="grid grid-cols-1 gap-4 md:grid-cols-2" @submit.prevent="submit">
                <div>
                    <InputLabel for="from_account_id" value="Cuenta origen" />
                    <select id="from_account_id" v-model="form.from_account_id" class="form-select">
                        <option :value="null" disabled>Seleccione</option>
                        <option v-for="a in accounts" :key="a.id" :value="a.id">{{ a.label }} ({{ a.currency }})</option>
                    </select>
                    <InputError :message="form.errors.from_account_id" />
                </div>

                <div>
                    <InputLabel for="to_account_id" value="Cuenta destino" />
                    <select id="to_account_id" v-model="form.to_account_id" class="form-select">
                        <option :value="null" disabled>Seleccione</option>
                        <option v-for="a in accounts" :key="a.id" :value="a.id">{{ a.label }} ({{ a.currency }})</option>
                    </select>
                    <InputError :message="form.errors.to_account_id" />
                </div>

                <div>
                    <InputLabel for="amount" value="Monto" />
                    <input id="amount" v-model="form.amount" type="number" min="0.01" step="0.01" class="form-input" />
                    <InputError :message="form.errors.amount" />
                </div>

                <div>
                    <InputLabel for="transfer_date" value="Fecha" />
                    <input id="transfer_date" v-model="form.transfer_date" type="date" class="form-input" />
                    <InputError :message="form.errors.transfer_date" />
                </div>

                <div v-if="crossCurrency" class="md:col-span-2 rounded border border-primary/30 bg-primary/5 p-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel for="exchange_rate" value="Tipo de cambio" />
                            <input id="exchange_rate" v-model="form.exchange_rate" type="number" min="0.0001" step="0.0001" class="form-input" />
                            <InputError :message="form.errors.exchange_rate" />
                        </div>
                        <div class="text-sm">
                            <p class="text-gray-600 dark:text-gray-300">
                                Se acreditarán <strong>{{ money(preview.destination, toAccount?.currency) }}</strong>
                                en la cuenta destino.
                            </p>
                            <p v-if="rates?.[rateCode]?.is_stale" class="mt-1 flex items-center gap-1 text-xs text-warning">
                                <font-awesome-icon :icon="faTriangleExclamation" />
                                TC del {{ rates[rateCode].date }} (desactualizado)
                            </p>
                            <p v-if="!rate" class="mt-1 text-xs text-danger">
                                No hay tipo de cambio vigente. Actualízalo antes de transferir.
                            </p>
                        </div>
                    </div>
                </div>

                <div>
                    <InputLabel for="to_fixed_fee" value="Comisión fija del destino" />
                    <input id="to_fixed_fee" v-model="form.to_fixed_fee" type="number" min="0" step="0.01" class="form-input" />
                </div>

                <div>
                    <InputLabel for="to_percent_fee" value="Comisión % del destino" />
                    <input id="to_percent_fee" v-model="form.to_percent_fee" type="number" min="0" max="100" step="0.001" class="form-input" />
                </div>

                <div class="md:col-span-2">
                    <label class="flex items-center gap-2">
                        <input v-model="form.own_account_itf_free" type="checkbox" class="form-checkbox" />
                        <span>Transferencia de mi propia cuenta libre de ITF</span>
                    </label>
                    <p class="mt-1 text-xs text-gray-500">
                        Según SUNAT (Ley 28194, art. 9). Esta elección se guarda para este par de cuentas.
                    </p>
                </div>

                <div class="md:col-span-2">
                    <label class="flex items-center gap-2">
                        <input v-model="form.charge_origin_fee" type="checkbox" class="form-checkbox" />
                        <span>Cobrar comisión también al origen (entre cuentas propias)</span>
                    </label>
                </div>

                <template v-if="form.charge_origin_fee">
                    <div>
                        <InputLabel for="from_fixed_fee" value="Comisión fija del origen" />
                        <input id="from_fixed_fee" v-model="form.from_fixed_fee" type="number" min="0" step="0.01" class="form-input" />
                    </div>
                    <div>
                        <InputLabel for="from_percent_fee" value="Comisión % del origen" />
                        <input id="from_percent_fee" v-model="form.from_percent_fee" type="number" min="0" max="100" step="0.001" class="form-input" />
                    </div>
                </template>

                <div class="md:col-span-2 rounded border border-gray-200 p-3 text-sm dark:border-gray-700">
                    <p class="text-xs font-semibold uppercase text-gray-500">Gastos de la transferencia (se registran como egresos aparte)</p>
                    <div class="mt-2 grid grid-cols-1 gap-1 sm:grid-cols-2">
                        <p>Comisión origen: {{ money(preview.fromFee, fromAccount?.currency) }}</p>
                        <p>IGV origen (18 %): {{ money(preview.fromIgv, fromAccount?.currency) }}</p>
                        <p>Comisión destino: {{ money(preview.toFee, toAccount?.currency) }}</p>
                        <p>IGV destino (18 %): {{ money(preview.toIgv, toAccount?.currency) }}</p>
                        <p>ITF: {{ money(preview.itf, fromAccount?.currency) }} (0,005 % desde {{ money(itfThreshold) }})</p>
                        <p class="font-medium">Total gastos: {{ money(preview.expenses, fromAccount?.currency) }}</p>
                    </div>
                    <p class="mt-2 font-semibold">Neto que recibe el destino: {{ money(preview.net, toAccount?.currency) }}</p>
                </div>

                <div class="md:col-span-2">
                    <PrimaryButton type="submit" :disabled="form.processing || (crossCurrency && !rate)">
                        Registrar transferencia
                    </PrimaryButton>
                </div>
            </form>
        </div>

        <div class="panel mt-6">
            <h2 class="mb-4 text-lg font-semibold">Últimas transferencias</h2>
            <div class="overflow-x-auto">
                <table class="table-hover w-full">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Origen</th>
                            <th>Destino</th>
                            <th class="text-right">Sale</th>
                            <th class="text-right">Llega</th>
                            <th class="text-right">Comisión</th>
                            <th class="text-right">IGV</th>
                            <th class="text-right">ITF</th>
                            <th class="text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="transfers.length === 0">
                            <td colspan="9" class="text-center text-gray-500">Sin transferencias registradas.</td>
                        </tr>
                        <tr v-for="t in transfers" :key="t.id" :class="t.voided ? 'opacity-50' : ''">
                            <td>{{ t.transfer_date }}</td>
                            <td>{{ t.from }}</td>
                            <td>{{ t.to }}</td>
                            <td class="text-right">
                                {{ money(t.amount, t.from_currency) }}
                                <p v-if="t.exchange_rate" class="text-xs text-gray-500">TC {{ t.exchange_rate }}</p>
                            </td>
                            <td class="text-right">{{ money(t.amount_destination, t.to_currency) }}</td>
                            <td class="text-right">{{ money((t.from_fee_amount || 0) + (t.to_fee_amount || 0), t.from_currency) }}</td>
                            <td class="text-right">{{ money((t.from_fee_igv || 0) + (t.to_fee_igv || 0), t.from_currency) }}</td>
                            <td class="text-right">{{ t.itf_applies ? money(t.itf_amount, t.from_currency) : 'Libre' }}</td>
                            <td class="text-right">
                                <span v-if="t.voided" class="text-xs text-danger">Anulada</span>
                                <button
                                    v-else-if="canVoid"
                                    type="button"
                                    class="btn btn-sm btn-outline-danger"
                                    title="Anular transferencia"
                                    @click="voidTransfer(t)"
                                >
                                    <font-awesome-icon :icon="faBan" />
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
