<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import { Head, useForm } from '@inertiajs/vue3';
    import { computed, watch } from 'vue';
    import InputLabel from '@/Components/InputLabel.vue';
    import InputError from '@/Components/InputError.vue';
    import PrimaryButton from '@/Components/PrimaryButton.vue';

    const props = defineProps({
        accounts: { type: Array, default: () => [] },
        transfers: { type: Array, default: () => [] },
        itfThreshold: { type: Number, default: 1000 },
    });

    const money = (value) =>
        new Intl.NumberFormat('es-PE', { style: 'currency', currency: 'PEN' }).format(value || 0);

    const form = useForm({
        from_account_id: null,
        to_account_id: null,
        transfer_date: new Date().toISOString().slice(0, 10),
        amount: null,
        reference: '',
        // Comisión del destino: siempre se pregunta
        to_fixed_fee: 0,
        to_percent_fee: 0,
        // Comisión del origen: solo entre cuentas propias (aquí siempre lo son)
        charge_origin_fee: false,
        from_fixed_fee: 0,
        from_percent_fee: 0,
        // Check: transferencia de mi propia cuenta libre de ITF (se guarda por par)
        own_account_itf_free: false,
    });

    // Vista previa del desglose (cálculo local, mismo criterio que el servidor)
    const preview = computed(() => {
        const amount = Number(form.amount) || 0;
        const toFee = Number(form.to_fixed_fee) + amount * Number(form.to_percent_fee) / 100;
        const fromFee = form.charge_origin_fee
            ? Number(form.from_fixed_fee) + amount * Number(form.from_percent_fee) / 100
            : 0;
        const itf = !form.own_account_itf_free && amount >= props.itfThreshold ? amount * 0.00005 : 0;
        return {
            fromFee: Math.round(fromFee * 100) / 100,
            toFee: Math.round(toFee * 100) / 100,
            itf: Math.round(itf * 100) / 100,
            net: Math.max(0, Math.round((amount - fromFee - toFee - itf) * 100) / 100),
        };
    });

    // Al elegir el par, carga las comisiones e ITF guardados para ese par
    watch(
        () => [form.from_account_id, form.to_account_id],
        async ([from, to]) => {
            if (!from || !to || from === to) return;
            const params = new URLSearchParams({ from_account_id: from, to_account_id: to });
            const response = await fetch(route('treasury_transfers_profile') + '?' + params, {
                headers: { Accept: 'application/json' },
            });
            const profile = await response.json();
            if (!profile) return;
            form.to_fixed_fee = profile.to_fixed_fee;
            form.to_percent_fee = profile.to_percent_fee;
            form.from_fixed_fee = profile.from_fixed_fee;
            form.from_percent_fee = profile.from_percent_fee;
            form.own_account_itf_free = !!profile.own_account_itf_free;
        }
    );

    const submit = () => {
        form.post(route('treasury_transfers_store'), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset('amount', 'reference');
                window.alertaSistema('Transferencia registrada', 'La transferencia se guardó correctamente.', 'success');
            },
            onError: () => {
                window.alertaSistema('Revisa el formulario', 'Hay datos que no son válidos.', 'warning');
            },
        });
    };
</script>

<template>
    <AppLayout title="Transferencias de tesorería">
        <Head>
            <meta name="aracodito-view" content="new" />
        </Head>
        <Head title="Tesorería · Transferencias" />
        <Navigation>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Tesorería</span>
            </li>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Transferencias</span>
            </li>
        </Navigation>

        <div class="panel mt-6">
            <h2 class="mb-4 text-lg font-semibold">Nueva transferencia</h2>

            <form class="grid grid-cols-1 gap-4 md:grid-cols-2" @submit.prevent="submit">
                <div>
                    <InputLabel for="from_account_id" value="Cuenta origen" />
                    <select id="from_account_id" v-model="form.from_account_id" class="form-select">
                        <option :value="null" disabled>Seleccione</option>
                        <option v-for="a in accounts" :key="a.id" :value="a.id">{{ a.label }}</option>
                    </select>
                    <InputError :message="form.errors.from_account_id" />
                </div>

                <div>
                    <InputLabel for="to_account_id" value="Cuenta destino" />
                    <select id="to_account_id" v-model="form.to_account_id" class="form-select">
                        <option :value="null" disabled>Seleccione</option>
                        <option v-for="a in accounts" :key="a.id" :value="a.id">{{ a.label }}</option>
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

                <div>
                    <InputLabel for="to_fixed_fee" value="Comisión fija del destino (S/)" />
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
                        <InputLabel for="from_fixed_fee" value="Comisión fija del origen (S/)" />
                        <input id="from_fixed_fee" v-model="form.from_fixed_fee" type="number" min="0" step="0.01" class="form-input" />
                    </div>
                    <div>
                        <InputLabel for="from_percent_fee" value="Comisión % del origen" />
                        <input id="from_percent_fee" v-model="form.from_percent_fee" type="number" min="0" max="100" step="0.001" class="form-input" />
                    </div>
                </template>

                <div class="md:col-span-2 rounded border border-gray-200 p-3 text-sm dark:border-gray-700">
                    <p>Comisión origen: {{ money(preview.fromFee) }} · Comisión destino: {{ money(preview.toFee) }}</p>
                    <p>ITF: {{ money(preview.itf) }} (0,005 % desde {{ money(itfThreshold) }})</p>
                    <p class="font-semibold">Neto que recibe el destino: {{ money(preview.net) }}</p>
                </div>

                <div class="md:col-span-2">
                    <PrimaryButton type="submit" :disabled="form.processing">Registrar transferencia</PrimaryButton>
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
                            <th class="text-right">Monto</th>
                            <th class="text-right">ITF</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="transfers.length === 0">
                            <td colspan="5" class="text-center text-gray-500">Sin transferencias registradas.</td>
                        </tr>
                        <tr v-for="t in transfers" :key="t.id">
                            <td>{{ t.transfer_date }}</td>
                            <td>{{ t.from }}</td>
                            <td>{{ t.to }}</td>
                            <td class="text-right">{{ money(t.amount) }}</td>
                            <td class="text-right">{{ t.itf_applies ? money(t.itf_amount) : 'Libre' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
