<script setup>
import AppLayout from "@/Layouts/Vristo/AppLayout.vue";
import { ref, onMounted } from "vue";
import { Link, router } from '@inertiajs/vue3';
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
import Swal2 from "sweetalert2";

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

    const deleteAttention = (attention) => {
        const swalConfirm = Swal2.mixin({
            customClass: {
                popup: "sweet-alerts",
                confirmButton: "btn btn-secondary",
                cancelButton: "btn btn-dark ltr:mr-3 rtl:ml-3",
            },
            buttonsStyling: false,
        });
        swalConfirm.fire({
            title: "¿Estas seguro?",
            text: "¡No podrás revertir esto!",
            icon: "question",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "¡Sí, Eliminar!",
            cancelButtonText: "Cancelar",
            showLoaderOnConfirm: true,
            reverseButtons: true,
            padding: "2em",
            preConfirm: () => {
                return axios.delete(route("odontology_attention_destroy", attention.id)).then((res) => {
                    if (!res.data.success) {
                        Swal2.showValidationMessage(res.data.message);
                    }
                    return res;
                });
            },
            allowOutsideClick: () => !Swal2.isLoading(),
        }).then((result) => {
            if (result.isConfirmed) {
                showMessage("Se Eliminó correctamente.");
                refreshTable();
            }
        });
    };

    const showMessage = (msg = "", type = "success") => {
        const toast = Swal2.mixin({
            toast: true,
            position: "top",
            showConfirmButton: false,
            timer: 3000,
            customClass: { container: "toast" },
        });
        toast.fire({
            icon: type,
            title: msg,
            padding: "10px 20px",
        });
    };

    const odontologyTable = ref(null);

    const refreshTable = () => {
        const dataTableInstance = odontologyTable.value?.dt; // accede a la instancia del DataTable
        if (dataTableInstance) {
            dataTableInstance.ajax.reload();
        }
    };
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
                <DataTable ref="odontologyTable" :options="options" :ajax="route('odontology_attention_table')" :columns="columns">
                    <template #action="props">
                        <div class="flex gap-1 items-center justify-center">
                            <Link :href="route('odontology_attention_edit',props.rowData.id)"
                                v-tippy="{ content: 'Editar', placement: 'bottom' }"
                                type="button" class="btn btn-sm btn-outline-primary" @click="editAttention(props.rowData.id)">
                                <font-awesome-icon  :icon="faPencil"  />
                            </Link>
                            <button v-if="props.rowData.signed_accepted" v-tippy="{ content: 'Eliminar', placement: 'bottom' }"  type="button" class="btn btn-sm btn-outline-danger" @click="showMessage('No se puede eliminar, ya a sido aceptada','error')">
                                <font-awesome-icon :icon="faTrash"  />
                            </button>
                            <button v-else v-tippy="{ content: 'Eliminar', placement: 'bottom' }"  type="button" class="btn btn-sm btn-outline-danger" @click="deleteAttention(props.rowData)">
                                <font-awesome-icon :icon="faTrash"  />
                            </button>
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
