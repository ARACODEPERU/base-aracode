<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    total: { type: Number, default: 0 },
    /** Vista táctil / panel amplio: botones grandes y rejilla acotada */
    touchMode: { type: Boolean, default: false },
});

const emit = defineEmits(['confirm', 'cancel']);

const PERU_DENOMINATIONS = [
    { value: 0.1, label: 'S/ 0.10', type: 'coin' },
    { value: 0.2, label: 'S/ 0.20', type: 'coin' },
    { value: 0.5, label: 'S/ 0.50', type: 'coin' },
    { value: 1, label: 'S/ 1', type: 'coin' },
    { value: 2, label: 'S/ 2', type: 'coin' },
    { value: 5, label: 'S/ 5', type: 'coin' },
    { value: 10, label: 'S/ 10', type: 'bill' },
    { value: 20, label: 'S/ 20', type: 'bill' },
    { value: 50, label: 'S/ 50', type: 'bill' },
    { value: 100, label: 'S/ 100', type: 'bill' },
    { value: 200, label: 'S/ 200', type: 'bill' },
];

const cashTendered = ref(0);
const cashDenominations = ref([]);

const cashChange = computed(() => Math.max(0, cashTendered.value - props.total));
const cashInsufficient = computed(() => cashTendered.value > 0 && cashTendered.value < props.total);

const formatMoney = (n) => `S/ ${Number(n || 0).toFixed(2)}`;

const addDenomination = (denom) => {
    cashTendered.value = Math.round((cashTendered.value + denom.value) * 100) / 100;
    const existing = cashDenominations.value.find((d) => d.value === denom.value);
    if (existing) {
        existing.count += 1;
    } else {
        cashDenominations.value.push({ value: denom.value, label: denom.label, count: 1 });
    }
};

const setExactCash = () => {
    cashTendered.value = props.total;
};

const clearCash = () => {
    cashTendered.value = 0;
    cashDenominations.value = [];
};

const reset = () => {
    clearCash();
};

const confirm = () => {
    if (cashTendered.value < props.total) return;
    emit('confirm', {
        tendered: cashTendered.value,
        change: cashChange.value,
        denominations: [...cashDenominations.value],
    });
};

defineExpose({ reset });
</script>

<template>
    <div class="qs-cash" :class="{ 'qs-cash--touch': touchMode }">
        <div class="qs-summary">
            <div
                class="qs-summary-card"
                :class="touchMode
                    ? 'bg-zinc-800 border-zinc-500'
                    : 'bg-gray-100 border-gray-300'"
            >
                <p class="qs-summary-label" :class="touchMode ? 'text-zinc-400' : 'text-gray-600'">Total</p>
                <p class="qs-summary-value" :class="touchMode ? 'text-white' : 'text-gray-900'">{{ formatMoney(total) }}</p>
            </div>
            <div
                class="qs-summary-card"
                :class="touchMode
                    ? 'bg-sky-950 border-sky-500'
                    : 'bg-sky-100 border-sky-400'"
            >
                <p class="qs-summary-label" :class="touchMode ? 'text-sky-300' : 'text-sky-800'">Recibido</p>
                <p class="qs-summary-value" :class="touchMode ? 'text-sky-50' : 'text-sky-950'">{{ formatMoney(cashTendered) }}</p>
            </div>
            <div
                class="qs-summary-card"
                :class="touchMode
                    ? (cashInsufficient ? 'bg-red-950 border-red-500' : 'bg-emerald-950 border-emerald-500')
                    : (cashInsufficient ? 'bg-red-100 border-red-400' : 'bg-emerald-100 border-emerald-400')"
            >
                <p
                    class="qs-summary-label"
                    :class="touchMode
                        ? (cashInsufficient ? 'text-red-300' : 'text-emerald-300')
                        : (cashInsufficient ? 'text-red-800' : 'text-emerald-800')"
                >Vuelto</p>
                <p
                    class="qs-summary-value"
                    :class="touchMode
                        ? (cashInsufficient ? 'text-red-50' : 'text-emerald-50')
                        : (cashInsufficient ? 'text-red-950' : 'text-emerald-950')"
                >{{ formatMoney(cashChange) }}</p>
            </div>
        </div>

        <div>
            <label class="qs-field-label">Monto recibido</label>
            <div class="qs-amount-row">
                <input
                    v-model.number="cashTendered"
                    type="number"
                    min="0"
                    step="0.01"
                    inputmode="decimal"
                    class="form-input qs-amount-input"
                />
                <button type="button" @click="setExactCash" class="btn btn-outline-primary qs-action-btn">
                    Exacto
                </button>
                <button type="button" @click="clearCash" class="btn btn-outline-danger qs-action-btn">
                    Limpiar
                </button>
            </div>
            <p v-if="cashInsufficient" class="qs-error-text">
                Falta {{ formatMoney(total - cashTendered) }} para completar el pago.
            </p>
        </div>

        <div>
            <p class="qs-section-title">Monedas y billetes</p>
            <div class="qs-denom-grid">
                <button
                    v-for="denom in PERU_DENOMINATIONS"
                    :key="denom.value"
                    type="button"
                    @click="addDenomination(denom)"
                    class="qs-denom-btn"
                    :class="denom.type === 'bill' ? 'qs-denom-bill' : 'qs-denom-coin'"
                >
                    {{ denom.label }}
                </button>
            </div>
        </div>

        <div
            v-if="cashDenominations.length"
            class="qs-breakdown rounded-lg border p-3"
            :class="touchMode
                ? 'bg-zinc-800 border-zinc-600'
                : 'bg-gray-50 border-gray-200 dark:bg-zinc-800 dark:border-zinc-600'"
        >
            <p class="qs-breakdown-title" :class="touchMode ? 'text-zinc-400' : 'text-gray-600 dark:text-zinc-400'">
                Desglose ingresado
            </p>
            <ul class="qs-breakdown-list">
                <li
                    v-for="d in cashDenominations"
                    :key="d.value"
                    class="qs-breakdown-row"
                    :class="touchMode ? 'text-zinc-100' : 'text-gray-800 dark:text-zinc-200'"
                >
                    <span>{{ d.label }} × {{ d.count }}</span>
                    <span class="font-semibold">{{ formatMoney(d.value * d.count) }}</span>
                </li>
            </ul>
        </div>

        <div class="qs-footer-actions">
            <button type="button" @click="emit('cancel')" class="btn btn-outline-primary qs-cancel-btn">
                Cancelar
            </button>
            <button
                type="button"
                :disabled="cashTendered < total"
                @click="confirm"
                class="btn btn-primary qs-confirm-btn disabled:opacity-50"
            >
                Confirmar — Vuelto {{ formatMoney(cashChange) }}
            </button>
        </div>
    </div>
