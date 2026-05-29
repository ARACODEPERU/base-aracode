<script setup>
import ModalLarge from '@/Components/ModalLarge.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import GreenButton from '@/Components/GreenButton.vue';
import iconLoader from '@/Components/vristo/icon/icon-loader.vue';
import Swal2 from 'sweetalert2';
import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    show: { type: Boolean, default: false },
    rentalId: { type: Number, default: null },
    reloadOnly: { type: Array, default: () => ['rentals'] },
});

const emit = defineEmits(['close']);

const loading = ref(false);
const saving = ref(false);
const formalizingAdvance = ref(false);
const payload = ref(null);
const amount = ref('');
const notes = ref('');
const paymentMethodId = ref('');
const advancePaymentMethodId = ref('');
const advanceReference = ref('');

const close = () => emit('close');

const summary = computed(() => payload.value?.summary || {});
const payments = computed(() => payload.value?.payments || []);
const linePreview = computed(() => payload.value?.line_preview || []);
const rental = computed(() => payload.value?.rental || null);
const paymentMethods = computed(() => payload.value?.payment_methods || []);
const unformalizedAdvance = computed(() => Number(summary.value.unformalized_advance || 0));
const hasPendingAdvance = computed(() => unformalizedAdvance.value > 0 && rental.value?.reservation_status !== 'cancelled');

const formatMoney = (value) => `S/ ${Number(value || 0).toFixed(2)}`;

