<script setup>
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import { useForm } from '@inertiajs/vue3';
import Keypad from '@/Components/Keypad.vue';
import Pagination from '@/Components/Pagination.vue';
import Swal2 from 'sweetalert2';
import { Link, router } from '@inertiajs/vue3';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import IconEdit from '@/Components/vristo/icon/icon-edit.vue';
import iconTrashLines from '@/Components/vristo/icon/icon-trash-lines.vue';
import IconBallSoccer from '@/Components/vristo/icon/icon-ball-soccer.vue';

const props = defineProps({
    editions: {
        type: Object,
        default: () => ({}),
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const form = useForm({
    search: props.filters.search || '',
});

const xhttp = assetUrl;

const destroyEdition = async (id) => {
    const result = await Swal2.fire({
        title: '¿Estás seguro?',
        text: 'Esta acción eliminará permanentemente la edición y todos sus datos relacionados.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        customClass: {
            popup: 'rounded-lg shadow-xl',
            confirmButton: 'rounded-md font-medium',
            cancelButton: 'rounded-md font-medium',
        },
    });

    if (!result.isConfirmed) return;

    try {
        await axios.delete(route('even_ediciones_eliminar', id));
        Swal2.fire({
            title: 'Eliminado',
            text: 'La edición ha sido eliminada correctamente.',
            icon: 'success',
            timer: 2000,
            showConfirmButton: false,
            customClass: {
                popup: 'rounded-lg',
            },
        });
        router.visit(route('even_ediciones_listado'), {
            preserveState: true,
            preserveScroll: true,
            only: ['editions'],
        });
    } catch (error) {
        Swal2.fire({
            title: 'Error',
            text: 'No se pudo eliminar la edición. Inténtalo de nuevo.',
            icon: 'error',
            customClass: {
                popup: 'rounded-lg',
            },
        });
    }
};

const hasEditions = computed(() => props.editions?.data?.length > 0);

const finishEdition = async (editionId, event) => {
    const result = await Swal2.fire({
        title: '¿Finalizar edición?',
        text: 'Esta acción marcará la edición como finalizada y no se podrán realizar más cambios.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, finalizar',
        cancelButtonText: 'Cancelar',
        padding: '2em',
        customClass: 'sweet-alerts',
    });

    if (!result.isConfirmed) {
        event.target.checked = false;
        return;
    }

    try {
        await axios.put(route('even_ediciones_update_status', editionId), { status: 'finished' });
        Swal2.fire({
            title: 'Finalizada',
            text: 'La edición ha sido marcada como finalizada.',
            icon: 'success',
            timer: 2000,
            showConfirmButton: false,
            padding: '2em',
            customClass: 'sweet-alerts',
        });

        router.visit(route('even_ediciones_listado'), {
            preserveState: true,
            preserveScroll: true,
            only: ['editions'],
        });
    } catch (error) {
        event.target.checked = false;
        Swal2.fire({
            title: 'Error',
            text: 'No se pudo finalizar la edición. Inténtalo de nuevo.',
            icon: 'error',
            padding: '2em',
            customClass: 'sweet-alerts',
        });
    }
};

const translateCompetitionFormat = (format) => {
    if (!format) return 'N/A';
    const translations = {
        'round_robin': 'Todos contra todos',
        'group_stage_and_playoffs': 'Fase de grupos y Eliminatorias',
        'single_elimination': 'Eliminación simple',
        'round_robin_playoff': 'Todos contra todos y Eliminatorias',
        'relampago': 'Relampago',
        // Agregar más traducciones según los valores posibles
    };
    return translations[format.toLowerCase()] || format;
};

const getPrizes = (prizeDetails) => {
    if (!prizeDetails || typeof prizeDetails !== 'object') return [];
    return Object.entries(prizeDetails).filter(([position, prize]) => prize && (prize.money || prize.gift)).map(([position, prize]) => ({ position, ...prize }));
};

const getPositionLabel = (position) => {
    const labels = {
        'first': '1°',
        'second': '2°',
        'third': '3°',
        'fourth': '4°',
        'fifth': '5°',
    };
    return labels[position] || position;
};

</script>
<template>
    <AppLayout title="Ediciones de Torneos">
        <Navigation :routeModule="route('even_dashboard')" :titleModule="'Eventos sociales'">
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <span>Ediciones</span>
            </li>
        </Navigation>

        <div class="mt-6">
            <!-- Header con búsqueda y botón -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
                <div class="w-full sm:w-auto">
                    <form @submit.prevent="form.get(route('even_ediciones_listado'))" class="relative">
                        <label for="search" class="sr-only">Buscar ediciones</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                            <input
                                id="search"
                                v-model="form.search"
                                type="text"
                                class="form-input"
                                placeholder="Buscar por nombre o evento..."
                            />
                        </div>
                    </form>
                </div>
                <Keypad>
                    <template #botones>
                        <Link
                            v-can="'even_ediciones_nuevo'"
                            :href="route('even_ediciones_nuevo')"
                            class="btn btn-primary text-xs uppercase"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Nueva Edición
                        </Link>
                    </template>
                </Keypad>
            </div>

            <!-- Lista de ediciones -->
            <div v-if="hasEditions" class="space-y-6">
                <div
                    v-for="(edition, index) in editions.data"
                    :key="edition.id"
                    class="panel overflow-hidden p-0"
                    :style="{ animationDelay: `${index * 100}ms` }"
                    style="animation: fadeInUp 0.5s ease-out forwards; opacity: 0;"
                >
                    <svg class="wave" viewBox="0 0 100 1440" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M100,0 Q70,80 100,160 Q80,240 100,320 Q70,400 100,480 Q80,560 100,640 Q70,720 100,800 Q80,880 100,960 Q70,1040 100,1120 Q80,1200 100,1280 Q70,1360 100,1440 L0,1440 L0,0 Z"
                            fill="rgba(4, 228, 0, 0.3)"
                        />
                    </svg>
                     <!-- Header de la edición -->
                     <div class="bg-gray-100 px-6 py-4 border-b border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                         <div class="flex justify-between items-center pl-4">
                             <h3 class="text-xl font-bold text-gray-800 dark:text-white">{{ edition.name }}</h3>
                             <span class="inline-flex items-center px-3 py-1 rounded-full bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium">
                                 {{ edition.year }}
                             </span>
                         </div>
                         <p class="text-gray-600 mt-1 pl-4">{{ edition.evento.title }}</p>
                     </div>

                      <!-- Información principal -->
                      <div class="px-6 py-6 bg-gray-50 dark:bg-gray-900">
                         <div class="grid sm:grid-cols-6">
                             <div class="sm:col-span-4">
                                  <ul class="w-full flex flex-col divide-y divide-gray-200 dark:divide-gray-700">
                                     <li class="py-3 px-4">
                                         <div class="flex items-center justify-between">
                                             <div class="flex items-center">
                                                 <svg class="w-6 h-6 text-gray-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                 </svg>
                                                  <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Duración</span>
                                              </div>
                                              <span class="inline-flex items-center px-3 py-1 rounded-full bg-gray-200 dark:bg-gray-600 text-gray-800 dark:text-gray-300 text-sm font-medium">
                                                 {{ edition.start_date }} - {{ edition.end_date }}
                                             </span>
                                         </div>
                                     </li>
                                     <li class="py-3 px-4">
                                         <div class="flex items-center justify-between">
                                             <div class="flex items-center">
                                                 <svg class="w-6 h-6 text-gray-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                                 </svg>
                                                 <span class="text-sm font-medium text-gray-700">Equipos Inscritos</span>
                                             </div>
                                             <span class="inline-flex items-center px-3 py-1 rounded-full bg-blue-100 text-blue-800 text-lg font-semibold">
                                                 {{ edition.equipos ? edition.equipos.length : 0 }}
                                             </span>
                                         </div>
                                     </li>
                                     <li class="py-3 px-4">
                                         <div class="flex items-center justify-between">
                                             <div class="flex items-center">
                                                 <svg class="w-6 h-6 text-gray-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                 </svg>
                                                 <span class="text-sm font-medium text-gray-700">Formato de Competencia</span>
                                             </div>
                                             <span class="inline-flex items-center px-3 py-1 rounded-full bg-green-100 text-green-800 text-sm font-medium">
                                                 {{ translateCompetitionFormat(edition.competition_format) }}
                                             </span>
                                         </div>
                                     </li>
                                     <li v-if="edition.details" class="py-3 px-4">
                                         <div class="flex flex-col">
                                             <div class="flex items-center mb-2">
                                                 <svg class="w-6 h-6 text-gray-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                 </svg>
                                                 <span class="text-sm font-medium text-gray-700">Descripción</span>
                                             </div>
                                             <p class="text-sm text-gray-600 ml-9" v-html="edition.details"></p>
                                         </div>
                                     </li>
                                     <li v-if="edition.path_database_file" class="py-3 px-4">
                                         <div class="flex items-center justify-between">
                                             <div class="flex items-center">
                                                 <svg class="w-6 h-6 text-gray-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                 </svg>
                                                 <span class="text-sm font-medium text-gray-700">Bases del Torneo</span>
                                             </div>
                                              <a
                                                  :href="xhttp + 'storage/' + edition.path_database_file"
                                                  :download="edition.name_database_file"
                                                  class="inline-flex items-center px-3 py-1 rounded-full bg-blue-100 text-blue-800 text-sm font-medium hover:bg-blue-200 transition-colors duration-200"
                                              >
                                                  <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                  </svg>
                                                  {{ edition.name_database_file }}
                                              </a>
                                         </div>
                                     </li>
                                 </ul>
                              </div>
                             <div class="sm:col-span-2">
                                 <div class="p-6 bg-white border border-gray-300 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700">
                                     <h5 class="mb-4 text-lg font-medium text-gray-800 dark:text-white">Inscripción</h5>
                                     <div class="flex items-baseline">
                                         <span class="text-4xl font-bold text-gray-900 dark:text-white">S/ {{ edition.inscription_fee }}</span>
                                         <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">/ soles</span>
                                     </div>
                                 </div>
                                 <!-- Premios -->
                                 <div v-if="getPrizes(edition.prize_details).length > 0" class="mt-4 px-4 py-1 bg-gradient-to-br from-yellow-50 to-orange-50 border border-yellow-200 rounded-lg shadow-sm dark:from-gray-800 dark:to-gray-900 dark:border-yellow-700">
                                     <h5 class="mb-1 text-lg font-medium text-gray-800 flex items-center dark:text-white">
                                         <svg class="w-5 h-5 mr-2 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                                         </svg>
                                         Premios
                                     </h5>
                                     <div class="space-y-1">
                                         <div v-for="(prize, position) in getPrizes(edition.prize_details)" :key="position" class="flex items-center justify-between bg-white px-2 py-1 rounded-md shadow-sm border border-yellow-100 dark:bg-gray-800 dark:border-yellow-700">
                                             <div class="flex items-center">
                                                 <span class="inline-flex items-center justify-center w-8 h-8 bg-yellow-100 text-yellow-800 text-sm font-bold rounded-full mr-3 dark:bg-yellow-700 dark:text-yellow-200">
                                                     {{ getPositionLabel(position) }}
                                                 </span>
                                                 <div class="flex flex-col">
                                                     <span v-if="prize.money" class="text-sm font-semibold text-gray-800">S/ {{ prize.money }}</span>
                                                     <span v-if="prize.gift" class="text-sm text-gray-600">{{ prize.gift }}</span>
                                                 </div>
                                             </div>
                                             <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                                             </svg>
                                         </div>
                                     </div>
                                 </div>
                             </div>
                         </div>
                     </div>

                      <!-- Botones de acción -->
                      <div class="px-6 py-4 bg-gray-100 border-t border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                         <div class="flex flex-wrap items-center gap-3" :class="edition.status == 'in_progress' ? 'justify-between' : 'justify-end'">
                             <div v-if="edition.status === 'in_progress'" class="flex items-center gap-3">
                                 <span class="text-sm font-medium text-gray-700">Finalizar Edición</span>
                                 <label class="relative inline-flex items-center cursor-pointer">
                                     <input type="checkbox" class="sr-only peer" @change="finishEdition(edition.id, $event)">
                                     <div class="w-14 h-7 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-green-400 peer-checked:to-green-600 shadow-lg">
                                         <div class="absolute inset-0 flex items-center justify-center">
                                             <IconBallSoccer class="w-4 h-4 text-white opacity-0 peer-checked:opacity-100 transition-opacity" />
                                         </div>
                                     </div>
                                 </label>
                             </div>
                             <div class="flex flex-wrap gap-3">
                                 <Link
                                v-can="'even_ediciones_editar'"
                                :href="route('even_ediciones_editar', edition.id)"
                                class="inline-flex items-center px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded-md hover:bg-gray-700 transition-colors duration-200"
                            >
                                <IconEdit class="w-4 h-4 mr-2" />
                                Editar
                            </Link>
                            <Link
                                v-can="'even_ediciones_equipos'"
                                :href="route('even_ediciones_equipos', edition.id)"
                                class="inline-flex items-center px-4 py-2 bg-gray-500 text-white text-sm font-medium rounded-md hover:bg-gray-600 transition-colors duration-200"
                            >
                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 512 512">
                                    <path d="M387 228.3c-4.4-2.8-7.6-7-9.2-11.9s-1.4-10.2 .5-15L411.6 118c-19.9-22.4-44.6-40.5-72.4-52.7l-69.1 57.6c-4 3.3-9 5.1-14.1 5.1s-10.2-1.8-14.1-5.1L172.8 65.3c-27.8 12.2-52.5 30.3-72.4 52.7l33.4 83.4c1.9 4.8 2.1 10.1 .5 15s-4.9 9.1-9.2 11.9L49 276.2c3 30.9 12.7 59.7 27.6 85.2l89.7-6c5.2-.3 10.3 1.1 14.5 4.2s7.2 7.4 8.4 12.5l22 87.2c14.4 3.2 29.4 4.8 44.8 4.8s30.3-1.7 44.8-4.8l22-87.2c1.3-5 4.2-9.4 8.4-12.5s9.3-4.5 14.5-4.2l89.7 6c15-25.4 24.7-54.3 27.6-85.1L387 228.3zM256 0a256 256 0 1 1 0 512 256 256 0 1 1 0-512zm62 221c8.4 6.1 11.9 16.9 8.7 26.8l-18.3 56.3c-3.2 9.9-12.4 16.6-22.8 16.6l-59.2 0c-10.4 0-19.6-6.7-22.8-16.6l-18.3-56.3c-3.2-9.9 .3-20.7 8.7-26.8l47.9-34.8c8.4-6.1 19.8-6.1 28.2 0L318 221z"/>
                                </svg>
                                Equipos
                            </Link>
                            <Link
                                v-can="'even_ediciones_fixtures'"
                                :href="route('even_ediciones_fixtures', edition.id)"
                                class="inline-flex items-center px-4 py-2 bg-gray-500 text-white text-sm font-medium rounded-md hover:bg-gray-600 transition-colors duration-200"
                            >
                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 448 512">
                                    <path d="M168.5 0c-13.3 0-24 10.7-24 24s10.7 24 24 24l32 0 0 25.3c-108 11.9-192 103.5-192 214.7 0 119.3 96.7 216 216 216s216-96.7 216-216c0-39.8-10.8-77.1-29.6-109.2l28.2-28.2c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0l-23.4 23.4c-32.9-30.2-75.2-50.3-122-55.5l0-25.3 32 0c13.3 0 24-10.7 24-24s-10.7-24-24-24l-112 0zm-60 240c0-28.7 23.3-52 52-52s52 23.3 52 52l0 3.8c0 11.7-3.2 23.1-9.3 33l-43.8 71.2 33.1 0c11 0 20 9 20 20s-9 20-20 20l-57.8 0c-14.5 0-26.2-11.7-26.2-26.2 0-4.9 1.3-9.6 3.9-13.8l56.7-92.1c2.2-3.6 3.4-7.8 3.4-12.1l0-3.8c0-6.6-5.4-12-12-12s-12 5.4-12 12c0 11-9 20-20 20s-20-9-20-20zm180-52c28.7 0 52 23.3 52 52l0 96c0 28.7-23.3 52-52 52s-52-23.3-52-52l0-96c0-28.7 23.3-52 52-52zm-12 52l0 96c0 6.6 5.4 12 12 12s12-5.4 12-12l0-96c0-6.6-5.4-12-12-12s-12 5.4-12 12z"/>
                                </svg>
                                Partidos
                            </Link>
                            <Link
                                v-can="'even_ediciones_sanciones'"
                                :href="route('even_ediciones_pago_sanciones', edition.id)"
                                class="inline-flex items-center px-4 py-2 bg-gray-500 text-white text-sm font-medium rounded-md hover:bg-gray-600 transition-colors duration-200"
                            >
                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 640 640">
                                    <path d="M544 72C544 58.7 533.3 48 520 48L418.2 48C404.9 48 394.2 58.7 394.2 72C394.2 85.3 404.9 96 418.2 96L462.1 96L350.8 207.3L255.7 125.8C246.7 118.1 233.5 118.1 224.5 125.8L112.5 221.8C102.4 230.4 101.3 245.6 109.9 255.6C118.5 265.6 133.7 266.8 143.7 258.2L240.1 175.6L336.5 258.2C346 266.4 360.2 265.8 369.1 256.9L496.1 129.9L496.1 173.8C496.1 187.1 506.8 197.8 520.1 197.8C533.4 197.8 544.1 187.1 544.1 173.8L544 72zM112 320C85.5 320 64 341.5 64 368L64 528C64 554.5 85.5 576 112 576L528 576C554.5 576 576 554.5 576 528L576 368C576 341.5 554.5 320 528 320L112 320zM159.3 376C155.9 396.1 140.1 412 119.9 415.4C115.5 416.1 111.9 412.5 111.9 408.1L111.9 376.1C111.9 371.7 115.5 368.1 119.9 368.1L151.9 368.1C156.3 368.1 160 371.7 159.2 376.1zM159.3 520.1C160 524.5 156.4 528.1 152 528.1L120 528.1C115.6 528.1 112 524.5 112 520.1L112 488.1C112 483.7 115.6 480 120 480.8C140.1 484.2 156 500 159.4 520.2zM520 480.7C524.4 480 528 483.6 528 488L528 520C528 524.4 524.4 528 520 528L488 528C483.6 528 479.9 524.4 480.7 520C484.1 499.9 499.9 484 520.1 480.6zM480.7 376C480 371.6 483.6 368 488 368L520 368C524.4 368 528 371.6 528 376L528 408C528 412.4 524.4 416.1 520 415.3C499.9 411.9 484 396.1 480.6 375.9zM256 448C256 412.7 284.7 384 320 384C355.3 384 384 412.7 384 448C384 483.3 355.3 512 320 512C284.7 512 256 483.3 256 448z"/>
                                </svg>
                                Sanciones
                            </Link>
                            <Link
                                v-can="'even_ediciones_actas'"
                                :href="route('even_ediciones_actas_listado', edition.id)"
                                class="inline-flex items-center px-4 py-2 bg-gray-500 text-white text-sm font-medium rounded-md hover:bg-gray-600 transition-colors duration-200"
                            >
                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 640 640">
                                    <path d="M64.1 128C64.1 92.7 92.8 64 128.1 64L277.6 64C294.6 64 310.9 70.7 322.9 82.7L429.3 189.3C441.3 201.3 448 217.6 448 234.6L448 332.1L316 464.1L273.9 464.1L257.8 410.5C253.1 394.8 238.7 384.1 222.3 384.1C211 384.1 200.4 389.2 193.4 398L133.3 473C125 483.3 126.7 498.5 137 506.7C147.3 514.9 162.5 513.3 170.7 502.9L217.8 444.1L233 494.8C236 505 245.4 511.9 256 511.9L287.5 511.9C286.6 515 285.8 518.2 285.2 521.4L274.3 575.9L128.1 575.9C92.8 575.9 64.1 547.2 64.1 511.9L64.1 127.9zM272.1 122.5L272.1 216C272.1 229.3 282.8 240 296.1 240L389.6 240L272.1 122.5zM332.3 530.9C334.8 518.5 340.9 507.1 349.8 498.2L468.7 379.3L548.7 459.3L429.8 578.2C420.9 587.1 409.5 593.2 397.1 595.7L337.5 607.6C336.6 607.8 335.6 607.9 334.6 607.9C326.6 607.9 320 601.4 320 593.3C320 592.3 320.1 591.4 320.3 590.4L332.2 530.8zM600.1 407.9L571.3 436.7L491.3 356.7L520.1 327.9C542.2 305.8 578 305.8 600.1 327.9C622.2 350 622.2 385.8 600.1 407.9z"/>
                                </svg>
                                Actas
                            </Link>
                            <button
                                v-can="'even_ediciones_eliminar'"
                                @click="destroyEdition(edition.id)"
                                class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700 transition-colors duration-200"
                            >
                                <iconTrashLines class="w-4 h-4 mr-2" />
                                Eliminar
                             </button>
                             </div>
                         </div>
                     </div>
                </div>
            </div>

            <!-- Estado vacío -->
            <div v-else class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No hay ediciones</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Comienza creando tu primera edición de torneo.</p>
                <div class="mt-6">
                    <Link
                        v-can="'even_ediciones_nuevo'"
                        :href="route('even_ediciones_nuevo')"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 transition ease-in-out duration-150"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Crear Edición
                    </Link>
                </div>
            </div>

            <!-- Paginación -->
            <div v-if="hasEditions" class="mt-8">
                <Pagination :data="editions" />
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
    .wave {
        position: absolute;
        left: -50px;
        top: 0;
        width: 100px;
        height: 100%;
    }
</style>
