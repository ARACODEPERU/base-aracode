<script setup>
    import { Link, router } from '@inertiajs/vue3';
    import { faArrowLeft, faArrowRight, faStepBackward, faStepForward } from "@fortawesome/free-solid-svg-icons";
    import { ref } from 'vue';

    const props = defineProps({
        data: {
            type: Object,
            default: () => ({}),
        },
    });

    const perPageOptions = [10, 20, 50, 100];
    const selectedPerPage = ref(props.data.per_page || 20);

    const changePerPage = () => {
        const url = new URL(window.location);
        url.searchParams.set('per_page', selectedPerPage.value);
        router.visit(url.toString(), { preserveState: true });
    };
</script>
<template>
    <div v-if="data.links.length > 0" class="p-0">
        <!-- Select for per page if slot is provided -->
        <div v-if="$slots.default" class="flex justify-between items-center px-4 py-3">
            <div class="flex items-center space-x-2">
                <span for="per-page-select" class="text-sm font-bold text-gray-700 dark:text-gray-300">Mostrar:</span>
                <select
                    id="per-page-select"
                    v-model="selectedPerPage"
                    @change="changePerPage"
                    class="form-select form-select-sm"
                >
                    <option v-for="option in perPageOptions" :key="option" :value="option">
                        {{ option }}
                    </option>
                </select>
                <span class="text-sm text-gray-600 dark:text-gray-400">registros</span>
            </div>
        </div>

        <!-- Table slot -->
        <div>
            <slot />
        </div>

        <!-- Pagination -->
        <nav role="navigation" aria-label="Navegación de Paginación" class="flex flex-col sm:flex-row items-center justify-between space-y-4 sm:space-y-0 px-4 py-4">
            <!-- Mobile Pagination -->
            <div class="flex justify-between w-full sm:hidden space-x-3">
                <button v-if="data.current_page <= 1"
                        class="inline-flex items-center px-4 py-3 text-sm font-bold text-gray-400 bg-gray-200 rounded-lg cursor-not-allowed dark:bg-gray-700 dark:text-gray-500"
                        disabled
                        aria-label="Página anterior no disponible">
                    <font-awesome-icon :icon="faArrowLeft" class="w-4 h-4 mr-2" />
                    Anterior
                </button>
                <Link v-else :href="data.prev_page_url"
                    class="inline-flex items-center px-4 py-3 text-sm font-bold text-blue-700 bg-white rounded-lg hover:bg-blue-500 hover:text-white focus:outline-none focus:ring-2 focus:ring-blue-300 transition-all duration-200 dark:bg-gray-700 dark:text-blue-300 dark:hover:bg-blue-600 dark:hover:text-white"
                    aria-label="Ir a la página anterior">
                    <font-awesome-icon :icon="faArrowLeft" class="w-4 h-4 mr-2" />
                    Anterior
                </Link>
                <Link v-if="data.current_page < data.last_page" :href="data.next_page_url"
                    class="inline-flex items-center px-4 py-3 text-sm font-bold text-blue-700 bg-white rounded-lg hover:bg-blue-500 hover:text-white focus:outline-none focus:ring-2 focus:ring-blue-300 transition-all duration-200 dark:bg-gray-700 dark:text-blue-300 dark:hover:bg-blue-600 dark:hover:text-white"
                    aria-label="Ir a la página siguiente">
                    Siguiente
                    <font-awesome-icon :icon="faArrowRight" class="w-4 h-4 ml-2" />
                </Link>
                <button v-else
                        class="inline-flex items-center px-4 py-3 text-sm font-bold text-gray-400 bg-gray-200 rounded-lg cursor-not-allowed dark:bg-gray-700 dark:text-gray-500"
                        disabled
                        aria-label="Página siguiente no disponible">
                    Siguiente
                    <font-awesome-icon :icon="faArrowRight" class="w-4 h-4 ml-2" />
                </button>
            </div>
            <!-- Desktop Pagination -->
            <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm text-gray-700 dark:text-gray-300 font-sans">
                        Mostrando
                        <span class="font-bold text-gray-900 dark:text-white">{{ data.from }}</span>
                        a
                        <span class="font-bold text-gray-900 dark:text-white">{{ data.to }}</span>
                        de
                        <span class="font-bold text-gray-900 dark:text-white">{{ data.total }}</span>
                        resultados
                    </p>
                </div>
                <div>
                    <ul class="flex items-center space-x-1">
                        <li>
                             <Link v-if="data.current_page > 1" :href="data.first_page_url"
                                 class="flex items-center justify-center w-9 h-9 rounded-full bg-[#E8EBF3] text-[#4A5E8D] text-sm font-medium transition-colors hover:bg-blue-600 hover:text-white dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-blue-600"
                                 aria-label="Ir a la primera página">
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
                                    <path fill="currentColor" d="M9.4 233.4c-12.5 12.5-12.5 32.8 0 45.3l160 160c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L77.3 256 214.6 118.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0l-160 160zm352-160l-160 160c-12.5 12.5-12.5 32.8 0 45.3l160 160c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L269.3 256 406.6 118.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0z"/>
                                </svg>
                             </Link>
                             <button v-else type="button"
                                     class="flex items-center justify-center w-9 h-9 rounded-full bg-[#F3F5F9] text-[#BDC5D8] text-sm cursor-not-allowed dark:bg-gray-800/50 dark:text-gray-600"
                                     disabled
                                     aria-label="Primera página no disponible">
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
                                    <path fill="currentColor" d="M9.4 233.4c-12.5 12.5-12.5 32.8 0 45.3l160 160c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L77.3 256 214.6 118.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0l-160 160zm352-160l-160 160c-12.5 12.5-12.5 32.8 0 45.3l160 160c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L269.3 256 406.6 118.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0z"/>
                                </svg>
                             </button>
                        </li>
                        <template v-for="(link, key) in data.links" :key="key">
                            <li v-if="link.label === '...'">
                                <span class="flex items-center justify-center w-9 h-9 text-sm text-gray-400 dark:text-gray-500">...</span>
                            </li>
                            <li v-else-if="link.active">
                                <button type="button"
                                        class="flex items-center justify-center w-9 h-9 rounded-full bg-[#4466F2] text-white text-sm font-bold shadow-lg shadow-blue-200 dark:shadow-none"
                                        aria-current="page">
                                    {{ link.label }}
                                </button>
                            </li>
                            <li v-else-if="link.url === null">
                                <button type="button"
                                        class="flex items-center justify-center w-9 h-9 rounded-full bg-[#E8EBF3] text-[#4A5E8D] text-sm font-medium cursor-not-allowed dark:bg-gray-800 dark:text-gray-300"
                                        disabled>
                                    <template v-if="link.label.includes('Anterior') || link.label.includes('previous')">
                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 512">
                                            <path fill="currentColor" d="M9.4 233.4c-12.5 12.5-12.5 32.8 0 45.3l160 160c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L77.3 256 214.6 118.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0l-160 160z"/>
                                        </svg>
                                    </template>
                                    <template v-else-if="link.label.includes('Siguiente') || link.label.includes('Next')">
                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 512">
                                            <path fill="currentColor" d="M247.1 233.4c12.5 12.5 12.5 32.8 0 45.3l-160 160c-12.5 12.5-32.8 12.5-45.3 0s-12.5-32.8 0-45.3L179.2 256 41.9 118.6c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0l160 160z"/>
                                        </svg>
                                    </template>
                                    <template v-else>
                                        <span v-html="link.label"></span>
                                    </template>
                                </button>
                            </li>
                            <li v-else>
                                <Link :href="link.url"
                                    class="flex items-center justify-center w-9 h-9 rounded-full bg-[#E8EBF3] text-[#4A5E8D] text-sm font-medium transition-colors hover:bg-blue-600 hover:text-white dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-blue-600"
                                    :aria-label="`Ir a la página ${link.label}`">
                                    <template v-if="link.label.includes('Anterior') || link.label.includes('previous')">
                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 512">
                                            <path fill="currentColor" d="M9.4 233.4c-12.5 12.5-12.5 32.8 0 45.3l160 160c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L77.3 256 214.6 118.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0l-160 160z"/>
                                        </svg>
                                    </template>
                                    <template v-else-if="link.label.includes('Siguiente') || link.label.includes('Next')">
                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 256 512">
                                            <path fill="currentColor" d="M247.1 233.4c12.5 12.5 12.5 32.8 0 45.3l-160 160c-12.5 12.5-32.8 12.5-45.3 0s-12.5-32.8 0-45.3L179.2 256 41.9 118.6c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0l160 160z"/>
                                        </svg>
                                    </template>
                                    <template v-else>
                                        <span v-html="link.label"></span>
                                    </template>
                                </Link>
                            </li>
                        </template>
                        <li>
                            <Link v-if="data.current_page < data.last_page" :href="data.last_page_url"
                                class="flex items-center justify-center w-9 h-9 rounded-full bg-[#E8EBF3] text-[#4A5E8D] text-sm font-medium transition-colors hover:bg-blue-600 hover:text-white dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-blue-600"
                                aria-label="Ir a la última página">

                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
                                <path fill="currentColor" d="M439.1 278.6c12.5-12.5 12.5-32.8 0-45.3l-160-160c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L371.2 256 233.9 393.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0l160-160zm-352 160l160-160c12.5-12.5 12.5-32.8 0-45.3l-160-160c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L179.2 256 41.9 393.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0z"/>
                            </svg>
                            </Link>
                            <button v-else type="button"
                                    class="flex items-center justify-center w-9 h-9 rounded-full bg-[#F3F5F9] text-[#BDC5D8] text-sm cursor-not-allowed dark:bg-gray-800/50 dark:text-gray-600"
                                    disabled
                                    aria-label="Última página no disponible">
                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
                                <path fill="currentColor" d="M439.1 278.6c12.5-12.5 12.5-32.8 0-45.3l-160-160c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L371.2 256 233.9 393.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0l160-160zm-352 160l160-160c12.5-12.5 12.5-32.8 0-45.3l-160-160c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L179.2 256 41.9 393.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0z"/>
                            </svg>
                            </button>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </div>
</template>
