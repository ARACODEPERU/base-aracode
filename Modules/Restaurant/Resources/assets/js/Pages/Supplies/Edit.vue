<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import EditForm from './Partials/EditForm.vue';

const props = defineProps({
    supply: { type: Object, default: () => ({}) },
    movements: { type: Array, default: () => [] },
    units: { type: Array, default: () => [] },
});

const typeLabel = (type) => ({
    purchase: 'Compra',
    consumption: 'Consumo venta',
    adjustment: 'Ajuste',
    void_sale: 'Anulación venta',
}[type] || type);
</script>

<template>
    <AppLayout :title="`Editar: ${supply.name}`">
        <Navigation
            :routeModule="route('res_dashboard')"
            titleModule="Restaurante"
            :data="[{ title: 'Insumos', route: route('res_supplies_list') }, { title: supply.name }]"
        />
        <div class="pt-5 space-y-8">
            <EditForm :supply="supply" :units="units" />

            <div class="rounded-sm border border-stroke bg-white shadow-default dark:border-strokedark dark:bg-boxdark">
                <div class="w-full p-4 border-b border-gray-200 bg-gray-50 dark:border-gray-600 dark:bg-gray-700">
                    <h3 class="font-semibold text-black dark:text-white">Historial de movimientos</h3>
                </div>
                <div class="max-w-full overflow-x-auto">
                    <table class="w-full table-auto text-sm">
                        <thead class="border-b border-stroke">
                            <tr class="bg-gray-50 text-left dark:bg-meta-4">
                                <th class="py-2 px-4 font-medium text-black dark:text-white">Fecha</th>
                                <th class="py-2 px-4 font-medium text-black dark:text-white">Tipo</th>
                                <th class="py-2 px-4 text-right font-medium text-black dark:text-white">Cantidad</th>
                                <th class="py-2 px-4 font-medium text-black dark:text-white">Nota</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="m in movements" :key="m.id" class="border-b border-stroke">
                                <td class="py-2 px-4">{{ m.created_at?.substring(0, 16) }}</td>
                                <td class="py-2 px-4">{{ typeLabel(m.type) }}</td>
                                <td class="py-2 px-4 text-right" :class="Number(m.quantity) < 0 ? 'text-red-600' : 'text-green-600'">
                                    {{ Number(m.quantity).toFixed(2) }}
                                </td>
                                <td class="py-2 px-4 text-gray-500">{{ m.notes || '—' }}</td>
                            </tr>
                            <tr v-if="!movements.length">
                                <td colspan="4" class="py-8 text-center text-gray-500">Sin movimientos registrados</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
