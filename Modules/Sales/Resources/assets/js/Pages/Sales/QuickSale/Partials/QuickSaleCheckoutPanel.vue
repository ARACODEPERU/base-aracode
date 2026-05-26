<script setup>
import { ref, computed, watch, nextTick } from 'vue';
import ModalLarge from '@/Components/ModalLargeX.vue';
import QuickSaleCashContent from './QuickSaleCashContent.vue';

const props = defineProps({
    total: { type: Number, default: 0 },
    cartLength: { type: Number, default: 0 },
    saving: { type: Boolean, default: false },
    paymentMethods: { type: Array, default: () => [] },
    compact: { type: Boolean, default: false },
});

const emit = defineEmits(['checkout', 'cash-panel-toggle']);

const cashPanelEl = ref(null);
const showCashModal = ref(false);
const showOtherPayments = ref(false);
const useSplitPayment = ref(false);
const cashContentRef = ref(null);

const otherPayments = ref([
    { payment_method_id: null, amount: 0, reference: '' },
]);

const methodId = (keywords) => {
    const list = props.paymentMethods || [];
    const found = list.find((pm) =>
        keywords.some((k) => pm.description?.toLowerCase().includes(k))
    );
    return found?.id ?? null;
};

const yapeId = computed(() => methodId(['yape']));
const plinId = computed(() => methodId(['plin']));
const cashId = computed(() => methodId(['efectivo']));

const canCheckout = computed(() => props.cartLength > 0 && !props.saving);

const otherPaymentsTotal = computed(() =>
    otherPayments.value.reduce((s, p) => s + (parseFloat(p.amount) || 0), 0)
);

const otherPaymentsRemaining = computed(() =>
    Math.round((props.total - otherPaymentsTotal.value) * 100) / 100
);

const formatMoney = (n) => `S/ ${Number(n || 0).toFixed(2)}`;

const buildPayload = (mode, payments, extra = {}) => ({
    mode,
    payments,
    payment_amount: props.total,
    ...extra,
});

const emitQuickPay = (paymentMethodId, methodKey) => {
    if (!canCheckout.value || !paymentMethodId) return;
    emit('checkout', buildPayload('quick', [{
        payment_method_id: paymentMethodId,
        amount: props.total,
        reference: null,
    }], { quick_method: methodKey }));
};

const payYape = () => emitQuickPay(yapeId.value, 'yape');
const payPlin = () => emitQuickPay(plinId.value, 'plin');

