<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import {
    faRobot,
    faGauge,
    faCoins,
    faComments,
    faChartSimple,
    faTriangleExclamation,
    faCircleCheck,
    faCircleXmark,
} from '@fortawesome/free-solid-svg-icons';

const props = defineProps({
    stats: { type: Object, default: () => ({}) },
    byProvider: { type: Array, default: () => [] },
    recent: { type: Array, default: () => [] },
    budget: { type: Number, default: 0 },
    dailyLimit: { type: Number, default: 0 },
    provider: { type: String, default: 'gemini' },
    openAiModel: { type: String, default: '' },
    geminiModel: { type: String, default: '' },
    openAiConfigured: { type: Boolean, default: false },
    geminiConfigured: { type: Boolean, default: false },
    hasTable: { type: Boolean, default: true },
    budgetState: { type: Object, default: () => ({ level: 'ok', budget: 0, spend: 0, percent: null, remaining: 0, hard_stop: true }) },
    budgetAlerts: { type: Array, default: () => [] },
});

const número = (valor) => new Intl.NumberFormat('es-PE').format(Number(valor || 0));

const usd = (valor) => {
    const numero = Number(valor || 0);

    return `US$ ${numero.toFixed(numero > 0 && numero < 0.01 ? 6 : 2)}`;
};

const proveedorActivo = computed(() => (props.provider === 'openai' ? 'OpenAI' : 'Gemini AI'));
const modeloActivo = computed(() => (props.provider === 'openai' ? props.openAiModel : props.geminiModel));
const configurado = computed(() => (props.provider === 'openai' ? props.openAiConfigured : props.geminiConfigured));

const porcentajePresupuesto = computed(() => {
    if (!props.budget || props.budget <= 0) {
        return null;
    }

    return Math.min(100, Math.round((Number(props.stats.spend_month || 0) / props.budget) * 100));
});

const claseEstado = (estado) => (estado === 'error' ? 'bg-danger/20 text-danger' : 'bg-success/20 text-success');
</script>

