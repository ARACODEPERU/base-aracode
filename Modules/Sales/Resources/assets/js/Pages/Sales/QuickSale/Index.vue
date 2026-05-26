<script setup>
import { ref, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import SearchClients from '../../Documents/Partials/SearchClients.vue';
import QuickSaleTouch from './Partials/QuickSaleTouch.vue';
import QuickSaleWeb from './Partials/QuickSaleWeb.vue';

const props = defineProps({
    products: { type: Array, default: () => [] },
    clientDefault: { type: Object, default: () => ({}) },
    paymentMethods: { type: Array, default: () => [] },
    saleDocumentTypes: { type: Array, default: () => [] },
    documentTypes: { type: Array, default: () => [] },
    departments: { type: Array, default: () => [] },
});

const isMobile = ref(window.innerWidth < 768 || 'ontouchstart' in window);
const viewMode = ref(isMobile.value ? 'touch' : 'web');
const documentType = ref('80');
const printOption = ref('auto');
const selectedClient = ref({
    id: props.clientDefault?.id,
    full_name: props.clientDefault?.full_name ?? 'Cliente genérico',
    number: props.clientDefault?.number ?? '',
});

const displayModalClient = ref(false);
const saleDocumentTypesId = ref(null);

const clientLabel = computed(() => {
    const name = selectedClient.value?.full_name ?? 'Cliente genérico';
    const number = selectedClient.value?.number;
    return number ? `${number} - ${name}` : name;
});

const openModalClient = () => {
    const docType = props.saleDocumentTypes.find((dt) => dt.sunat_id === documentType.value);
    saleDocumentTypesId.value = docType?.id ?? null;
    displayModalClient.value = true;
};

const closeModalClient = () => {
    saleDocumentTypesId.value = null;
    displayModalClient.value = false;
};

const onClientSelected = (data) => {
    selectedClient.value = {
        id: data.id,
        full_name: data.full_name,
        number: data.number ?? '',
    };
    closeModalClient();
};

const PRODUCT_ENTITY_CLASS = 'App\\Models\\Product';

/** Copia plana del producto (evita proxies de Vue y pérdida de campos al serializar). */
const snapshotProduct = (product) => JSON.parse(JSON.stringify(product ?? {}));

const cart = ref([]);
const saving = ref(false);

const getItemDiscount = (item) => {
    const discount = Number(item?.discount ?? 0);
    if (!Number.isFinite(discount) || discount < 0) return 0;
    return Math.min(discount, Number(item?.price ?? 0));
};

const getItemTotal = (item) => {
    const price = Number(item?.price ?? 0);
    const qty = Number(item?.qty ?? 0);
    return Math.max(0, (price - getItemDiscount(item)) * qty);
};

const total = computed(() =>
    cart.value.reduce((sum, item) => sum + getItemTotal(item), 0)
);

const buildCartLine = (product, qty) => {
    const price = getProductPrice(product);
    return {
        id: product.id,
        qty,
        price,
        discount: 0,
        description: product.description,
        interne: product.interne,
        entity_name_product: product.entity_name_product ?? PRODUCT_ENTITY_CLASS,
        product: snapshotProduct(product),
    };
};

const addToCart = (product) => {
    const existing = cart.value.find(i => i.id === product.id);
    if (existing) {
        existing.qty++;
        existing.product = snapshotProduct(product);
    } else {
        cart.value.push(buildCartLine(product, 1));
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

const updateDiscount = (item, value) => {
    const idx = cart.value.findIndex(i => i.id === item.id);
    if (idx < 0) return;

    const raw = Number(value);
    const nextDiscount = Number.isFinite(raw) ? raw : 0;
    cart.value[idx].discount = Math.min(Math.max(nextDiscount, 0), Number(cart.value[idx].price ?? 0));
};

const removeItem = (item) => {
    const idx = cart.value.findIndex(i => i.id === item.id);
    if (idx >= 0) cart.value.splice(idx, 1);
};

const paymentMethodLabel = (id) => {
    const pm = props.paymentMethods.find((p) => p.id === id);
    return pm?.description ?? `Método #${id}`;
};

let printWindow = null;

const openUrl = (url) => {
    if (!url) return;
    const fullUrl = `${url}${url.includes('?') ? '&' : '?'}_=${Date.now()}`;
    try {
        if (printWindow && !printWindow.closed) {
            printWindow.close();
        }
    } catch {
        /* ventana ya cerrada */
    }
    printWindow = window.open(fullUrl, '_blank', 'noopener,noreferrer');
};

const handlePrintAfterSale = async (saleResult) => {
    const ticketUrl = saleResult?.ticket_url;
    const pdfA4Url = saleResult?.pdf_a4_url;

    if (printOption.value === 'manual') {
        return;
    }

    if (printOption.value === 'auto') {
        setTimeout(() => openUrl(ticketUrl || pdfA4Url), 600);
        return;
    }

    if (printOption.value === 'ask') {
        const docLabel = saleResult?.invoice_serie && saleResult?.invoice_correlative
            ? `${saleResult.invoice_serie}-${saleResult.invoice_correlative}`
            : 'comprobante';

        const { value: action } = await Swal.fire({
            icon: 'question',
            title: 'Venta registrada',
            html: `<p class="text-sm mb-2">${docLabel} guardado correctamente.</p>
                <p class="text-sm text-gray-500">¿Qué desea hacer?</p>`,
            input: 'select',
            inputOptions: {
                print_ticket: 'Imprimir ticket',
                print_a4: 'Ver / imprimir PDF A4',
                email: 'Enviar por correo',
                whatsapp: 'Enviar por WhatsApp',
                none: 'Solo registrar (cerrar)',
            },
            inputPlaceholder: 'Seleccione una opción',
            showCancelButton: true,
            confirmButtonText: 'Continuar',
            cancelButtonText: 'Cerrar',
            padding: '2em',
            customClass: 'sweet-alerts',
        });

        if (!action) return;

        if (action === 'print_ticket') {
            setTimeout(() => openUrl(ticketUrl || pdfA4Url), 300);
            return;
        }

        if (action === 'print_a4') {
            setTimeout(() => openUrl(pdfA4Url || ticketUrl), 300);
            return;
        }

        if (action === 'email' || action === 'whatsapp') {
            const isEmail = action === 'email';
            const { value: contact } = await Swal.fire({
                icon: 'info',
                title: isEmail ? 'Enviar por correo' : 'Enviar por WhatsApp',
                input: isEmail ? 'email' : 'tel',
                inputPlaceholder: isEmail ? 'correo@ejemplo.com' : '999 999 999',
                showCancelButton: true,
                confirmButtonText: 'Enviar',
                cancelButtonText: 'Cancelar',
                padding: '2em',
                customClass: 'sweet-alerts',
            });

            if (contact) {
                console.info(`[Venta rápida] Envío ${action} pendiente de implementar:`, contact, saleResult);
                await Swal.fire({
                    icon: 'info',
                    title: 'Próximamente',
                    text: 'El envío se habilitará en una siguiente versión. Por ahora puede imprimir el PDF.',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
                openUrl(pdfA4Url || ticketUrl);
            }
        }
    }
};

const formatPaymentsSummary = (paymentPayload) => {
    const lines = (paymentPayload.payments || []).map(
        (p) => `• ${paymentMethodLabel(p.payment_method_id)}: S/ ${Number(p.amount).toFixed(2)}${p.reference ? ` (${p.reference})` : ''}`
    );
    if (paymentPayload.cash?.change > 0) {
        lines.push(`• Vuelto: S/ ${Number(paymentPayload.cash.change).toFixed(2)}`);
    }
    return lines.join('<br>');
};

/** Cobro con payload de pago (Yape, Plin, efectivo, otros, combinado). */
const checkout = async (paymentPayload) => {
    if (cart.value.length === 0) {
        Swal.fire({ icon: 'error', title: 'Error', text: 'El carrito está vacío', padding: '2em', customClass: 'sweet-alerts' });
        return;
    }

    saving.value = true;

    const body = {
        items: cart.value.map((i) => ({
            id: i.id,
            qty: i.qty,
            price: i.price,
            discount: i.discount ?? 0,
            entity_name_product: i.entity_name_product ?? PRODUCT_ENTITY_CLASS,
            product: i.product,
        })),
        payment_amount: total.value,
        client_id: selectedClient.value.id,
        document_type_id: documentType.value,
        payment_mode: paymentPayload?.mode ?? 'quick',
        payments: paymentPayload?.payments ?? [],
        cash: paymentPayload?.cash ?? null,
        quick_method: paymentPayload?.quick_method ?? null,
    };

    try {
        const response = await axios.post(route('sales_quick_sale_store'), body);

        if (response.data.success) {
            cart.value = [];
            await handlePrintAfterSale(response.data);
            await Swal.fire({
                icon: 'success',
                title: 'Venta realizada',
                html: `<p class="text-sm">${formatPaymentsSummary(paymentPayload)}</p>`,
                padding: '2em',
                customClass: 'sweet-alerts',
            });
        }
    } catch (error) {
        const validationErrors = error.response?.data?.errors;
        let message = error.response?.data?.message || 'No se pudo procesar la venta';
        if (validationErrors) {
            message = Object.values(validationErrors).flat().join('\n');
        }
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: message,
            padding: '2em',
            customClass: 'sweet-alerts',
        });
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
        <Navigation v-if="!isMaximized" :routeModule="route('sales_dashboard')" :titleModule="'Ventas'"
            :data="[
                { title: 'Punto de Venta Rápido' }
            ]" />

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

                    <button @click="openModalClient(); closeMobileMenu()"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition flex items-center gap-1 shrink-0 max-w-[220px] truncate"
                        type="button"
                        title="Buscar, crear o editar cliente">
                        <svg class="w-4 h-4 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path fill="currentColor" d="M224 256A128 128 0 1 0 224 0a128 128 0 1 0 0 256zm-45.7 48C79.8 304 0 383.8 0 482.3C0 498.7 13.3 512 29.7 512l388.6 0c16.4 0 29.7-13.3 29.7-29.7C448 383.8 368.2 304 269.7 304l-91.4 0z"/></svg>
                        <span class="truncate">{{ clientLabel }}</span>
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
                :payment-methods="paymentMethods"
                @add-to-cart="addToCart"
                @update-qty="updateQty"
                @update-discount="updateDiscount"
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
                @update-discount="updateDiscount"
                @remove-item="removeItem"
                @checkout="checkout"
            />

            <SearchClients
                :display="displayModalClient"
                :close-modal-client="closeModalClient"
                :client-default="clientDefault"
                :document-types="documentTypes"
                :sale-document-types="saleDocumentTypesId"
                :ubigeo="departments"
                @client-id="onClientSelected"
            />
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
