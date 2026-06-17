<script setup>
import { ref } from 'vue';
import QuickSaleCheckoutPanel from './QuickSaleCheckoutPanel.vue';

const props = defineProps({
    products: { type: Array, default: () => [] },
    cart: { type: Array, default: () => [] },
    total: { type: Number, default: 0 },
    saving: { type: Boolean, default: false },
    paymentMethods: { type: Array, default: () => [] },
});

const emit = defineEmits(['add-to-cart', 'update-qty', 'update-discount', 'remove-item', 'checkout']);

const search = ref('');
const showCart = ref(false);
const cashPanelOpen = ref(false);

const filteredProducts = () => {
    if (!search.value) return props.products;
    const s = search.value.toLowerCase();
    return props.products.filter(p =>
        p.description?.toLowerCase().includes(s) ||
        p.interne?.toLowerCase().includes(s)
    );
};

const getProductPrice = (product) => {
    if (product.sale_prices) {
        try { return parseFloat(JSON.parse(product.sale_prices).high) || 0; } catch (e) { return 0; }
    }
    return 0;
};

const getItemDiscount = (item) => {
    const discount = Number(item?.discount ?? 0);
    if (!Number.isFinite(discount) || discount < 0) return 0;
    return Math.min(discount, Number(item?.price ?? 0));
};

const getItemTotal = (item) => Math.max(0, (Number(item?.price ?? 0) - getItemDiscount(item)) * Number(item?.qty ?? 0));

const hasProductImage = (product) => {
    const image = product?.image;
    return typeof image === 'string'
        && image.trim() !== ''
        && !image.endsWith('/storage')
        && !image.includes('imagen-no-disponible');
};

const openCartCheckout = () => {
    if (props.cart.length === 0) return;
    showCart.value = true;
};
</script>

<template>
    <div class="pb-36">
        <div class="mb-4">
            <input v-model="search" type="text"
                class="w-full p-4 text-lg form-input rounded-xl"
                placeholder="🔍 Buscar producto por código o nombre..."
                autofocus />
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3">
            <div v-for="product in filteredProducts()" :key="product.id"
                @click="emit('add-to-cart', product)"
                class="p-3 bg-white dark:bg-zinc-800 rounded-xl shadow hover:shadow-lg cursor-pointer transition transform hover:scale-105 min-h-[185px] md:min-h-[195px] flex flex-col active:scale-95 overflow-hidden">
                <div class="w-full h-28 md:h-32 rounded-xl overflow-hidden bg-gray-100 dark:bg-zinc-700/70 mb-3 flex items-center justify-center">
                    <img
                        v-if="hasProductImage(product)"
                        :src="product.image"
                        :alt="product.description"
                        class="w-full h-full object-cover"
                        loading="lazy"
                    />
                    <div v-else class="text-xs font-medium text-gray-400 dark:text-zinc-400">
                        Sin imagen
                    </div>
                </div>
                <div class="text-center flex-1 flex flex-col justify-between">
                    <div class="text-lg md:text-xl font-bold text-primary mb-2">
                        S/ {{ getProductPrice(product) }}
                    </div>
                    <div class="text-sm text-gray-700 dark:text-gray-300 line-clamp-2 min-h-[2.5rem]">{{ product.description }}</div>
                    <div v-if="product.presentations == 1 || product.presentations === true" class="text-xs text-primary font-medium mt-1">
                        Con tallas
                    </div>
                    <div class="text-xs text-gray-400 mt-2 line-clamp-1">{{ product.interne }}</div>
                </div>
            </div>
            <div v-if="filteredProducts().length === 0" class="col-span-2 md:col-span-3 xl:col-span-4 text-center py-12 text-gray-400">
                No se encontraron productos
            </div>
        </div>

        <div class="fixed bottom-0 left-0 right-0 bg-white dark:bg-zinc-800 shadow-lg border-t border-gray-200 dark:border-zinc-700 z-40">
            <div class="p-3 flex justify-between items-center">
                <div>
                    <span class="text-lg font-bold text-gray-900 dark:text-white">S/ {{ total.toFixed(2) }}</span>
                    <button @click="showCart = !showCart" class="ml-2 text-sm text-primary">
                        {{ cart.length }} item(s)
                    </button>
                </div>
                <button
                    type="button"
                    @click="openCartCheckout"
                    :disabled="saving || cart.length === 0"
                    class="px-5 py-3 bg-green-600 text-white font-bold rounded-xl disabled:opacity-50"
                >
                    Cobrar
                </button>
            </div>
        </div>

        <div v-if="showCart" class="fixed inset-0 bg-black/50 z-[60] flex items-end" @click.self="showCart = false; cashPanelOpen = false">
            <div
                class="bg-white dark:bg-zinc-800 w-full rounded-t-2xl overflow-y-auto transition-[max-height]"
                :class="cashPanelOpen ? 'max-h-[95vh]' : 'max-h-[85vh]'"
            >
                <div class="sticky top-0 bg-white dark:bg-zinc-800 border-b border-gray-200 dark:border-zinc-700 px-4 py-3 flex justify-between items-center z-10">
                    <h3 class="text-lg font-bold">Cobrar venta</h3>
                    <button type="button" @click="showCart = false" class="text-2xl text-gray-500 leading-none">&times;</button>
                </div>

                <div class="p-4 space-y-4">
                    <div v-show="!cashPanelOpen" class="max-h-[34vh] overflow-y-auto space-y-2">
                        <div v-for="item in cart" :key="item.lineKey ?? item.id"
                            class="p-3 bg-gray-50 dark:bg-zinc-700 rounded-lg text-sm space-y-2">
                            <div class="flex justify-between items-start gap-2">
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium truncate">{{ item.description }}</div>
                                    <div v-if="item.size" class="text-xs text-primary font-medium">Talla: {{ item.size }}</div>
                                    <div class="text-xs text-gray-500 dark:text-zinc-400">
                                        S/ {{ Number(item.price).toFixed(2) }} × {{ item.qty }}
                                    </div>
                                </div>
                                <div class="text-right shrink-0">
                                    <div class="text-xs text-gray-500 dark:text-zinc-400">Total</div>
                                    <div class="font-semibold text-gray-900 dark:text-white">
                                        S/ {{ getItemTotal(item).toFixed(2) }}
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-end gap-2">
                                <div class="flex-1">
                                    <label class="block text-[11px] text-gray-500 dark:text-zinc-400 mb-1">Descuento unit.</label>
                                    <input
                                        :value="item.discount ?? 0"
                                        type="number"
                                        min="0"
                                        :max="item.price"
                                        step="0.01"
                                        inputmode="decimal"
                                        @input="emit('update-discount', item, $event.target.value)"
                                        class="form-input w-full text-sm"
                                    />
                                </div>
                                <div class="flex items-center gap-1 shrink-0">
                                    <button @click="emit('update-qty', item, -1)" class="w-8 h-8 rounded-full bg-gray-200 dark:bg-zinc-600 font-bold">-</button>
                                    <span class="w-6 text-center font-medium">{{ item.qty }}</span>
                                    <button @click="emit('update-qty', item, 1)" class="w-8 h-8 rounded-full bg-gray-200 dark:bg-zinc-600 font-bold">+</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <QuickSaleCheckoutPanel
                        compact
                        :total="total"
                        :cart-length="cart.length"
                        :saving="saving"
                        :payment-methods="paymentMethods"
                        @cash-panel-toggle="cashPanelOpen = $event"
                        @checkout="(payload) => { emit('checkout', payload); showCart = false; cashPanelOpen = false; }"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
