<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import Keypad from '@/Components/Keypad.vue';
import Pagination from '@/Components/Pagination.vue';
import Swal2 from 'sweetalert2';
import { Link, router, useForm } from '@inertiajs/vue3';
import { Space, Tooltip } from 'ant-design-vue';
import { faPencilAlt, faTrashAlt } from '@fortawesome/free-solid-svg-icons';

const props = defineProps({
    supplies: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
});

const form = useForm({
    search: props.filters.search || '',
    stock_filter: props.filters.stock_filter || '',
});

const stockBadge = (supply) => {
    const stock = Number(supply.stock);
    if (stock <= 0) return { label: 'Sin stock', class: 'bg-red-100 text-red-800 border border-red-400' };
    if (stock <= Number(supply.stock_min)) return { label: 'Por agotarse', class: 'bg-amber-100 text-amber-800 border border-amber-400' };
    return { label: 'OK', class: 'bg-green-100 text-green-800 border border-green-400' };
};

const destroySupply = (id) => {
    Swal2.fire({
        title: '¿Estás seguro?',
        text: '¡No podrás revertir esto!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: '¡Sí, Eliminar!',
        cancelButtonText: 'Cancelar',
        showLoaderOnConfirm: true,
        preConfirm: () => axios.delete(route('res_supplies_destroy', id)).then((res) => {
            if (!res.data.success) Swal2.showValidationMessage(res.data.message);
            return res;
        }),
        allowOutsideClick: () => !Swal2.isLoading(),
    }).then((result) => {
        if (result.isConfirmed) {
            Swal2.fire('Enhorabuena', 'Insumo eliminado correctamente', 'success');
            router.visit(route('res_supplies_list'), { replace: true });
        }
    });
};
</script>

<template>
    <AppLayout title="Insumos">
        <Navigation
            :routeModule="route('res_dashboard')"
            titleModule="Restaurante"
            :data="[{ title: 'Insumos' }]"
        />
        <div class="pt-5 space-y-8">
            <div class="flex flex-col gap-10">
                <div class="rounded-sm border border-stroke bg-white shadow-default dark:border-strokedark dark:bg-boxdark">
                    <div class="w-full p-4 border-b border-gray-200 bg-gray-50 dark:border-gray-600 dark:bg-gray-700">
                        <div class="grid grid-cols-3 gap-4">
                            <div class="col-span-3 sm:col-span-1">
                                <form @submit.prevent="form.get(route('res_supplies_list'))">
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                            <svg class="w-5 h-5 text-gray-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" /></svg>
                                        </div>
                                        <input
                                            v-model="form.search"
                                            type="text"
                                            class="block p-2 pl-10 text-sm text-gray-900 border border-gray-300 rounded-lg w-full sm:w-80 bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                            placeholder="Buscar insumo..."
                                        />
                                    </div>
                                </form>
                            </div>
                            <div class="col-span-3 sm:col-span-1">
                                <select
                                    v-model="form.stock_filter"
                                    class="block p-2 text-sm border border-gray-300 rounded-lg w-full sm:w-56 bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                    @change="form.get(route('res_supplies_list'))"
                                >
                                    <option value="">Todos los estados</option>
                                    <option value="ok">Stock OK</option>
                                    <option value="low">Por agotarse</option>
                                    <option value="out">Sin stock</option>
                                </select>
                            </div>
                            <div class="col-span-3 sm:col-span-1">
                                <Keypad>
                                    <template #botones>
                                        <Link
                                            v-can="'res_insumos_nuevo'"
                                            :href="route('res_supplies_create')"
                                            class="inline-block px-6 py-2.5 bg-blue-900 text-white font-medium text-xs uppercase rounded shadow-md hover:bg-blue-700 transition"
                                        >
                                            Nuevo
                                        </Link>
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
                    <div class="max-w-full overflow-x-auto">
                        <table class="w-full table-auto">
                            <thead class="border-b border-stroke">
                                <tr class="bg-gray-50 text-left dark:bg-meta-4">
                                    <th class="py-2 px-4 text-center font-medium text-black dark:text-white">Acciones</th>
                                    <th class="py-2 px-4 font-medium text-black dark:text-white">Insumo</th>
                                    <th class="py-2 px-4 font-medium text-black dark:text-white">Unidad</th>
                                    <th class="py-2 px-4 text-right font-medium text-black dark:text-white">Stock</th>
                                    <th class="py-2 px-4 text-right font-medium text-black dark:text-white">Mínimo</th>
                                    <th class="py-2 px-4 text-center font-medium text-black dark:text-white">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="supply in supplies.data" :key="supply.id" class="border-b border-stroke">
                                    <td class="text-center py-2">
                                        <Space>
                                            <div v-can="'res_insumos_editar'">
                                                <Tooltip placement="top" title="Editar">
                                                    <Link
                                                        :href="route('res_supplies_edit', supply.id)"
                                                        class="text-white bg-blue-700 hover:bg-blue-800 font-medium rounded-full text-sm p-2.5 inline-flex items-center"
                                                    >
                                                        <font-awesome-icon :icon="faPencilAlt" />
                                                    </Link>
                                                </Tooltip>
                                            </div>
                                            <div v-can="'res_insumos_editar'">
                                                <Tooltip placement="top" title="Eliminar">
                                                    <button
                                                        type="button"
                                                        class="text-white bg-red-700 hover:bg-red-800 font-medium rounded-full text-sm p-2.5 inline-flex items-center"
                                                        @click="destroySupply(supply.id)"
                                                    >
                                                        <font-awesome-icon :icon="faTrashAlt" />
                                                    </button>
                                                </Tooltip>
                                            </div>
                                        </Space>
                                    </td>
                                    <td class="py-2 px-2 font-medium">{{ supply.name }}</td>
                                    <td class="py-2 px-2">{{ supply.unit }}</td>
                                    <td class="py-2 px-2 text-right">{{ Number(supply.stock).toFixed(2) }}</td>
                                    <td class="py-2 px-2 text-right">{{ Number(supply.stock_min).toFixed(2) }}</td>
                                    <td class="text-center py-2 px-2">
                                        <span class="text-xs font-medium px-2.5 py-0.5 rounded" :class="stockBadge(supply).class">
                                            {{ stockBadge(supply).label }}
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="!supplies.data?.length">
                                    <td colspan="6" class="py-8 text-center text-gray-500">No hay insumos registrados</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <Pagination :data="supplies" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