</template>

<style scoped>
.qs-cash {
    @apply space-y-4;
}

/* —— Resumen total / recibido / vuelto —— */
.qs-summary {
    @apply grid grid-cols-3 gap-2 sm:gap-3;
}

.qs-summary-card {
    @apply rounded-xl border p-2 sm:p-3 text-center;
}

.qs-summary-label {
    @apply text-[10px] sm:text-xs uppercase font-medium tracking-wide mb-0.5;
}

.qs-summary-value {
    @apply text-lg sm:text-xl font-bold leading-tight;
}

.qs-field-label {
    @apply text-xs text-gray-600 dark:text-zinc-400 mb-1 block;
}

.qs-amount-row {
    @apply flex flex-wrap gap-2;
}

.qs-amount-input {
    @apply flex-1 min-w-[120px] text-lg font-semibold;
}

.qs-action-btn {
    @apply text-xs sm:text-sm shrink-0;
}

.qs-error-text {
    @apply text-xs text-red-600 dark:text-red-400 mt-1;
}

.qs-section-title {
    @apply text-xs font-semibold uppercase text-gray-600 dark:text-zinc-400 mb-2;
}

/* —— Monedas y billetes (web) —— */
.qs-denom-grid {
    @apply grid grid-cols-3 sm:grid-cols-4 gap-2;
}

.qs-denom-btn {
    @apply rounded-lg font-semibold border transition active:scale-95;
    @apply py-2.5 px-2 text-xs sm:text-sm;
}

.qs-denom-coin {
    @apply bg-amber-50 border-amber-300 text-amber-950 hover:bg-amber-100;
    @apply dark:bg-amber-950/60 dark:border-amber-600 dark:text-amber-100 dark:hover:bg-amber-900/70;
}

.qs-denom-bill {
    @apply bg-teal-50 border-teal-300 text-teal-950 hover:bg-teal-100;
    @apply dark:bg-teal-950/60 dark:border-teal-600 dark:text-teal-100 dark:hover:bg-teal-900/70;
}

.qs-breakdown-title {
    @apply text-xs mb-2 font-medium uppercase tracking-wide;
}

.qs-breakdown-list {
    @apply text-sm space-y-1;
}

.qs-breakdown-row {
    @apply flex justify-between;
}

.qs-footer-actions {
    @apply flex flex-col sm:flex-row gap-2 pt-1;
}

.qs-cancel-btn {
    @apply w-full sm:w-auto order-2 sm:order-1;
}

.qs-confirm-btn {
    @apply w-full sm:flex-1 order-1 sm:order-2 font-semibold;
}

/* —— Modo táctil (pantalla maximizada) —— */
.qs-cash--touch .qs-summary {
    @apply gap-3;
}

.qs-cash--touch .qs-summary-card {
    @apply p-3;
}

.qs-cash--touch .qs-summary-value {
    @apply text-xl;
}

.qs-cash--touch .qs-amount-input {
    @apply min-h-[48px] text-xl py-3;
}

.qs-cash--touch .qs-action-btn {
    @apply min-h-[48px] px-4 text-sm font-semibold;
}

.qs-cash--touch .qs-denom-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(5.5rem, 1fr));
    gap: 0.75rem;
    max-width: 32rem;
    margin-left: auto;
    margin-right: auto;
}

.qs-cash--touch .qs-denom-btn {
    min-height: 3.75rem;
    padding: 0.75rem 0.5rem;
    font-size: 1rem;
    line-height: 1.2;
    border-radius: 0.75rem;
    width: 100%;
    max-width: 7.5rem;
    justify-self: center;
    touch-action: manipulation;
    -webkit-tap-highlight-color: transparent;
}

.qs-cash--touch .qs-footer-actions {
    @apply flex-col gap-3 pt-2;
}

.qs-cash--touch .qs-cancel-btn,
.qs-cash--touch .qs-confirm-btn {
    @apply w-full order-none min-h-[52px] text-base rounded-xl;
}

/* Pantallas táctiles muy anchas: más columnas pero botones con tamaño mínimo */
@media (min-width: 640px) {
    .qs-cash--touch .qs-denom-grid {
        max-width: 36rem;
        grid-template-columns: repeat(4, minmax(6rem, 1fr));
    }

    .qs-cash--touch .qs-denom-btn {
        min-height: 4rem;
        max-width: 8.5rem;
        font-size: 1.0625rem;
    }
}

@media (min-width: 1024px) {
    .qs-cash--touch .qs-denom-grid {
        max-width: 40rem;
    }
}
</style>
