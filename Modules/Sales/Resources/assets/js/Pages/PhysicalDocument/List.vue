<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import { faTimes, faCopy, faGears } from "@fortawesome/free-solid-svg-icons";
    import Pagination from '@/Components/Pagination.vue';
    import Keypad from '@/Components/Keypad.vue';
    import { ref } from 'vue';
    import swal from "sweetalert2";
    import { Link, router, useForm } from '@inertiajs/vue3';
    import { Badge } from 'flowbite-vue'
    import { ConfigProvider, Dropdown,Menu , MenuItem, Button } from 'ant-design-vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';

    const props = defineProps({
        sales: {
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

    const anularDocument = (id) => {
        swal.fire({
            title: '¿Estas seguro?',
            text: "¡No podrás revertir esto!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: '¡Sí, Anular!',
            cancelButtonText: 'Cancelar',
            showLoaderOnConfirm: true,
            padding: '2em',
            customClass: 'sweet-alerts',
            preConfirm: () => {
                return axios.delete(route('sale_physical_document_destroy', id)).then((res) => {
                    if (!res.data.success) {
                        swal.showValidationMessage(res.data.message)
                    }
                    return res
                });
            },
            allowOutsideClick: () => !swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                swal.fire({
                    title: 'Enhorabuena',
                    text: 'Se Eliminó correctamente',
                    icon: 'success',
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
                router.visit(route('sale_physical_document_list'), { replace: false, method: 'get' });
            }
        });
    }
</script>

<template>
    <AppLayout title="Ventas">
        <Navigation :routeModule="route('sales_dashboard')" :titleModule="'Ventas'">
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Documento Físico</span>
            </li>
        </Navigation>
        <div class="mt-5">
            <!-- ====== Table Section Start -->
            <div class="flex flex-col gap-10">
                <!-- ====== Table One Start -->
                <div class="panel p-0">
                    <div class="w-full p-4">
                        <div class="grid grid-cols-3">
                            <div class="col-span-3 sm:col-span-1">
                                <form @submit.prevent="form.get(route('sale_physical_document_list'))">
                                    <label for="table-search" class="sr-only">Search</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                            <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path></svg>
                                        </div>
                                        <input v-model="form.search" type="text" id="table-search-users" class="form-input placeholder:tracking-wider ltr:pl-12 rtl:pr-12 peer" placeholder="Buscar por cliente">
                                    </div>
                                </form>
                            </div>
                            <div class="col-span-3 sm:col-span-2">
                                <Keypad>
                                    <template #botones>
                                        <Link :href="route('sale_physical_document_create')" class="inline-block px-6 py-2.5 bg-blue-900 text-white font-medium text-xs leading-tight uppercase rounded shadow-md hover:bg-blue-700 hover:shadow-lg focus:bg-blue-700 focus:shadow-lg focus:outline-none focus:ring-0 active:bg-blue-800 active:shadow-lg transition duration-150 ease-in-out">Nuevo</Link>
                                    </template>
                                </Keypad>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <ConfigProvider>
                        <table class="w-full table-auto">
                            <thead>
                                <tr>
                                    <th>
                                        Acciones
                                    </th>
                                    <th>
                                        Documento
                                    </th>
                                    <th>
                                        Fecha
                                    </th>
                                    <th>
                                        Cliente
                                    </th>
                                    <th>
                                        Total
                                    </th>
                                    <th>
                                        Estado
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(sale, index) in sales.data" :key="sale.id" >
                                    <td>
                                        <Button v-can="'sale_documento_fisico_eliminar'" type="dashed" @click="anularDocument(sale.id)" >
                                            Anular
                                        </Button>
                                    </td>
                                    <td>
                                        {{ sale.serie + '-' + sale.corelative }}
                                    </td>
                                    <td>
                                        {{ sale.document_date }}
                                    </td>
                                    <td>
                                        {{ sale.client_rzn_social }}
                                    </td>
                                    <td>
                                        {{ sale.total }}
                                    </td>
                                    <td>

                                        <Badge v-if="sale.status == 'R'" type="yellow">Registrado</Badge>
                                        <Badge v-else="sale.status == 'A'" type="red">Anulado</Badge>
                                        <!-- <Badge v-else type="red">Anulado</Badge> -->

                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        </ConfigProvider>
                    </div>
                    <Pagination :data="sales" />
                </div>
            </div>
        </div>

    </AppLayout>
</template>
