<script setup>
import { reactive } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import { faFilter, faRotateLeft } from '@fortawesome/free-solid-svg-icons';

const props = defineProps({
    usages: { type: Object, default: () => ({ data: [], links: [], total: 0 }) },
    totals: { type: Object, default: () => ({ requests: 0, tokens: 0, cost: 0 }) },
    filters: { type: Object, default: () => ({}) },
    users: { type: Array, default: () => [] },
    sinTabla: { type: Boolean, default: false },
});

const form = reactive({
    user_id: props.filters.user_id || '',
    provider: props.filters.provider || '',
    desde: props.filters.desde || '',
    hasta: props.filters.hasta || '',
});

const número = (valor) => new Intl.NumberFormat('es-PE').format(Number(valor || 0));

const usd = (valor) => {
    const numero = Number(valor || 0);

    return `US$ ${numero.toFixed(numero > 0 && numero < 0.01 ? 6 : 2)}`;
};

const filtrar = () => {
    router.get(route('ai_consumo'), { ...form }, { preserveState: true, replace: true });
};

const limpiar = () => {
    form.user_id = '';
    form.provider = '';
    form.desde = '';
    form.hasta = '';
    filtrar();
};

const exportUrl = (name) => route(name, { ...form });

const etiqueta = (label) =>
    String(label || '')
        .replace('&laquo;', '«')
        .replace('&raquo;', '»')
        .replace('Previous', 'Anterior')
        .replace('Next', 'Siguiente');
</script>

<template>
    <AppLayout title="Consumo de IA">
        <Head title="Consumo de IA">
            <meta name="aracodito-view" content="new" />
        </Head>

        <Navigation :routeModule="route('ai_dashboard')" :titleModule="'IA'">
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Consumo</span>
            </li>
        </Navigation>

        <div class="mt-5 space-y-5">
            <div
                v-if="sinTabla"
                class="panel border-l-4 border-warning p-4 text-sm text-gray-600 dark:text-gray-300"
            >
                Ejecuta <code class="mx-1 rounded bg-gray-100 px-1 dark:bg-gray-800">php artisan module:migrate Ai</code>
                para crear la tabla de consumo.
            </div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                <div class="panel p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Consultas</p>
                    <p class="mt-2 text-2xl font-bold">{{ número(totals.requests) }}</p>
                </div>
                <div class="panel p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Tokens</p>
                    <p class="mt-2 text-2xl font-bold">{{ número(totals.tokens) }}</p>
                </div>
                <div class="panel p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Costo estimado</p>
                    <p class="mt-2 text-2xl font-bold text-primary">{{ usd(totals.cost) }}</p>
                </div>
            </div>

            <div class="panel">
                <div class="flex flex-col gap-2 border-b border-gray-200 p-5 dark:border-gray-700 sm:flex-row sm:items-center sm:justify-between">
                    <h2 class="text-lg font-semibold">Filtros</h2>
                    <div class="flex flex-wrap gap-2">
                        <a :href="exportUrl('ai_consumo_exportar')" class="btn btn-sm btn-outline-primary">
                            Exportar CSV
                        </a>
                        <a :href="exportUrl('ai_consumo_pdf')" class="btn btn-sm btn-outline-primary">
                            Exportar PDF
                        </a>
                    </div>
                </div>
                <form class="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2 lg:grid-cols-5" @submit.prevent="filtrar">
                    <div>
                        <label class="mb-1 block text-xs font-medium">Usuario</label>
                        <select v-model="form.user_id" class="form-select w-full">
                            <option value="">Todos</option>
                            <option v-for="user in users" :key="user.id" :value="user.id">{{ user.nombre }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium">Proveedor</label>
                        <select v-model="form.provider" class="form-select w-full">
                            <option value="">Todos</option>
                            <option value="openai">OpenAI</option>
                            <option value="gemini">Gemini AI</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium">Desde</label>
                        <input v-model="form.desde" type="date" class="form-input w-full" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium">Hasta</label>
                        <input v-model="form.hasta" type="date" class="form-input w-full" />
                    </div>
                    <div class="flex items-end gap-2">
                        <button type="submit" class="btn btn-primary">
                            <font-awesome-icon :icon="faFilter" class="mr-1" /> Filtrar
                        </button>
                        <button type="button" class="btn btn-outline-primary" @click="limpiar">
                            <font-awesome-icon :icon="faRotateLeft" class="mr-1" /> Limpiar
                        </button>
                    </div>
                </form>
            </div>

            <div class="panel">
                <div class="table-responsive">
                    <table class="table-hover w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 text-left dark:border-gray-700">
                                <th class="p-4 font-semibold">Fecha</th>
                                <th class="p-4 font-semibold">Usuario</th>
                                <th class="p-4 font-semibold">Proveedor / modelo</th>
                                <th class="p-4 font-semibold">Pantalla</th>
                                <th class="p-4 text-right font-semibold">Entrada</th>
                                <th class="p-4 text-right font-semibold">Salida</th>
                                <th class="p-4 text-right font-semibold">Total</th>
                                <th class="p-4 text-right font-semibold">Costo</th>
                                <th class="p-4 font-semibold">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="fila in usages.data" :key="fila.id" class="border-b border-gray-100 dark:border-gray-800">
                                <td class="p-4 whitespace-nowrap">{{ fila.fecha }}</td>
                                <td class="p-4">{{ fila.usuario }}</td>
                                <td class="p-4">
                                    <span class="capitalize">{{ fila.proveedor }}</span>
                                    <span class="block text-xs text-gray-500 dark:text-gray-400">{{ fila.modelo }}</span>
                                </td>
                                <td class="p-4 text-xs text-gray-500 dark:text-gray-400">{{ fila.pantalla || '—' }}</td>
                                <td class="p-4 text-right">{{ número(fila.prompt_tokens) }}</td>
                                <td class="p-4 text-right">{{ número(fila.completion_tokens) }}</td>
                                <td class="p-4 text-right">{{ número(fila.total_tokens) }}</td>
                                <td class="p-4 text-right">{{ usd(fila.costo) }}</td>
                                <td class="p-4">
                                    <span
                                        class="rounded px-2 py-1 text-xs"
                                        :class="fila.estado === 'error' ? 'bg-danger/20 text-danger' : 'bg-success/20 text-success'"
                                    >
                                        {{ fila.estado === 'error' ? 'Error' : 'Correcta' }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="!usages.data.length">
                                <td colspan="9" class="p-6 text-center text-gray-500 dark:text-gray-400">
                                    No hay consultas con esos filtros.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="usages.links?.length > 3" class="flex flex-wrap gap-1 border-t border-gray-200 p-4 dark:border-gray-700">
                    <template v-for="(link, index) in usages.links" :key="index">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            preserve-scroll
                            class="rounded px-3 py-1 text-sm"
                            :class="link.active ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-primary/10 dark:bg-gray-800 dark:text-gray-200'"
                        >
                            {{ etiqueta(link.label) }}
                        </Link>
                        <span
                            v-else
                            class="cursor-not-allowed rounded px-3 py-1 text-sm text-gray-400 dark:text-gray-500"
                        >
                            {{ etiqueta(link.label) }}
                        </span>
                    </template>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
