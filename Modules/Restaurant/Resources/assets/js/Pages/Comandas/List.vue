<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
     import Keypad from '@/Components/Keypad.vue';
    import Pagination from '@/Components/Pagination.vue';
    import { Image } from 'ant-design-vue';
    import Swal2 from "sweetalert2";
    import { Link, router, useForm } from '@inertiajs/vue3';
    import { faPencilAlt, faCheck, faTrashAlt, faBook } from "@fortawesome/free-solid-svg-icons";

    const props = defineProps({
        comandas: {
            type: Object,
            default: () => ({}),
        },
        filters: {
            type: Object,
            default: () => ({}),
        },
    });

    const form = useForm({
        search: props.filters.search,
    });

    const destroyComanda = (id) => {
        Swal2.fire({
            title: '¿Estas seguro?',
            text: "¡No podrás revertir esto!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: '¡Sí, Eliminar!',
            cancelButtonText: 'Cancelar',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return axios.delete(route('res_comandas_destroy', id)).then((res) => {
                    if (!res.data.success) {
                        Swal2.showValidationMessage(res.data.message)
                    }
                    return res
                });
            },
            allowOutsideClick: () => !Swal2.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                Swal2.fire({
                    title: 'Enhorabuena',
                    text: 'Se Eliminó correctamente',
                    icon: 'success',
                });
                router.visit(route('res_comandas_list'), { replace: true, method: 'get' });
            }
        });
    }

    const xhttp =  assetUrl;
</script>

<template>
    <AppLayout title="Comandas">
        <Navigation
            :routeModule="route('res_dashboard')"
            titleModule="Restaurante"
            :data="[{ title: 'Comandas' }]"
        />
        <div class="pt-5 space-y-8">
            <div class="flex flex-col gap-10">
                <!-- ====== Table One Start -->
                <div class="rounded-sm border border-stroke bg-white shadow-default dark:border-strokedark dark:bg-boxdark">
                    <div class="w-full p-4 border-b border-gray-200 bg-gray-50 dark:border-gray-600 dark:bg-gray-700">
                        <div class="grid grid-cols-3">
                            <div class="col-span-3 sm:col-span-1">
                                <form id="form-search-items" @submit.prevent="form.get(route('res_comandas_list'))">
                                    <label for="table-search" class="sr-only">Search</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                            <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path></svg>
                                        </div>
                                        <input v-model="form.search" type="text" id="table-search-users" class="block p-2 pl-10 text-sm text-gray-900 border border-gray-300 rounded-lg w-80 bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="Buscar por Descripción">
                                    </div>
                                </form>
                            </div>
                            <div class="col-span-3 sm:col-span-2">
                                <Keypad>
                                    <template #botones>
                                        <Link v-can="'res_comandas_nuevo'" :href="route('res_comandas_create')" class="flex items-center justify-center inline-block px-6 py-2.5 bg-blue-900 text-white font-medium text-xs leading-tight uppercase rounded shadow-md hover:bg-blue-700 hover:shadow-lg focus:bg-blue-700 focus:shadow-lg focus:outline-none focus:ring-0 active:bg-blue-800 active:shadow-lg transition duration-150 ease-in-out">
                                            Nuevo
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
                                    <th  class="py-2 px-4 text-center font-medium text-black dark:text-white">
                                        Acciones
                                    </th>
                                    <th class="py-2 px-4 font-medium text-black dark:text-white">
                                        Image
                                    </th>
                                    <th class="py-2 px-4 font-medium text-black dark:text-white">
                                        Nombre
                                    </th>
                                    <th class="py-2 px-4 font-medium text-black dark:text-white">
                                        Categoría
                                    </th>
                                    <th class="py-2 px-4 font-medium text-black dark:text-white">
                                        Presentación
                                    </th>
                                    <th class="py-2 px-4 font-medium text-black dark:text-white">
                                        Precio
                                    </th>
                                    <th class="py-2 px-4 font-medium text-black dark:text-white">
                                        Estado
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-for="(comanda, index) in comandas.data" :key="comanda.id">
                                    <tr class="border-b border-stroke">
                                        <td class="text-center py-2 dark:border-strokedark">
                                            <Link v-can="'res_comandas_editar'" :href="route('res_comandas_edit',comanda.id)" class="mr-1 text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-full text-sm p-2.5 text-center inline-flex items-center mr-2 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                                                <font-awesome-icon :icon="faPencilAlt" />
                                            </Link>
                                            <Link v-can="'res_recetas'" :href="route('res_comandas_recipe', comanda.id)" class="mr-1 text-white bg-emerald-700 hover:bg-emerald-800 focus:ring-4 focus:outline-none focus:ring-emerald-300 font-medium rounded-full text-sm p-2.5 text-center inline-flex items-center mr-2" title="Receta">
                                                <font-awesome-icon :icon="faBook" />
                                            </Link>
                                            <button v-can="'res_comandas_eliminar'" @click="destroyComanda(comanda.id)" type="button" class="mr-1 text-white bg-red-700 hover:bg-red-800 focus:ring-4 focus:outline-none focus:ring-red-300 font-medium rounded-full text-sm p-2.5 text-center inline-flex items-center mr-2 dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-800">
                                                <font-awesome-icon :icon="faTrashAlt" />
                                            </button>
                                        </td>
                                        <td class="py-2 px-2 dark:border-strokedark">
                                            <Image
                                                :width="70"
                                                :src="xhttp + 'storage/' + comanda.image"
                                            />
                                        </td>
                                        <td class="py-2 px-2 dark:border-strokedark">
                                            {{ comanda.name }}
                                        </td>
                                        <td class="py-2 px-2 dark:border-strokedark">
                                            {{ comanda.category.description }}
                                        </td>
                                        <td class="py-2 px-2 dark:border-strokedark">
                                            {{ comanda.presentation.description }}
                                        </td>
                                        <td class="py-2 px-2 dark:border-strokedark">
                                            {{ comanda.price }}
                                        </td>
                                        <td class="text-center py-2 px-2 dark:border-strokedark">
                                            <span v-if="comanda.status" class="bg-blue-100 text-blue-800 text-xs font-medium mr-2 px-2.5 py-0.5 rounded dark:bg-gray-700 dark:text-blue-400 border border-blue-400">Activo</span>
                                            <span v-else class="bg-red-100 text-red-800 text-xs font-medium mr-2 px-2.5 py-0.5 rounded dark:bg-gray-700 dark:text-red-400 border border-red-400">Inactivo</span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <Pagination :data="comandas" />
                </div>
            </div>
        </div>
        
    </AppLayout>
</template>
