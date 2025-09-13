<script setup>
    import { useForm, Link, router } from "@inertiajs/vue3"
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
    import es_PE from '@/Components/vristo/datatables/datatables-es.js'
    import { ref, onMounted, computed } from 'vue';
    import Multiselect from '@suadelabs/vue3-multiselect';
    import '@suadelabs/vue3-multiselect/dist/vue3-multiselect.css';
    import { faPencil } from "@fortawesome/free-solid-svg-icons";

    DataTable.use(DataTablesCore);

    const props = defineProps({
        sedes: {
            type: Object,
            default: () => ({})
        },
        identityDocumentTypes: {
            type: Object,
            default: () => ({}),
        },
        ubigeo: {
            type: Object,
            default: () => ({})
        },
        schedules: {
            type: Object,
            default: () => ({})
        },
        evangelizations: {
            type: Object,
            default: () => ({})
        },
        memberTypes: {
            type: Object,
            default: () => ({})
        }
    });

    const filters = ref({
        nivel: null,
        sede: null,
        ubigeo: null,
        evangelization: null
    });

    const columns = [
        {
            data: null,
            render: '#action',
            title: 'Acciones',
            orderable: false, // 👈 esto
            searchable: false // opcional
        },
        { data: 'person.full_name', name: 'person.full_name', title: 'Nombre completo' },
        { data: 'level.description', name: 'level.description', title: 'Nivel' },
    ];

    const options = {
        serverSide: true,
        processing: true,
        responsive: true,
        language: es_PE,
        order: [[1, 'desc']], // 👈 ordenar por la columna 1 (nombre)
        ajax: {
            url: route('cigle_member_table'),
            data: function (d) {
                d.filters = filters.value;
            },
            type: 'post',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        }
    }

    const handleFilterChange = () => {
        refreshTable();
    };

    const memberTable = ref(null);
    let instanceTable = null;

    onMounted(() => {
        instanceTable = memberTable.value?.dt;
    });

    const refreshTable = () => {
        // accede a la instancia del DataTable
        if (instanceTable) {
            //instanceTable.ajax.url(null).load();
            instanceTable.ajax.reload(null, false);
        }
    };

    const optionsWithAll = computed(() => [
        { district_id: null, ubigeo_description: 'Todos' },
        ...props.ubigeo
    ]);

    const ubigeoSelected = ref({
        ubigeo_description: null,
        district_id: null
    });

    const selectCity = () => {
        //filters.ubigeo_description = ubigeoSelected.value.ubigeo_description;
        filters.value.ubigeo = ubigeoSelected.value.district_id;
        refreshTable();
    }
</script>
<template>
    <AppLayout title="Comunidad de la iglesia">
        <Navigation :routeModule="route('cigle_dashboard')" :titleModule="'Comunidad de la iglesia'">
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Miembros</span>
            </li>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Listado</span>
            </li>
        </Navigation>
        <div class="mt-5">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <h2 class="text-xl">Lista de miembros </h2>
                <div class="flex sm:flex-row flex-col sm:items-center sm:gap-3 gap-4 w-full sm:w-auto">
                    <div class="flex gap-3">
                        <Keypad>
                            <template #botones>

                            </template>
                        </Keypad>

                    </div>
                </div>
            </div>
            <div class="panel pb-1.5 mt-6">
                <div class="grid grid-cols-6 gap-6">
                    <div>
                        <InputLabel value="Sede" />
                        <select @change="handleFilterChange" v-model="filters.sede" class="form-select">
                            <option :value="null">Todos</option>
                            <template v-for="sede in sedes">
                                <option :value="sede.id">{{ sede.description }}</option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <InputLabel value="Nivel" />
                        <select @change="handleFilterChange" v-model="filters.nivel" class="form-select">
                            <option :value="null">Todos</option>
                            <template v-for="(type, key) in memberTypes">
                                <option :value="type.id">{{ type.description }}</option>
                            </template>
                        </select>
                    </div>
                    <div class="col-span-6 sm:col-span-2">
                        <InputLabel value="Lugar de evangelización" />
                        <select @change="handleFilterChange" v-model="filters.evangelization" class="form-select">
                            <option :value="null">Todos</option>
                            <template v-for="(evangelization, key) in evangelizations">
                                <option :value="evangelization.id">{{ evangelization.description }}</option>
                            </template>
                        </select>
                    </div>
                    <div class="col-span-6 sm:col-span-2">
                        <InputLabel for="ubigeo" value="Ciudad" />
                        <multiselect
                            id="industry_id"
                            :model-value="ubigeoSelected"
                            v-model="ubigeoSelected"
                            :options="optionsWithAll"
                            class="custom-multiselect"
                            :searchable="true"
                            placeholder="Todos"
                            selected-label="seleccionado"
                            select-label="Elegir"
                            deselect-label="Quitar"
                            label="ubigeo_description"
                            track-by="district_id"
                            @update:model-value="selectCity"
                        ></multiselect>
                    </div>
                </div>
                <DataTable ref="memberTable" :options="options" :columns="columns">
                    <template #action="props">
                        <div class="flex gap-1 items-center justify-center">
                            <Link v-tippy="{ content: 'Editar', placement: 'bottom'}" :href="route('permissions.edit',props.rowData.id)" type="button" class="btn btn-sm btn-outline-primary">
                                <font-awesome-icon  :icon="faPencil" class="m-0" />
                            </Link>
                        </div>
                    </template>
                </DataTable>
            </div>
        </div>
    </AppLayout>
</template>
