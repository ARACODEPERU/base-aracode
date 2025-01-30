<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import Keypad from '@/Components/Keypad.vue';
import DataTable from 'datatables.net-vue3';
import DataTablesCore from 'datatables.net';
import 'datatables.net-responsive';
import '@/Components/vristo/datatables/datatables.css'
import '@/Components/vristo/datatables/style.css'
import 'datatables.net-buttons'
import 'datatables.net-buttons/js/buttons.html5';
import es_PE from '@/Components/vristo/datatables/datatables-es.js'
import iconEdit from '@/Components/vristo/icon/icon-edit.vue';
import iconTrash from '@/Components/vristo/icon/icon-trash.vue';
import Swal2 from "sweetalert2";
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

DataTable.use(DataTablesCore);

const columns = [
    { data: null, render: '#action', title: 'Acción', className: 'text-center' },
    { data: null, render: '#perinformation', title: 'Nombre' },
    { data: 'number', title: 'RUC' },
    { data: 'telephone', title: 'Teléfono' },
];

const options = {
    responsive: true, 
    language: es_PE,
    order: [[3]],
    paging: true
}


const baseUrl = assetUrl;

const getImage = (path) => {
    return baseUrl + 'storage/'+ path;
}


const props = defineProps({
    P000009: {
        type: String,
        default: null
    }
});

const destroyCompanies = (id) => {
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
            return axios.delete(route('crm_companies_destroy', id)).then((res) => {
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
            }).then(() => {
                refreshTable();
            });
        }
    });
    
}

///refresar la tabla dinamica////
const empresasTable = ref(null);

const refreshTable = () => {
    const dataTableInstance = empresasTable.value?.dt; // accede a la instancia del DataTable
    if (dataTableInstance) {
        dataTableInstance.ajax.reload();
    }
};
</script>
<template>
    <AppLayout title="Empresas">
        <Navigation :routeModule="route('crm_dashboard')" :titleModule="'CRM'">
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Empresas</span>
            </li>
        </Navigation>
        <div class="mt-5">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <h2 class="text-xl">Lista de empresas </h2>
                <div class="flex sm:flex-row flex-col sm:items-center sm:gap-3 gap-4 w-full sm:w-auto">
                    <div class="flex gap-3">
                        <Keypad>
                            <template #botones>
                                <Link v-can="'crm_empresas_nuevo'" :href="route('crm_companies_create')" class="btn btn-primary uppercase text-xs px-4 py-2">Nuevo</Link>
                            </template>
                        </Keypad>

                    </div>
                </div>
            </div>
            <div class="panel pb-1.5 mt-6">
                <div class="table-responsive">
                    <DataTable ref="empresasTable" :ajax="route('crm_companies_list_pagination')" :columns="columns" :options="options">
                        <template #action="props">
                            <div class="flex gap-1 items-center justify-start">
                                <Link 
                                    :href="route('crm_companies_edit',props.rowData.id)"
                                    v-can="'crm_empresas_editar'" 
                                    v-tippy="{ content: 'Editar', placement: 'bottom'}" 
                                    type="button" 
                                    class="btn btn-sm btn-outline-warning"
                                >
                                    <icon-edit class="w-4 h-4" />
                                </Link>
                                <Link :href="route('crm_companies_employees',props.rowData.id)" v-can="'crm_empresas_empleados'" v-tippy="{ content: 'Ver Empleados', placement: 'bottom'}" type="button" class="btn btn-sm btn-outline-info">
                                    <svg class="w-4 h-4" fill="currentColor" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512">
                                        <path d="M144 0a80 80 0 1 1 0 160A80 80 0 1 1 144 0zM512 0a80 80 0 1 1 0 160A80 80 0 1 1 512 0zM0 298.7C0 239.8 47.8 192 106.7 192l42.7 0c15.9 0 31 3.5 44.6 9.7c-1.3 7.2-1.9 14.7-1.9 22.3c0 38.2 16.8 72.5 43.3 96c-.2 0-.4 0-.7 0L21.3 320C9.6 320 0 310.4 0 298.7zM405.3 320c-.2 0-.4 0-.7 0c26.6-23.5 43.3-57.8 43.3-96c0-7.6-.7-15-1.9-22.3c13.6-6.3 28.7-9.7 44.6-9.7l42.7 0C592.2 192 640 239.8 640 298.7c0 11.8-9.6 21.3-21.3 21.3l-213.3 0zM224 224a96 96 0 1 1 192 0 96 96 0 1 1 -192 0zM128 485.3C128 411.7 187.7 352 261.3 352l117.3 0C452.3 352 512 411.7 512 485.3c0 14.7-11.9 26.7-26.7 26.7l-330.7 0c-14.7 0-26.7-11.9-26.7-26.7z"/>
                                    </svg>
                                </Link>
                                <button 
                                    v-can="'crm_empresas_eliminar'"
                                    v-tippy="{ content: 'Eliminar', placement: 'bottom'}" 
                                    type="button" class="btn btn-sm btn-outline-danger"
                                    @click="destroyCompanies(props.rowData.id)"
                                >
                                    <icon-trash class="w-4 h-4" />
                                </button>
                            </div>
                        </template>
                        <template #perinformation="props">
                            <div class="flex items-center w-max hover:text-primary">
                                <div class="w-max">
                                    <img v-if="props.rowData.image"
                                        :src="getImage(props.rowData.image)"
                                        class="h-8 w-8 rounded-full object-cover ltr:mr-2 rtl:ml-2"
                                        alt="AV"
                                    />
                                    <img v-else :src="'https://ui-avatars.com/api/?name='+props.rowData.full_name+'&size=54&rounded=true'" class="h-8 w-8 rounded-full object-cover ltr:mr-2 rtl:ml-2" :alt="props.rowData.full_name"/>
                                </div>
                                <div>
                                    <h6 v-html="props.rowData.short_name"></h6>
                                    <p class="text-xs text-white-dark mt-1" v-html="props.rowData.full_name"></p>
                                </div>
                            </div>
                        </template>
                        <template #peremail="props">
                            <Link v-can="'crm_empresas_empleados'" href="" class="py-3 px-4 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent text-blue-600 hover:text-blue-800 focus:outline-none focus:text-blue-800 disabled:opacity-50 disabled:pointer-events-none dark:text-blue-500 dark:hover:text-blue-400 dark:focus:text-blue-400">
                                {{ props.rowData.email }}
                            </Link>
                        </template>
                    </DataTable>
                </div>
            </div>
        </div>
        
    </AppLayout>
</template>
