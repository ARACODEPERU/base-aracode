<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import AyudaTip from '../../Components/AyudaTip.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import {
    faBug,
    faCircleCheck,
    faCircleInfo,
    faCircleXmark,
    faClock,
    faCopy,
    faImages,
    faLayerGroup,
    faListCheck,
    faRotate,
    faTerminal,
    faTriangleExclamation,
} from '@fortawesome/free-solid-svg-icons';

const props = defineProps({
    report: {
        type: Object,
        default: () => ({
            connection: { name: '', driver: '', queue: '', checks: [], ok: true },
            pending: { total: 0, reserved: 0, queues: [], oldest_minutes: null },
            failed: { total: 0, last_24h: 0, last_7d: 0, items: [] },
            log: { queue_errors: [], entries: [], errors: 0, warnings: 0, file: null },
            job_classes: { count: 0, names: [] },
            status: { level: 'idle', title: 'Sin datos', detail: '' },
            checked_at: '',
            stale_minutes: 15,
        }),
    },
});

const estilos = {
    ok: {
        caja: 'border-success/40 bg-success/5',
        icono: 'text-success',
        badge: 'bg-success/15 text-success',
        etiqueta: 'Funcionando',
    },
    warning: {
        caja: 'border-warning/40 bg-warning/5',
        icono: 'text-warning',
        badge: 'bg-warning/15 text-warning',
        etiqueta: 'Revisar',
    },
    error: {
        caja: 'border-danger/40 bg-danger/5',
        icono: 'text-danger',
        badge: 'bg-danger/15 text-danger',
        etiqueta: 'Con errores',
    },
    info: {
        caja: 'border-primary/40 bg-primary/5',
        icono: 'text-primary',
        badge: 'bg-primary/15 text-primary',
        etiqueta: 'Informativo',
    },
    idle: {
        caja: 'border-gray-300 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/40',
        icono: 'text-gray-400',
        badge: 'bg-gray-200 text-gray-500 dark:bg-gray-700 dark:text-gray-300',
        etiqueta: 'Sin actividad',
    },
};

const estilo = computed(() => estilos[props.report.status.level] || estilos.idle);

const iconoEstado = computed(() => {
    if (props.report.status.level === 'ok') return faCircleCheck;
    if (props.report.status.level === 'error') return faCircleXmark;
    if (props.report.status.level === 'warning') return faTriangleExclamation;

    return faCircleInfo;
});

const comandos = [
    'php artisan queue:work --once',
    'php artisan queue:monitor database:default --max=100',
    'php artisan queue:failed',
    'php artisan queue:retry all',
];

const formatoBytes = (bytes) => {
    const valor = Number(bytes || 0);

    if (valor < 1024) return `${valor} B`;
    if (valor < 1024 * 1024) return `${(valor / 1024).toFixed(0)} KB`;

    return `${(valor / (1024 * 1024)).toFixed(2)} MB`;
};

const minutos = (valor) => {
    if (valor === null || valor === undefined) return '—';
    if (valor < 60) return `${valor} min`;

    const horas = Math.floor(valor / 60);

    return horas < 24 ? `${horas} h` : `${Math.floor(horas / 24)} d`;
};

const actualizar = () => {
    router.get(route('performance_queue_index'), { refresh: 1 }, { preserveScroll: true });
};

const copiar = async (texto) => {
    try {
        await navigator.clipboard.writeText(texto);

        if (typeof window.alertaSistema === 'function') {
            window.alertaSistema('Copiado', 'El comando se copió al portapapeles.', 'success');
        }
    } catch (error) {
        if (typeof window.alertaSistema === 'function') {
            window.alertaSistema('No se pudo copiar', 'Selecciona el texto y cópialo manualmente.', 'warning');
        }
    }
};
</script>

