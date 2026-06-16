<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import {
    faClipboardList,
    faMoneyBillWave,
    faUtensils,
    faBoxesStacked,
    faTriangleExclamation,
} from '@fortawesome/free-solid-svg-icons';

const props = defineProps({
    metrics: {
        type: Object,
        default: () => ({}),
    },
    critical_supplies: {
        type: Array,
        default: () => [],
    },
});

const formatMoney = (value) => `S/ ${Number(value || 0).toFixed(2)}`;

const kpiCards = computed(() => [
    {
        label: 'Ventas del día',
        value: props.metrics.sales_today_count ?? 0,
        detail: formatMoney(props.metrics.sales_today_total),
        icon: faMoneyBillWave,
        style: {
            background: 'linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%)',
            boxShadow: '0 18px 35px rgba(37, 99, 235, 0.25)',
        },
    },
    {
        label: 'Pedidos en cocina',
        value: props.metrics.pending_kitchen ?? 0,
        detail: 'Pendientes o en preparación',
        icon: faClipboardList,
        style: {
            background: 'linear-gradient(135deg, #ea580c 0%, #c2410c 100%)',
            boxShadow: '0 18px 35px rgba(234, 88, 12, 0.24)',
        },
    },
    {
        label: 'Insumos por agotarse',
        value: props.metrics.low_stock_count ?? 0,
        detail: `${props.metrics.out_of_stock_count ?? 0} sin stock`,
        icon: faBoxesStacked,
        style: {
            background: 'linear-gradient(135deg, #dc2626 0%, #b91c1c 100%)',
            boxShadow: '0 18px 35px rgba(220, 38, 38, 0.24)',
        },
    },
    {
        label: 'Platos sin receta',
        value: props.metrics.comandas_without_recipe ?? 0,
        detail: 'Configurar en comandas',
        icon: faUtensils,
        style: {
            background: 'linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%)',
            boxShadow: '0 18px 35px rgba(124, 58, 237, 0.24)',
        },
    },
]);

const quickLinks = [
    { route: 'res_sales_create', permission: 'res_venta_nuevo', label: 'Nueva venta', description: 'Registrar cobro POS' },
    { route: 'res_sales_cuisine', permission: 'res_venta', label: 'Vista cocina', description: 'Estado de preparación' },
    { route: 'res_supplies_shopping_list', permission: 'res_lista_compras', label: 'Lista para el mercado', description: 'Qué comprar hoy' },
    { route: 'res_supplies_purchase', permission: 'res_insumos_compra', label: 'Registrar compra', description: 'Entrada de insumos' },
    { route: 'res_supplies_list', permission: 'res_insumos', label: 'Insumos', description: 'Stock y alertas' },
    { route: 'res_comandas_list', permission: 'res_comandas', label: 'Comandas', description: 'Platos y recetas' },
    { route: 'res_menu_list', permission: 'res_menu', label: 'Carta del día', description: 'Menús del restaurante' },
    { route: 'res_sales_list', permission: 'res_venta', label: 'Listado ventas', description: 'Historial y estados' },
];
</script>

<template>
    <AppLayout title="Restaurante">
        <Navigation
            :routeModule="route('res_dashboard')"
            titleModule="Restaurante"
            :data="[{ title: 'Panel' }]"
        />

        <div class="pt-5 space-y-8">
            <div>
                <h2 class="text-xl font-semibold text-gray-800 dark:text-white">Panel operativo</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Resumen del día: ventas, cocina e inventario de insumos.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
                <div
                    v-for="card in kpiCards"
                    :key="card.label"
                    class="rounded-2xl p-5 text-white"
                    :style="card.style"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm text-white/80">{{ card.label }}</p>
                            <p class="text-3xl font-bold mt-1">{{ card.value }}</p>
                            <p class="text-xs text-white/75 mt-2">{{ card.detail }}</p>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-white/15 flex items-center justify-center">
                            <FontAwesomeIcon :icon="card.icon" class="text-lg" />
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="critical_supplies.length" class="panel border-l-4 border-amber-500">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold flex items-center gap-2">
                        <FontAwesomeIcon :icon="faTriangleExclamation" class="text-amber-500" />
                        Atención inventario
                    </h3>
                    <Link v-can="'res_lista_compras'" :href="route('res_supplies_shopping_list')" class="text-primary text-sm font-medium">
                        Ver lista de compras →
                    </Link>
                </div>
                <ul class="space-y-2">
                    <li v-for="s in critical_supplies" :key="s.id" class="flex justify-between text-sm">
                        <span>{{ s.name }}</span>
                        <span class="text-amber-600 font-medium">{{ Number(s.stock).toFixed(2) }} / {{ Number(s.stock_min).toFixed(2) }} {{ s.unit }}</span>
                    </li>
                </ul>
            </div>

            <div class="panel">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">Accesos rápidos</h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    <Link
                        v-for="link in quickLinks"
                        :key="link.route"
                        v-can="link.permission"
                        :href="route(link.route)"
                        class="block p-4 rounded-xl border border-gray-200 dark:border-gray-700 hover:border-primary hover:shadow-sm transition"
                    >
                        <p class="font-medium text-gray-800 dark:text-white">{{ link.label }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ link.description }}</p>
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
