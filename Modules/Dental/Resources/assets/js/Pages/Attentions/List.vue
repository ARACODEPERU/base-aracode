<script setup>
import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
import { ref, onMounted } from "vue";
import { Link } from '@inertiajs/vue3';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import Keypad from '@/Components/Keypad.vue';
import iconAward from '@/Components/vristo/icon/icon-award.vue';
import iconInfoHexagon from '@/Components/vristo/icon/icon-info-hexagon.vue';
import { faPerson, faPersonDress, faFile, faClock, faPencil, faTrash } from "@fortawesome/free-solid-svg-icons";
import DataTable from 'datatables.net-vue3';
import DataTablesCore from 'datatables.net';
import 'datatables.net-responsive';
import '@/Components/vristo/datatables/datatables.css'
import '@/Components/vristo/datatables/style.css'
import es_PE from '@/Components/vristo/datatables/datatables-es.js'

DataTable.use(DataTablesCore);

    const columns = [
        {
            data: null,
            render: '#action',
            title: 'Acciones'
        },
        { data: null, render: '#date_time_attention', title: 'Fecha Atencion' },
        { data: null, render: '#history', title: 'Num. Historia' },
        { data: null, render: '#patient', title: 'Paciente' },
        { data: 'age', title: 'Edad' },
        { data: null, render: '#status', title: 'Estado' },
    ];

    const options = { 
        responsive: true, 
        language: es_PE,
        order: [[3, 'desc']]
    }

    const formatDate = (dateString) => {
        const date = new Date(dateString);

        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0'); // Los meses empiezan en 0
        const year = date.getFullYear();

        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        const seconds = String(date.getSeconds()).padStart(2, '0');

        return `${day}/${month}/${year} ${hours}:${minutes}:${seconds}`;
    }

</script>
<template>
    <AppLayout title="Atencion">
        <Navigation :routeModule="route('dental_dashboard')" :titleModule="'Salud'">
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Odontología</span>
            </li>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Atencion</span>
            </li>
        </Navigation>
        <div class="mt-5">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <h2 class="text-xl">Lista de Atenciones </h2>
                <div class="flex sm:flex-row flex-col sm:items-center sm:gap-3 gap-4 w-full sm:w-auto">
                    <div class="flex gap-3">
                        <Keypad>
                            <template #botones>
                                <Link :href="route('odontology_attention_create')" class="inline-block px-6 py-2.5 bg-blue-900 text-white font-medium text-xs leading-tight uppercase rounded shadow-md hover:bg-blue-700 hover:shadow-lg focus:bg-blue-700 focus:shadow-lg focus:outline-none focus:ring-0 active:bg-blue-800 active:shadow-lg transition duration-150 ease-in-out">Nuevo</Link>
                            </template>
                        </Keypad>

                    </div>
                </div>
            </div>
            <div class="panel pb-1.5 mt-6">
                <DataTable :options="options" :ajax="route('odontology_attention_table')" :columns="columns">
                    <template #action="props">
                        <div class="flex gap-1 items-center justify-center">
                            <Link :href="route('odontology_attention_edit',props.rowData.id)" v-tippy:editar type="button" class="btn btn-sm btn-outline-primary" @click="editAttention(props.rowData.id)">
                                <font-awesome-icon  :icon="faPencil"  />
                            </Link>
                            <tippy target="editar" placement="bottom">Editar</tippy>
                            <button v-tippy:eliminar type="button" class="btn btn-sm btn-outline-danger" @click="deleteAttention(props.rowData.id)">
                                <font-awesome-icon :icon="faTrash"  />
                            </button>
                            <tippy target="eliminar" placement="bottom">Eliminar</tippy>
                        </div>
                    </template>
                    <template #date_time_attention="props">
                        {{ formatDate(props.rowData.date_time_attention) }}
                    </template>
                    <template #history="props">
                        {{ props.rowData.history.history_code }}
                    </template>
                    <template #patient="props">
                        {{ props.rowData.patient.person.full_name }}
                    </template>
                    <template #status="props">
                        <span v-if="props.rowData.signed_accepted" class="flex items-center text-base text-blue-700 dark:text-white">
                            <icon-award class="w-6 h-6 object-cover" />
                            <span class="ltr:ml-2 rtl:mr-2">Firmado</span>
                        </span>

                        <span v-else class="flex items-center text-base text-danger-700 dark:text-white">
                            <icon-info-hexagon class="w-6 h-6 object-cover" />
                            <span class="ltr:ml-2 rtl:mr-2">Pendiente</span>
                        </span>
                    </template>
                </DataTable>
            </div>
        </div>
    </AppLayout>
</template>