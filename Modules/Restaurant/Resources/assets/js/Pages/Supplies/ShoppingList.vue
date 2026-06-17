<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import Keypad from '@/Components/Keypad.vue';
import { Link } from '@inertiajs/vue3';
import { faPrint } from '@fortawesome/free-solid-svg-icons';

defineProps({
    items: { type: Array, default: () => [] },
});

const printList = () => {
    const ventana = window.open('', '_blank');
    const estilos = document.querySelectorAll('link[rel="stylesheet"]');
    estilos.forEach((estilo) => ventana.document.head.appendChild(estilo.cloneNode(true)));
    ventana.document.body.innerHTML = document.getElementById('shoppingListPrint').outerHTML;
    ventana.print();
};
</script>

<template>
    <AppLayout title="Lista para el mercado">
        <Navigation
            :routeModule="route('res_dashboard')"
            titleModule="Restaurante"
            :data="[{ title: 'Lista para el mercado' }]"
        />
        <div class="pt-5 space-y-8">
            <div class="flex flex-col gap-10">
                <div class="rounded-sm border border-stroke bg-white shadow-default dark:border-strokedark dark:bg-boxdark">
                    <div class="w-full p-4 border-b border-gray-200 bg-gray-50 dark:border-gray-600 dark:bg-gray-700 no-print">
                        <div class="grid grid-cols-3">
                            <div class="col-span-3 sm:col-span-2">
                                <h2 class="text-lg font-semibold text-black dark:text-white">Lista para el mercado</h2>
                                <p class="text-sm text-gray-500 mt-1">Insumos con stock bajo o agotado</p>
                            </div>
                            <div class="col-span-3 sm:col-span-1">
                                <Keypad>
                                    <template #botones>
                                        <button
                                            type="button"
                                            class="inline-block px-6 py-2.5 bg-blue-900 text-white font-medium text-xs uppercase rounded shadow-md hover:bg-blue-700 transition"
                                            @click="printList"
                                        >
                                            <font-awesome-icon :icon="faPrint" class="mr-1" />
                                            Imprimir
                                        </button>
                                        <Link
                                            v-can="'res_insumos_compra'"
                                            :href="route('res_supplies_purchase')"
                                            class="inline-block px-6 py-2.5 bg-green-700 text-white font-medium text-xs uppercase rounded shadow-md hover:bg-green-800 transition"
                                        >
                                            Registrar compra
                                        </Link>
                                    </template>
                                </Keypad>
                            </div>
                        </div>
                    </div>
                    <div id="shoppingListPrint" class="max-w-full overflow-x-auto">
                        <table class="w-full table-auto">
                            <thead class="border-b border-stroke">
                                <tr class="bg-gray-50 text-left dark:bg-meta-4">
                                    <th class="py-2 px-4 font-medium text-black dark:text-white">Insumo</th>
                                    <th class="py-2 px-4 font-medium text-black dark:text-white">Unidad</th>
                                    <th class="py-2 px-4 text-right font-medium text-black dark:text-white">Stock actual</th>
                                    <th class="py-2 px-4 text-right font-medium text-black dark:text-white">Mínimo</th>
                                    <th class="py-2 px-4 text-right font-medium text-black dark:text-white">Comprar</th>
                                    <th class="py-2 px-4 text-center font-medium text-black dark:text-white w-12 print-only">✓</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in items" :key="item.id" class="border-b border-stroke">
                                    <td class="py-2 px-4">{{ item.name }}</td>
                                    <td class="py-2 px-4">{{ item.unit }}</td>
                                    <td class="py-2 px-4 text-right">{{ Number(item.stock).toFixed(2) }}</td>
                                    <td class="py-2 px-4 text-right">{{ Number(item.stock_min).toFixed(2) }}</td>
                                    <td class="py-2 px-4 text-right font-semibold">{{ Number(item.suggested_qty).toFixed(2) }}</td>
                                    <td class="py-2 px-4 text-center print-only border border-gray-400">&nbsp;</td>
                                </tr>
                                <tr v-if="!items.length">
                                    <td colspan="6" class="py-8 text-center text-gray-500">
                                        ¡Todo en orden! No hay insumos por comprar.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
@media print {
    .no-print { display: none !important; }
    .print-only { display: table-cell !important; }
}
.print-only { display: none; }
</style>