const loadPayments = async () => {
    if (!props.rentalId) return;

    loading.value = true;

    try {
        const response = await axios.get(route('even_alquiler_local_payments', props.rentalId));
        payload.value = response.data;
        amount.value = Number(response.data.summary?.balance_amount || 0).toFixed(2);
        paymentMethodId.value = response.data.payment_methods?.[0]?.id || '';
        advancePaymentMethodId.value = response.data.payment_methods?.[0]?.id || '';
    } catch (error) {
        Swal2.fire({
            icon: 'error',
            text: 'No se pudo cargar la información de pagos.',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    } finally {
        loading.value = false;
    }
};

watch(() => [props.show, props.rentalId], ([visible]) => {
    if (visible) {
        loadPayments();
    }
});

const printPdf = (saleId) => {
    if (!saleId) return;
    window.open(route('printA4pdf_sales', saleId), '_blank');
};

const formalizeAdvance = async () => {
    if (!props.rentalId || !advancePaymentMethodId.value) {
        Swal2.fire({ icon: 'warning', text: 'Seleccione un método de pago.', customClass: 'sweet-alerts', padding: '2em' });
        return;
    }

    formalizingAdvance.value = true;

    try {
        const response = await axios.post(route('even_alquiler_local_formalize_advance', props.rentalId), {
            payment_method_id: Number(advancePaymentMethodId.value),
            reference: advanceReference.value || null,
        });

        await loadPayments();

        Swal2.fire({
            title: 'Enhorabuena',
            text: 'Nota de venta del adelanto emitida correctamente',
            icon: 'success',
            showCancelButton: true,
            confirmButtonText: 'Generar PDF',
            cancelButtonText: 'Cerrar',
            padding: '2em',
            customClass: 'sweet-alerts',
        }).then((result) => {
            if (result.isConfirmed && response.data.sale_id) {
                printPdf(response.data.sale_id);
            }
        });

        router.reload({ only: props.reloadOnly, preserveScroll: true });
    } catch (error) {
        Swal2.fire({
            icon: 'error',
            text: error.response?.data?.message || 'No se pudo emitir la nota de venta del adelanto.',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    } finally {
        formalizingAdvance.value = false;
    }
};

const registerPayment = async () => {
    if (!props.rentalId || !amount.value || Number(amount.value) <= 0) {
        return;
    }

    if (!paymentMethodId.value) {
        Swal2.fire({ icon: 'warning', text: 'Seleccione un método de pago.', customClass: 'sweet-alerts', padding: '2em' });
        return;
    }

    saving.value = true;

    try {
        const response = await axios.post(route('even_alquiler_local_store_payment', props.rentalId), {
            amount: Number(amount.value),
            notes: notes.value,
            emit_note: true,
            payments: [{
                payment_method_id: Number(paymentMethodId.value),
                amount: Number(amount.value),
            }],
        });

        await loadPayments();

        Swal2.fire({
            title: 'Enhorabuena',
            text: 'Abono registrado correctamente',
            icon: 'success',
            showCancelButton: true,
            confirmButtonText: 'Generar PDF',
            cancelButtonText: 'Cerrar',
            padding: '2em',
            customClass: 'sweet-alerts',
        }).then((result) => {
            if (result.isConfirmed && response.data.sale_id) {
                printPdf(response.data.sale_id);
            }
        });

        router.reload({ only: props.reloadOnly, preserveScroll: true });
    } catch (error) {
        Swal2.fire({
            icon: 'error',
            text: error.response?.data?.message || 'No se pudo registrar el abono.',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    } finally {
        saving.value = false;
    }
};
</script>

<template>
    <ModalLarge :show="show" :on-close="close" :icon="'/img/caja-registradora.png'">
        <template #title>Ver pagos</template>
        <template #message>
            <span v-if="rental">Reserva #{{ rental.id }} — {{ rental.customer?.full_name }}</span>
        </template>
        <template #content>
            <div v-if="loading" class="flex justify-center py-8">
                <icon-loader class="w-8 h-8 animate-spin" />
            </div>
            <div v-else-if="payload" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="panel p-3">
                        <p class="text-sm text-gray-500">Total</p>
                        <p class="text-lg font-semibold">{{ formatMoney(summary.total_price) }}</p>
                    </div>
                    <div class="panel p-3">
                        <p class="text-sm text-gray-500">Pagado</p>
                        <p class="text-lg font-semibold text-success">{{ formatMoney(summary.paid_amount) }}</p>
                    </div>
                    <div class="panel p-3">
                        <p class="text-sm text-gray-500">Saldo</p>
                        <p class="text-lg font-semibold text-danger">{{ formatMoney(summary.balance_amount) }}</p>
                    </div>
                </div>

                <div
                    v-if="hasPendingAdvance"
                    class="panel p-4 border border-warning bg-warning/10 space-y-4"
                >
                    <div>
                        <h4 class="font-semibold text-warning">Adelanto al reservar (sin nota de venta)</h4>
                        <p class="text-sm text-gray-600 mt-1">
                            Al crear la reserva se registró un adelanto de
                            <strong>{{ formatMoney(unformalizedAdvance) }}</strong>.
                            Formalícelo aquí para emitir la nota de venta correspondiente.
                        </p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Método de pago del adelanto" />
                            <select v-model="advancePaymentMethodId" class="form-select w-full">
                                <option v-for="method in paymentMethods" :key="method.id" :value="method.id">
                                    {{ method.description }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <InputLabel value="Referencia (opcional)" />
                            <TextInput v-model="advanceReference" type="text" class="w-full" />
                        </div>
                    </div>
                    <button
                        type="button"
                        class="btn btn-warning"
                        :disabled="formalizingAdvance"
                        @click="formalizeAdvance"
                    >
                        <icon-loader v-if="formalizingAdvance" class="w-4 h-4 animate-spin mr-1 inline" />
                        Emitir NV del adelanto ({{ formatMoney(unformalizedAdvance) }})
                    </button>
                </div>

                <div>
                    <h4 class="font-semibold mb-2">Desglose del alquiler</h4>
                    <div class="panel p-3 space-y-1">
                        <div v-for="(line, index) in linePreview" :key="index" class="flex justify-between text-sm">
                            <span>{{ line.description }}</span>
                            <span>{{ formatMoney(line.amount) }}</span>
                        </div>
                    </div>
                </div>

                <div>
                    <h4 class="font-semibold mb-2">Historial de pagos</h4>
                    <div class="table-responsive panel p-0">
                        <table class="table-striped table-hover w-full">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Concepto</th>
                                    <th>Monto</th>
                                    <th>Método</th>
                                    <th>Nota venta</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="payment in payments" :key="payment.id">
                                    <td>{{ new Date(payment.created_at).toLocaleString('es-PE') }}</td>
                                    <td>{{ payment.notes || 'Abono' }}</td>
                                    <td>{{ formatMoney(payment.amount) }}</td>
                                    <td>{{ payment.payment_method?.description || '—' }}</td>
                                    <td>
                                        <span v-if="payment.sale_id">NV #{{ payment.sale_id }}</span>
                                        <span v-else class="text-gray-500">—</span>
                                    </td>
                                    <td>
                                        <button
                                            v-if="payment.sale_id"
                                            type="button"
                                            class="btn btn-sm btn-primary"
                                            @click="printPdf(payment.sale_id)"
                                        >
                                            Generar
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="!payments.length && !hasPendingAdvance">
                                    <td colspan="6" class="text-center py-4 text-gray-500">Sin pagos formalizados.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div v-if="Number(summary.balance_amount) > 0 && rental?.reservation_status !== 'cancelled'" class="panel p-4 space-y-4">
                    <h4 class="font-semibold">Registrar abono (saldo pendiente)</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <InputLabel value="Monto (S/)" />
                            <TextInput v-model="amount" type="number" min="0.01" step="0.01" class="w-full" />
                        </div>
                        <div>
                            <InputLabel value="Método de pago" />
                            <select v-model="paymentMethodId" class="form-select w-full">
                                <option v-for="method in paymentMethods" :key="method.id" :value="method.id">
                                    {{ method.description }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <InputLabel value="Notas" />
                            <TextInput v-model="notes" type="text" class="w-full" />
                        </div>
                    </div>
                </div>
            </div>
        </template>
        <template #buttons>
            <GreenButton
                v-if="Number(summary.balance_amount) > 0 && rental?.reservation_status !== 'cancelled'"
                :disabled="saving"
                @click="registerPayment"
            >
                <icon-loader v-if="saving" class="w-4 h-4 animate-spin mr-1" />
                Registrar abono y NV
            </GreenButton>
        </template>
    </ModalLarge>
</template>
