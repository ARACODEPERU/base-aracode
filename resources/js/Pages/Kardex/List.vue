<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import Pagination from '@/Components/Pagination.vue';
import ModalSmall from '@/Components/ModalSmall.vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Swal2 from 'sweetalert2';
import {
    faArrowsRotate,
    faBan,
    faBoxesStacked,
    faChartLine,
    faLayerGroup,
    faMagnifyingGlassChart,
    faSearch,
    faTriangleExclamation,
    faXmark,
} from '@fortawesome/free-solid-svg-icons';
import VueMagnifier from '@websitebeaver/vue-magnifier';
import '@websitebeaver/vue-magnifier/styles.css';

const props = defineProps({
    // Colección de establecimientos (LocalSale::all()).
    establishments: {
        type: Array,
        default: () => [],
    },
    // Paginador del inventario: un renglón por producto y establecimiento.
    kardexes: {
        type: Object,
        default: () => ({}),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    local_id: {
        type: [Number, String],
        default: 0,
    },
});

/* ------------------------------------------------------------------ Filtros */

const form = useForm({
    search: props.filters?.search ?? '',
    // El controlador puede enviar null cuando no se filtró por local.
    local_id: Number(props.local_id) || 0,
    per_page: Number(props.kardexes?.per_page) || 20,
});

const applyFilters = () => {
    form.get(route('kardex_index'), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const clearSearch = () => {
    form.search = '';
    applyFilters();
};

const resetFilters = () => {
    form.search = '';
    form.local_id = 0;
    form.per_page = 20;
    applyFilters();
};

const refreshing = ref(false);

const refresh = () => {
    refreshing.value = true;
    router.reload({
        only: ['kardexes'],
        preserveScroll: true,
        preserveState: true,
        onFinish: () => {
            refreshing.value = false;
        },
    });
};

/* -------------------------------------------------------------------- Datos */

const rows = computed(() => props.kardexes?.data ?? []);

const quantityOf = (row) => Number(row.kardex_stock ?? 0);
const minimumOf = (row) => Number(row.stock_min ?? 0);

const paginatedUnits = computed(() => rows.value.reduce((total, row) => total + quantityOf(row), 0));
const paginatedValue = computed(() =>
    rows.value.reduce((total, row) => total + quantityOf(row) * Number(row.purchase_prices ?? 0), 0)
);
const lowStockCount = computed(
    () => rows.value.filter((row) => quantityOf(row) > 0 && minimumOf(row) > 0 && quantityOf(row) <= minimumOf(row)).length
);
const outOfStockCount = computed(() => rows.value.filter((row) => quantityOf(row) <= 0).length);

const hasFilters = computed(() => Boolean(form.search) || Number(form.local_id) > 0);

const stockStatus = (row) => {
    const stock = quantityOf(row);
    const minimum = minimumOf(row);

    if (stock <= 0) {
        return { label: 'Sin stock', class: 'badge bg-danger' };
    }

    if (minimum > 0 && stock <= minimum) {
        return { label: 'Stock bajo', class: 'badge bg-warning' };
    }

    return { label: 'Disponible', class: 'badge bg-success' };
};

/* ------------------------------------------------------------------ Formato */

const formatQuantity = (value) => {
    const number = Number(value ?? 0);

    return Number.isFinite(number)
        ? number.toLocaleString('es-PE', { maximumFractionDigits: 2 })
        : '0';
};

const formatMoney = (value) => `S/ ${Number(value ?? 0).toLocaleString('es-PE', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
})}`;

/**
 * La imagen del producto puede llegar como ruta de storage, como ruta ya
 * publicada o como el nombre de la imagen por defecto; se normaliza para que
 * nunca quede un enlace roto cuando el producto no tiene foto.
 */
const imageUrl = (row) => {
    const value = String(row.image ?? '').trim();

    if (value === '') {
        return '/img/imagen-no-disponible.jpg';
    }

    if (/^https?:\/\//i.test(value)) {
        return value;
    }

    return value.startsWith('img/') ? `/${value}` : `/storage/${value}`;
};

/* ------------------------------------------------- Detalle de cantidades por talla */

const showSizes = ref(false);
const loadingSizes = ref(false);
const sizes = ref([]);
// El paginador devuelve `products.*`, así que la columna `id` es la del producto.
const sizesProduct = ref({});

const sizesTotal = computed(() =>
    sizes.value.reduce((total, size) => total + Number(size.total ?? 0), 0)
);

const openSizes = async (row) => {
    sizesProduct.value = row;
    sizes.value = [];
    showSizes.value = true;
    loadingSizes.value = true;

    try {
        const { data } = await axios.post(route('kardex_sizes'), {
            product_id: row.id,
            local_id: row.local_id,
        });
        sizes.value = Array.isArray(data) ? data : [];
    } catch (error) {
        sizes.value = [];
        showMessage('No se pudo cargar el detalle por talla de este producto.', 'error');
    } finally {
        loadingSizes.value = false;
    }
};

const closeSizes = () => {
    showSizes.value = false;
    sizes.value = [];
    sizesProduct.value = {};
};

const showMessage = (msg = '', type = 'success') => {
    Swal2.mixin({
        toast: true,
        position: 'top',
        showConfirmButton: false,
        timer: 3000,
        customClass: { container: 'toast' },
    }).fire({ icon: type, title: msg, padding: '10px 20px' });
};
</script>

<template>
    <AppLayout title="Inventario">
        <Navigation :routeModule="route('sales_dashboard')" :titleModule="'Ventas'"
            :data="[{ title: 'Inventario' }]" />

        <div class="mt-5 space-y-5">
            <!-- Encabezado -->
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Inventario por establecimiento
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Stock de productos físicos calculado desde el kardex. Solo se muestran productos, no servicios.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" class="btn btn-outline-secondary" :disabled="refreshing"
                        v-tippy="{ content: 'Volver a leer el stock', placement: 'bottom' }" @click="refresh">
                        <font-awesome-icon :icon="faArrowsRotate" class="ltr:mr-2 rtl:ml-2"
                            :class="{ 'animate-spin': refreshing }" />
                        Actualizar
                    </button>
                    <Link v-can="'sale_reportes'" :href="route('inventory_kardex_report')"
                        class="btn btn-outline-primary"
                        v-tippy="{ content: 'Entradas y salidas registradas en el kardex', placement: 'bottom' }">
                        <font-awesome-icon :icon="faChartLine" class="ltr:mr-2 rtl:ml-2" />
                        Ver movimientos
                    </Link>
                </div>
            </div>

            <!-- Filtros -->
            <div class="panel">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-12">
                    <div class="xl:col-span-3">
                        <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">
                            Establecimiento
                        </label>
                        <select v-model.number="form.local_id" class="form-select w-full" @change="applyFilters">
                            <option :value="0">Todos los locales</option>
                            <option v-for="establishment in establishments" :key="establishment.id"
                                :value="establishment.id">
                                {{ establishment.description }}
                            </option>
                        </select>
                    </div>

                    <div class="xl:col-span-5">
                        <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">
                            Buscar producto
                        </label>
                        <form @submit.prevent="applyFilters">
                            <div class="relative">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <font-awesome-icon :icon="faSearch" class="h-4 w-4 text-gray-400" />
                                </div>
                                <input v-model="form.search" type="text" class="form-input w-full ltr:pl-9 rtl:pr-9"
                                    placeholder="Código exacto o descripción" />
                                <button v-if="form.search" type="button"
                                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                                    v-tippy="{ content: 'Limpiar búsqueda', placement: 'bottom' }" @click="clearSearch">
                                    <font-awesome-icon :icon="faXmark" class="h-4 w-4" />
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="xl:col-span-2">
                        <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">
                            Mostrar
                        </label>
                        <select v-model.number="form.per_page" class="form-select w-full" @change="applyFilters">
                            <option :value="20">20 registros</option>
                            <option :value="50">50 registros</option>
                            <option :value="100">100 registros</option>
                        </select>
                    </div>

                    <div class="flex items-end gap-2 xl:col-span-2">
                        <button type="button" class="btn btn-primary w-full" @click="applyFilters">
                            <font-awesome-icon :icon="faMagnifyingGlassChart" class="ltr:mr-2 rtl:ml-2" />
                            Buscar
                        </button>
                        <button v-if="hasFilters" type="button" class="btn btn-outline-danger"
                            v-tippy="{ content: 'Quitar filtros', placement: 'bottom' }" @click="resetFilters">
                            <font-awesome-icon :icon="faXmark" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Resumen -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="panel flex items-center gap-4">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
                        <font-awesome-icon :icon="faBoxesStacked" class="h-5 w-5" />
                    </div>
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Productos con stock</div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ formatQuantity(kardexes.total ?? 0) }}
                        </div>
                    </div>
                </div>

                <div class="panel flex items-center gap-4">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-info/10 text-info">
                        <font-awesome-icon :icon="faLayerGroup" class="h-5 w-5" />
                    </div>
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Unidades en esta página</div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ formatQuantity(paginatedUnits) }}
                        </div>
                    </div>
                </div>

                <div class="panel flex items-center gap-4">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-warning/10 text-warning">
                        <font-awesome-icon :icon="faTriangleExclamation" class="h-5 w-5" />
                    </div>
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Stock bajo en esta página</div>
                        <div class="text-2xl font-bold text-warning">{{ lowStockCount }}</div>
                    </div>
                </div>

                <div class="panel flex items-center gap-4">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-danger/10 text-danger">
                        <font-awesome-icon :icon="faBan" class="h-5 w-5" />
                    </div>
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Sin stock en esta página</div>
                        <div class="text-2xl font-bold text-danger">{{ outOfStockCount }}</div>
                    </div>
                </div>
            </div>

            <!-- Tabla -->
            <div class="panel p-0 overflow-hidden">
                <div class="table-responsive">
                    <table class="table-striped table-hover w-full">
                        <thead>
                            <tr>
                                <th class="text-center">Tallas</th>
                                <th>Tienda</th>
                                <th class="text-center">Imagen</th>
                                <th>Código</th>
                                <th class="min-w-[220px]">Producto</th>
                                <th class="text-right">Mínimo</th>
                                <th class="text-right">Stock</th>
                                <th class="text-right">Valorizado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="rows.length === 0">
                                <td colspan="8" class="py-8 text-center text-gray-500 dark:text-gray-400">
                                    <font-awesome-icon :icon="faBoxesStacked" class="mb-2 h-6 w-6 opacity-50" />
                                    <div>
                                        {{ hasFilters
                                            ? 'No hay productos que coincidan con los filtros aplicados.'
                                            : 'Todavía no hay movimientos de inventario registrados.' }}
                                    </div>
                                </td>
                            </tr>
                            <tr v-for="row in rows" :key="`${row.id}-${row.local_id}`">
                                <td class="text-center">
                                    <!-- El detalle por talla solo existe en productos con presentaciones. -->
                                    <button v-if="row.presentations" type="button" class="btn btn-sm btn-outline-primary"
                                        v-tippy="{ content: 'Ver cantidades por talla', placement: 'bottom' }"
                                        @click="openSizes(row)">
                                        <font-awesome-icon :icon="faLayerGroup" />
                                    </button>
                                    <span v-else class="text-gray-300 dark:text-gray-600">—</span>
                                </td>
                                <td class="whitespace-nowrap">{{ row.local_names }}</td>
                                <td class="w-24 text-center">
                                    <VueMagnifier :src="imageUrl(row)" width="52px" :zoomImgSrc="imageUrl(row)"
                                        :mgWidth="200" :mgHeight="200" />
                                </td>
                                <td class="whitespace-nowrap font-medium">{{ row.interne }}</td>
                                <td>
                                    <div class="font-medium text-gray-900 dark:text-white">{{ row.description }}</div>
                                    <div class="text-xs text-gray-400">
                                        {{ row.presentations ? 'Con presentaciones' : 'Stock simple' }}
                                    </div>
                                </td>
                                <td class="text-right">{{ formatQuantity(row.stock_min) }}</td>
                                <td class="text-right">
                                    <div class="font-bold" :class="quantityOf(row) <= 0
                                        ? 'text-danger'
                                        : quantityOf(row) <= minimumOf(row) && minimumOf(row) > 0
                                            ? 'text-warning'
                                            : 'text-success'">
                                        {{ formatQuantity(row.kardex_stock) }}
                                    </div>
                                    <span :class="stockStatus(row).class">{{ stockStatus(row).label }}</span>
                                </td>
                                <td class="whitespace-nowrap text-right">
                                    {{ formatMoney(quantityOf(row) * Number(row.purchase_prices ?? 0)) }}
                                </td>
                            </tr>
                        </tbody>
                        <tfoot v-if="rows.length > 0">
                            <tr>
                                <td colspan="6" class="text-right font-semibold">Total de la página</td>
                                <td class="text-right font-bold">{{ formatQuantity(paginatedUnits) }}</td>
                                <td class="text-right font-bold">{{ formatMoney(paginatedValue) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <Pagination :data="kardexes" />
            </div>

            <p class="text-xs text-gray-400">
                El stock es la suma de los movimientos del kardex (compras, ventas y traslados) y la valorización usa el
                precio de compra del producto.
            </p>
        </div>

        <!-- Detalle de tallas -->
        <ModalSmall :show="showSizes" :onClose="closeSizes">
            <template #title>
                <span class="text-base">{{ sizesProduct.local_names }}</span>
            </template>
            <template #message>
                {{ sizesProduct.interne }} — {{ sizesProduct.description }}
            </template>
            <template #content>
                <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800">
                    <div class="table-responsive max-h-72 overflow-y-auto">
                        <table class="table-striped w-full">
                            <thead>
                                <tr>
                                    <th>Talla</th>
                                    <th class="text-right">Cantidad</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="loadingSizes">
                                    <td colspan="2" class="py-6 text-center text-gray-500 dark:text-gray-400">
                                        Cargando tallas…
                                    </td>
                                </tr>
                                <tr v-else-if="sizes.length === 0">
                                    <td colspan="2" class="py-6 text-center text-gray-500 dark:text-gray-400">
                                        Este producto no tiene movimientos por talla en este establecimiento.
                                    </td>
                                </tr>
                                <template v-else>
                                    <tr v-for="size in sizes" :key="size.size">
                                        <td>{{ size.size }}</td>
                                        <td class="text-right">{{ formatQuantity(size.total) }}</td>
                                    </tr>
                                </template>
                            </tbody>
                            <tfoot v-if="!loadingSizes && sizes.length > 0">
                                <tr>
                                    <th class="text-right">Total por tallas</th>
                                    <th class="text-right">{{ formatQuantity(sizesTotal) }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <p v-if="!loadingSizes && sizes.length > 0" class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                    Stock total del producto en este establecimiento:
                    <strong>{{ formatQuantity(sizesProduct.kardex_stock) }}</strong>
                </p>
            </template>
        </ModalSmall>
    </AppLayout>
</template>
