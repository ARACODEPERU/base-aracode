<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import { Link, useForm, router } from '@inertiajs/vue3';
import { ConfigProvider, Select, Input, message } from 'ant-design-vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import { ref, computed, onMounted } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import {
    faCartShopping,
    faMagnifyingGlass,
    faMinus,
    faPlus,
    faTrashCan,
    faUser,
    faCreditCard,
    faCircleCheck,
    faListUl,
    faFileInvoice,
} from '@fortawesome/free-solid-svg-icons';
import esES from 'ant-design-vue/es/locale/es_ES';
import { withCsrfPayload } from '@/utils/csrf';
import Swal from 'sweetalert2';
import SearchClients from 'Modules/Sales/Resources/assets/js/Pages/Documents/Partials/SearchClients.vue';

const props = defineProps({
    paymentMethods: { type: Object, default: () => ({}) },
    clientDefault: { type: Object, default: () => ({}) },
    documentTypes: { type: Object, default: () => ({}) },
    saleDocumentTypes: { type: Array, default: () => [] },
    departments: { type: Object, default: () => ({}) },
    comandas: { type: Object, default: () => ({}) },
});

const assetBase = assetUrl;
const searchQuery = ref('');
const activeCategoryIndex = ref(0);
const activeSubcategoryIndex = ref(null);
const comandasData = ref([]);

const displayModalClient = ref(false);
const saleDocumentTypesId = ref(null);
const documentType = ref('80');

const selectedDocumentLabel = computed(() => {
    const found = props.saleDocumentTypes.find((dt) => dt.sunat_id === documentType.value);
    return found?.description ?? 'Nota de venta';
});

const selectedClient = ref({
    id: props.clientDefault?.id ?? null,
    full_name: props.clientDefault?.full_name ?? 'Cliente genérico',
    number: props.clientDefault?.number ?? '',
    document_type_id: props.clientDefault?.document_type_id ?? null,
});

const clientHasValidRuc = computed(() => {
    const docTypeId = String(selectedClient.value?.document_type_id ?? '');
    const number = String(selectedClient.value?.number ?? '').trim();
    return docTypeId === '6' && /^\d{11}$/.test(number);
});

const isFactura = computed(() => documentType.value === '01');

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
        document_type_id: data.document_type_id ?? null,
    };
    form.client_id = data.id;
    closeModalClient();
};

const resetToDefaultClient = () => {
    selectedClient.value = {
        id: props.clientDefault?.id ?? null,
        full_name: props.clientDefault?.full_name ?? 'Cliente genérico',
        number: props.clientDefault?.number ?? '',
        document_type_id: props.clientDefault?.document_type_id ?? null,
    };
    form.client_id = props.clientDefault?.id ?? null;
};

const categories = computed(() => props.comandas ?? []);

const activeCategory = computed(() => categories.value[activeCategoryIndex.value] ?? null);

const subcategories = computed(() => activeCategory.value?.subcategories ?? []);

const loadComandas = (list) => {
    comandasData.value = (list ?? []).map((c) => ({
        id: c.id,
        name: c.name,
        description: c.description,
        image: c.image,
        price: c.price,
        presentation: c.presentation?.description ?? '',
    }));
};

const selectCategory = (index) => {
    activeCategoryIndex.value = index;
    activeSubcategoryIndex.value = null;
    const cat = categories.value[index];
    if (cat?.subcategories?.length) {
        loadComandas([]);
    } else {
        loadComandas(cat?.comandas ?? []);
    }
};

const selectSubcategory = (index) => {
    activeSubcategoryIndex.value = index;
    loadComandas(subcategories.value[index]?.comandas ?? []);
};

const filteredProducts = computed(() => {
    const q = searchQuery.value.trim().toLowerCase();
    if (!q) return comandasData.value;
    return comandasData.value.filter(
        (c) =>
            c.name.toLowerCase().includes(q) ||
            c.description?.toLowerCase().includes(q) ||
            c.presentation?.toLowerCase().includes(q)
    );
});

onMounted(() => {
    if (categories.value.length) {
        selectCategory(0);
    }
    if (
        props.saleDocumentTypes.length &&
        !props.saleDocumentTypes.some((dt) => dt.sunat_id === documentType.value)
    ) {
        documentType.value = props.saleDocumentTypes[0].sunat_id;
    }
});

const form = useForm({
    client_id: props.clientDefault?.id ?? null,
    total: 0,
    comandas: [],
    payments: [{ type: props.paymentMethods?.[0]?.id ?? 1, reference: null, amount: 0 }],
});

