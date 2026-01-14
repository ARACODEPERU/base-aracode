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
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- From Uiverse.io by seoulchik -->
                    <div v-for="edition in editions.data" class="panel p-0 dark:border-gray-800 shadow-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors bg-white dark:bg-gray-800 rounded-xl overflow-hidden">

                        <div class="flex items-center bg-gradient-to-r from-blue-100 via-indigo-50 to-purple-100 dark:from-gray-700 dark:via-gray-600 dark:to-gray-500 justify-between py-6 px-6 dark:border-gray-500 rounded-t-xl">
                            <span class="text-lg dark:text-white">{{ edition.name }}</span>
                            <span class="badge badge-outline-dark dark:badge-outline-info">{{ edition.year }}</span>
                        </div>

                        <div class="p-6 bg-gray-50 dark:bg-gray-700">
                            <div class="text-xl font-semibold dark:text-white mb-4 text-gray-800">
                                {{ edition.evento.title }}
                            </div>
                            <div class="flex items-center justify-between bg-white dark:bg-gray-600 p-4 rounded-lg shadow-sm">
                                <div class="flex-1">
                                    <div class="flex items-baseline text-3xl font-bold text-green-600 dark:text-green-400">
                                        <span class="text-sm mr-1 font-normal">S/.</span>
                                        {{ edition.inscription_fee }}
                                    </div>
                                    <span class="text-sm text-gray-500 dark:text-gray-300">Inscripción</span>
                                </div>
                                <button v-tippy="{ content: 'Descargar base', placement: 'bottom'}" class="p-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-full transition-colors duration-200 shadow-md">
                                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512">
                                        <path fill="currentColor" d="M0 64C0 28.7 28.7 0 64 0L213.5 0c17 0 33.3 6.7 45.3 18.7L365.3 125.3c12 12 18.7 28.3 18.7 45.3L384 448c0 35.3-28.7 64-64 64L64 512c-35.3 0-64-28.7-64-64L0 64zm208-5.5l0 93.5c0 13.3 10.7 24 24 24L325.5 176 208 58.5zM175 441c9.4 9.4 24.6 9.4 33.9 0l64-64c9.4-9.4 9.4-24.6 0-33.9s-24.6-9.4-33.9 0l-23 23 0-86.1c0-13.3-10.7-24-24-24s-24 10.7-24 24l0 86.1-23-23c-9.4-9.4-24.6-9.4-33.9 0s-9.4 24.6 0 33.9l64 64z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div class="p-6 bg-white dark:bg-gray-800 space-y-2">
                             <Link v-can="'even_ediciones_editar'" :href="route('even_ediciones_editar', edition.id)" type="button" class="btn btn-info flex justify-between uppercase text-xs">
                                <IconEdit class="w-4 h-4 mr-1" />
                                Editar
                            </Link>
                             <Link v-can="'even_ediciones_equipos'" :href="route('even_ediciones_equipos', edition.id)" type="button" class="btn btn-warning flex justify-between uppercase text-xs">
                                <svg class="w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                    <path fill="currentColor" d="M387 228.3c-4.4-2.8-7.6-7-9.2-11.9s-1.4-10.2 .5-15L411.6 118c-19.9-22.4-44.6-40.5-72.4-52.7l-69.1 57.6c-4 3.3-9 5.1-14.1 5.1s-10.2-1.8-14.1-5.1L172.8 65.3c-27.8 12.2-52.5 30.3-72.4 52.7l33.4 83.4c1.9 4.8 2.1 10.1 .5 15s-4.9 9.1-9.2 11.9L49 276.2c3 30.9 12.7 59.7 27.6 85.2l89.7-6c5.2-.3 10.3 1.1 14.5 4.2s7.2 7.4 8.4 12.5l22 87.2c14.4 3.2 29.4 4.8 44.8 4.8s30.3-1.7 44.8-4.8l22-87.2c1.3-5 4.2-9.4 8.4-12.5s9.3-4.5 14.5-4.2l89.7 6c15-25.4 24.7-54.3 27.6-85.1L387 228.3zM256 0a256 256 0 1 1 0 512 256 256 0 1 1 0-512zm62 221c8.4 6.1 11.9 16.9 8.7 26.8l-18.3 56.3c-3.2 9.9-12.4 16.6-22.8 16.6l-59.2 0c-10.4 0-19.6-6.7-22.8-16.6l-18.3-56.3c-3.2-9.9 .3-20.7 8.7-26.8l47.9-34.8c8.4-6.1 19.8-6.1 28.2 0L318 221z"/>
                                </svg>
                                Inscripciones y Tabla
                            </Link>
                             <Link v-can="'even_ediciones_fixtures'" :href="route('even_ediciones_fixtures', edition.id)" type="button" class="btn btn-success flex justify-between uppercase text-xs">
                                <svg class="w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
                                    <path fill="currentColor" d="M168.5 0c-13.3 0-24 10.7-24 24s10.7 24 24 24l32 0 0 25.3c-108 11.9-192 103.5-192 214.7 0 119.3 96.7 216 216 216s216-96.7 216-216c0-39.8-10.8-77.1-29.6-109.2l28.2-28.2c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0l-23.4 23.4c-32.9-30.2-75.2-50.3-122-55.5l0-25.3 32 0c13.3 0 24-10.7 24-24s-10.7-24-24-24l-112 0zm-60 240c0-28.7 23.3-52 52-52s52 23.3 52 52l0 3.8c0 11.7-3.2 23.1-9.3 33l-43.8 71.2 33.1 0c11 0 20 9 20 20s-9 20-20 20l-57.8 0c-14.5 0-26.2-11.7-26.2-26.2 0-4.9 1.3-9.6 3.9-13.8l56.7-92.1c2.2-3.6 3.4-7.8 3.4-12.1l0-3.8c0-6.6-5.4-12-12-12s-12 5.4-12 12c0 11-9 20-20 20s-20-9-20-20zm180-52c28.7 0 52 23.3 52 52l0 96c0 28.7-23.3 52-52 52s-52-23.3-52-52l0-96c0-28.7 23.3-52 52-52zm-12 52l0 96c0 6.6 5.4 12 12 12s12-5.4 12-12l0-96c0-6.6-5.4-12-12-12s-12 5.4-12 12z"/>
                                </svg>
                                Control de Partidos
                            </Link>
                             <Link v-can="'even_ediciones_sanciones'" :href="route('even_ediciones_pago_sanciones', edition.id)" type="button" class="btn btn-dark flex justify-between uppercase text-xs">
                                <svg class="w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
                                    <path fill="currentColor" d="M544 72C544 58.7 533.3 48 520 48L418.2 48C404.9 48 394.2 58.7 394.2 72C394.2 85.3 404.9 96 418.2 96L462.1 96L350.8 207.3L255.7 125.8C246.7 118.1 233.5 118.1 224.5 125.8L112.5 221.8C102.4 230.4 101.3 245.6 109.9 255.6C118.5 265.6 133.7 266.8 143.7 258.2L240.1 175.6L336.5 258.2C346 266.4 360.2 265.8 369.1 256.9L496.1 129.9L496.1 173.8C496.1 187.1 506.8 197.8 520.1 197.8C533.4 197.8 544.1 187.1 544.1 173.8L544 72zM112 320C85.5 320 64 341.5 64 368L64 528C64 554.5 85.5 576 112 576L528 576C554.5 576 576 554.5 576 528L576 368C576 341.5 554.5 320 528 320L112 320zM159.3 376C155.9 396.1 140.1 412 119.9 415.4C115.5 416.1 111.9 412.5 111.9 408.1L111.9 376.1C111.9 371.7 115.5 368.1 119.9 368.1L151.9 368.1C156.3 368.1 160 371.7 159.2 376.1zM159.3 520.1C160 524.5 156.4 528.1 152 528.1L120 528.1C115.6 528.1 112 524.5 112 520.1L112 488.1C112 483.7 115.6 480 120 480.8C140.1 484.2 156 500 159.4 520.2zM520 480.7C524.4 480 528 483.6 528 488L528 520C528 524.4 524.4 528 520 528L488 528C483.6 528 479.9 524.4 480.7 520C484.1 499.9 499.9 484 520.1 480.6zM480.7 376C480 371.6 483.6 368 488 368L520 368C524.4 368 528 371.6 528 376L528 408C528 412.4 524.4 416.1 520 415.3C499.9 411.9 484 396.1 480.6 375.9zM256 448C256 412.7 284.7 384 320 384C355.3 384 384 412.7 384 448C384 483.3 355.3 512 320 512C284.7 512 256 483.3 256 448z"/>
                                </svg>
                                Cobro de Sanciones
                            </Link>
                             <Link v-can="'even_ediciones_actas'" :href="route('even_ediciones_actas_listado', edition.id)" type="button" class="btn btn-secondary flex justify-between uppercase text-xs">
                                <svg class="w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
                                    <path fill="currentColor" d="M64.1 128C64.1 92.7 92.8 64 128.1 64L277.6 64C294.6 64 310.9 70.7 322.9 82.7L429.3 189.3C441.3 201.3 448 217.6 448 234.6L448 332.1L316 464.1L273.9 464.1L257.8 410.5C253.1 394.8 238.7 384.1 222.3 384.1C211 384.1 200.4 389.2 193.4 398L133.3 473C125 483.3 126.7 498.5 137 506.7C147.3 514.9 162.5 513.3 170.7 502.9L217.8 444.1L233 494.8C236 505 245.4 511.9 256 511.9L287.5 511.9C286.6 515 285.8 518.2 285.2 521.4L274.3 575.9L128.1 575.9C92.8 575.9 64.1 547.2 64.1 511.9L64.1 127.9zM272.1 122.5L272.1 216C272.1 229.3 282.8 240 296.1 240L389.6 240L272.1 122.5zM332.3 530.9C334.8 518.5 340.9 507.1 349.8 498.2L468.7 379.3L548.7 459.3L429.8 578.2C420.9 587.1 409.5 593.2 397.1 595.7L337.5 607.6C336.6 607.8 335.6 607.9 334.6 607.9C326.6 607.9 320 601.4 320 593.3C320 592.3 320.1 591.4 320.3 590.4L332.2 530.8zM600.1 407.9L571.3 436.7L491.3 356.7L520.1 327.9C542.2 305.8 578 305.8 600.1 327.9C622.2 350 622.2 385.8 600.1 407.9z"/>
                                </svg>
                                Acuerdos y Actas
                            </Link>
                             <button v-can="'even_ediciones_eliminar'" type="button" class="btn btn-danger flex justify-between uppercase text-xs w-full">
                                <iconTrashLines class="w-4 h-4 mr-1" />
                                Eliminar Torneo
                            </button>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
