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
    import { faPencilAlt, faObjectGroup, faTrashAlt, faPlus } from "@fortawesome/free-solid-svg-icons";

    const destroySection = (id) => {
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
                return axios.delete(route('cms_section_destroy', id)).then((res) => {
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
                router.visit(route('cms_section_list'), { replace: true, method: 'get' });
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
        { data: 'description', title: 'Sección' },
        { data: 'component_id', title: 'Componente' },
    ];

    const options = {
        responsive: true,
        language: {
            ...es_PE,
            paginate: { first: 'Primero', last: 'Último', next: 'Siguiente', previous: 'Anterior' },
        },
        order: [[1, 'asc']],
    };
</script>

<template>
    <AppLayout title="Secciones">
        <Navigation :routeModule="route('cms_dashboard')" :titleModule="'CMS'" :data="[{ title: 'Secciones' }]" />
        <div class="mt-5">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <h2 class="text-xl">Secciones</h2>
                <div class="flex sm:flex-row flex-col sm:items-center sm:gap-3 gap-4 w-full sm:w-auto">
                    <Link v-can="'cms_seccion_nuevo'" :href="route('cms_section_create')" type="button" class="btn btn-primary">
                        <icon-plus class="ltr:mr-2 rtl:ml-2" />
                        Nuevo
                    </Link>
                </div>
            </div>
            <div class="panel pb-1.5 mt-6">
                <DataTable :options="options" :ajax="route('cms_sections_data')" :columns="columns">
                    <template #action="props">
                        <div class="flex gap-1 items-center justify-center">
                            <Link v-can="'cms_seccion_editar'" :href="route('cms_section_edit', props.rowData.id)" type="button" class="btn btn-sm btn-outline-primary" title="Editar">
                                <font-awesome-icon :icon="faPencilAlt" />
                            </Link>
                            <Link v-can="'cms_seccion_items'" :href="route('cms_section_items', props.rowData.id)" type="button" class="btn btn-sm btn-outline-success" title="Agregar items a esta sección">
                                <font-awesome-icon :icon="faPlus" />
                            </Link>
                            <Link v-can="'cms_seccion_grupos'" :href="route('cms_section_group_items', props.rowData.id)" type="button" class="btn btn-sm btn-outline-warning" title="Crear grupo de items">
                                <font-awesome-icon :icon="faObjectGroup" />
                            </Link>
                            <button v-can="'cms_seccion_eliminar'" type="button" class="btn btn-sm btn-outline-danger" title="Eliminar" @click="destroySection(props.rowData.id)">
                                <font-awesome-icon :icon="faTrashAlt" />
                            </button>
                        </div>
                    </template>
                </DataTable>
            </div>
        </div>
    </AppLayout>
</template>
