<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import DataTable from 'datatables.net-vue3';
    import DataTablesCore from 'datatables.net';
    import Keypad from '@/Components/Keypad.vue';
    import InputLabel from "@/Components/InputLabel.vue";
    import 'datatables.net-responsive';
    import '@/Components/vristo/datatables/datatables.css'
    import '@/Components/vristo/datatables/style.css'
    import 'datatables.net-buttons'; // Importa el plugin de botones
    import 'datatables.net-buttons-dt'; // Importa los estilos de los botones
    import es_PE from '@/Components/vristo/datatables/datatables-es.js';
    import { ref, onMounted } from 'vue';
    import DropdownExports from '@/Components/DropdownExports.vue';

    DataTable.use(DataTablesCore);

    const props = defineProps({
        arrivalSources: {
            type: Object,
            default: () => ({})
        },
    });

    const filters = ref({
        arrival_source: null,
    });

    const handleFilterChange = () => {
        refreshTable();
    };

    const columns = [
        {
            data: null,
            className: 'control',
            render: '#actions',
            orderable: false,
            searchable: false,
            responsivePriority: 2
        },
        { data: 'person.country.description', name: 'person.country.description', title: 'País', orderable: false, searchable: false},
        { data: 'person.ubigeo_description', name: 'person.ubigeo_description', title: 'Ciudad'},
        { data: 'person.full_name', name: 'person.full_name', title: 'Nombre completo' },
        { data: 'person.identity_document_type.description', name: 'person.identity_document_type.description', title: 'Tipo documento de identificación' , orderable: false, searchable: false},
        { data: 'person.number', name: 'person.number', title: 'Número' },
        { data: 'person.email', name: 'person.email', title: 'Correo Electrónico' },
        { data: 'person.telephone', name: 'person.telephone', title: 'Teléfono' },
        { data: 'person.birthdate', name: 'person.birthdate', title: 'Fecha nacimiento' },
        { data: 'arrival_source.name', name: 'arrival_source.name', title: 'Fuentes de llegada', orderable: false, searchable: false},
        { data: 'arrival_source_text', name: 'arrival_source_text', title: 'Descripción', orderable: false, searchable: false },
        {
            data: null,
            title: 'Curso gratuito',
            orderable: false,
            searchable: false,
            render: function (data, type, row) {
                if (row.registrations?.length) {
                    // Supongamos que cada registro tiene un campo course.name
                    const cursos = row.registrations.map(r => r.course?.description ?? 'Sin nombre');
                    return `<div class="text-sm/6 text-gray-900 dark:text-white font-semibold">
                            ${cursos.join('<br>')}
                        </div>`;
                }
                return `<span class="text-gray-500">—</span>`;
            }
        },
        {
            data: null,
            title: 'Cursos de interés',
            orderable: false,
            searchable: false,
            render: function (data, type, row) {
                if (row.courses_interest?.length) {
                    const intereses = row.courses_interest.map(ci => ci.course?.description ?? 'Sin nombre');
                    return `<div class="text-sm/6 text-gray-900 dark:text-white font-semibold">
                            ${intereses.join('<br>')}
                        </div>`;
                }
                return `<span class="text-gray-500">Sin intereses</span>`;
            }
        }
    ];

    const options = {
        serverSide: true,
        processing: true,
        responsive: true,
        language: es_PE,
        order: [[1, 'desc']], // ðŸ‘ˆ ordenar por la columna 1 (nombre)
        ajax: {
            url: route('crm_new_catchments_list'),
            data: function (d) {
                d.filters = filters.value;
            },
            type: 'post',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        }
    }

    const memberTable = ref(null);
    let instanceTable = null;

    onMounted(() => {
        instanceTable = memberTable.value?.dt;
    });

    const refreshTable = () => {
        // accede a la instancia del DataTable
        if (instanceTable) {
            instanceTable.ajax.reload(null, false);
        }
    };
</script>
<template>
    <AppLayout title="Contactos">
        <Navigation :routeModule="route('crm_dashboard')" :titleModule="'CRM'">
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Nuevas Captaciones</span>
            </li>
        </Navigation>
        <div class="mt-5">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <h2 class="text-xl">Lista de Captaciones </h2>
                <div class="flex sm:flex-row flex-col sm:items-center sm:gap-3 gap-4 w-full sm:w-auto">
                    <div class="flex gap-3">
                        <Keypad>
                            <template #botones>
                                <DropdownExports
                                    :showExcel="true"
                                    :statusRouteName="'crm_export_status'"
                                    :actionUrl="route('crm_new_catchments_export')"
                                    :data="filters"
                                />
                            </template>
                        </Keypad>

                    </div>
                </div>
            </div>
            <div class="panel pb-1.5 mt-6">
                <div class="grid grid-cols-6 gap-6">
                    <div>
                        <InputLabel value="Fuentes de llegada" />
                        <select @change="handleFilterChange" v-model="filters.arrival_source" class="form-select">
                            <option :value="null">Todos</option>
                            <template v-for="item in arrivalSources">
                                <option :value="item.id">{{ item.name }}</option>
                            </template>
                        </select>
                    </div>


                </div>
                <DataTable ref="memberTable" :options="options" :columns="columns">
                    <template #actions="props">
                        <svg class="w-5 h-5 text-info"  xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
                            <path fill="currentColor" d="M160 96C124.7 96 96 124.7 96 160L96 480C96 515.3 124.7 544 160 544L480 544C515.3 544 544 515.3 544 480L544 160C544 124.7 515.3 96 480 96L160 96zM296 408L296 344L232 344C218.7 344 208 333.3 208 320C208 306.7 218.7 296 232 296L296 296L296 232C296 218.7 306.7 208 320 208C333.3 208 344 218.7 344 232L344 296L408 296C421.3 296 432 306.7 432 320C432 333.3 421.3 344 408 344L344 344L344 408C344 421.3 333.3 432 320 432C306.7 432 296 421.3 296 408z"/>
                        </svg>
                    </template>
                </DataTable>
            </div>
        </div>
    </AppLayout>
</template>