<template>
    <AppLayout title="Rendimiento · Colas y errores">
        <Head title="Rendimiento · Colas y errores">
            <meta name="aracodito-view" content="new" />
        </Head>

        <Navigation>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Rendimiento</span>
            </li>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2">
                <span>Colas y errores</span>
            </li>
        </Navigation>

        <div class="mt-5 space-y-5">
            <div class="panel flex flex-col gap-4 p-5 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Colas y errores</h2>
                    <p class="mt-1 max-w-3xl text-sm text-gray-500 dark:text-gray-400">
                        Diagnóstico de solo lectura para programadores: muestra la conexión de cola, los trabajos
                        pendientes, los fallidos y los errores recientes del log. No ejecuta, reintenta ni borra nada.
                    </p>
                </div>

                <div class="flex shrink-0 flex-wrap gap-2">
                    <Link :href="route('performance_images_index')" class="btn btn-outline-primary">
                        <font-awesome-icon :icon="faImages" class="mr-2" />
                        Imágenes
                    </Link>
                    <span class="group relative inline-flex">
                        <button type="button" class="btn btn-outline-secondary" @click="actualizar">
                            <font-awesome-icon :icon="faRotate" class="mr-2" />
                            Volver a comprobar
                        </button>
                        <AyudaTip texto="Vuelve a leer las tablas de cola y el log. No modifica ni reintenta ningún trabajo." />
                    </span>
                </div>
            </div>

            <div class="panel border p-5" :class="estilo.caja">
                <div class="flex flex-wrap items-start gap-4">
                    <font-awesome-icon :icon="iconoEstado" class="mt-1 text-2xl" :class="estilo.icono" />
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-base font-semibold text-gray-800 dark:text-white">
                                {{ report.status.title }}
                            </h3>
                            <span class="rounded px-2 py-0.5 text-[11px] font-semibold uppercase" :class="estilo.badge">
                                {{ estilo.etiqueta }}
                            </span>
                        </div>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ report.status.detail }}</p>
                        <p class="mt-2 text-xs text-gray-400">Comprobado: {{ report.checked_at }}</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
                <div class="panel p-5" title="Conexión por defecto de config/queue.php">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Conexión</p>
                    <p class="mt-2 text-2xl font-bold text-gray-800 dark:text-white">{{ report.connection.name }}</p>
                    <p class="mt-1 text-xs text-gray-400">Driver: {{ report.connection.driver }}</p>
                </div>
                <div class="panel p-5" title="Trabajos esperando a que un worker los tome">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Pendientes</p>
                    <p class="mt-2 text-2xl font-bold" :class="report.pending.total ? 'text-warning' : 'text-success'">
                        {{ report.pending.total }}
                    </p>
                    <p class="mt-1 text-xs text-gray-400">
                        Más antiguo: {{ minutos(report.pending.oldest_minutes) }}
                    </p>
                </div>
                <div class="panel p-5" title="Trabajos tomados por un worker en este momento">
                    <p class="text-sm text-gray-500 dark:text-gray-400">En proceso</p>
                    <p class="mt-2 text-2xl font-bold text-primary">{{ report.pending.reserved }}</p>
                    <p class="mt-1 text-xs text-gray-400">Reservados por un worker</p>
                </div>
                <div class="panel p-5" title="Trabajos que lanzaron una excepción y quedaron en failed_jobs">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Fallidos (24 h)</p>
                    <p class="mt-2 text-2xl font-bold" :class="report.failed.last_24h ? 'text-danger' : 'text-success'">
                        {{ report.failed.last_24h }}
                    </p>
                    <p class="mt-1 text-xs text-gray-400">{{ report.failed.total }} en total</p>
                </div>
            </div>

            <div class="panel p-5">
                <h3 class="mb-3 flex items-center gap-2 text-sm font-semibold text-gray-800 dark:text-white">
                    <font-awesome-icon :icon="faLayerGroup" class="text-primary" />
                    Conexión y comprobaciones
                </h3>

                <div class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
                    <div>
                        <p class="text-xs uppercase text-gray-400">Conexión por defecto</p>
                        <p class="font-medium text-gray-700 dark:text-gray-200">
                            {{ report.connection.name }} ({{ report.connection.driver }})
                        </p>
                    </div>
                    <div>
                        <p class="text-xs uppercase text-gray-400">Cola por defecto</p>
                        <p class="font-medium text-gray-700 dark:text-gray-200">{{ report.connection.queue }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase text-gray-400">Reintento tras</p>
                        <p class="font-medium text-gray-700 dark:text-gray-200">
                            {{ report.connection.retry_after ? `${report.connection.retry_after} s` : '—' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs uppercase text-gray-400">Registro de fallidos</p>
                        <p class="font-medium text-gray-700 dark:text-gray-200">{{ report.connection.failed_driver }}</p>
                    </div>
                </div>

                <ul class="mt-4 space-y-2">
                    <li v-for="check in report.connection.checks" :key="check.label" class="flex items-start gap-2 text-sm">
                        <font-awesome-icon
                            :icon="check.ok ? faCircleCheck : faCircleXmark"
                            class="mt-0.5 shrink-0"
                            :class="check.ok ? 'text-success' : 'text-danger'"
                        />
                        <span class="text-gray-700 dark:text-gray-200">
                            {{ check.label }}
                            <span class="text-gray-400">· {{ check.detail }}</span>
                        </span>
                    </li>
                </ul>
            </div>

            <div class="panel overflow-hidden p-0">
                <div class="border-b border-gray-200 p-4 dark:border-gray-700">
                    <h3 class="flex items-center gap-2 text-sm font-semibold text-gray-800 dark:text-white">
                        <font-awesome-icon :icon="faListCheck" class="text-primary" />
                        Trabajos pendientes por cola
                    </h3>
                </div>
                <div class="table-responsive">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800/60">
                                <th class="min-w-[160px]">Cola</th>
                                <th class="w-28">Esperando</th>
                                <th class="w-28">En proceso</th>
                                <th class="w-32">Más antiguo</th>
                                <th class="w-28">Intentos</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="!report.pending.queues.length">
                                <td colspan="5" class="py-8 text-center text-sm text-gray-500">
                                    No hay trabajos esperando en ninguna cola.
                                </td>
                            </tr>
                            <tr v-for="cola in report.pending.queues" :key="cola.queue">
                                <td class="font-medium text-gray-700 dark:text-gray-200">{{ cola.queue }}</td>
                                <td class="text-gray-600 dark:text-gray-300">{{ cola.waiting }}</td>
                                <td class="text-gray-600 dark:text-gray-300">{{ cola.reserved }}</td>
                                <td :class="cola.oldest_minutes >= report.stale_minutes ? 'text-danger' : 'text-gray-600 dark:text-gray-300'">
                                    {{ minutos(cola.oldest_minutes) }}
                                </td>
                                <td class="text-gray-600 dark:text-gray-300">{{ cola.attempts }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="panel overflow-hidden p-0">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-200 p-4 dark:border-gray-700">
                    <h3 class="flex items-center gap-2 text-sm font-semibold text-gray-800 dark:text-white">
                        <font-awesome-icon :icon="faBug" class="text-danger" />
                        Trabajos fallidos
                    </h3>
                    <p class="text-xs text-gray-400">
                        {{ report.failed.total }} en total · {{ report.failed.last_7d }} en 7 días
                    </p>
                </div>
                <div class="table-responsive">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800/60">
                                <th class="w-36">Fecha</th>
                                <th class="w-28">Cola</th>
                                <th class="min-w-[220px]">Trabajo</th>
                                <th class="min-w-[280px]">Error</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="!report.failed.items.length">
                                <td colspan="4" class="py-8 text-center text-sm text-gray-500">
                                    No hay trabajos fallidos registrados.
                                </td>
                            </tr>
                            <tr v-for="item in report.failed.items" :key="item.uuid || item.id">
                                <td class="whitespace-nowrap text-gray-600 dark:text-gray-300">{{ item.failed_at || '—' }}</td>
                                <td class="text-gray-600 dark:text-gray-300">{{ item.queue || '—' }}</td>
                                <td class="font-medium text-gray-700 dark:text-gray-200">{{ item.job }}</td>
                                <td class="text-xs text-danger" :title="item.exception">{{ item.exception }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="panel p-5">
                <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                    <h3 class="flex items-center gap-2 text-sm font-semibold text-gray-800 dark:text-white">
                        <font-awesome-icon :icon="faClock" class="text-warning" />
                        Errores recientes en el log
                    </h3>
                    <p class="text-xs text-gray-400">
                        <template v-if="report.log.file">
                            {{ report.log.file }} · {{ formatoBytes(report.log.bytes) }} · analizados los últimos
                            {{ formatoBytes(report.log.scanned_bytes) }} · {{ report.log.errors }} error(es),
                            {{ report.log.warnings }} aviso(s) en 24 h
                        </template>
                        <template v-else>Sin archivo de log</template>
                    </p>
                </div>

                <ul v-if="report.log.entries.length" class="space-y-2">
                    <li
                        v-for="(entrada, index) in report.log.entries"
                        :key="index"
                        class="rounded-lg border border-gray-200 p-3 text-xs dark:border-gray-700"
                    >
                        <div class="mb-1 flex flex-wrap items-center gap-2">
                            <span
                                class="rounded px-1.5 py-0.5 font-semibold uppercase"
                                :class="entrada.level === 'ERROR' ? 'bg-warning/15 text-warning' : 'bg-danger/15 text-danger'"
                            >
                                {{ entrada.level }}
                            </span>
                            <span class="text-gray-400">{{ entrada.at }}</span>
                            <span
                                v-if="entrada.related"
                                class="rounded bg-primary/10 px-1.5 py-0.5 font-semibold uppercase text-primary"
                            >
                                Cola
                            </span>
                        </div>
                        <p class="break-words text-gray-600 dark:text-gray-300">{{ entrada.message }}</p>
                    </li>
                </ul>
                <p v-else class="text-sm text-gray-500">Sin errores registrados en el log reciente.</p>
            </div>

            <div class="panel p-5">
                <h3 class="mb-2 flex items-center gap-2 text-sm font-semibold text-gray-800 dark:text-white">
                    <font-awesome-icon :icon="faLayerGroup" class="text-primary" />
                    Clases encolables del proyecto
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ report.job_classes.count }} clase(s) en <code>app/Jobs</code> y <code>Modules/*/Jobs</code>.
                    Es solo un inventario: no indica que estén en ejecución.
                </p>
                <div v-if="report.job_classes.names.length" class="mt-3 flex flex-wrap gap-1.5">
                    <span
                        v-for="nombre in report.job_classes.names"
                        :key="nombre"
                        class="rounded bg-gray-100 px-2 py-1 text-xs text-gray-600 dark:bg-gray-800 dark:text-gray-300"
                    >
                        {{ nombre }}
                    </span>
                </div>
            </div>

            <div class="panel p-5">
                <h3 class="mb-2 flex items-center gap-2 text-sm font-semibold text-gray-800 dark:text-white">
                    <font-awesome-icon :icon="faTerminal" class="text-primary" />
                    Comandos útiles en el servidor
                </h3>
                <p class="mb-3 text-sm text-gray-500 dark:text-gray-400">
                    Para comprobar el worker desde la consola. La pantalla no los ejecuta.
                </p>
                <ul class="space-y-2">
                    <li
                        v-for="comando in comandos"
                        :key="comando"
                        class="flex flex-wrap items-center justify-between gap-2 rounded-lg bg-gray-900 px-3 py-2"
                    >
                        <code class="break-all text-xs text-green-300">{{ comando }}</code>
                        <button type="button" class="btn btn-sm btn-outline-secondary" @click="copiar(comando)">
                            <font-awesome-icon :icon="faCopy" />
                        </button>
                    </li>
                </ul>
            </div>

            <p class="text-xs text-gray-400">
                El estado del worker se deduce de los trabajos reservados y de su antigüedad: desde la web no se puede
                ver el proceso del sistema. Si la cola está en modo síncrono no existe worker que comprobar.
            </p>
        </div>
    </AppLayout>
</template>
