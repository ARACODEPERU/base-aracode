<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import { Link } from '@inertiajs/vue3';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import Keypad from '@/Components/Keypad.vue';
import TextInput from '@/Components/TextInput.vue';
import DropdownExports from '@/Components/DropdownExports.vue';
import { computed, onMounted, ref, watch } from 'vue';
import Swal from 'sweetalert2';

const props = defineProps({
    locals: {
        type: Array,
        default: () => [],
    },
    products: {
        type: Array,
        default: () => [],
    },
});

const loading = ref(false);
const rows = ref([]);
const summary = ref({
    total_movements: 0,
    net_quantity: 0,
    entries_in: 0,
    entries_out: 0,
});

const filters = ref({
    local_id: 0,
    product_id: 0,
    size: '',
    date_from: '',
    date_to: '',
});

const availableSizes = ref([]);

const selectedProduct = computed(() =>
    props.products.find((p) => p.id === Number(filters.value.product_id)) ?? null
);

const showSizeFilter = computed(() => {
    const p = selectedProduct.value;
    return p && (p.presentations == 1 || p.presentations === true);
});

const motionBadgeClass = (motion) => {
    if (motion === 'purchase') {
        return 'bg-success/15 text-success border-success/30';
    }
    return 'bg-danger/15 text-danger border-danger/30';
};

const formatQuantity = (qty) => {
    const n = Number(qty);
    if (!Number.isFinite(n)) return '0';
    const prefix = n > 0 ? '+' : '';
    return `${prefix}${n.toLocaleString('es-PE', { minimumFractionDigits: 0, maximumFractionDigits: 2 })}`;
};

const formatDate = (value) => {
    if (!value) return '—';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value;
    return date.toLocaleDateString('es-PE');
};

const loadSizes = async () => {
    availableSizes.value = [];
    filters.value.size = '';

    if (!showSizeFilter.value) return;

    try {
        const { data } = await axios.post(route('inventory_kardex_report_sizes'), {
            product_id: filters.value.product_id,
            local_id: filters.value.local_id,
        });
        availableSizes.value = data.sizes ?? [];
    } catch {
        availableSizes.value = [];
    }
};

const loadReport = async () => {
    if (filters.value.date_from && filters.value.date_to && filters.value.date_from > filters.value.date_to) {
        showMessage('La fecha de inicio no puede ser mayor a la fecha de término', 'error');
        return;
    }

    loading.value = true;
    try {
        const { data } = await axios.post(route('inventory_kardex_report_data'), filters.value);
        rows.value = data.rows ?? [];
        summary.value = data.summary ?? {
            total_movements: 0,
            net_quantity: 0,
            entries_in: 0,
            entries_out: 0,
        };
    } catch {
        showMessage('No se pudo cargar el reporte de kardex', 'error');
        rows.value = [];
    } finally {
        loading.value = false;
    }
};

const resetFilters = () => {
    filters.value = {
        local_id: 0,
        product_id: 0,
        size: '',
        date_from: '',
        date_to: '',
    };
    availableSizes.value = [];
    loadReport();
};

const showMessage = (msg = '', type = 'success') => {
    Swal.mixin({
        toast: true,
        position: 'top',
        showConfirmButton: false,
        timer: 3000,
        customClass: { container: 'toast' },
    }).fire({ icon: type, title: msg, padding: '10px 20px' });
};

watch(
    () => filters.value.product_id,
    () => {
        loadSizes();
    }
);

watch(
    () => filters.value.local_id,
    () => {
        if (showSizeFilter.value) {
            loadSizes();
        }
    }
);

onMounted(() => {
    loadReport();
});
</script>

