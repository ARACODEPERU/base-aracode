<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import { useForm } from '@inertiajs/vue3';
    import InputLabel from '@/Components/InputLabel.vue';
    import Pagination from '@/Components/Pagination.vue';
    import Swal2 from "sweetalert2";
    import { Link, router } from '@inertiajs/vue3';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import { computed, onMounted, ref } from "vue";
    import Multiselect from '@suadelabs/vue3-multiselect';
    import '@suadelabs/vue3-multiselect/dist/vue3-multiselect.css';
    import IconPencilPaper from '@/Components/vristo/icon/icon-pencil-paper.vue';
    import iconTrashLines from '@/Components/vristo/icon/icon-trash-lines.vue';
    import IconEdit from '@/Components/vristo/icon/icon-edit.vue';
    import IconPlus from '@/Components/vristo/icon/icon-plus.vue';

    const props = defineProps({
        edicion: {
            type: Object,
            default: () => ({}),
        },
        teams: {
            type: Object,
            default: () => ({}),
        },
        urrentEquipment: {
            type: Object,
            default: () => ({}),
        },
    });

    const form = useForm({
        edition_id: props.edicion.id,
        team_id: null,
        team_name: null
    });

    const xhttp =  assetUrl;

    const destroyTeam = (id) => {
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
                return axios.delete(route('even_equipos_destroy', id)).then((res) => {
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
                router.visit(route('even_equipos_listado'), {
                    replace: true,
                    method: 'get',
                    only: ['teams'],
                });
            }
        });
    }

    const eventSelected = ref(null);

    const selectTeam = () => {
        form.team_name = eventSelected.value.name;
        form.team_id = eventSelected.value.id;
    }


</script>
<template>
    <AppLayout title="Ediciones">
        <Navigation :routeModule="route('even_dashboard')" :titleModule="'Eventos sociales'">
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <Link :href="route('even_ediciones_listado')" class="text-primary hover:underline">Ediciones</Link>
            </li>
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <span>Equipos</span>
            </li>
        </Navigation>
        <div class="pt-5">
            <div class="w-full">
                <InputLabel value="Equipo" />
                <div class="flex items-center gap-4 w-1/2">
                    <div class="flex-1">
                        <Multiselect
                            id="single-select-object"
                            v-model="form.team"
                            track-by="id"
                            label="name"
                            placeholder="Buscar"
                            selected-label="seleccionado"
                            select-label="Elegir"
                            deselect-label="Quitar"
                            :options="teams"
                            :allow-empty="false"
                            :searchable="true"
                            @update:model-value="selectTeam"
                        >
                        </Multiselect>
                    </div>
                    <button class="btn btn-primary" type="button"><icon-plus class="w-4 h-4 mr-1" />Agregar</button>
                </div>
            </div>
            <div class="mt-6">
                <div class="panel pt-4">
                    <h3 class="text-lg uppercase mb-4 font-medium">Tabla de posiciones</h3>
                    <div class="table-responsive">
                        <table class="w-full text-sm text-left rtl:text-right">
                            <thead class="text-sm text-white border border-dark">
                                <tr>
                                    <th scope="col" class="px-6 py-3 bg-dark font-medium">
                                        #
                                    </th>
                                    <th scope="col" class="px-6 py-3 bg-dark font-medium">
                                        Equipo
                                    </th>
                                    <th scope="col" class="px-6 py-3 bg-dark font-medium">
                                        PTS
                                    </th>
                                    <th scope="col" class="px-6 py-3 bg-dark font-medium">
                                        PJ
                                    </th>
                                    <th scope="col" class="px-6 py-3 bg-dark font-medium">
                                        PG
                                    </th>
                                    <th scope="col" class="px-6 py-3 bg-dark font-medium">
                                        PE
                                    </th>
                                    <th scope="col" class="px-6 py-3 bg-dark font-medium">
                                        PP
                                    </th>
                                    <th scope="col" class="px-6 py-3 bg-dark font-medium">
                                        DG
                                    </th>
                                    <th scope="col" class="px-6 py-3 bg-dark font-medium">
                                        GF
                                    </th>
                                    <th scope="col" class="px-6 py-3 bg-dark font-medium">
                                        GC
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="border-b border-default">
                                    <td class="px-6 py-4">
                                        12
                                    </td>
                                    <th scope="row" class="px-6 py-4 font-medium text-heading whitespace-nowrap bg-neutral-secondary-soft">
                                        Apple MacBook Pro 17"
                                    </th>
                                    <td class="px-6 py-4 bg-dark font-medium">
                                        Silver
                                    </td>
                                    <td class="px-6 py-4 bg-neutral-secondary-soft">
                                        Laptop
                                    </td>
                                    <td class="px-6 py-4">
                                        $2999
                                    </td>
                                    <td class="px-6 py-4">
                                        $2999
                                    </td>
                                    <td class="px-6 py-4">
                                        $2999
                                    </td>
                                    <td class="px-6 py-4">
                                        $2999
                                    </td>
                                    <td class="px-6 py-4">
                                        $2999
                                    </td>
                                    <td class="px-6 py-4">
                                        $2999
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                                </div>
            </div>
        </div>
    </AppLayout>
</template>
