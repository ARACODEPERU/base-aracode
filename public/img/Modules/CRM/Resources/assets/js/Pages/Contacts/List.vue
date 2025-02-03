<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import IconChatDot from '@/Components/vristo/icon/icon-chat-dots.vue';
import Keypad from '@/Components/Keypad.vue';
import DataTable from 'datatables.net-vue3';
import DataTablesCore from 'datatables.net';
import 'datatables.net-responsive';
import '@/Components/vristo/datatables/datatables.css'
import '@/Components/vristo/datatables/style.css'
import 'datatables.net-buttons'
import 'datatables.net-buttons/js/buttons.html5';
import es_PE from '@/Components/vristo/datatables/datatables-es.js'

import Swal2 from "sweetalert2";
import { Link, router } from '@inertiajs/vue3';
import { faComments, faPerson, faPersonDress, faTrash } from "@fortawesome/free-solid-svg-icons";

DataTable.use(DataTablesCore);

const columns = [
    { data: null, render: '#action', title: 'Acción' },
    { data: null, render: '#peremail', title: 'Email' },
    { data: null, render: '#perinformation', title: 'Nombre' },
    { data: 'number', title: 'Número Identificación' },
    { data: 'birthdate', title: 'Fecha nacimiento' },
    { data: 'telephone', title: 'Teléfono' },
    { data: null, render: '#pergender', title: 'Sexo' },
];

const options = {
    responsive: true, 
    language: es_PE,
    order: [[2, 'desc']],
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

</script>
<template>
    <AppLayout title="Contactos">
        <Navigation :routeModule="route('crm_dashboard')" :titleModule="'CRM'">
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Contactos</span>
            </li>
        </Navigation>
        <div class="mt-5">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <h2 class="text-xl">Lista de contactos </h2>
                <div class="flex sm:flex-row flex-col sm:items-center sm:gap-3 gap-4 w-full sm:w-auto">
                    <div class="flex gap-3">
                        <Keypad>
                            <template #botones>
                                <Link v-can="'crm_envio_correo_masivo'" :href="route('crm_send_mass_mailing')" class="inline-block px-6 py-2.5 bg-red-600 text-white font-medium text-xs leading-tight uppercase rounded shadow-md hover:bg-red-700 hover:shadow-lg focus:bg-red-700 focus:shadow-lg focus:outline-none focus:ring-0 active:bg-red-800 active:shadow-lg transition duration-150 ease-in-out">Enviar Correo Masivo</Link>
                            </template>
                        </Keypad>

                    </div>
                </div>
            </div>
            <div class="panel pb-1.5 mt-6">
                <div class="table-responsive">
                    <DataTable :ajax="route('crm_contacts_list_data')" :columns="columns" :options="options">
                        <template #action="props">
                            <div class="flex gap-1 items-center justify-center">

                                <button v-tippy:bottom type="button" class="btn btn-sm btn-outline-info" @click="destroyItem(props.rowData.id)">
                                    <font-awesome-icon :icon="faComments" />
                                </button>
                                <tippy target="bottom" placement="bottom">Enviar Mensaje</tippy>
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
                                <div>{{ props.rowData.full_name }}</div>
                            </div>
                        </template>
                        <template #peremail="props">
                            <Link href="" class="py-3 px-4 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent text-blue-600 hover:text-blue-800 focus:outline-none focus:text-blue-800 disabled:opacity-50 disabled:pointer-events-none dark:text-blue-500 dark:hover:text-blue-400 dark:focus:text-blue-400">
                                {{ props.rowData.email }}
                            </Link>
                        </template>
                        <template #pergender="props">
                            <div class="text-center" >
                                <template v-if="props.rowData.gender == 'M'">
                                    <font-awesome-icon v-tippy:left :icon="faPerson" class="w-6 h-6 text-success" /> 
                                    <tippy target="left" placement="left">Masculino</tippy>
                                </template> 
                                <template v-else>
                                    <font-awesome-icon v-tippy:left :icon="faPersonDress" class="w-6 h-6 text-primary" /> 
                                    <tippy target="left" placement="left">Femenino</tippy>
                                </template> 
                            </div>
                        </template>
                    </DataTable>
                </div>
            </div>
        </div>
        
    </AppLayout>
</template>
