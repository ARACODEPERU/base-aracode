<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';

/**
 * Auditoría del servidor MCP: cada llamada de un agente queda registrada con la
 * herramienta, el usuario del token, el resultado, el tiempo y la IP. Los
 * argumentos se muestran recortados (nunca el token).
 */
const props = defineProps({
    logs: { type: Object, default: () => ({ data: [], links: [] }) },
    filters: { type: Object, default: () => ({ tool: '', status: '', user_id: '' }) },
    tools: { type: Array, default: () => [] },
    summary: { type: Object, default: () => ({ total: 0, errors: 0, today: 0 }) },
});

const filtros = ref({
    tool: props.filters.tool || '',
    status: props.filters.status || '',
    user_id: props.filters.user_id || '',
});

let temporizador = null;

function aplicarFiltros() {
    clearTimeout(temporizador);
    temporizador = setTimeout(() => {
        router.get(
            route('aca_mcp_audit'),
            {
                tool: filtros.value.tool || undefined,
                status: filtros.value.status || undefined,
                user_id: filtros.value.user_id || undefined,
            },
            { preserveState: true, preserveScroll: true, replace: true }
        );
    }, 250);
}

watch(filtros, aplicarFiltros, { deep: true });

function limpiar() {
    filtros.value = { tool: '', status: '', user_id: '' };
}

function argumentos(log) {
    if (!log.arguments) {
        return '—';
    }

    const texto = Object.entries(log.arguments)
        .map(([clave, valor]) => {
            const contenido = typeof valor === 'object' ? JSON.stringify(valor) : String(valor);

            return `${clave}: ${contenido.length > 60 ? contenido.slice(0, 60) + '…' : contenido}`;
        })
        .join(' · ');

    return texto || '—';
}
</script>

<template>
    <AppLayout title="Auditoría IA">
        <Head>
            <meta name="aracodito-view" content="new" />
        </Head>

        <Navigation
            :routeModule="route('aca_dashboard')"
            :titleModule="'Académico'"
            :data="[{ title: 'Auditoría IA' }]"
        />

        <div class="mt-5 flex flex-col gap-6">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="panel">
                    <span class="text-xs uppercase text-gray-400">Llamadas registradas</span>
                    <p class="mt-1 text-2xl font-semibold dark:text-white-light">{{ summary.total }}</p>
                </div>
                <div class="panel">
                    <span class="text-xs uppercase text-gray-400">Hoy</span>
                    <p class="mt-1 text-2xl font-semibold dark:text-white-light">{{ summary.today }}</p>
                </div>
                <div class="panel">
                    <span class="text-xs uppercase text-gray-400">Con error</span>
                    <p class="mt-1 text-2xl font-semibold" :class="summary.errors > 0 ? 'text-danger' : 'text-success'">
                        {{ summary.errors }}
                    </p>
                </div>
            </div>

            <div class="panel">
                <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                    <div>
                        <h5 class="text-lg font-semibold dark:text-white-light">Llamadas de los agentes</h5>
                        <p class="mt-1 text-sm text-gray-500">
                            Cada herramienta del servidor MCP deja su rastro aquí. Un error repetido suele ser una
                            validación que el agente no está respetando.
                        </p>
                    </div>

                    <div class="flex flex-col gap-2 sm:flex-row">
                        <select v-model="filtros.tool" class="form-select">
                            <option value="">Todas las herramientas</option>
                            <option v-for="herramienta in tools" :key="herramienta" :value="herramienta">
                                {{ herramienta }}
                            </option>
                        </select>

                        <select v-model="filtros.status" class="form-select">
                            <option value="">Todos los estados</option>
                            <option value="ok">Correctas</option>
                            <option value="error">Con error</option>
                        </select>

                        <button type="button" class="btn btn-outline-primary" @click="limpiar">Limpiar</button>
                    </div>
                </div>

                <div v-if="logs.data.length === 0" class="mt-6 rounded border border-dashed border-gray-300 p-8 text-center dark:border-gray-700">
                    <p class="text-sm font-semibold dark:text-white-light">Sin llamadas para estos filtros</p>
                    <p class="mt-1 text-xs text-gray-500">Cuando un agente use el servidor MCP, la llamada aparecerá aquí.</p>
                </div>

                <div v-else class="mt-4 overflow-x-auto">
                    <table class="table-hover w-full text-sm">
                        <thead>
                            <tr>
                                <th class="text-left">Fecha</th>
                                <th class="text-left">Herramienta</th>
                                <th class="text-left">Usuario</th>
                                <th class="text-left">Estado</th>
                                <th class="text-left">Argumentos</th>
                                <th class="text-right">ms</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="log in logs.data" :key="log.id">
                                <td class="whitespace-nowrap text-gray-500">{{ log.created_at }}</td>
                                <td class="font-mono text-xs text-primary">{{ log.tool }}</td>
                                <td class="text-gray-500">{{ log.user }}</td>
                                <td>
                                    <span
                                        class="badge"
                                        :class="log.status === 'ok' ? 'bg-success/10 text-success' : 'bg-danger/10 text-danger'"
                                    >{{ log.status }}</span>
                                </td>
                                <td class="max-w-md truncate text-xs text-gray-500" :title="log.error || argumentos(log)">
                                    {{ argumentos(log) }}
                                </td>
                                <td class="text-right text-gray-500">{{ log.duration_ms }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="logs.links && logs.links.length > 3" class="mt-4 flex flex-wrap gap-1">
                    <template v-for="(link, indice) in logs.links" :key="indice">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            class="rounded px-3 py-1 text-sm"
                            :class="link.active ? 'bg-primary text-white' : 'bg-gray-100 text-gray-600 dark:bg-dark dark:text-gray-300'"
                            v-html="link.label"
                        />
                        <span v-else class="px-3 py-1 text-sm text-gray-400" v-html="link.label" />
                    </template>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
