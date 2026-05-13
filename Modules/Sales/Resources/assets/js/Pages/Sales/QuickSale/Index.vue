<script setup>
import { ref, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import SearchClients from '../Partials/SearchClients.vue';
import QuickSaleTouch from './Partials/QuickSaleTouch.vue';
import QuickSaleWeb from './Partials/QuickSaleWeb.vue';

const props = defineProps({
    products: { type: Array, default: () => [] },
    clientDefault: { type: Object, default: () => ({}) },
    paymentMethods: { type: Array, default: () => [] },
    saleDocumentTypes: { type: Array, default: () => [] },
});

const isMobile = ref(window.innerWidth < 768 || 'ontouchstart' in window);
const viewMode = ref(isMobile.value ? 'touch' : 'web');
const documentType = ref('80');
const printOption = ref('auto');
const selectedClient = ref(props.clientDefault);
const showClientModal = ref(false);
const cart = ref([]);
const saving = ref(false);

const total = computed(() =>
    cart.value.reduce((sum, item) => sum + item.price * item.qty, 0)
);

const addToCart = (product) => {
    const existing = cart.value.find(i => i.id === product.id);
    if (existing) {
        existing.qty++;
    } else {
        cart.value.push({
            id: product.id,
            description: product.description,
            price: getProductPrice(product),
            interne: product.interne,
            qty: 1,
        });
    }
};

const getProductPrice = (product) => {
    if (product.sale_prices) {
        try {
            const prices = JSON.parse(product.sale_prices);
            return parseFloat(prices.high) || 0;
        } catch (e) {
            return 0;
        }
    }
    return 0;
};

const updateQty = (item, change) => {
    const idx = cart.value.findIndex(i => i.id === item.id);
    if (idx >= 0) {
        cart.value[idx].qty += change;
        if (cart.value[idx].qty <= 0) {
            cart.value.splice(idx, 1);
        }
    }
};

const removeItem = (item) => {
    const idx = cart.value.findIndex(i => i.id === item.id);
    if (idx >= 0) cart.value.splice(idx, 1);
};

const checkout = async () => {
    if (cart.value.length === 0) {
        Swal.fire('Error', 'El carrito está vacío', 'error');
        return;
    }
    saving.value = true;
    try {
        const response = await axios.post(route('sales_quick_sale_store'), {
            items: cart.value.map(i => ({ id: i.id, qty: i.qty, price: i.price })),
            payment_amount: total.value,
            client_id: selectedClient.value.id,
            document_type_id: documentType.value,
        });
        if (response.data.success) {
            cart.value = [];
            if (printOption.value === 'auto') {
                window.open(response.data.ticket_url, '_blank');
            } else if (printOption.value === 'ask') {
                const result = await Swal.fire({
                    icon: 'question', title: '¿Imprimir ticket?',
                    showCancelButton: true, confirmButtonText: 'Sí',
                    cancelButtonText: 'No',
                });
                if (result.isConfirmed) window.open(response.data.ticket_url, '_blank');
            }
            Swal.fire({ icon: 'success', title: 'Venta realizada', timer: 2000, showConfirmButton: false });
        }
    } catch (error) {
        Swal.fire('Error', error.response?.data?.message || 'No se pudo procesar la venta', 'error');
    } finally {
        saving.value = false;
    }
};

const goToNotes = () => {
    router.visit(route('sale_credit_notes_list'));
};

const isMaximized = ref(false);
const showMobileMenu = ref(false);
const contentEl = ref(null);

const toggleMaximize = () => {
    if (!isMaximized.value) {
        const el = contentEl.value;
        if (!el) return;
        if (el.requestFullscreen) {
            el.requestFullscreen();
        } else if (el.webkitRequestFullscreen) {
            el.webkitRequestFullscreen();
        } else if (el.msRequestFullscreen) {
            el.msRequestFullscreen();
        }
        isMaximized.value = true;
    } else {
        if (document.exitFullscreen) {
            document.exitFullscreen();
        } else if (document.webkitExitFullscreen) {
            document.webkitExitFullscreen();
        } else if (document.msExitFullscreen) {
            document.msExitFullscreen();
        }
        isMaximized.value = false;
    }
};

onMounted(() => {
    const sync = () => {
        isMaximized.value = !!(
            document.fullscreenElement ||
            document.webkitFullscreenElement ||
            document.msFullscreenElement
        );
    };
    document.addEventListener('fullscreenchange', sync);
    document.addEventListener('webkitfullscreenchange', sync);
    document.addEventListener('MSFullscreenChange', sync);
});

const closeMobileMenu = () => {
    showMobileMenu.value = false;
};
</script>

<template>
    <AppLayout title="Venta Rápida">
        <Navigation v-if="!isMaximized" :routeModule="route('sales_dashboard')" :titleModule="'Venta Rápida'"
            :data="[{ title: 'Punto de Venta Rápido' }]" />

        <div ref="contentEl" :class="[isMaximized ? 'fixed inset-0 z-50 bg-slate-900 overflow-y-auto p-4 maximized-dark' : 'pt-5 px-4']">
            <!-- Toolbar -->
            <div class="bg-white dark:bg-zinc-800 p-3 rounded-xl shadow mb-4">
                <!-- Botón hamburguesa (solo móvil) -->
                <div class="lg:hidden flex items-center justify-between">
                    <button @click="showMobileMenu = !showMobileMenu" class="p-2 rounded-lg bg-gray-100 dark:bg-zinc-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-zinc-600 transition">
                        <svg v-if="!showMobileMenu" class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M0 96C0 78.3 14.3 64 32 64l384 0c17.7 0 32 14.3 32 32s-14.3 32-32 32L32 128C14.3 128 0 113.7 0 96zM0 256c0-17.7 14.3-32 32-32l384 0c17.7 0 32 14.3 32 32s-14.3 32-32 32L32 288c-17.7 0-32-14.3-32-32zM448 416c0 17.7-14.3 32-32 32L32 448c-17.7 0-32-14.3-32-32s14.3-32 32-32l384 0c17.7 0 32 14.3 32 32z"/></svg>
                        <svg v-else class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path fill="currentColor" d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/></svg>
                    </button>
                    <span class="font-medium text-sm text-gray-900 dark:text-white">Menú de opciones</span>
                </div>

                <!-- Opciones del toolbar -->
                <!-- Desktop: siempre visible en fila. Móvil: visible solo cuando showMobileMenu = true -->
                <div :class="[
                    'gap-2',
                    'lg:flex lg:items-center lg:overflow-x-auto',
                    showMobileMenu ? 'flex flex-col mt-3' : 'hidden'
                ]">
                    <!-- Botones Táctil / Web -->
                    <div class="flex items-center gap-1 shrink-0">
                        <button @click="viewMode = 'touch'; closeMobileMenu()"
                            :class="viewMode === 'touch' ? 'bg-primary text-white' : 'bg-gray-100 dark:bg-zinc-700 text-gray-600 dark:text-gray-300'"
                            class="px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-1">
                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path fill="currentColor" d="M80 0C44.7 0 16 28.7 16 64l0 384c0 35.3 28.7 64 64 64l224 0c35.3 0 64-28.7 64-64l0-384c0-35.3-28.7-64-64-64L80 0zM224 448c0 8.8-7.2 16-16 16l-32 0c-8.8 0-16-7.2-16-16s7.2-16 16-16l32 0c8.8 0 16 7.2 16 16z"/></svg>
                            Táctil
                        </button>
                        <button @click="viewMode = 'web'; closeMobileMenu()"
                            :class="viewMode === 'web' ? 'bg-primary text-white' : 'bg-gray-100 dark:bg-zinc-700 text-gray-600 dark:text-gray-300'"
                            class="px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-1">
                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path fill="currentColor" d="M128 0C92.7 0 64 28.7 64 64l0 288c0 35.3 28.7 64 64 64l384 0c35.3 0 64-28.7 64-64l0-288c0-35.3-28.7-64-64-64L128 0zM512 128l0 192L128 320l0-192 384 0z"/></svg>
                            Web
                        </button>
                    </div>

                    <select v-model="documentType" @change="closeMobileMenu" class="form-select p-2 text-sm border rounded-lg">
                        <option v-for="dt in saleDocumentTypes" :key="dt.id" :value="dt.sunat_id">
                            {{ dt.description }}
                        </option>
                    </select>

                    <select v-model="printOption" @change="closeMobileMenu" class="form-select p-2 text-sm border rounded-lg">
                        <option value="auto">Imprimir al cobrar</option>
                        <option value="ask">Preguntar antes</option>
                        <option value="manual">No imprimir</option>
                    </select>

                    <button @click="showClientModal = true; closeMobileMenu()"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition flex items-center gap-1 shrink-0">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path fill="currentColor" d="M224 256A128 128 0 1 0 224 0a128 128 0 1 0 0 256zm-45.7 48C79.8 304 0 383.8 0 482.3C0 498.7 13.3 512 29.7 512l388.6 0c16.4 0 29.7-13.3 29.7-29.7C448 383.8 368.2 304 269.7 304l-91.4 0z"/></svg>
                        {{ selectedClient.full_name }}
                    </button>

                    <button @click="toggleMaximize(); closeMobileMenu()"
                        class="px-3 py-2 rounded-lg text-sm font-medium transition flex items-center gap-1 shrink-0"
                        :class="isMaximized ? 'bg-orange-500 text-white' : 'bg-gray-100 dark:bg-zinc-700 text-gray-600 dark:text-gray-300'">
                        <svg v-if="!isMaximized" class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M32 32C14.3 32 0 46.3 0 64l0 96c0 17.7 14.3 32 32 32s32-14.3 32-32l0-64 64 0c17.7 0 32-14.3 32-32s-14.3-32-32-32L32 32zM64 352c0-17.7-14.3-32-32-32s-32 14.3-32 32l0 96c0 17.7 14.3 32 32 32l96 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-64 0 0-64zM320 32c-17.7 0-32 14.3-32 32s14.3 32 32 32l64 0 0 64c0 17.7 14.3 32 32 32s32-14.3 32-32l0-96c0-17.7-14.3-32-32-32l-96 0zM448 352c0-17.7-14.3-32-32-32s-32 14.3-32 32l0 64-64 0c-17.7 0-32 14.3-32 32s14.3 32 32 32l96 0c17.7 0 32-14.3 32-32l0-96z"/></svg>
                        <svg v-else class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M0 96C0 60.7 28.7 32 64 32l384 0c35.3 0 64 28.7 64 64l0 320c0 35.3-28.7 64-64 64L64 480c-35.3 0-64-28.7-64-64L0 96zM176 128l-64 0 0 64c0 8.8-7.2 16-16 16s-16-7.2-16-16l0-96c0-8.8 7.2-16 16-16l80 0c8.8 0 16 7.2 16 16s-7.2 16-16 16zm216 256l0-64c0-8.8 7.2-16 16-16s16 7.2 16 16l0 96c0 8.8-7.2 16-16 16l-80 0c-8.8 0-16-7.2-16-16s7.2-16 16-16l64 0z"/></svg>
                        <span>{{ isMaximized ? 'Restaurar' : 'Maximizar' }}</span>
                    </button>

                    <button @click="goToNotes(); closeMobileMenu()"
                        class="px-4 py-2 bg-gray-500 text-white rounded-lg text-sm font-medium hover:bg-gray-600 transition flex items-center gap-1 shrink-0">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M9.4 233.4c-12.5 12.5-12.5 32.8 0 45.3l160 160c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L109.3 288 480 288c17.7 0 32-14.3 32-32s-14.3-32-32-32l-370.7 0 105.4-105.4c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0l-160 160z"/></svg>
                        Volver
                    </button>
                </div>
            </div>

            <!-- Vista según modo -->
            <QuickSaleTouch
                v-if="viewMode === 'touch'"
                :products="products"
                :cart="cart"
                :total="total"
                :saving="saving"
                @add-to-cart="addToCart"
                @update-qty="updateQty"
                @remove-item="removeItem"
                @checkout="checkout"
            />
            <QuickSaleWeb
                v-else
                :products="products"
                :cart="cart"
                :total="total"
                :saving="saving"
                :payment-methods="paymentMethods"
                @add-to-cart="addToCart"
                @update-qty="updateQty"
                @remove-item="removeItem"
                @checkout="checkout"
            />

            <!-- Modal de cliente -->
            <div v-if="showClientModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
                @click.self="showClientModal = false">
                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-bold">Seleccionar Cliente</h3>
                        <button @click="showClientModal = false" class="text-gray-400 hover:text-gray-600 text-xl">&times;</button>
                    </div>
                    <SearchClients
                        :client-default="selectedClient"
                        :document-types="[]"
                        @clientId="(data) => { selectedClient = data; showClientModal = false; }"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<!-- Estilos globales para dark mode al maximizar (penetra en componentes hijos) -->
