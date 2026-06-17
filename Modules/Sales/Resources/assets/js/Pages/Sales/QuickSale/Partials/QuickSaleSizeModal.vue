<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    product: { type: Object, default: null },
});

const emit = defineEmits(['close', 'confirm']);

const selectedSize = ref(null);
const selectedStock = ref(0);

const parseSizes = (product) => {
    if (!product) return [];
    try {
        const raw = product.local_sizes ?? product.sizes;
        if (!raw) return [];
        const parsed = typeof raw === 'string' ? JSON.parse(raw) : raw;
        return Array.isArray(parsed) ? parsed : [];
    } catch {
        return [];
    }
};

const availableSizes = computed(() =>
    parseSizes(props.product).filter((item) => Number(item.quantity) > 0)
);

watch(
    () => props.show,
    (open) => {
        if (!open) {
            selectedSize.value = null;
            selectedStock.value = 0;
            return;
        }
        const first = availableSizes.value[0];
        if (first) {
            selectedSize.value = first.size;
            selectedStock.value = Number(first.quantity) || 0;
        }
    }
);

const selectSize = (item) => {
    selectedSize.value = item.size;
    selectedStock.value = Number(item.quantity) || 0;
};

const confirm = () => {
    if (!selectedSize.value) return;
    emit('confirm', { size: selectedSize.value, stock: selectedStock.value });
};

const close = () => emit('close');
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 z-[70] flex items-end sm:items-center justify-center bg-black/50 p-0 sm:p-4"
        @click.self="close"
    >
        <div class="w-full sm:max-w-md bg-white dark:bg-zinc-800 rounded-t-2xl sm:rounded-2xl shadow-xl overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-zinc-700 flex justify-between items-center">
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Seleccionar talla</h3>
                    <p v-if="product" class="text-sm text-gray-500 dark:text-zinc-400 line-clamp-1">
                        {{ product.description }}
                    </p>
                </div>
                <button type="button" class="text-2xl text-gray-500 leading-none" @click="close">&times;</button>
            </div>

            <div class="p-4 max-h-[50vh] overflow-y-auto">
                <p v-if="!availableSizes.length" class="text-sm text-amber-600 dark:text-amber-400">
                    No hay tallas con stock disponible.
                </p>

                <div v-else class="grid grid-cols-3 sm:grid-cols-4 gap-2">
                    <button
                        v-for="item in availableSizes"
                        :key="item.size"
                        type="button"
                        class="px-3 py-3 rounded-xl border text-sm font-semibold transition"
                        :class="selectedSize === item.size
                            ? 'border-primary bg-primary/10 text-primary'
                            : 'border-gray-200 dark:border-zinc-600 text-gray-700 dark:text-gray-200 hover:border-primary/50'"
                        @click="selectSize(item)"
                    >
                        <div>{{ item.size }}</div>
                        <div class="text-xs font-normal opacity-70">Stock: {{ item.quantity }}</div>
                    </button>
                </div>
            </div>

            <div class="px-4 py-3 border-t border-gray-200 dark:border-zinc-700 flex gap-2">
                <button
                    type="button"
                    class="flex-1 px-4 py-2.5 rounded-xl bg-gray-100 dark:bg-zinc-700 text-gray-700 dark:text-gray-200 font-medium"
                    @click="close"
                >
                    Cancelar
                </button>
                <button
                    type="button"
                    class="flex-1 px-4 py-2.5 rounded-xl bg-primary text-white font-medium disabled:opacity-50"
                    :disabled="!selectedSize"
                    @click="confirm"
                >
                    Agregar
                </button>
            </div>
        </div>
    </div>
</template>
