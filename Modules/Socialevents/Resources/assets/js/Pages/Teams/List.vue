<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import { useForm } from '@inertiajs/vue3';
    import Keypad from '@/Components/Keypad.vue';
    import Pagination from '@/Components/Pagination.vue';
    import Swal2 from "sweetalert2";
    import { Link, router } from '@inertiajs/vue3';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';

    import IconPencilPaper from '@/Components/vristo/icon/icon-pencil-paper.vue';
    import iconTrashLines from '@/Components/vristo/icon/icon-trash-lines.vue';
    import { faUsers, faCalendarAlt, faTrophy } from "@fortawesome/free-solid-svg-icons";

    const props = defineProps({
        teams: {
            type: Object,
            default: () => ({}),
        },
        filters: {
            type: Object,
            default: () => ({}),
        },
    });

    const form = useForm({
        search: props.filters.search,
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
</script>
<template>
    <AppLayout title="Equipos">
        <Navigation :routeModule="route('even_dashboard')" :titleModule="'Eventos sociales'"
            :data="[
                {route: title: 'Equipos'}
            ]"
        />
        <div class="pt-5">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div class="grid grid-cols-3 w-full">
                    <div class="col-span-3 sm:col-span-1">
                        <form id="form-search-items" @submit.prevent="form.get(route('even_equipos_listado'))">
                            <label for="table-search" class="sr-only">Search</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                    <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path></svg>
                                </div>
                                <input v-model="form.search" type="text" class="block pl-10 form-input w-80" placeholder="Buscar por Descripción">
                            </div>
                        </form>
                    </div>
                    <div class="col-span-3 sm:col-span-2">
                        <Keypad>
                            <template #botones>
                                <Link v-can="'even_equipos_nuevo'" :href="route('even_equipos_create')" class="flex items-center justify-center inline-block px-6 py-2.5 bg-blue-900 text-white font-medium text-xs leading-tight uppercase rounded shadow-md hover:bg-blue-700 hover:shadow-lg focus:bg-blue-700 focus:shadow-lg focus:outline-none focus:ring-0 active:bg-blue-800 active:shadow-lg transition duration-150 ease-in-out">
                                    Nuevo
                                </Link>
                            </template>
                        </Keypad>
                    </div>
                </div>
            </div>
            <div class="mt-5">
                 <!-- Grid -->
                 <div class="grid grid-cols-1
                     sm:grid-cols-2
                     md:grid-cols-3
                     lg:grid-cols-3
                     xl:grid-cols-3
                     2xl:grid-cols-5
                     gap-6"
                 >
                    <!-- From Uiverse.io by narmesh_sah -->
                    <div v-for="team in teams.data" class="relative bg-white border border-gray-200 border-t-4 border-t-blue-600 shadow-2xs rounded-xl dark:bg-neutral-900 dark:border-neutral-700 dark:border-t-blue-500 dark:shadow-neutral-700/70 p-6">
                        <div class="w-24 h-24 rounded-full bg-gray-100 mx-auto mb-4 overflow-hidden border-2 border-white shadow-md flex items-center justify-center">
                            <img v-if="team.logo_path" :src="xhttp + 'storage/' + team.logo_path" class="w-full h-full object-cover" />
                            <img v-else :src="'/img/team1.png'" class="w-full h-full object-cover" />
                        </div>
                        <div class="text-center mb-4">
                            <p class="text-lg font-semibold text-gray-900 dark:text-white mb-1">{{ team.name }}</p>
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                <template v-if="team.manager">@{{ team.manager.full_name }}</template>
                                <template v-else>No tiene representante</template>
                            </div>
                        </div>
                        <div class="flex justify-center gap-2 mb-4">
                            <Link :href="route('even_equipos_edit', team.id)" v-tippy="{content:'Editar', placement:'bottom'}" class="btn btn-info w-8 h-8 p-0 rounded-full flex items-center justify-center">
                                <IconPencilPaper class="w-4 h-4" />
                            </Link>
                            <button @click="destroyTeam(team.id)" v-tippy="{content:'Eliminar', placement:'bottom'}" class="btn btn-danger w-8 h-8 p-0 rounded-full flex items-center justify-center">
                                <iconTrashLines class="w-4 h-4" />
                            </button>
                        </div>
                        <div class="flex justify-between pt-4 border-t border-gray-200 dark:border-gray-600">
                            <div class="text-center">
                                <div class="flex items-center justify-center mb-1">
                                    <font-awesome-icon :icon="faUsers" class="w-4 h-4 text-blue-500 mr-1" />
                                </div>
                                <div class="text-sm font-bold text-gray-900 dark:text-white">{{ team.players.length }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Jugadores</div>
                            </div>
                            <div class="text-center">
                                <div class="flex items-center justify-center mb-1">
                                    <font-awesome-icon :icon="faCalendarAlt" class="w-4 h-4 text-green-500 mr-1" />
                                </div>
                                <div class="text-sm font-bold text-gray-900 dark:text-white">{{ team.editions.length }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Ediciones</div>
                            </div>
                            <div class="text-center">
                                <div class="flex items-center justify-center mb-1">
                                    <font-awesome-icon :icon="faTrophy" class="w-4 h-4 text-yellow-500 mr-1" />
                                </div>
                                <div class="text-sm font-bold text-gray-900 dark:text-white">{{ team.editions.filter(e => e.is_champion).length || 0 }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Victorias</div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End Grid -->
                 <Pagination :data="teams" class="mt-6" />
            </div>
        </div>
    </AppLayout>
</template>

