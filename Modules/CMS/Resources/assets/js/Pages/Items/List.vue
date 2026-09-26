<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import IconPlus from '@/Components/vristo/icon/icon-plus.vue';
    import DataTable from 'datatables.net-vue3';
    import DataTablesCore from 'datatables.net';
    import 'datatables.net-responsive';
    import '@/Components/vristo/datatables/datatables.css'
    import '@/Components/vristo/datatables/style.css'
    import es_PE from '@/Components/vristo/datatables/datatables-es.js'

    DataTable.use(DataTablesCore);

    import Swal2 from "sweetalert2";
    import { Link, router } from '@inertiajs/vue3';
    import { faPencilAlt, faTrashAlt } from "@fortawesome/free-solid-svg-icons";

    const destroyItem = (id) => {
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
            padding: '2em',
            customClass: 'sweet-alerts',
            preConfirm: () => {
                return axios.delete(route('cms_items_destroy', id)).then((res) => {
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
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
                router.visit(route('cms_items_list'), { replace: false, method: 'get' });
            }
        });
    }

    // El contenido viene escapado desde el servidor, solo se recorta para la vista.
    const truncateContent = (data, type) => {
        if (!data) return '';
        if (type !== 'display') return data;
        return data.length > 80 ? data.substring(0, 80) + '…' : data;
    };

    const columns = [
        {
            data: null,
            render: '#action',
            title: 'Acciones',
            className: 'text-center',
            orderable: false,
            searchable: false,
        },
        { data: 'type', title: 'Tipo' },
        { data: 'description', title: 'Descripción' },
        { data: 'content', title: 'Contenido', render: truncateContent },
        { data: 'position', title: 'Posición', className: 'text-center' },
    ];

    const options = {
        responsive: true,
        language: {
            ...es_PE,
            paginate: { first: 'Primero', last: 'Último', next: 'Siguiente', previous: 'Anterior' },
        },
        order: [[4, 'asc']],
    };
</script>

<template>
    <AppLayout title="Items">
        <Navigation :routeModule="route('cms_dashboard')" :titleModule="'CMS'" :data="[{ title: 'Items' }]" />
        <div class="mt-5">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <h2 class="text-xl">Items</h2>
                <div class="flex sm:flex-row flex-col sm:items-center sm:gap-3 gap-4 w-full sm:w-auto">
                    <Link v-can="'cms_items'" :href="route('cms_items_create')" type="button" class="btn btn-primary">
                        <icon-plus class="ltr:mr-2 rtl:ml-2" />
                        Nuevo
                    </Link>
                </div>
            </div>
            <div class="panel pb-1.5 mt-6">
                <DataTable :options="options" :ajax="route('cms_items_data')" :columns="columns">
                    <template #action="props">
                        <div class="flex gap-1 items-center justify-center">
                            <Link v-can="'cms_items'" :href="route('cms_items_edit', props.rowData.id)" type="button" class="btn btn-sm btn-outline-primary" title="Editar">
                                <font-awesome-icon :icon="faPencilAlt" class="m-0" />
                            </Link>
                            <button v-can="'cms_items'" type="button" class="btn btn-sm btn-outline-danger" title="Eliminar" @click="destroyItem(props.rowData.id)">
                                <font-awesome-icon :icon="faTrashAlt" />
                            </button>
                        </div>
                    </template>
                </DataTable>
            </div>
        </div>
    </AppLayout>
</template>
