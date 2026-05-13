<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    products: { type: Array, default: () => [] },
    cart: { type: Array, default: () => [] },
    total: { type: Number, default: 0 },
    saving: { type: Boolean, default: false },
    paymentMethods: { type: Array, default: () => [] },
});

const emit = defineEmits(['add-to-cart', 'update-qty', 'remove-item', 'checkout']);

const search = ref('');
const selectedPayment = ref(1);
const paymentReference = ref('');

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
</script>

<template>
    <div class="flex gap-6">
        <!-- Columna izquierda: Productos -->
        <div class="flex-1 min-w-0">
            <!-- Búsqueda -->
            <div class="mb-4">
                <input v-model="search" type="text"
                    class="w-full p-3 text-base form-input rounded-xl"
                    placeholder="🔍 Buscar producto por código o nombre..." />
            </div>

            <!-- Grid de productos -->
            <div class="grid grid-cols-3 lg:grid-cols-4 gap-4">
                <div v-for="product in filteredProducts()" :key="product.id"
                    @click="emit('add-to-cart', product)"
                    class="panel hover:shadow-lg cursor-pointer transition transform hover:scale-105 min-h-[120px] flex flex-col items-center justify-center active:scale-95">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-primary mb-3">
                            S/ {{ getProductPrice(product) }}
                        </div>
                        <div class="text-base text-gray-700 dark:text-gray-300 line-clamp-2">{{ product.description }}</div>
                        <div class="text-xs text-gray-400 mt-2">{{ product.interne }}</div>
                    </div>
                </div>
                <div v-if="filteredProducts().length === 0"
                    class="col-span-3 lg:col-span-4 text-center py-12 text-gray-400">
                    No se encontraron productos
                </div>
            </div>
        </div>

        <!-- Columna derecha: Carrito lateral -->
        <div class="w-96 shrink-0">
            <div class="bg-white dark:bg-zinc-800 p-4 rounded-xl shadow sticky top-6">
                <h3 class="text-lg font-bold mb-4">Carrito ({{ cart.length }})</h3>

                <!-- Lista de items -->
                <div class="max-h-[45vh] overflow-y-auto space-y-3 mb-4">
                    <div v-for="item in cart" :key="item.id"
                        class="flex justify-between items-center p-3 bg-gray-50 dark:bg-zinc-700 rounded-lg">
                        <div class="flex-1 min-w-0">
                            <div class="font-medium text-gray-900 dark:text-white text-sm truncate">{{ item.description }}</div>
                            <div class="text-xs text-gray-500">S/ {{ item.price }} c/u</div>
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
                    <div v-if="cart.length === 0" class="text-center py-8 text-gray-400">El carrito está vacío</div>
                </div>

                <!-- Método de pago -->
                <div class="mb-4">
                    <label class="text-xs text-gray-500 mb-1 block">Método de pago</label>
                    <select v-model="selectedPayment" class="form-select w-full p-2 text-sm">
                        <option v-for="pm in paymentMethods" :key="pm.id" :value="pm.id">{{ pm.description }}</option>
                    </select>
                </div>

                <!-- Total y botón pagar -->
                <div class="border-t pt-4">
                    <div class="flex justify-between text-xl font-bold mb-4">
                        <span>Total:</span>
                        <span>S/ {{ total.toFixed(2) }}</span>
                    </div>
                    <button @click="emit('checkout')"
                        :disabled="saving || cart.length === 0"
                        :class="{ 'opacity-50 cursor-not-allowed': saving || cart.length === 0 }"
                        class="w-full py-3 bg-green-600 text-white text-lg font-bold rounded-xl hover:bg-green-700 transition active:bg-green-800 flex items-center justify-center gap-2">
                        <span v-if="saving" class="animate-spin">⏳</span>
                        <span v-else>💰 COBRAR</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