<template>
    <AppLayout title="IA">
        <Head title="IA">
            <meta name="aracodito-view" content="new" />
        </Head>

        <Navigation :routeModule="route('ai_dashboard')" :titleModule="'IA'">
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Dashboard</span>
            </li>
        </Navigation>

        <div class="mt-5 space-y-5">
            <div
                v-if="!hasTable"
                class="panel border-l-4 border-warning p-4 text-sm text-gray-600 dark:text-gray-300"
            >
                <font-awesome-icon :icon="faTriangleExclamation" class="mr-1 text-warning" />
                Falta ejecutar las migraciones del módulo de IA. Ejecuta
                <code class="mx-1 rounded bg-gray-100 px-1 dark:bg-gray-800">php artisan module:migrate Ai</code>
                para habilitar las estadísticas.
            </div>

            <div
                v-if="budgetState?.level === 'warning'"
                class="panel border-l-4 border-warning p-4 text-sm text-gray-700 dark:text-gray-200"
            >
                <font-awesome-icon :icon="faTriangleExclamation" class="mr-1 text-warning" />
                El gasto de IA llegó al <strong>{{ budgetState.percent }}%</strong> del presupuesto mensual.
                Quedan {{ usd(budgetState.remaining) }}. Revisa el consumo o amplía el presupuesto en Configuración.
            </div>

            <div
                v-if="budgetState?.level === 'exceeded'"
                class="panel border-l-4 border-danger p-4 text-sm text-gray-700 dark:text-gray-200"
            >
                <font-awesome-icon :icon="faTriangleExclamation" class="mr-1 text-danger" />
                Se superó el presupuesto mensual de IA ({{ usd(budgetState.spend) }} de {{ usd(budgetState.budget) }}).
                <template v-if="budgetState.hard_stop">Las consultas están cortadas hasta que un administrador amplíe el presupuesto.</template>
                <template v-else>Las consultas siguen activas porque el corte automático está desactivado.</template>
            </div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
                <div class="panel bg-gradient-to-br from-primary to-primary-dark text-white">
                    <div class="flex items-start justify-between p-5">
                        <div>
                            <p class="text-sm opacity-80">Gasto del mes</p>
                            <p class="mt-2 text-2xl font-bold">{{ usd(stats.spend_month) }}</p>
                            <p class="mt-1 text-xs opacity-80">
                                <template v-if="budget > 0">Presupuesto: {{ usd(budget) }}</template>
                                <template v-else>Sin presupuesto definido</template>
                            </p>
                        </div>
                        <div class="rounded-full bg-white/20 p-3">
                            <font-awesome-icon :icon="faCoins" class="text-xl" />
                        </div>
                    </div>
                </div>

                <div class="panel">
                    <div class="flex items-start justify-between p-5">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Consultas del mes</p>
                            <p class="mt-2 text-2xl font-bold">{{ número(stats.requests_month) }}</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Hoy: {{ número(stats.requests_today) }}
                                <template v-if="dailyLimit > 0"> · límite diario {{ número(dailyLimit) }}</template>
                            </p>
                        </div>
                        <div class="rounded-full bg-info/10 p-3 text-info">
                            <font-awesome-icon :icon="faComments" class="text-xl" />
                        </div>
                    </div>
                </div>

                <div class="panel">
                    <div class="flex items-start justify-between p-5">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Tokens del mes</p>
                            <p class="mt-2 text-2xl font-bold">{{ número(stats.tokens_month) }}</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Entrada + salida</p>
                        </div>
                        <div class="rounded-full bg-success/10 p-3 text-success">
                            <font-awesome-icon :icon="faChartSimple" class="text-xl" />
                        </div>
                    </div>
                </div>

                <div class="panel">
                    <div class="flex items-start justify-between p-5">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Errores del mes</p>
                            <p class="mt-2 text-2xl font-bold">{{ número(stats.errors_month) }}</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Consultas que no respondieron</p>
                        </div>
                        <div class="rounded-full bg-danger/10 p-3 text-danger">
                            <font-awesome-icon :icon="faTriangleExclamation" class="text-xl" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
                <div class="panel lg:col-span-1">
                    <div class="border-b border-gray-200 p-5 dark:border-gray-700">
                        <div class="flex items-center gap-2">
                            <font-awesome-icon :icon="faRobot" class="text-primary" />
                            <h2 class="text-lg font-semibold">Proveedor activo</h2>
                        </div>
                    </div>
                    <div class="space-y-4 p-5">
                        <div>
                            <p class="text-xs uppercase text-gray-500 dark:text-gray-400">Proveedor</p>
                            <p class="text-base font-semibold">{{ proveedorActivo }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase text-gray-500 dark:text-gray-400">Modelo</p>
                            <p class="break-words font-mono text-sm">{{ modeloActivo || 'Sin definir' }}</p>
                        </div>
                        <div class="flex items-center gap-2 text-sm">
                            <font-awesome-icon
                                :icon="configurado ? faCircleCheck : faCircleXmark"
                                :class="configurado ? 'text-success' : 'text-danger'"
                            />
                            {{ configurado ? 'Credencial configurada' : 'Falta la credencial del proveedor' }}
                        </div>
                        <div v-if="porcentajePresupuesto !== null" class="space-y-1">
                            <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                                <span>Uso del presupuesto</span>
                                <span>{{ porcentajePresupuesto }}%</span>
                            </div>
                            <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                <div
                                    class="h-full rounded-full"
                                    :class="porcentajePresupuesto >= 90 ? 'bg-danger' : 'bg-primary'"
                                    :style="{ width: `${porcentajePresupuesto}%` }"
                                ></div>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2 pt-2">
                            <Link :href="route('ai_configuracion')" class="btn btn-sm btn-primary">Configurar</Link>
                            <Link :href="route('ai_probar')" class="btn btn-sm btn-outline-primary">Probar IA</Link>
                        </div>
                    </div>
                </div>

                <div class="panel lg:col-span-2">
                    <div class="flex items-center justify-between border-b border-gray-200 p-5 dark:border-gray-700">
                        <div class="flex items-center gap-2">
                            <font-awesome-icon :icon="faGauge" class="text-primary" />
                            <h2 class="text-lg font-semibold">Consumo por proveedor</h2>
                        </div>
                        <Link :href="route('ai_consumo')" class="text-sm text-primary hover:underline">
                            Ver todo
                        </Link>
                    </div>
                    <div class="table-responsive">
                        <table class="table-hover w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 text-left dark:border-gray-700">
                                    <th class="p-4 font-semibold">Proveedor</th>
                                    <th class="p-4 font-semibold">Consultas</th>
                                    <th class="p-4 font-semibold">Tokens</th>
                                    <th class="p-4 font-semibold">Costo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="fila in byProvider" :key="fila.provider" class="border-b border-gray-100 dark:border-gray-800">
                                    <td class="p-4 capitalize">{{ fila.provider }}</td>
                                    <td class="p-4">{{ número(fila.requests) }}</td>
                                    <td class="p-4">{{ número(fila.tokens) }}</td>
                                    <td class="p-4">{{ usd(fila.cost) }}</td>
                                </tr>
                                <tr v-if="!byProvider.length">
                                    <td colspan="4" class="p-6 text-center text-gray-500 dark:text-gray-400">
                                        Todavía no hay consultas registradas este mes.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div v-if="budgetAlerts.length" class="panel">
                <div class="border-b border-gray-200 p-5 dark:border-gray-700">
                    <h2 class="text-lg font-semibold">Alertas de presupuesto</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Una sola alerta por mes y nivel.</p>
                </div>
                <ul class="divide-y divide-gray-100 text-sm dark:divide-gray-800">
                    <li v-for="alerta in budgetAlerts" :key="alerta.period + alerta.level" class="flex flex-wrap items-center justify-between gap-2 p-4">
                        <div>
                            <p class="font-medium">
                                {{ alerta.level === 'exceeded' ? 'Presupuesto excedido' : 'Aviso de presupuesto' }}
                                <span class="text-xs text-gray-500 dark:text-gray-400">· {{ alerta.period }}</span>
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Gasto {{ usd(alerta.spend) }} de {{ usd(alerta.budget) }} · aviso al {{ alerta.notified_at }}
                            </p>
                        </div>
                        <span
                            class="rounded px-2 py-1 text-xs"
                            :class="alerta.level === 'exceeded' ? 'bg-danger/20 text-danger' : 'bg-warning/20 text-warning'"
                        >
                            {{ alerta.level === 'exceeded' ? 'Excedido' : 'Aviso' }}
                        </span>
                    </li>
                </ul>
            </div>

            <div class="panel">
                <div class="flex items-center justify-between border-b border-gray-200 p-5 dark:border-gray-700">
                    <h2 class="text-lg font-semibold">Últimas consultas</h2>
                    <span class="text-xs text-gray-500 dark:text-gray-400">El texto de la consulta no se guarda por defecto</span>
                </div>
                <div class="table-responsive">
                    <table class="table-hover w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 text-left dark:border-gray-700">
                                <th class="p-4 font-semibold">Fecha</th>
                                <th class="p-4 font-semibold">Usuario</th>
                                <th class="p-4 font-semibold">Proveedor / modelo</th>
                                <th class="p-4 font-semibold">Pantalla</th>
                                <th class="p-4 text-right font-semibold">Tokens</th>
                                <th class="p-4 text-right font-semibold">Costo</th>
                                <th class="p-4 font-semibold">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="fila in recent" :key="fila.id" class="border-b border-gray-100 dark:border-gray-800">
                                <td class="p-4 whitespace-nowrap">{{ fila.fecha }}</td>
                                <td class="p-4">{{ fila.usuario }}</td>
                                <td class="p-4">
                                    <span class="capitalize">{{ fila.proveedor }}</span>
                                    <span class="block text-xs text-gray-500 dark:text-gray-400">{{ fila.modelo }}</span>
                                </td>
                                <td class="p-4 text-xs text-gray-500 dark:text-gray-400">{{ fila.pantalla || '—' }}</td>
                                <td class="p-4 text-right">{{ número(fila.tokens) }}</td>
                                <td class="p-4 text-right">{{ usd(fila.costo) }}</td>
                                <td class="p-4">
                                    <span class="rounded px-2 py-1 text-xs" :class="claseEstado(fila.estado)">
                                        {{ fila.estado === 'error' ? 'Error' : 'Correcta' }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="!recent.length">
                                <td colspan="7" class="p-6 text-center text-gray-500 dark:text-gray-400">
                                    Sin consultas registradas. Puedes probar la conexión desde «Probar IA».
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