<template>
    <AppLayout title="Reporte de Kardex">
        <Navigation
            :routeModule="route('sales_dashboard')"
            :titleModule="'Ventas'"
            :data="[
                { route: route('reports'), title: 'Reportes' },
                { title: 'Reporte de kardex' },
            ]"
        />

        <div class="mt-5 space-y-5">
            <div class="panel">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between mb-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Movimientos de inventario</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Historial de entradas y salidas registradas en kardex. Solo productos físicos.
                        </p>
                    </div>
                    <Keypad>
                        <template #botones>
                            <Link
                                :href="route('reports')"
                                class="inline-block px-5 py-2.5 bg-blue-900 text-white text-xs font-medium uppercase rounded shadow-md hover:bg-blue-700 transition"
                            >
                                Volver a reportes
                            </Link>
                        </template>
                    </Keypad>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-6 gap-3">
                    <div class="xl:col-span-1">
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Local</label>
                        <select v-model.number="filters.local_id" class="form-select w-full">
                            <option :value="0">Todos los locales</option>
                            <option v-for="local in locals" :key="local.id" :value="local.id">
                                {{ local.description }}
                            </option>
                        </select>
                    </div>

                    <div class="xl:col-span-2">
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Producto</label>
                        <select v-model.number="filters.product_id" class="form-select w-full">
                            <option :value="0">Todos los productos</option>
                            <option v-for="product in products" :key="product.id" :value="product.id">
                                {{ product.interne }} — {{ product.description }}
                            </option>
                        </select>
                    </div>

                    <div class="xl:col-span-1">
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Talla / presentación</label>
                        <select
                            v-model="filters.size"
                            class="form-select w-full"
                            :disabled="!showSizeFilter"
                        >
                            <option value="">Todas</option>
                            <option v-for="size in availableSizes" :key="size" :value="size">
                                {{ size }}
                            </option>
                        </select>
                        <p v-if="filters.product_id && !showSizeFilter" class="text-[11px] text-gray-400 mt-1">
                            Este producto no maneja tallas.
                        </p>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Desde</label>
                        <TextInput v-model="filters.date_from" type="date" class="w-full" />
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Hasta</label>
                        <TextInput v-model="filters.date_to" type="date" class="w-full" />
                    </div>
                </div>

                <div class="flex flex-wrap gap-2 mt-4">
                    <button
                        type="button"
                        class="btn btn-primary"
                        :disabled="loading"
                        @click="loadReport"
                    >
                        {{ loading ? 'Cargando…' : 'Buscar' }}
                    </button>
                    <button
                        type="button"
                        class="btn btn-outline-secondary"
                        :disabled="loading"
                        @click="resetFilters"
                    >
                        Limpiar filtros
                    </button>
                    <DropdownExports
                        :show-excel="true"
                        :action-url="route('inventory_kardex_report_export')"
                        status-route-name="inventory_kardex_export_status"
                        :data="filters"
                    />
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                <div class="panel">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Movimientos</div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ summary.total_movements }}</div>
                </div>
                <div class="panel">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Entradas (+)</div>
                    <div class="text-2xl font-bold text-success">{{ formatQuantity(summary.entries_in) }}</div>
                </div>
                <div class="panel">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Salidas (−)</div>
                    <div class="text-2xl font-bold text-danger">{{ formatQuantity(-summary.entries_out) }}</div>
                </div>
                <div class="panel">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Saldo neto</div>
                    <div class="text-2xl font-bold text-primary">{{ formatQuantity(summary.net_quantity) }}</div>
                </div>
            </div>

            <div class="panel p-0 overflow-hidden">
                <div class="table-responsive">
                    <table class="table-striped w-full">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Local</th>
                                <th>Producto</th>
                                <th>Talla</th>
                                <th>Tipo</th>
                                <th class="text-right">Cantidad</th>
                                <th>Descripción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="loading">
                                <td colspan="7" class="text-center py-8 text-gray-500">Cargando movimientos…</td>
                            </tr>
                            <tr v-else-if="!rows.length">
                                <td colspan="7" class="text-center py-8 text-gray-500">
                                    No hay movimientos con los filtros seleccionados.
                                </td>
                            </tr>
                            <tr v-for="row in rows" :key="row.id">
                                <td class="whitespace-nowrap">{{ formatDate(row.date_of_issue) }}</td>
                                <td>{{ row.local_name }}</td>
                                <td class="min-w-[220px]">
                                    <div class="font-medium text-gray-900 dark:text-white">{{ row.product_description }}</div>
                                    <div class="text-xs text-primary">Cód: {{ row.interne }}</div>
                                </td>
                                <td>
                                    <span
                                        v-if="row.size"
                                        class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300"
                                    >
                                        {{ row.size }}
                                    </span>
                                    <span v-else class="text-gray-400">—</span>
                                </td>
                                <td>
                                    <span
                                        class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold border"
                                        :class="motionBadgeClass(row.motion)"
                                    >
                                        {{ row.motion_label }}
                                    </span>
                                </td>
                                <td class="text-right font-semibold whitespace-nowrap" :class="row.quantity >= 0 ? 'text-success' : 'text-danger'">
                                    {{ formatQuantity(row.quantity) }}
                                </td>
                                <td class="text-sm text-gray-600 dark:text-gray-300">{{ row.description || '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
