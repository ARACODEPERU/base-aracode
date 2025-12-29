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
    import IconEdit from '@/Components/vristo/icon/icon-edit.vue';

    const props = defineProps({
        editions: {
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
    <AppLayout title="Ediciones">
        <Navigation :routeModule="route('even_dashboard')" :titleModule="'Eventos sociales'">
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <span>Ediciones</span>
            </li>
        </Navigation>
        <div class="pt-5">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div class="grid grid-cols-3 w-full">
                    <div class="col-span-3 sm:col-span-1">
                        <form id="form-search-items" @submit.prevent="form.get(route('even_ediciones_listado'))">
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
                                <Link v-can="'even_ediciones_nuevo'" :href="route('even_ediciones_nuevo')" class="flex items-center justify-center inline-block px-6 py-2.5 bg-blue-900 text-white font-medium text-xs leading-tight uppercase rounded shadow-md hover:bg-blue-700 hover:shadow-lg focus:bg-blue-700 focus:shadow-lg focus:outline-none focus:ring-0 active:bg-blue-800 active:shadow-lg transition duration-150 ease-in-out">
                                    Nuevo
                                </Link>
                            </template>
                        </Keypad>
                    </div>
                </div>
            </div>
            <div class="mt-5">
                <div class="grid sm:grid-cols-3 gap-6">
                    <!-- From Uiverse.io by seoulchik -->
                    <div v-for="edition in editions.data" class="panel p-0 border-2 dark:border-gray-800">

                        <div class="flex items-center bg-gray-100 justify-between border-b-4 py-4 px-6 rounded-t-sm dark:bg-gray-700 dark:border-gray-800">
                            <span class="text-lg dark:text-white">{{ edition.name }}</span>
                            <span class="badge badge-outline-dark dark:badge-outline-info">{{ edition.year }}</span>
                        </div>

                        <div class="p-6">
                            <div class="text-lg dark:text-white mb-6">
                                {{ edition.evento.title }}
                            </div>
                            <div class="flex items-center justify-between">
                                <div class="">
                                    <div class="flex items-center text-4xl">
                                        <span class="text-sm mr-1"><strong>S/.</strong></span>
                                        <strong>{{ edition.inscription_fee }}</strong>
                                    </div>
                                    <span class="price-period">Inscripción</span>
                                </div>
                                <button v-tippy="{ content: 'Descargar base', placement: 'bottom'}">
                                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512">
                                        <path fill="currentColor" d="M0 64C0 28.7 28.7 0 64 0L213.5 0c17 0 33.3 6.7 45.3 18.7L365.3 125.3c12 12 18.7 28.3 18.7 45.3L384 448c0 35.3-28.7 64-64 64L64 512c-35.3 0-64-28.7-64-64L0 64zm208-5.5l0 93.5c0 13.3 10.7 24 24 24L325.5 176 208 58.5zM175 441c9.4 9.4 24.6 9.4 33.9 0l64-64c9.4-9.4 9.4-24.6 0-33.9s-24.6-9.4-33.9 0l-23 23 0-86.1c0-13.3-10.7-24-24-24s-24 10.7-24 24l0 86.1-23-23c-9.4-9.4-24.6-9.4-33.9 0s-9.4 24.6 0 33.9l64 64z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div class="grid sm:grid-cols-2 p-4 gap-4">
                            <Link v-can="'even_ediciones_editar'" :href="route('even_ediciones_editar', edition.id)" type="button" class="btn btn-info">
                                <IconEdit class="w-4 h-4 mr-1" />
                                Editar
                            </Link>
                            <Link v-can="'even_ediciones_equipos'" :href="route('even_ediciones_equipos', edition.id)" type="button" class="btn btn-warning">
                                <svg class="w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                    <path fill="currentColor" d="M387 228.3c-4.4-2.8-7.6-7-9.2-11.9s-1.4-10.2 .5-15L411.6 118c-19.9-22.4-44.6-40.5-72.4-52.7l-69.1 57.6c-4 3.3-9 5.1-14.1 5.1s-10.2-1.8-14.1-5.1L172.8 65.3c-27.8 12.2-52.5 30.3-72.4 52.7l33.4 83.4c1.9 4.8 2.1 10.1 .5 15s-4.9 9.1-9.2 11.9L49 276.2c3 30.9 12.7 59.7 27.6 85.2l89.7-6c5.2-.3 10.3 1.1 14.5 4.2s7.2 7.4 8.4 12.5l22 87.2c14.4 3.2 29.4 4.8 44.8 4.8s30.3-1.7 44.8-4.8l22-87.2c1.3-5 4.2-9.4 8.4-12.5s9.3-4.5 14.5-4.2l89.7 6c15-25.4 24.7-54.3 27.6-85.1L387 228.3zM256 0a256 256 0 1 1 0 512 256 256 0 1 1 0-512zm62 221c8.4 6.1 11.9 16.9 8.7 26.8l-18.3 56.3c-3.2 9.9-12.4 16.6-22.8 16.6l-59.2 0c-10.4 0-19.6-6.7-22.8-16.6l-18.3-56.3c-3.2-9.9 .3-20.7 8.7-26.8l47.9-34.8c8.4-6.1 19.8-6.1 28.2 0L318 221z"/>
                                </svg>
                                Equipos
                            </Link>
                            <Link v-can="'even_ediciones_fixtures'" :href="route('even_ediciones_fixtures', edition.id)" type="button" class="btn btn-success">
                                <svg class="w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
                                    <path fill="currentColor" d="M168.5 0c-13.3 0-24 10.7-24 24s10.7 24 24 24l32 0 0 25.3c-108 11.9-192 103.5-192 214.7 0 119.3 96.7 216 216 216s216-96.7 216-216c0-39.8-10.8-77.1-29.6-109.2l28.2-28.2c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0l-23.4 23.4c-32.9-30.2-75.2-50.3-122-55.5l0-25.3 32 0c13.3 0 24-10.7 24-24s-10.7-24-24-24l-112 0zm-60 240c0-28.7 23.3-52 52-52s52 23.3 52 52l0 3.8c0 11.7-3.2 23.1-9.3 33l-43.8 71.2 33.1 0c11 0 20 9 20 20s-9 20-20 20l-57.8 0c-14.5 0-26.2-11.7-26.2-26.2 0-4.9 1.3-9.6 3.9-13.8l56.7-92.1c2.2-3.6 3.4-7.8 3.4-12.1l0-3.8c0-6.6-5.4-12-12-12s-12 5.4-12 12c0 11-9 20-20 20s-20-9-20-20zm180-52c28.7 0 52 23.3 52 52l0 96c0 28.7-23.3 52-52 52s-52-23.3-52-52l0-96c0-28.7 23.3-52 52-52zm-12 52l0 96c0 6.6 5.4 12 12 12s12-5.4 12-12l0-96c0-6.6-5.4-12-12-12s-12 5.4-12 12z"/>
                                </svg>
                                Fixture
                            </Link>
                            <button v-can="'even_ediciones_eliminar'" type="button" class="btn btn-danger">
                                <iconTrashLines class="w-4 h-4 mr-1" />
                                Eliminar
                            </button>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
