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
    import { faPencilAlt, faCheck, faTrashAlt, faLayerGroup } from "@fortawesome/free-solid-svg-icons";

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
            padding: '2em',
            customClass: 'sweet-alerts',
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
                    padding: '2em',
                    customClass: 'sweet-alerts',
                });
                router.visit(route('cms_pages_list'), { replace: true, method: 'get' });
            }
        });
    }

    const columns = [
        {
            data: null,
            render: '#action',
            title: 'Acciones',
            className: 'text-center',
            orderable: false,
            searchable: false,
        },
        { data: 'icon', title: 'Icono' },
        { data: 'description', title: 'Descripción' },
        { data: 'route', title: 'Ruta' },
        { data: null, render: '#rowMain', title: 'Principal', className: 'text-center', orderable: false, searchable: false },
        { data: null, render: '#rowStatus', title: 'Estado', orderable: false, searchable: false },
    ];

    const options = {
        responsive: true,
        language: {
            ...es_PE,
            paginate: { first: 'Primero', last: 'Último', next: 'Siguiente', previous: 'Anterior' },
        },
        order: [[2, 'asc']],
    };
</script>

<template>
    <AppLayout title="Páginas">
        <Navigation :routeModule="route('cms_dashboard')" :titleModule="'CMS'" :data="[{ title: 'Páginas' }]" />
        <div class="mt-5">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <h2 class="text-xl">Páginas</h2>
                <div class="flex sm:flex-row flex-col sm:items-center sm:gap-3 gap-4 w-full sm:w-auto">
                    <Link v-can="'cms_pagina_nuevo'" :href="route('cms_pages_create')" type="button" class="btn btn-primary">
                        <icon-plus class="ltr:mr-2 rtl:ml-2" />
                        Nuevo
                    </Link>
                </div>
            </div>
            <div class="panel pb-1.5 mt-6">
                <DataTable :options="options" :ajax="route('cms_pages_data')" :columns="columns">
                    <template #action="props">
                        <div class="flex gap-1 items-center justify-center">
                            <Link v-can="'cms_pagina_editar'" :href="route('cms_pages_edit', props.rowData.id)" type="button" class="btn btn-sm btn-outline-primary" title="Editar">
                                <font-awesome-icon :icon="faPencilAlt" />
                            </Link>
                            <Link v-can="'cms_pagina_seccion'" :href="route('cms_pages_section_list', props.rowData.id)" type="button" class="btn btn-sm btn-outline-success" title="Secciones de la página">
                                <font-awesome-icon :icon="faLayerGroup" />
                            </Link>
                            <button v-can="'cms_pagina_eliminar'" type="button" class="btn btn-sm btn-outline-danger" title="Eliminar" @click="destroyPages(props.rowData.id)">
                                <font-awesome-icon :icon="faTrashAlt" />
                            </button>
                        </div>
                    </template>
                    <template #rowMain="props">
                        <font-awesome-icon v-if="props.rowData.main" :icon="faCheck" class="text-success" />
                        <span v-else class="text-gray-400">—</span>
                    </template>
                    <template #rowStatus="props">
                        <span v-if="props.rowData.status" class="badge bg-success">Activo</span>
                        <span v-else class="badge bg-danger">Inactivo</span>
                    </template>
                </DataTable>
            </div>
        </div>
    </AppLayout>
</template>
