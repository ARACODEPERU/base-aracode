<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import { useForm } from '@inertiajs/vue3';
    import Keypad from '@/Components/Keypad.vue';
    import Pagination from '@/Components/Pagination.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';

    import Swal2 from "sweetalert2";
    import { Link, router } from '@inertiajs/vue3';
    import { faPencilAlt, faCheck, faTrashAlt, faLayerGroup } from "@fortawesome/free-solid-svg-icons";

    const props = defineProps({
        pages: {
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

    const deleteForm = useForm({});

    const destroyPages = (id) => {
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
                return axios.delete(route('cms_pages_destroy', id)).then((res) => {
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
                router.visit(route('cms_pages_list'), { replace: true, method: 'get' });
            }
        });
    }

</script>

<template>
    <AppLayout title="Resumen">
        <Navigation :routeModule="route('cms_dashboard')" :titleModule="'CMS'">
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Paginas</span>
            </li>
        </Navigation>
        <div class="mt-5">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div class="col-span-3 sm:col-span-1">
                    <form @submit.prevent="form.get(route('saledocuments_list'))">
                        <label for="table-search-users" class="sr-only">Search</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path></svg>
                            </div>
                            <input v-model="form.search" type="text" id="table-search-users" class="form-input pl-10" placeholder="Buscar por cliente">
                        </div>
                    </form>
                </div>
                <div class="col-span-3 sm:col-span-2">
                    <Keypad>
                        <template #botones>
                            <Link v-can="'cms_pagina_nuevo'" :href="route('cms_pages_create')" class="flex items-center justify-center inline-block px-6 py-2.5 bg-blue-900 text-white font-medium text-xs leading-tight uppercase rounded shadow-md hover:bg-blue-700 hover:shadow-lg focus:bg-blue-700 focus:shadow-lg focus:outline-none focus:ring-0 active:bg-blue-800 active:shadow-lg transition duration-150 ease-in-out">
                                Nuevo
                            </Link>
                        </template>
                    </Keypad>
                </div>
            </div>
        </div>
        <div class="panel p-0 mt-6">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>
                                Acciones
                            </th>
                            <th>
                                Icono
                            </th>
                            <th>
                                Descripción
                            </th>
                            <th>
                                Ruta
                            </th>
                            <th>
                                Es principal
                            </th>
                            <th>
                                Estado
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-for="(item, index) in pages.data" :key="item.id">
                            <tr>
                                <td>
                                    <Link v-can="'cms_pagina_editar'" :href="route('cms_pages_edit',item.id)" class="mr-1 text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-full text-sm p-2.5 text-center inline-flex items-center mr-2 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                                        <font-awesome-icon :icon="faPencilAlt" />
                                    </Link>
                                    <Link v-can="'cms_pagina_seccion'" :href="route('cms_pages_section_list',item.id)" class="mr-1 text-white bg-gray-700 hover:bg-gray-800 focus:ring-4 focus:outline-none focus:ring-gray-300 font-medium rounded-full text-sm p-2.5 text-center inline-flex items-center mr-2 dark:bg-gray-600 dark:hover:bg-gray-700 dark:focus:ring-gray-800">
                                        <font-awesome-icon :icon="faLayerGroup" />
                                    </Link>
                                    <button v-can="'cms_pagina_eliminar'" @click="destroyPages(item.id)" type="button" class="mr-1 text-white bg-red-700 hover:bg-red-800 focus:ring-4 focus:outline-none focus:ring-red-300 font-medium rounded-full text-sm p-2.5 text-center inline-flex items-center mr-2 dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-800">
                                        <font-awesome-icon :icon="faTrashAlt" />
                                    </button>
                                </td>
                                <td>
                                    {{ item.icon }}
                                </td>
                                <td>
                                    {{ item.description }}
                                </td>
                                <td>
                                    {{ item.route }}
                                </td>
                                <td>
                                    <font-awesome-icon v-if="item.main" :icon="faCheck" class="ml-1" />
                                </td>
                                <td>
                                    <span v-if="item.status" class="bg-blue-100 text-blue-800 text-xs font-medium mr-2 px-2.5 py-0.5 rounded dark:bg-gray-700 dark:text-blue-400 border border-blue-400">Activo</span>
                                    <span v-else class="bg-red-100 text-red-800 text-xs font-medium mr-2 px-2.5 py-0.5 rounded dark:bg-gray-700 dark:text-red-400 border border-red-400">Inactivo</span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
                <Pagination :data="pages" />
            </div>
        </div>
    </AppLayout>
</template>