const cartCount = computed(() =>
    form.comandas.reduce((sum, item) => sum + Number(item.quantity), 0)
);

const paymentsTotal = computed(() =>
    form.payments.reduce((sum, p) => sum + parseFloat(p.amount || 0), 0).toFixed(2)
);

const paymentBalanced = computed(
    () => parseFloat(paymentsTotal.value) === parseFloat(form.total || 0)
);

const recalcTotal = () => {
    form.total = form.comandas
        .reduce((acc, item) => acc + parseFloat(item.total), 0)
        .toFixed(2);
    if (form.payments.length === 1) {
        form.payments[0].amount = form.total;
    }
};

const addComanda = (comanda) => {
    const existing = form.comandas.find((item) => item.id === comanda.id);
    if (existing) {
        existing.quantity = Number(existing.quantity) + 1;
        existing.total = (existing.quantity * parseFloat(existing.price)).toFixed(2);
    } else {
        form.comandas.push({
            id: comanda.id,
            name: comanda.name,
            description: comanda.description,
            image: comanda.image,
            presentation: comanda.presentation,
            price: comanda.price,
            quantity: 1,
            total: parseFloat(comanda.price).toFixed(2),
        });
    }
    recalcTotal();
};

const changeQuantity = (index, delta) => {
    const item = form.comandas[index];
    const next = Number(item.quantity) + delta;
    if (next < 1) {
        removeItem(index);
        return;
    }
    item.quantity = next;
    item.total = (next * parseFloat(item.price)).toFixed(2);
    recalcTotal();
};

const removeItem = (index) => {
    form.comandas.splice(index, 1);
    recalcTotal();
};

const clearCart = () => {
    if (!form.comandas.length) return;
    Swal.fire({
        title: '¿Vaciar pedido?',
        text: 'Se quitarán todos los platos del carrito.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, vaciar',
        cancelButtonText: 'Cancelar',
    }).then((result) => {
        if (result.isConfirmed) {
            form.comandas = [];
            recalcTotal();
        }
    });
};

const addPayment = () => {
    form.payments.push({
        type: props.paymentMethods?.[0]?.id ?? 1,
        reference: null,
        amount: 0,
    });
};

const removePayment = (index) => {
    if (form.payments.length > 1) {
        form.payments.splice(index, 1);
    }
};

