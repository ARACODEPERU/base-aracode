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
                    <ul class="flex items-center space-x-2">
                        <li>
                            <Link v-if="data.current_page > 1" :href="data.first_page_url"
                                class="flex justify-center items-center px-3 py-2 font-bold rounded-lg transition-all duration-300 bg-blue-500 text-white hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700"
                                aria-label="Ir a la primera página">
                                <font-awesome-icon :icon="faStepBackward" class="w-4 h-4" />
                            </Link>
                            <button v-else type="button"
                                    class="flex justify-center items-center px-3 py-2 font-bold rounded-lg bg-gray-300 text-gray-500 cursor-not-allowed dark:bg-gray-600 dark:text-gray-400"
                                    disabled
                                    aria-label="Primera página no disponible">
                                <font-awesome-icon :icon="faStepBackward" class="w-4 h-4" />
                            </button>
                        </li>
                        <template v-for="(link, key) in data.links" :key="key">
                            <template v-if="key > 0 && key < data.last_page + 1">
                                <li v-if="!link.active && link.url === null">
                                    <button type="button"
                                            class="flex justify-center items-center px-3 py-2 font-bold rounded-lg transition-all duration-300 bg-gray-200 text-gray-400 cursor-not-allowed dark:bg-gray-600 dark:text-gray-500"
                                            disabled>
                                        <template v-if="link.label.includes('Next')">
                                            <font-awesome-icon :icon="faArrowRight" class="w-4 h-4" />
                                        </template>
                                        <template v-else>
                                            <span v-html="link.label"></span>
                                        </template>
                                    </button>
                                </li>
                                <li v-else-if="link.active">
                                    <button type="button"
                                            class="flex justify-center items-center px-4 py-2 font-bold rounded-lg bg-indigo-600 text-white dark:bg-indigo-500"
                                            aria-current="page">
                                        {{ link.label }}
                                    </button>
                                </li>
                                <li v-else>
                                    <Link :href="link.url"
                                        class="flex justify-center items-center px-3 py-2 font-bold rounded-lg transition-all duration-300 bg-gray-100 text-gray-700 hover:bg-indigo-500 hover:text-white focus:outline-none focus:ring-2 focus:ring-indigo-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-indigo-600 dark:hover:text-white"
                                        :aria-label="`Ir a la página ${link.label}`">
                                        <template v-if="link.label.includes('Next')">
                                            <font-awesome-icon :icon="faArrowRight" class="w-4 h-4" />
                                        </template>
                                        <template v-else>
                                            <span v-html="link.label"></span>
                                        </template>
                                    </Link>
                                </li>
                            </template>
                        </template>
                        <li>
                            <Link v-if="data.current_page < data.last_page" :href="data.last_page_url"
                                class="flex justify-center items-center px-3 py-2 font-bold rounded-lg transition-all duration-300 bg-blue-500 text-white hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700"
                                aria-label="Ir a la última página">
                                <font-awesome-icon :icon="faStepForward" class="w-4 h-4" />
                            </Link>
                            <button v-else type="button"
                                    class="flex justify-center items-center px-3 py-2 font-bold rounded-lg bg-gray-300 text-gray-500 cursor-not-allowed dark:bg-gray-600 dark:text-gray-400"
                                    disabled
                                    aria-label="Última página no disponible">
                                <font-awesome-icon :icon="faStepForward" class="w-4 h-4" />
                            </button>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </div>
</template>