<style>
.maximized-dark {
    background-color: #0f172a !important;
    color: #f1f5f9 !important;
}
.maximized-dark .panel,
.maximized-dark .bg-white,
.maximized-dark .dark\:bg-zinc-800 {
    background-color: #1e293b !important;
}
.maximized-dark input,
.maximized-dark select,
.maximized-dark .form-input,
.maximized-dark .form-select {
    background-color: #1e293b !important;
    color: #e2e8f0 !important;
    border-color: #334155 !important;
}
.maximized-dark select option {
    background-color: #1e293b !important;
    color: #e2e8f0 !important;
}
.maximized-dark,
.maximized-dark * {
    color: #f1f5f9;
}
.maximized-dark .text-gray-500,
.maximized-dark .text-gray-400,
.maximized-dark .text-gray-300 {
    color: #94a3b8 !important;
}
.maximized-dark .text-primary {
    color: #60a5fa !important;
}
.maximized-dark .bg-green-600 { background-color: #16a34a !important; }
.maximized-dark .bg-blue-600 { background-color: #2563eb !important; }
.maximized-dark .bg-gray-500 { background-color: #6b7280 !important; }
.maximized-dark .bg-orange-500 { background-color: #f97316 !important; }
.maximized-dark .hover\:bg-green-700:hover { background-color: #15803d !important; }
.maximized-dark .hover\:bg-blue-700:hover { background-color: #1d4ed8 !important; }
.maximized-dark .hover\:bg-gray-600:hover { background-color: #4b5563 !important; }
.maximized-dark .border-gray-200,
.maximized-dark .border-t {
    border-color: #334155 !important;
}
.maximized-dark .shadow {
    box-shadow: 0 4px 6px -1px rgba(0,0,0,0.4) !important;
}
.maximized-dark .bg-gray-50,
.maximized-dark .dark\:bg-zinc-700 {
    background-color: #0f172a !important;
}
</style>
