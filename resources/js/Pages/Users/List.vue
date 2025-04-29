<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import { useForm, Link } from '@inertiajs/vue3';
    import { faTrashAlt, faPencilAlt, faPrint, faWarehouse } from "@fortawesome/free-solid-svg-icons";
    import Pagination from '@/Components/Pagination.vue';
    import Keypad from '@/Components/Keypad.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import { ref, onMounted } from 'vue';
    import DataTable from 'datatables.net-vue3';
    import DataTablesCore from 'datatables.net';
    import 'datatables.net-responsive';
    import '@/Components/vristo/datatables/datatables.css'
    import '@/Components/vristo/datatables/style.css'
    import 'datatables.net-buttons'
    import 'datatables.net-buttons/js/buttons.html5';
    import es_PE from '@/Components/vristo/datatables/datatables-es.js'

    DataTable.use(DataTablesCore);

    const columns = [
        { data: null, render: '#action', title: 'Acción', className: 'text-center' },
        { data: null, render: '#userDescription', title: 'Nombres' },
        { data: 'email', title: 'Email' },
        {
            data: 'created_at',
            title: 'Fecha Registrado',
            render: function (data) {
                if (!data) return '';
                return new Date(data).toLocaleString('es-ES', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: false
                });
            }
        },
        { data: null, render: '#userStatus', title: 'Estado' },
    ];

    const options = {
        responsive: true,
        language: es_PE,
        order: [[3,'DESC']],
        paging: true
    }

    const usersTable = ref(null);

    const form = useForm({
        search: null,
    });

    const formDelete = useForm({});

    function destroy(id) {
        if (confirm("¿Estás seguro de que quieres eliminar?")) {
            formDelete.delete(route('users.destroy', id));
        }
    }

    const baseUrl = assetUrl;

    const getImage = (path) => {
        return baseUrl + 'storage/'+ path;
    }
</script>
<template>
    <AppLayout title="Usuarios">
        <Navigation >
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Configuraciones</span>
            </li>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Usuarios</span>
            </li>
        </Navigation>
        <div class="mt-5">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <h2 class="text-xl">Lista de Usuarios </h2>
                <div class="flex sm:flex-row flex-col sm:items-center sm:gap-3 gap-4 w-full sm:w-auto">
                    <div class="flex gap-3">
                        <Keypad>
                            <template #botones>
                                <Link :href="route('users.create')" class="inline-block px-6 py-2.5 bg-blue-900 text-white font-medium text-xs leading-tight uppercase rounded shadow-md hover:bg-blue-700 hover:shadow-lg focus:bg-blue-700 focus:shadow-lg focus:outline-none focus:ring-0 active:bg-blue-800 active:shadow-lg transition duration-150 ease-in-out">Nuevo</Link>
                            </template>
                        </Keypad>
                    </div>
                </div>
            </div>
            <div class="panel pb-1.5 mt-6">
                <DataTable ref="usersTable" :ajax="route('users-tables-list')" :columns="columns" :options="options">
                    <template #action="props">
                        <div class="flex gap-4 items-center justify-center">
                            <Link :href="route('users.edit', props.rowData.id)" class="btn btn-sm btn-outline-primary">
                                <font-awesome-icon :icon="faPencilAlt" />
                            </Link>
                            <button @click="destroy(props.rowData.id)" type="button" class="btn btn-sm btn-outline-danger" >
                                <font-awesome-icon :icon="faTrashAlt" />
                            </button>
                        </div>
                    </template>
                    <template #userDescription="props">
                        <div class="flex items-center">
                            <div class="ltr:mr-4 rtl:ml-4">
                                <img v-if="props.rowData.image" :src="getImage(props.rowData.image)" alt="" class="w-10 h-10 rounded" />
                                <img v-else :src="'https://ui-avatars.com/api/?name='+props.rowData.full_name+'&size=96&rounded=false'" alt="" class="w-10 h-10 rounded" />
                            </div>
                            <div class="flex-1">
                                <h4 class="font-semibold text-sm">{{ props.rowData.full_name ?? props.rowData.name }}</h4>
                                <p class="media-text">{{ props.rowData.number ?? 'FALTA DATOS' }}</p>
                            </div>
                        </div>
                    </template>
                    <template #userStatus="props">
                        <div class="flex items-center">
                            <span v-if="props.rowData.status" class="badge bg-success">Activo</span>
                            <span v-else class="badge bg-danger">Inactivo</span>
                        </div>
                    </template>
                </DataTable>
            </div>

        </div>
    </AppLayout>
</template>