const openCashModal = () => {
    if (!canCheckout.value) return;
    showCashModal.value = true;
    emit('cash-panel-toggle', true);
    nextTick(() => {
        cashContentRef.value?.reset();
        cashPanelEl.value?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
};

const closeCashModal = () => {
    showCashModal.value = false;
    emit('cash-panel-toggle', false);
};

const onCashConfirm = (cash) => {
    if (!cashId.value) return;
    emit('checkout', buildPayload('cash', [{
        payment_method_id: cashId.value,
        amount: props.total,
        reference: null,
    }], { cash }));
    closeCashModal();
};

const addOtherPaymentLine = () => {
    otherPayments.value.push({ payment_method_id: null, amount: 0, reference: '' });
};

const removeOtherPaymentLine = (index) => {
    if (otherPayments.value.length <= 1) return;
    otherPayments.value.splice(index, 1);
};

const confirmOtherPay = () => {
    if (!canCheckout.value) return;
    const lines = otherPayments.value
        .filter((p) => p.payment_method_id && parseFloat(p.amount) > 0)
        .map((p) => ({
            payment_method_id: p.payment_method_id,
            amount: parseFloat(p.amount),
            reference: p.reference || null,
        }));

    if (lines.length === 0) return;

    if (useSplitPayment.value && Math.abs(otherPaymentsRemaining.value) > 0.01) {
        return;
    }

    if (!useSplitPayment.value) {
        const line = lines[0];
        emit('checkout', buildPayload('other', [{
            ...line,
            amount: props.total,
        }]));
        return;
    }

    emit('checkout', buildPayload('split', lines));
};

watch(showOtherPayments, (open) => {
    if (open && otherPayments.value.length === 1) {
        otherPayments.value[0].amount = props.total;
        if (!otherPayments.value[0].payment_method_id && props.paymentMethods[0]) {
            otherPayments.value[0].payment_method_id = props.paymentMethods[0].id;
        }
    }
});

watch(() => props.total, (t) => {
    if (!useSplitPayment.value && otherPayments.value[0]) {
        otherPayments.value[0].amount = t;
    }
});
</script>

<template>
    <div class="space-y-3">
        <div class="flex justify-between items-baseline">
            <span class="text-sm text-gray-500 dark:text-gray-400">Total a cobrar</span>
            <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatMoney(total) }}</span>
        </div>

        <!-- Cobro rápido (oculto mientras se cobra en efectivo inline) -->
        <div v-show="!(compact && showCashModal)">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-2">
                Cobro rápido
            </p>
            <div class="grid grid-cols-3 gap-2">
                <button
                    type="button"
                    :disabled="!canCheckout || !yapeId"
                    @click="payYape"
                    class="qs-pay-btn bg-purple-600 hover:bg-purple-700 text-white disabled:opacity-40"
                    title="Cobrar con Yape"
                >
                    <span class="qs-pay-icon">Y</span>
                    <span>Yape</span>
                </button>
                <button
                    type="button"
                    :disabled="!canCheckout || !plinId"
                    @click="payPlin"
                    class="qs-pay-btn bg-teal-600 hover:bg-teal-700 text-white disabled:opacity-40"
                    title="Cobrar con Plin"
                >
                    <span class="qs-pay-icon">P</span>
                    <span>Plin</span>
                </button>
                <button
                    type="button"
                    :disabled="!canCheckout || !cashId"
                    @click="openCashModal"
                    class="qs-pay-btn bg-emerald-600 hover:bg-emerald-700 text-white disabled:opacity-40"
                    title="Cobrar en efectivo"
                >
                    <span class="qs-pay-icon">$</span>
                    <span>Efectivo</span>
                </button>
            </div>
        </div>

        <!-- Efectivo inline (vista táctil: evita modal recortado por overflow/z-index) -->
        <div
            v-if="compact && showCashModal"
            ref="cashPanelEl"
            class="rounded-xl border-2 border-emerald-500 dark:border-emerald-600 bg-white dark:bg-zinc-900 p-4 shadow-lg"
        >
            <div class="flex justify-between items-center mb-3">
                <h4 class="font-bold text-emerald-700 dark:text-emerald-300">Cobro en efectivo</h4>
                <button
                    type="button"
                    @click="closeCashModal"
                    class="w-8 h-8 rounded-full bg-gray-100 dark:bg-zinc-700 text-gray-600 dark:text-gray-300 text-xl leading-none"
                >
                    ×
                </button>
            </div>
            <QuickSaleCashContent
                ref="cashContentRef"
                :total="total"
                :touch-mode="compact"
                @confirm="onCashConfirm"
                @cancel="closeCashModal"
            />
        </div>

        <!-- Otras formas (opcional) -->
        <div v-show="!(compact && showCashModal)" class="border border-gray-200 dark:border-zinc-600 rounded-xl overflow-hidden">
            <button
                type="button"
                @click="showOtherPayments = !showOtherPayments"
                class="w-full flex items-center justify-between px-3 py-2.5 text-sm font-medium bg-gray-50 dark:bg-zinc-700/80 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-zinc-700 transition"
            >
                <span>Otras formas de pago</span>
                <span class="text-xs text-gray-500">{{ showOtherPayments ? '▲' : '▼' }}</span>
            </button>

            <div v-show="showOtherPayments" class="p-3 space-y-3 bg-white dark:bg-zinc-800">
                <label class="flex items-center gap-2 text-sm cursor-pointer">
                    <input v-model="useSplitPayment" type="checkbox" class="form-checkbox rounded" />
                    <span>Pago combinado (varios métodos en una venta)</span>
                </label>

                <div
                    v-for="(line, index) in otherPayments"
                    :key="index"
                    class="grid gap-2"
                    :class="compact ? 'grid-cols-1' : 'grid-cols-12'"
                >
                    <div :class="compact ? '' : 'col-span-5'">
                        <label class="text-xs text-gray-500 mb-0.5 block">Método</label>
                        <select v-model="line.payment_method_id" class="form-select w-full text-sm py-2">
                            <option :value="null">Seleccionar</option>
                            <option v-for="pm in paymentMethods" :key="pm.id" :value="pm.id">
                                {{ pm.description }}
                            </option>
                        </select>
                    </div>
                    <div :class="compact ? '' : 'col-span-3'">
                        <label class="text-xs text-gray-500 mb-0.5 block">Monto</label>
                        <input
                            v-model.number="line.amount"
                            type="number"
                            min="0"
                            step="0.01"
                            :disabled="!useSplitPayment && index === 0"
                            class="form-input w-full text-sm py-2"
                            placeholder="0.00"
                        />
                    </div>
                    <div :class="compact ? '' : 'col-span-4'">
                        <label class="text-xs text-gray-500 mb-0.5 block">Referencia</label>
                        <div class="flex gap-1">
                            <input
                                v-model="line.reference"
                                type="text"
                                class="form-input w-full text-sm py-2"
                                placeholder="Nº operación, banco..."
                            />
                            <button
                                v-if="useSplitPayment && otherPayments.length > 1"
                                type="button"
                                @click="removeOtherPaymentLine(index)"
                                class="shrink-0 px-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded"
                            >
                                ×
                            </button>
                        </div>
                    </div>
                </div>

                <button
                    v-if="useSplitPayment"
                    type="button"
                    @click="addOtherPaymentLine"
                    class="text-sm text-primary hover:underline"
                >
                    + Agregar otro método
                </button>

                <div
                    v-if="useSplitPayment"
                    class="text-sm rounded-lg px-3 py-2"
                    :class="Math.abs(otherPaymentsRemaining) < 0.01
                        ? 'bg-green-50 text-green-800 dark:bg-green-900/30 dark:text-green-300'
                        : 'bg-amber-50 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300'"
                >
                    <span v-if="Math.abs(otherPaymentsRemaining) < 0.01">Montos cuadran con el total.</span>
                    <span v-else>
                        Falta o sobra: <strong>{{ formatMoney(Math.abs(otherPaymentsRemaining)) }}</strong>
                        ({{ otherPaymentsRemaining > 0 ? 'por asignar' : 'de más' }})
                    </span>
                </div>

                <button
                    type="button"
                    :disabled="!canCheckout"
                    @click="confirmOtherPay"
                    class="w-full py-2.5 bg-slate-600 hover:bg-slate-700 text-white text-sm font-semibold rounded-xl disabled:opacity-40 transition"
                >
                    Cobrar (otro método)
                </button>
            </div>
        </div>

        <p v-if="saving" class="text-center text-sm text-primary animate-pulse">Procesando venta...</p>

        <!-- Modal efectivo (vista web, teleport al body) -->
        <Teleport to="body">
            <ModalLarge
                v-if="!compact"
                :show="showCashModal"
                :on-close="closeCashModal"
                :icon="'/img/comunidad.png'"
                z-index="z-[100]"
            >
                <template #title>Cobro en efectivo</template>
                <template #message>
                    Ingresa el monto recibido o toca monedas y billetes.
                </template>
                <template #content>
                    <QuickSaleCashContent
                        ref="cashContentRef"
                        :total="total"
                        @confirm="onCashConfirm"
                        @cancel="closeCashModal"
                    />
                </template>
                <template #buttons />
            </ModalLarge>
        </Teleport>
    </div>
</template>

<style scoped>
.qs-pay-btn {
    @apply flex flex-col items-center justify-center gap-1 py-3 px-2 rounded-xl text-sm font-bold transition active:scale-95 disabled:cursor-not-allowed;
}
.qs-pay-icon {
    @apply w-8 h-8 rounded-full bg-white/20 flex items-center justify-center text-base font-black;
}
</style>
