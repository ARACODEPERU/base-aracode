<script setup>
import { ref } from 'vue';

const props = defineProps({
    products: { type: Array, default: () => [] },
    cart: { type: Array, default: () => [] },
    total: { type: Number, default: 0 },
    saving: { type: Boolean, default: false },
});

const emit = defineEmits(['add-to-cart', 'update-qty', 'remove-item', 'checkout']);

const search = ref('');
const showCart = ref(false);

const filteredProducts = () => {
    if (!search.value) return props.products;
    const s = search.value.toLowerCase();
    return props.products.filter(p =>
        p.description?.toLowerCase().includes(s) ||
        p.interne?.toLowerCase().includes(s)
    );
};
</script>

<template>
    <div class="pb-32">
        <!-- Barra de búsqueda -->
        <div class="mb-4">
            <input v-model="search" type="text"
                class="w-full p-4 text-lg form-input rounded-xl"
                placeholder="🔍 Buscar producto por código o nombre..."
                autofocus />
        </div>

        <!-- Grid de productos -->
        <div class="grid grid-cols-2 gap-3">
            <div v-for="product in filteredProducts()" :key="product.id"
                @click="emit('add-to-cart', product)"
                class="p-4 bg-white dark:bg-zinc-800 rounded-xl shadow hover:shadow-lg cursor-pointer transition transform hover:scale-105 min-h-[100px] flex flex-col items-center justify-center active:scale-95">
                <div class="text-center">
                    <div class="text-xl font-bold text-primary mb-2">
                        S/ {{ product.sale_prices ? JSON.parse(product.sale_prices).high || '0' : '0' }}
                    </div>
                    <div class="text-sm text-gray-700 dark:text-gray-300 line-clamp-2">{{ product.description }}</div>
                    <div class="text-xs text-gray-400 mt-1">{{ product.interne }}</div>
                </div>
            </div>
            <div v-if="filteredProducts().length === 0" class="col-span-2 text-center py-12 text-gray-400">
                No se encontraron productos
            </div>
        </div>

        <!-- Barra inferior fija del carrito -->
        <div class="fixed bottom-0 left-0 right-0 bg-white dark:bg-zinc-800 shadow-lg p-4 border-t border-gray-200 dark:border-zinc-700">
            <div class="flex justify-between items-center mb-3">
                <div>
                    <span class="text-lg font-bold text-gray-900 dark:text-white">Total: S/ {{ total.toFixed(2) }}</span>
                </div>
                <button @click="showCart = !showCart" class="text-sm text-primary hover:text-primary/80">
                    {{ cart.length }} item(s)
                </button>
            </div>
            <button @click="emit('checkout')"
                :disabled="saving || cart.length === 0"
                :class="{ 'opacity-50 cursor-not-allowed': saving || cart.length === 0 }"
                class="w-full py-4 bg-green-600 text-white text-xl font-bold rounded-xl hover:bg-green-700 transition active:bg-green-800 flex items-center justify-center gap-2">
                <span v-if="saving" class="animate-spin">⏳</span>
                <span v-else>💰 PAGAR AHORA</span>
            </button>
        </div>

        <!-- Modal del carrito (slide-up) -->
        <div v-if="showCart" class="fixed inset-0 bg-black/50 z-50 flex items-end" @click.self="showCart = false">
            <div class="bg-white dark:bg-zinc-800 w-full max-h-[70vh] rounded-t-2xl overflow-y-auto p-4">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold">Carrito de Compras</h3>
                    <button @click="showCart = false" class="text-gray-500 hover:text-gray-700">&times;</button>
                </div>
                <div v-if="cart.length === 0" class="text-center py-8 text-gray-400">El carrito está vacío</div>
                <div v-else class="space-y-3">
                    <div v-for="item in cart" :key="item.id"
                        class="flex justify-between items-center p-3 bg-gray-50 dark:bg-zinc-700 rounded-lg">
                        <div class="flex-1">
                            <div class="font-medium text-gray-900 dark:text-white">{{ item.description }}</div>
                            <div class="text-sm text-gray-500">S/ {{ item.price }} c/u</div>
                        </div>
                        <div class="flex items-center gap-3">
                            <button @click="emit('update-qty', item, -1)"
                                class="w-8 h-8 rounded-full bg-gray-200 dark:bg-zinc-600 flex items-center justify-center font-bold hover:bg-gray-300">-</button>
                            <span class="w-8 text-center font-medium">{{ item.qty }}</span>
                            <button @click="emit('update-qty', item, 1)"
                                class="w-8 h-8 rounded-full bg-gray-200 dark:bg-zinc-600 flex items-center justify-center font-bold hover:bg-gray-300">+</button>
                            <button @click="emit('remove-item', item)" class="ml-2 text-red-500 hover:text-red-700">&times;</button>
                        </div>
                    </div>
                </div>
                <div v-if="cart.length > 0" class="mt-4 pt-4 border-t border-gray-200 dark:border-zinc-700">
                    <div class="flex justify-between text-lg font-bold">
                        <span>Total:</span>
                        <span>S/ {{ total.toFixed(2) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