const saveSale = () => {
    if (!form.client_id) {
        message.error('Seleccione un cliente');
        return;
    }
    if (!form.comandas.length) {
        message.error('Agregue al menos un plato al pedido');
        return;
    }
    if (!paymentBalanced.value) {
        message.error('El total de pagos debe coincidir con el total de la venta');
        return;
    }
    if (isFactura.value && !clientHasValidRuc.value) {
        Swal.fire({
            icon: 'warning',
            title: 'Cliente con RUC requerido',
            text: 'Para factura electrónica debe seleccionar un cliente registrado con RUC de 11 dígitos. Use el botón de cliente para buscarlo o crearlo.',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
        return;
    }

    form.processing = true;
    const payload = {
        client_id: form.client_id,
        document_type_id: documentType.value,
        total: parseFloat(form.total),
        comandas: form.comandas.map((item) => ({
            id: item.id,
            quantity: item.quantity,
            price: item.price,
        })),
        payments: form.payments,
    };

    axios
        .post(route('res_sales_store'), withCsrfPayload(payload))
        .then((res) => {
            if (!res.data.success) {
                message.error(res.data.message);
                return;
            }
            Swal.fire({
                icon: 'success',
                title: 'Venta registrada',
                text: res.data.message,
                timer: 2000,
                showConfirmButton: false,
            });
            router.visit(route('res_sales_list'));
        })
        .catch((error) => {
            message.error(error.response?.data?.message || 'Error al registrar la venta');
        })
        .finally(() => {
            form.processing = false;
        });
};

const formatMoney = (value) => `S/ ${Number(value || 0).toFixed(2)}`;
</script>

<template>
    <AppLayout title="Vender">
        <Navigation
            :routeModule="route('res_dashboard')"
            titleModule="Restaurante"
            :data="[{ title: 'Vender' }]"
        />

        <ConfigProvider :locale="esES">
            <div class="pt-5 space-y-4">
                <!-- Encabezado -->
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-800 dark:text-white flex items-center gap-2">
                            <FontAwesomeIcon :icon="faCartShopping" class="text-primary" />
                            Punto de venta
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                            Toque un plato para agregarlo al pedido
                        </p>
                    </div>
                    <Link
                        :href="route('res_sales_list')"
                        class="btn btn-outline-primary btn-sm gap-2"
                    >
                        <FontAwesomeIcon :icon="faListUl" />
                        Ver ventas
                    </Link>
                </div>

                <div class="grid grid-cols-1 xl:grid-cols-12 gap-5">
                    <!-- Catálogo -->
                    <div class="xl:col-span-7 space-y-4">
                        <div class="panel p-0 overflow-hidden">
                            <!-- Búsqueda -->
                            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                                <div class="relative">
                                    <FontAwesomeIcon
                                        :icon="faMagnifyingGlass"
                                        class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"
                                    />
                                    <input
                                        v-model="searchQuery"
                                        type="text"
                                        placeholder="Buscar plato..."
                                        class="form-input pl-10 w-full"
                                    />
                                </div>
                            </div>

                            <!-- Categorías -->
                            <div
                                v-if="categories.length"
                                class="px-4 pt-3 flex gap-2 overflow-x-auto no-scrollbar border-b border-gray-200 dark:border-gray-700 pb-0"
                            >
                                <button
                                    v-for="(cat, idx) in categories"
                                    :key="cat.id"
                                    type="button"
                                    class="shrink-0 px-4 py-2 text-sm font-medium rounded-t-lg border-b-2 transition-colors"
                                    :class="
                                        activeCategoryIndex === idx
                                            ? 'border-primary text-primary bg-primary/5'
                                            : 'border-transparent text-gray-500 hover:text-gray-800 dark:hover:text-white'
                                    "
                                    @click="selectCategory(idx)"
                                >
                                    {{ cat.description }}
                                </button>
                            </div>

                            <!-- Subcategorías -->
                            <div
                                v-if="subcategories.length"
                                class="px-4 py-3 flex flex-wrap gap-2 bg-gray-50 dark:bg-gray-800/50"
                            >
                                <button
                                    v-for="(sub, idx) in subcategories"
                                    :key="sub.id"
                                    type="button"
                                    class="px-3 py-1.5 text-xs font-medium rounded-full border transition-colors"
                                    :class="
                                        activeSubcategoryIndex === idx
                                            ? 'bg-primary text-white border-primary'
                                            : 'border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:border-primary hover:text-primary'
                                    "
                                    @click="selectSubcategory(idx)"
                                >
                                    {{ sub.description }}
                                </button>
                                <p
                                    v-if="activeSubcategoryIndex === null"
                                    class="text-xs text-amber-600 dark:text-amber-400 self-center"
                                >
                                    Seleccione una subcategoría
                                </p>
                            </div>

                            <!-- Grid de productos -->
                            <div class="p-4 min-h-[320px]">
                                <div
                                    v-if="subcategories.length && activeSubcategoryIndex === null"
                                    class="flex flex-col items-center justify-center py-16 text-gray-400"
                                >
                                    <FontAwesomeIcon :icon="faCartShopping" class="text-4xl mb-3 opacity-40" />
                                    <p>Elija una subcategoría para ver los platos</p>
                                </div>
                                <div
                                    v-else-if="filteredProducts.length === 0"
                                    class="flex flex-col items-center justify-center py-16 text-gray-400"
                                >
                                    <FontAwesomeIcon :icon="faMagnifyingGlass" class="text-4xl mb-3 opacity-40" />
                                    <p>No hay platos en esta categoría</p>
                                </div>
                                <div
                                    v-else
                                    class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3"
                                >
                                    <button
                                        v-for="comanda in filteredProducts"
                                        :key="comanda.id"
                                        type="button"
                                        class="group text-left rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden hover:border-primary hover:shadow-md transition-all focus:outline-none focus:ring-2 focus:ring-primary/40"
                                        @click="addComanda(comanda)"
                                    >
                                        <div class="aspect-square bg-gray-100 dark:bg-gray-800 overflow-hidden">
                                            <img
                                                :src="assetBase + 'storage/' + comanda.image"
                                                :alt="comanda.name"
                                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200"
                                            />
                                        </div>
                                        <div class="p-2.5">
                                            <p class="font-medium text-sm text-gray-800 dark:text-white line-clamp-1">
                                                {{ comanda.name }}
                                            </p>
                                            <p
                                                v-if="comanda.presentation"
                                                class="text-xs text-gray-400 line-clamp-1 mt-0.5"
                                            >
                                                {{ comanda.presentation }}
                                            </p>
                                            <p class="text-sm font-bold text-primary mt-1">
                                                {{ formatMoney(comanda.price) }}
                                            </p>
                                        </div>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Carrito / Cobro -->
                    <div class="xl:col-span-5">
                        <div class="panel p-0 xl:sticky xl:top-4 flex flex-col">
                            <!-- Tipo de comprobante -->
                            <div class="shrink-0 p-4 border-b border-gray-200 dark:border-gray-700 space-y-2">
                                <InputLabel class="flex items-center gap-1.5">
                                    <FontAwesomeIcon :icon="faFileInvoice" class="text-gray-400 text-xs" />
                                    Tipo de comprobante
                                </InputLabel>
                                <select v-model="documentType" class="form-select w-full">
                                    <option
                                        v-for="dt in saleDocumentTypes"
                                        :key="dt.id"
                                        :value="dt.sunat_id"
                                    >
                                        {{ dt.description }}
                                    </option>
                                </select>
                                <p class="text-xs text-gray-400">
                                    Comprobante seleccionado: {{ selectedDocumentLabel }}
                                </p>
                            </div>

                            <!-- Cliente -->
                            <div class="shrink-0 p-4 border-b border-gray-200 dark:border-gray-700 space-y-2">
                                <InputLabel class="flex items-center gap-1.5">
                                    <FontAwesomeIcon :icon="faUser" class="text-gray-400 text-xs" />
                                    Cliente
                                </InputLabel>
                                <div class="flex gap-2">
                                    <button
                                        type="button"
                                        class="flex-1 min-w-0 flex items-center gap-2 px-3 py-2.5 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 hover:border-primary hover:text-primary transition-colors text-left text-sm"
                                        title="Buscar, crear o editar cliente"
                                        @click="openModalClient"
                                    >
                                        <FontAwesomeIcon :icon="faUser" class="shrink-0 text-primary" />
                                        <span class="truncate">{{ clientLabel }}</span>
                                    </button>
                                    <button
                                        v-if="form.client_id !== clientDefault?.id"
                                        type="button"
                                        class="shrink-0 px-3 py-2 text-xs rounded-lg border border-gray-200 dark:border-gray-600 text-gray-500 hover:border-primary hover:text-primary transition-colors"
                                        title="Restablecer cliente genérico"
                                        @click="resetToDefaultClient"
                                    >
                                        Genérico
                                    </button>
                                </div>
                                <p class="text-xs text-gray-400">
                                    Por defecto: cliente genérico. Use el botón para buscar en BD, SUNAT o RENIEC.
                                </p>
                                <p
                                    v-if="isFactura && !clientHasValidRuc"
                                    class="text-xs text-red-500 dark:text-red-400"
                                >
                                    Factura electrónica: el cliente debe tener RUC de 11 dígitos.
                                </p>
                                <InputError :message="form.errors.client_id" class="mt-1" />
                            </div>

                            <!-- Lista del pedido -->
                            <div class="flex-1 min-h-0 overflow-y-auto p-4 space-y-3">
                                <div class="flex items-center justify-between">
                                    <h3 class="font-semibold text-gray-800 dark:text-white">
                                        Pedido
                                        <span
                                            v-if="cartCount"
                                            class="ml-1 text-xs font-normal bg-primary/10 text-primary px-2 py-0.5 rounded-full"
                                        >
                                            {{ cartCount }} {{ cartCount === 1 ? 'ítem' : 'ítems' }}
                                        </span>
                                    </h3>
                                    <button
                                        v-if="form.comandas.length"
                                        type="button"
                                        class="text-xs text-red-500 hover:text-red-700"
                                        @click="clearCart"
                                    >
                                        Vaciar
                                    </button>
                                </div>
                                <div class="overflow-hidden max-h-[calc(100vh-9rem)]">
                                    <div
                                        v-if="!form.comandas.length"
                                        class="flex flex-col items-center justify-center py-10 text-gray-400 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-xl"
                                    >
                                        <FontAwesomeIcon :icon="faCartShopping" class="text-3xl mb-2 opacity-30" />
                                        <p class="text-sm">El pedido está vacío</p>
                                        <p class="text-xs mt-1">Agregue platos desde el catálogo</p>
                                    </div>

                                    <div
                                        v-for="(item, index) in form.comandas"
                                        :key="item.id"
                                        class="flex gap-3 p-3 rounded-xl bg-gray-50 dark:bg-gray-800/60 border border-gray-100 dark:border-gray-700"
                                    >
                                        <img
                                            :src="assetBase + 'storage/' + item.image"
                                            :alt="item.name"
                                            class="w-14 h-14 rounded-lg object-cover shrink-0"
                                        />
                                        <div class="flex-1 min-w-0">
                                            <p class="font-medium text-sm text-gray-800 dark:text-white truncate">
                                                {{ item.name }}
                                            </p>
                                            <p class="text-xs text-gray-400">{{ formatMoney(item.price) }} c/u</p>
                                            <div class="flex items-center gap-2 mt-2">
                                                <button
                                                    type="button"
                                                    class="w-7 h-7 rounded-lg border border-gray-300 dark:border-gray-600 flex items-center justify-center hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors"
                                                    @click="changeQuantity(index, -1)"
                                                >
                                                    <FontAwesomeIcon :icon="faMinus" class="text-xs" />
                                                </button>
                                                <span class="w-8 text-center font-semibold text-sm">{{ item.quantity }}</span>
                                                <button
                                                    type="button"
                                                    class="w-7 h-7 rounded-lg border border-gray-300 dark:border-gray-600 flex items-center justify-center hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors"
                                                    @click="changeQuantity(index, 1)"
                                                >
                                                    <FontAwesomeIcon :icon="faPlus" class="text-xs" />
                                                </button>
                                                <button
                                                    type="button"
                                                    class="ml-auto w-7 h-7 rounded-lg text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 flex items-center justify-center"
                                                    @click="removeItem(index)"
                                                >
                                                    <FontAwesomeIcon :icon="faTrashCan" class="text-xs" />
                                                </button>
                                            </div>
                                        </div>
                                        <p class="font-bold text-sm text-gray-800 dark:text-white shrink-0 self-start mt-1">
                                            {{ formatMoney(item.total) }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Pagos -->
                            <div class="shrink-0 p-4 border-t border-gray-200 dark:border-gray-700 space-y-3 bg-gray-50/50 dark:bg-gray-800/30">
                                <div class="flex items-center justify-between">
                                    <span class="flex items-center gap-1.5 text-sm font-medium text-gray-600 dark:text-gray-300">
                                        <FontAwesomeIcon :icon="faCreditCard" class="text-xs" />
                                        Pago
                                    </span>
                                    <button
                                        type="button"
                                        class="text-xs text-primary hover:underline"
                                        @click="addPayment"
                                    >
                                        + Dividir pago
                                    </button>
                                </div>

                                <div
                                    v-for="(met, ky) in form.payments"
                                    :key="ky"
                                    class="flex flex-wrap gap-2 items-center"
                                >
                                    <Select
                                        v-model:value="met.type"
                                        class="flex-1 min-w-[120px]"
                                        :options="paymentMethods.map((m) => ({ value: m.id, label: m.description }))"
                                    />
                                    <Input
                                        v-model:value="met.reference"
                                        placeholder="Ref."
                                        class="w-24"
                                    />
                                    <Input
                                        v-model:value="met.amount"
                                        class="w-28 text-right"
                                        placeholder="0.00"
                                    />
                                    <button
                                        v-if="form.payments.length > 1"
                                        type="button"
                                        class="text-red-500 hover:text-red-700 px-1"
                                        @click="removePayment(ky)"
                                    >
                                        <FontAwesomeIcon :icon="faTrashCan" class="text-sm" />
                                    </button>
                                </div>

                                <p
                                    v-if="form.comandas.length && !paymentBalanced"
                                    class="text-xs text-amber-600 dark:text-amber-400"
                                >
                                    Pagos: {{ formatMoney(paymentsTotal) }} — falta cuadrar con {{ formatMoney(form.total) }}
                                </p>
                            </div>

                            <!-- Total y cobrar -->
                            <div class="shrink-0 p-4 border-t border-gray-200 dark:border-gray-700 space-y-3 bg-white dark:bg-gray-900">
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-500 dark:text-gray-400">Total a cobrar</span>
                                    <span class="text-2xl font-bold text-gray-900 dark:text-white">
                                        {{ formatMoney(form.total) }}
                                    </span>
                                </div>
                                <button
                                    type="button"
                                    class="btn btn-primary w-full py-3 text-sm font-semibold gap-2 flex items-center justify-center"
                                    :class="{ 'opacity-50 cursor-not-allowed': form.processing || !form.comandas.length || (isFactura && !clientHasValidRuc) }"
                                    :disabled="form.processing || !form.comandas.length || (isFactura && !clientHasValidRuc)"
                                    @click="saveSale"
                                >
                                    <FontAwesomeIcon :icon="faCircleCheck" />
                                    {{ form.processing ? 'Procesando...' : 'Cobrar' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </ConfigProvider>

        <SearchClients
            :display="displayModalClient"
            :close-modal-client="closeModalClient"
            :client-default="clientDefault"
            :document-types="documentTypes"
            :sale-document-types="saleDocumentTypesId"
            :ubigeo="departments"
            @client-id="onClientSelected"
        />
    </AppLayout>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
.line-clamp-1 {
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
