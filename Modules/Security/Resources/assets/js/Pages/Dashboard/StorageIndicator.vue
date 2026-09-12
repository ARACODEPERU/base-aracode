<script setup>

    import { ref, onMounted, computed, watch } from 'vue';
    import apexchart from 'vue3-apexcharts';
    import axios from 'axios';
    import { faFolderOpen, faServer, faGlobe, faDatabase, faFileImage, faFilePdf, faFileAlt, faFileCode, faCog, faSync } from '@fortawesome/free-solid-svg-icons';

    const displayDiskBusy = ref(false);
    const showGraph = ref(false);
    const recalculating = ref(false);
    const donutChartSeries = ref([]);

    const metrics = ref({
        users: { total: 0, image: 0, pdf: 0, other: 0 },
        system: { total: 0, logs: 0, framework: 0, vendor: 0 },
        public_static: { total: 0 },
        database: { total: 0 },
        used: 0,
        quota: 0,
        used_percentage: 0,
        scanned_at: null
    });

    const GB = 1024 ** 3;

    const formatBytes = (bytes) => {
        if (!bytes || bytes <= 0) return '0 B';
        const units = ['B', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
        return `${(bytes / (1024 ** i)).toFixed(i >= 2 ? 2 : 0)} ${units[i]}`;
    };

    const formatScannedAt = (iso) => {
        if (!iso) return '';
        const d = new Date(iso);
        return d.toLocaleString('es-PE', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
    };

    // Porcentaje que representa una categoria respecto a la cuota configurada
    const pctOfQuota = (bytes) => {
        const quotaBytes = (metrics.value.quota ?? 0) * GB;
        return quotaBytes > 0 ? Math.min(100, (bytes / quotaBytes) * 100) : 0;
    };


    const getData = () => {
        displayDiskBusy.value = true;

        axios.get(route('security_storage_indicator')).then((response) => {
            return response.data;
        }).then((result) => {
            applyMetrics(result);
            return 1;
        }).then(() => {
            showGraph.value = true;
        }).catch((error) => {
            console.error('Error al obtener los datos:', error);
            displayDiskBusy.value = false;
        });
    };

    const recalculate = () => {
        recalculating.value = true;
        axios.post(route('security_storage_recalculate')).then((response) => {
            applyMetrics(response.data);
        }).catch((error) => {
            console.error('Error al recalcular:', error);
        }).finally(() => {
            recalculating.value = false;
        });
    };

    const applyMetrics = (result) => {
        metrics.value = {
            users: { total: 0, image: 0, pdf: 0, other: 0, ...(result.users ?? {}) },
            system: { total: 0, logs: 0, framework: 0, vendor: 0, ...(result.system ?? {}) },
            public_static: { total: 0, ...(result.public_static ?? {}) },
            database: { total: 0, ...(result.database ?? {}) },
            used: result.used ?? 0,
            quota: result.quota ?? 0,
            used_percentage: result.used_percentage ?? 0,
            scanned_at: result.scanned_at ?? null
        };
    };

    onMounted(() => {
        getData();
    });

    watch(metrics, () => {
        donutChartSeries.value = [
            metrics.value.users.total,
            metrics.value.system.total,
            metrics.value.public_static.total,
            metrics.value.database.total,
            Math.max(0, (metrics.value.quota * GB) - metrics.value.used)
        ];
        displayDiskBusy.value = false;
    });

    const donutChart = computed(() => {
        return {
            chart: {
                height: 300,
                type: 'donut',
                zoom: { enabled: false },
                toolbar: { show: false },
                animations: { enabled: true },
            },
            stroke: { show: false },
            labels: ['Archivos de usuarios', 'Archivos del sistema', 'Público estático', 'Base de datos', 'Disponible'],
            colors: ['#4361ee', '#e7515a', '#e2a03f', '#009688', '#ebedf2'],
            responsive: [
                {
                    breakpoint: 480,
                    options: { chart: { width: 200 } },
                },
            ],
            plotOptions: {
                pie: {
                    donut: {
                        size: '65%',
                        background: 'transparent',
                        labels: {
                            show: true,
                            name: {
                                show: true,
                                fontSize: '18px',
                                offsetY: -10,
                            },
                            value: {
                                show: true,
                                fontSize: '18px',
                                color: undefined,
                                offsetY: 14,
                                formatter: (val) => formatBytes(val),
                            },
                            total: {
                                show: true,
                                label: 'Usado',
                                color: '#888ea8',
                                fontSize: '18px',
                                formatter: (w) => {
                                    return formatBytes(w.globals.seriesTotals.reduce((a, b) => a + b, 0));
                                },
                            },
                        },
                    },
                },
            },
            legend: { position: 'top' },
            tooltip: {
                y: { formatter: (val) => formatBytes(val) },
            },
        };
    });

    // Tarjetas resumen (las 4 secciones de la medición)
    const summaryCards = computed(() => {
        const m = metrics.value;
        return [
            {
                label: 'Archivos de usuarios',
                value: m.users.total,
                pct: pctOfQuota(m.users.total),
                color: '#4361ee',
                icon: faFolderOpen,
                hint: 'Fotos, PDFs y documentos subidos por los clientes'
            },
            {
                label: 'Archivos del sistema',
                value: m.system.total,
                pct: pctOfQuota(m.system.total),
                color: '#e7515a',
                icon: faServer,
                hint: 'Logs, caché del framework y paquetes vendor'
            },
            {
                label: 'Público estático',
                value: m.public_static.total,
                pct: pctOfQuota(m.public_static.total),
                color: '#e2a03f',
                icon: faGlobe,
                hint: 'Themes, fuentes, builds de Vite y PDFs generados'
            },
            {
                label: 'Base de datos',
                value: m.database.total,
                pct: pctOfQuota(m.database.total),
                color: '#009688',
                icon: faDatabase,
                hint: 'Datos e índices MySQL (information_schema)'
            },
        ];
    });

</script>
<template>
    <div class="bg-white border border-gray-200 border-t-4 border-t-blue-600 shadow-2xs rounded-xl dark:bg-neutral-900 dark:border-neutral-700 dark:border-t-blue-500 dark:shadow-neutral-700/70">
        <div class="p-4 md:p-5">
            <template v-if="displayDiskBusy">
                <div class="flex items-center justify-between mb-5">
                    <div>
                        <div class="h-2.5 bg-gray-300 rounded-full dark:bg-gray-600 w-24 mb-2.5"></div>
                        <div class="w-32 h-2 bg-gray-200 rounded-full dark:bg-gray-700"></div>
                    </div>
                    <div class="h-2.5 bg-gray-300 rounded-full dark:bg-gray-700 w-12"></div>
                </div>
                <div class="grid grid-cols-2 gap-3 mb-5">
                    <div v-for="n in 4" :key="n" class="h-16 bg-gray-200 rounded-lg dark:bg-gray-700 animate-pulse"></div>
                </div>
                <div class="h-56 bg-gray-200 rounded-lg dark:bg-gray-700 animate-pulse"></div>
            </template>
            <template v-else>
                <!-- Encabezado -->
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h5 class="font-semibold text-lg dark:text-white-light">ALMACENAMIENTO</h5>
                        <small class="not-italic text-xs text-[#777]">
                            Proyecto completo
                            <template v-if="metrics.scanned_at"> · medido {{ formatScannedAt(metrics.scanned_at) }}</template>
                        </small>
                    </div>
                    <button
                        type="button"
                        class="btn btn-outline-primary btn-sm whitespace-nowrap"
                        :disabled="recalculating"
                        @click="recalculate"
                    >
                        <font-awesome-icon :icon="faSync" :spin="recalculating" class="w-3 h-3 mr-1" />
                        Recalcular
                    </button>
                </div>

                <!-- Tarjetas por sección -->
                <div class="grid grid-cols-2 gap-3 mb-5">
                    <div
                        v-for="card in summaryCards"
                        :key="card.label"
                        class="rounded-lg border border-gray-200 p-3 dark:border-neutral-700"
                        :title="card.hint"
                    >
                        <div class="flex items-center gap-2 mb-1.5">
                            <span class="w-7 h-7 grid place-content-center rounded-md" :style="{ backgroundColor: card.color + '1a', color: card.color }">
                                <font-awesome-icon :icon="card.icon" class="w-3.5 h-3.5" />
                            </span>
                            <span class="text-[11px] leading-tight text-[#777] font-medium">{{ card.label }}</span>
                        </div>
                        <div class="font-semibold text-sm dark:text-white-light">{{ formatBytes(card.value) }}</div>
                        <div class="w-full h-1.5 bg-[#ebedf2] dark:bg-dark/40 rounded-full mt-2">
                            <div class="h-1.5 rounded-full" :style="{ width: card.pct + '%', backgroundColor: card.color }"></div>
                        </div>
                        <small class="text-[10px] text-[#999]">{{ card.pct.toFixed(1) }}% de la cuota</small>
                    </div>
                </div>

                <!-- Donut -->
                <div class="flex items-center justify-center">
                    <apexchart
                        v-if="showGraph"
                        height="380"
                        :options="donutChart"
                        :series="donutChartSeries"
                        class="w-full bg-white dark:bg-black rounded-lg overflow-hidden"
                    >
                        <div class="min-h-[380px] grid place-content-center bg-white-light/30 dark:bg-dark dark:bg-opacity-[0.08]">
                            <span class="animate-spin border-2 border-black dark:border-white !border-l-transparent rounded-full w-5 h-5 inline-flex"></span>
                        </div>
                    </apexchart>
                </div>

                <!-- Barra de uso vs cuota -->
                <div class="space-y-1 mt-2">
                    <div class="flex justify-between items-center">
                        <span :class="metrics.used_percentage > 70.5 ? 'text-danger font-semibold' : 'text-primary font-semibold'">
                            usado {{ formatBytes(metrics.used) }} de {{ metrics.quota }} GB
                        </span>
                        <span class="text-xs text-[#777]">cuota configurable en Parámetros (PHD0001)</span>
                    </div>
                    <div class="w-full h-6 bg-[#ebedf2] dark:bg-dark/40 rounded-full">
                        <div
                            :style="`width: ${metrics.used_percentage}%;`"
                            class="h-6 rounded-full text-center text-white flex justify-between items-center px-2 text-xs"
                            :class="metrics.used_percentage > 70.5 ? 'bg-danger' : 'bg-success'"
                        >
                            <span v-if="metrics.used_percentage > 10.5">{{ metrics.used_percentage.toFixed(2) }}%</span>
                        </div>
                    </div>
                </div>

                <!-- Desglose detallado -->
                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-neutral-700 text-xs space-y-3">
                    <!-- Archivos de usuarios -->
                    <div>
                        <div class="flex items-center gap-1.5 mb-1.5 font-semibold dark:text-white-light">
                            <span class="w-2 h-2 rounded-full inline-block" style="background-color: #4361ee"></span>
                            Archivos de usuarios
                            <span class="font-normal text-[#999] ml-auto">{{ formatBytes(metrics.users.total) }}</span>
                        </div>
                        <div class="pl-3.5 space-y-1">
                            <div class="flex justify-between items-center">
                                <span class="text-[#777]"><font-awesome-icon :icon="faFileImage" class="w-3 h-3 mr-1 opacity-60" />Imágenes</span>
                                <span class="font-semibold dark:text-white-light">{{ formatBytes(metrics.users.image) }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-[#777]"><font-awesome-icon :icon="faFilePdf" class="w-3 h-3 mr-1 opacity-60" />Documentos PDF</span>
                                <span class="font-semibold dark:text-white-light">{{ formatBytes(metrics.users.pdf) }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-[#777]"><font-awesome-icon :icon="faFileAlt" class="w-3 h-3 mr-1 opacity-60" />Otros archivos</span>
                                <span class="font-semibold dark:text-white-light">{{ formatBytes(metrics.users.other) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Archivos del sistema -->
                    <div>
                        <div class="flex items-center gap-1.5 mb-1.5 font-semibold dark:text-white-light">
                            <span class="w-2 h-2 rounded-full inline-block" style="background-color: #e7515a"></span>
                            Archivos del sistema
                            <span class="font-normal text-[#999] ml-auto">{{ formatBytes(metrics.system.total) }}</span>
                        </div>
                        <div class="pl-3.5 space-y-1">
                            <div class="flex justify-between items-center">
                                <span class="text-[#777]"><font-awesome-icon :icon="faFileAlt" class="w-3 h-3 mr-1 opacity-60" />Logs (laravel.log, etc.)</span>
                                <span class="font-semibold dark:text-white-light">{{ formatBytes(metrics.system.logs) }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-[#777]"><font-awesome-icon :icon="faCog" class="w-3 h-3 mr-1 opacity-60" />Framework (caché, sesiones)</span>
                                <span class="font-semibold dark:text-white-light">{{ formatBytes(metrics.system.framework) }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-[#777]"><font-awesome-icon :icon="faFileCode" class="w-3 h-3 mr-1 opacity-60" />Vendor (paquetes composer)</span>
                                <span class="font-semibold dark:text-white-light">{{ formatBytes(metrics.system.vendor) }}</span>
                            </div>
                        </div>
                    </div>

                </div>
            </template>
        </div>
    </div>
</template>
