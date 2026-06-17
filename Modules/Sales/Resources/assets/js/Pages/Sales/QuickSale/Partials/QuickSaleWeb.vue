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

const hasProductImage = (product) => {
    const image = product?.image;
    return typeof image === 'string'
        && image.trim() !== ''
        && !image.endsWith('/storage')
        && !image.includes('imagen-no-disponible');
};

const getItemDiscount = (item) => {
    const discount = Number(item?.discount ?? 0);
    if (!Number.isFinite(discount) || discount < 0) return 0;
    return Math.min(discount, Number(item?.price ?? 0));
};

const getItemTotal = (item) => Math.max(0, (Number(item?.price ?? 0) - getItemDiscount(item)) * Number(item?.qty ?? 0));
</script>

<template>
    <div class="flex gap-6">
        <div class="flex-1 min-w-0">
            <div class="mb-4">
                <input v-model="search" type="text"
                    class="w-full p-3 text-base form-input rounded-xl"
                    placeholder="🔍 Buscar producto por código o nombre..." />
            </div>

            <div class="grid grid-cols-3 lg:grid-cols-4 gap-4">
                <div v-for="product in filteredProducts()" :key="product.id"
                    @click="emit('add-to-cart', product)"
                    class="panel hover:shadow-lg cursor-pointer transition transform hover:scale-105 min-h-[250px] flex flex-col active:scale-95 overflow-hidden">
                    <div class="w-full aspect-square rounded-xl overflow-hidden bg-gray-100 dark:bg-zinc-700/70 mb-4 flex items-center justify-center">
                        <img
                            v-if="hasProductImage(product)"
                            :src="product.image"
                            :alt="product.description"
                            class="w-full h-full object-cover"
                            loading="lazy"
                        />
                        <div v-else class="text-sm font-medium text-gray-400 dark:text-zinc-400">
                            Sin imagen
                        </div>
                    </div>
                    <div class="text-center flex-1 flex flex-col justify-between">
                        <div class="text-2xl font-bold text-primary mb-3">
                            S/ {{ getProductPrice(product) }}
                        </div>
                        <div class="text-base text-gray-700 dark:text-gray-300 line-clamp-2 min-h-[3rem]">{{ product.description }}</div>
                        <div v-if="product.presentations == 1 || product.presentations === true" class="text-xs text-primary font-medium mt-1">
                            Con tallas
                        </div>
                        <div class="text-xs text-gray-400 mt-2 line-clamp-1">{{ product.interne }}</div>
                    </div>
                </div>
                <div v-if="filteredProducts().length === 0"
                    class="col-span-3 lg:col-span-4 text-center py-12 text-gray-400">
                    No se encontraron productos
                </div>
            </div>
        </div>

        <div class="w-[22rem] xl:w-96 shrink-0">
            <div class="bg-white dark:bg-zinc-800 p-4 rounded-xl shadow sticky top-6">
                <h3 class="text-lg font-bold mb-4">Carrito ({{ cart.length }})</h3>

                <div class="max-h-[38vh] overflow-y-auto space-y-3 mb-4">
                    <div v-for="item in cart" :key="item.lineKey ?? item.id"
                        class="p-3 bg-gray-50 dark:bg-zinc-700 rounded-lg space-y-3">
                        <div class="flex justify-between items-start gap-3">
                            <div class="flex-1 min-w-0">
                                <div class="font-medium text-gray-900 dark:text-white text-sm truncate">{{ item.description }}</div>
                                <div v-if="item.size" class="text-xs text-primary font-medium">Talla: {{ item.size }}</div>
                                <div class="text-xs text-gray-500 dark:text-zinc-400">
                                    S/ {{ Number(item.price).toFixed(2) }} c/u
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <div class="text-[11px] text-gray-500 dark:text-zinc-400">Total</div>
                                <div class="font-semibold text-gray-900 dark:text-white">S/ {{ getItemTotal(item).toFixed(2) }}</div>
                            </div>
                        </div>
                        <div class="flex items-end gap-3">
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
                                    class="form-input w-full text-sm py-2"
                                />
                            </div>
                            <div class="flex items-center gap-2 shrink-0 ml-2">
                                <button @click="emit('update-qty', item, -1)"
                                    class="w-6 h-6 rounded-full bg-gray-200 dark:bg-zinc-600 flex items-center justify-center font-bold hover:bg-gray-300 text-sm">-</button>
                                <span class="w-6 text-center font-medium text-sm">{{ item.qty }}</span>
                                <button @click="emit('update-qty', item, 1)"
                                    class="w-6 h-6 rounded-full bg-gray-200 dark:bg-zinc-600 flex items-center justify-center font-bold hover:bg-gray-300 text-sm">+</button>
                                <button @click="emit('remove-item', item)" class="ml-1 text-red-500 hover:text-red-700">&times;</button>
                            </div>
                        </div>
                    </div>
                    <div v-if="cart.length === 0" class="text-center py-8 text-gray-400">El carrito está vacío</div>
                </div>

                <div class="border-t pt-4">
                    <QuickSaleCheckoutPanel
                        :total="total"
                        :cart-length="cart.length"
                        :saving="saving"
                        :payment-methods="paymentMethods"
                        @checkout="(payload) => emit('checkout', payload)"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
