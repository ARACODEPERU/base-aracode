<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { faArrowsRotate, faImages } from '@fortawesome/free-solid-svg-icons';

const props = defineProps({
    pages: { type: Array, default: () => [] },
    budget: { type: Number, default: 1500000 },
    globalAssets: { type: Array, default: () => [] },
    globalAssetsBytes: { type: Number, default: 0 },
    totalBytes: { type: Number, default: 0 },
    overBudget: { type: Number, default: 0 },
});

const formatoBytes = (bytes) => {
    const valor = Number(bytes || 0);

    if (valor < 1024) return `${valor} B`;
    if (valor < 1024 * 1024) return `${(valor / 1024).toFixed(0)} KB`;

    return `${(valor / (1024 * 1024)).toFixed(2)} MB`;
};

const actualizar = () => {
    router.get(route('performance_weight_index'), { refresh: 1 }, { preserveScroll: true });
};
</script>

<template>
    <AppLayout title="Rendimiento · Peso por página">
        <Head title="Rendimiento · Peso por página">
            <meta name="aracodito-view" content="new" />
        </Head>

        <Navigation>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Rendimiento</span>
            </li>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Peso por página</span>
            </li>
        </Navigation>

        <div class="mt-5 space-y-5">
            <div class="panel flex flex-col gap-4 p-5 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Peso estimado por página</h2>
                    <p class="mt-1 max-w-3xl text-sm text-gray-500 dark:text-gray-400">
                        Suma las imágenes que aparecen en cada página más los recursos globales. Es una estimación
                        basada en las referencias del inventario, no una medición de red.
                    </p>
                </div>

                <div class="flex shrink-0 flex-wrap gap-2">
                    <Link :href="route('performance_images_index')" class="btn btn-outline-primary">
                        <font-awesome-icon :icon="faImages" class="mr-2" />
                        Ver imágenes
                    </Link>
                    <button type="button" class="btn btn-outline-secondary" @click="actualizar">
                        <font-awesome-icon :icon="faArrowsRotate" class="mr-2" />
                        Recalcular
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
                <div class="panel p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Presupuesto por página</p>
                    <p class="mt-2 text-2xl font-bold text-primary">{{ formatoBytes(budget) }}</p>
                    <p class="mt-1 text-xs text-gray-400">Configurable en el módulo</p>
                </div>
                <div class="panel p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Vistas analizadas</p>
                    <p class="mt-2 text-2xl font-bold text-gray-800 dark:text-white">{{ pages.length }}</p>
                    <p class="mt-1 text-xs text-gray-400">Páginas y componentes con imágenes</p>
                </div>
                <div class="panel p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Vistas sobre presupuesto</p>
                    <p class="mt-2 text-2xl font-bold" :class="overBudget ? 'text-danger' : 'text-success'">
                        {{ overBudget }}
                    </p>
                    <p class="mt-1 text-xs text-gray-400">Páginas que conviene aligerar</p>
                </div>
                <div class="panel p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Recursos globales</p>
                    <p class="mt-2 text-2xl font-bold text-warning">{{ formatoBytes(globalAssetsBytes) }}</p>
                    <p class="mt-1 text-xs text-gray-400">Se suman a cada página pública</p>
                </div>
            </div>

            <div class="panel overflow-hidden p-0">
                <div class="table-responsive">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800/60">
                                <th class="min-w-[260px]">Página</th>
                                <th class="w-32">Imágenes</th>
                                <th class="w-32">Recursos</th>
                                <th class="w-28">Total</th>
                                <th class="min-w-[200px]">Presupuesto</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="!pages.length">
                                <td colspan="5" class="py-10 text-center text-sm text-gray-500">
                                    Todavía no hay referencias de página. Genera el inventario desde la pantalla de imágenes.
                                </td>
                            </tr>
                            <tr v-for="page in pages" :key="page.key">
                                <td>
                                    <a
                                        v-if="page.url"
                                        :href="page.url"
                                        target="_blank"
                                        rel="noopener"
                                        class="font-medium text-primary hover:underline"
                                    >
                                        {{ page.label }}
                                    </a>
                                    <span v-else class="font-medium text-gray-700 dark:text-gray-200">{{ page.label }}</span>
                                    <p class="mt-0.5 text-xs text-gray-400">{{ page.url || 'Sin ruta pública detectada' }}</p>
                                    <p
                                        v-if="page.heaviest.length"
                                        class="mt-1 truncate text-xs text-gray-400"
                                        :title="page.heaviest.map((image) => `${image.name} (${formatoBytes(image.bytes)})`).join(' · ')"
                                    >
                                        Más pesadas:
                                        {{ page.heaviest.map((image) => image.name).join(' · ') }}
                                    </p>
                                </td>
                                <td class="text-gray-600 dark:text-gray-300">
                                    {{ page.images }}
                                    <span class="block text-xs text-gray-400">{{ formatoBytes(page.bytes) }}</span>
                                </td>
                                <td class="text-gray-600 dark:text-gray-300">{{ formatoBytes(page.assets) }}</td>
                                <td class="font-semibold" :class="page.over_budget ? 'text-danger' : 'text-gray-800 dark:text-gray-100'">
                                    {{ formatoBytes(page.total_bytes) }}
                                </td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                            <div
                                                class="h-full rounded-full"
                                                :class="page.over_budget ? 'bg-danger' : 'bg-success'"
                                                :style="{ width: `${page.percent}%` }"
                                            />
                                        </div>
                                        <span class="w-10 text-right text-xs" :class="page.over_budget ? 'text-danger' : 'text-gray-400'">
                                            {{ page.percent }}%
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-if="globalAssets.length" class="panel p-5">
                <h3 class="mb-2 text-sm font-semibold text-gray-800 dark:text-white">Recursos globales considerados</h3>
                <ul class="space-y-1 text-sm text-gray-600 dark:text-gray-300">
                    <li v-for="asset in globalAssets" :key="asset.path" class="flex justify-between gap-4">
                        <span class="break-all">{{ asset.path }}</span>
                        <span class="shrink-0 text-gray-400">{{ formatoBytes(asset.bytes) }}</span>
                    </li>
                </ul>
                <p class="mt-3 text-xs text-gray-400">
                    Las imágenes que solo aparecen en un CSS se contabilizan en cada página que usa ese CSS; por eso el
                    total puede ser mayor que el peso real en disco.
                </p>
            </div>
        </div>
    </AppLayout>
</template>
