<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import { Head, router, useForm, usePage } from '@inertiajs/vue3';
    import ModalMedium from '@/Components/ModalMedium.vue';
    import InputLabel from '@/Components/InputLabel.vue';
    import InputError from '@/Components/InputError.vue';
    import PrimaryButton from '@/Components/PrimaryButton.vue';
    import { ref, computed } from 'vue';
    import apexchart from 'vue3-apexcharts';
    import { useAppStore } from '@/stores/index';
    import {
        faPlus,
        faTriangleExclamation,
        faTrash,
        faFileExcel,
        faFilePdf,
    } from '@fortawesome/free-solid-svg-icons';
    import {
        construirMetaPrevision,
        composicionPrevision,
        exportarPrevisionExcel,
        exportarPrevisionPdf,
    } from '../../Utils/treasuryForecastExport';

    const props = defineProps({
        forecast: { type: Object, required: true },
        currencies: { type: Array, default: () => ['PEN'] },
        adjustments: { type: Array, default: () => [] },
        filters: { type: Object, default: () => ({}) },
    });

    const money = (value, currency = 'PEN') => {
        try {
            return new Intl.NumberFormat('es-PE', { style: 'currency', currency }).format(value || 0);
        } catch (e) {
            return `${currency} ${Number(value || 0).toFixed(2)}`;
        }
    };

    // Las fechas se muestran como en el resto del ERP: dd/mm/aaaa.
    const fecha = (value) => {
        if (!value) return '—';

        const [year, month, day] = String(value).split('-');

        return year && month && day ? `${day}/${month}/${year}` : value;
    };

    const query = ref({
        currency: props.filters.currency || 'PEN',
        days: props.filters.days || 30,
        ratio: props.filters.ratio ?? 1,
    });

    const applyFilters = () => {
        router.get(route('treasury_forecast_index'), query.value, { preserveScroll: true, preserveState: true });
    };

    const store = useAppStore();
    const modoOscuro = computed(() => store.theme === 'dark' || store.isDarkMode);
    const usuario = computed(() => usePage().props.auth?.user);

    // Gris medio: se lee igual en pantalla clara, oscura y en el PDF exportado.
    const colorTexto = computed(() => '#64748b');
    const colorGrilla = computed(() => '#cbd5e1');

    // Eje compacto (1,3 K) para que quepa el importe sin apretar el gráfico.
    const montoCompacto = (valor) => {
        try {
            return new Intl.NumberFormat('es-PE', { notation: 'compact', maximumFractionDigits: 1 }).format(valor || 0);
        } catch (e) {
            return String(valor || 0);
        }
    };

    const maxFlow = computed(() =>
        Math.max(1, ...props.forecast.rows.map((row) => Math.max(row.inflows, row.outflows)))
    );

    const barWidth = (value) => Math.round((Number(value || 0) / maxFlow.value) * 100);

    const notifyFlash = (page) => {
        const flash = page?.props?.flash || {};
        if (flash.error) window.alertaSistema('No se pudo completar', flash.error, 'error');
        else if (flash.success) window.alertaSistema('Listo', flash.success, 'success');
    };

    const modal = ref(false);
    const form = useForm({
        date: new Date().toISOString().slice(0, 10),
        type: 'expense',
        currency: props.filters.currency || 'PEN',
        amount: '',
        concept: '',
    });

    const openModal = () => {
        form.clearErrors();
        form.date = new Date().toISOString().slice(0, 10);
        form.currency = props.filters.currency || 'PEN';
        form.amount = '';
        form.concept = '';
        modal.value = true;
    };

    const save = () => {
        form.post(route('treasury_forecast_adjustments_store'), {
            preserveScroll: true,
            onSuccess: (page) => {
                notifyFlash(page);
                modal.value = false;
            },
            onError: () => window.alertaSistema('Revisa el formulario', 'Hay campos obligatorios sin completar.', 'warning'),
        });
    };

    const remove = async (item) => {
        const result = await window.alertaSistema(
            'Quitar ítem',
            `Se quitará «${item.concept}» de la previsión.`,
            'warning',
            { duration: 0, showCancelButton: true, confirmButtonText: 'Sí, quitar', cancelButtonText: 'Cancelar' }
        );

        if (!result?.isConfirmed) return;

        router.delete(route('treasury_forecast_adjustments_destroy', item.id), {
            preserveScroll: true,
            onSuccess: notifyFlash,
        });
    };

    const typeLabel = (type) => (type === 'income' ? 'Ingreso' : 'Egreso');

    /**
     * Flujo para el gráfico: con muchos días se agrupa por semana para que
     * las etiquetas sigan siendo legibles.
     */
    const filasGrafico = computed(() => {
        const filas = props.forecast.rows || [];

        if (props.forecast.days <= 31) {
            return filas.map((row) => ({
                label: row.label,
                inflows: Number(row.inflows || 0),
                outflows: Number(row.outflows || 0),
                balance: Number(row.balance || 0),
            }));
        }

        const semanas = [];

        filas.forEach((row, indice) => {
            const posicion = Math.floor(indice / 7);

            if (!semanas[posicion]) {
                semanas[posicion] = { label: row.label, inflows: 0, outflows: 0, balance: 0 };
            }

            semanas[posicion].inflows += Number(row.inflows || 0);
            semanas[posicion].outflows += Number(row.outflows || 0);
            semanas[posicion].balance = Number(row.balance || 0);
        });

        return semanas.map((semana) => ({
            ...semana,
            inflows: Math.round(semana.inflows * 100) / 100,
            outflows: Math.round(semana.outflows * 100) / 100,
        }));
    });

    const seriesFlujo = computed(() => [
        { name: 'Entradas', type: 'column', data: filasGrafico.value.map((fila) => fila.inflows) },
        { name: 'Salidas', type: 'column', data: filasGrafico.value.map((fila) => fila.outflows) },
        { name: 'Saldo proyectado', type: 'line', data: filasGrafico.value.map((fila) => fila.balance) },
    ]);

    const opcionesFlujo = computed(() => ({
        chart: { type: 'line', height: 320, toolbar: { show: false }, background: 'transparent' },
        colors: ['#10b981', '#ef4444', '#4361ee'],
        stroke: { curve: 'smooth', width: [0, 0, 3] },
        plotOptions: { bar: { columnWidth: '55%' } },
        dataLabels: { enabled: false },
        grid: { borderColor: colorGrilla.value },
        xaxis: {
            categories: filasGrafico.value.map((fila) => fila.label),
            labels: { rotate: 0, hideOverlappingLabels: true, style: { colors: colorTexto.value } },
        },
        yaxis: { labels: { style: { colors: colorTexto.value }, formatter: montoCompacto } },
        legend: { labels: { colors: colorTexto.value } },
        tooltip: { theme: modoOscuro.value ? 'dark' : 'light' },
    }));

    const composicion = computed(() => composicionPrevision(props.forecast));

    const seriesComposicion = computed(() => [
        { name: 'Salidas', data: composicion.value.salidas.map((fila) => fila.total) },
    ]);

    const opcionesComposicion = computed(() => ({
        chart: { type: 'bar', height: 320, toolbar: { show: false }, background: 'transparent' },
        plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '60%' } },
        colors: ['#4361ee'],
        dataLabels: { enabled: false },
        grid: { borderColor: colorGrilla.value },
        xaxis: {
            categories: composicion.value.salidas.map((fila) => fila.label),
            labels: { style: { colors: colorTexto.value }, formatter: montoCompacto },
        },
        yaxis: { labels: { style: { colors: colorTexto.value } } },
        tooltip: { theme: modoOscuro.value ? 'dark' : 'light' },
    }));

    // Referencias a los gráficos: sus imágenes van dentro del Excel y del PDF.
    const chartFlujo = ref(null);
    const chartComposicion = ref(null);

    const imagenGrafico = async (referencia) => {
        try {
            const chart = referencia.value?.chart;

            if (!chart) return null;

            const { imgURI } = await chart.dataURI();

            return imgURI;
        } catch (e) {
            return null;
        }
    };

    const exportar = async (formato) => {
        try {
            const charts = {
                flujo: await imagenGrafico(chartFlujo),
                composicion: await imagenGrafico(chartComposicion),
            };
            const meta = construirMetaPrevision({ user: usuario.value, forecast: props.forecast });

            if (formato === 'excel') {
                await exportarPrevisionExcel({ forecast: props.forecast, meta, charts });
            } else {
                exportarPrevisionPdf({ forecast: props.forecast, meta, charts });
            }

            window.alertaSistema(
                formato === 'excel' ? 'Excel generado' : 'PDF generado',
                'La previsión incluye sus gráficas y el detalle del flujo.',
                'success'
            );
        } catch (e) {
            window.alertaSistema('No se pudo exportar', 'Vuelve a intentarlo en unos segundos.', 'error');
        }
    };
</script>

<template>
    <AppLayout title="Previsión de caja">
        <Head title="Tesorería · Previsión">
            <meta name="aracodito-view" content="new" />
        </Head>
        <Navigation>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2"><span>Tesorería</span></li>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2"><span>Previsión</span></li>
        </Navigation>

        <div class="mt-5 space-y-5">
            <!-- Filtros -->
            <div class="panel flex flex-col gap-4 p-5 lg:flex-row lg:items-end lg:justify-between">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Moneda</label>
                        <select v-model="query.currency" class="form-select w-full sm:w-40">
                            <option v-for="code in currencies" :key="code" :value="code">{{ code }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Horizonte</label>
                        <select v-model="query.days" class="form-select w-full sm:w-40">
                            <option :value="30">30 días</option>
                            <option :value="60">60 días</option>
                            <option :value="90">90 días</option>
                            <option :value="180">180 días</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Cobranza esperada</label>
                        <select v-model.number="query.ratio" class="form-select w-full sm:w-40">
                            <option :value="1">100 %</option>
                            <option :value="0.9">90 %</option>
                            <option :value="0.75">75 %</option>
                            <option :value="0.5">50 %</option>
                        </select>
                    </div>
                    <button type="button" class="btn btn-primary" @click="applyFilters">Aplicar</button>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <p class="max-w-md text-xs text-gray-500 dark:text-gray-400">
                        Estimación, no saldo real. Usa cobranza pendiente de Ventas, órdenes aprobadas, gastos recurrentes y ajustes manuales.
                    </p>
                    <div class="flex gap-2">
                        <button type="button" class="btn btn-outline-primary" @click="exportar('excel')">
                            <font-awesome-icon :icon="faFileExcel" class="mr-1" /> Excel
                        </button>
                        <button type="button" class="btn btn-outline-danger" @click="exportar('pdf')">
                            <font-awesome-icon :icon="faFilePdf" class="mr-1" /> PDF
                        </button>
                    </div>
                </div>
            </div>

            <!-- Avisos -->
            <div v-if="forecast.warnings.length" class="panel border-l-4 border-warning p-4">
                <p class="flex items-center gap-2 text-sm font-semibold text-warning">
                    <font-awesome-icon :icon="faTriangleExclamation" /> Atención
                </p>
                <ul class="mt-2 list-disc pl-6 text-sm text-gray-600 dark:text-gray-300">
                    <li v-for="warning in forecast.warnings" :key="warning">{{ warning }}</li>
                </ul>
            </div>

            <!-- Resumen -->
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
                <div class="panel p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Saldo inicial</p>
                    <p class="mt-2 text-2xl font-bold text-gray-800 dark:text-gray-100">{{ money(forecast.opening_balance, forecast.currency) }}</p>
                </div>
                <div class="panel p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Entradas estimadas</p>
                    <p class="mt-2 text-2xl font-bold text-success">{{ money(forecast.totals.inflows, forecast.currency) }}</p>
                </div>
                <div class="panel p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Salidas comprometidas</p>
                    <p class="mt-2 text-2xl font-bold text-danger">{{ money(forecast.totals.outflows, forecast.currency) }}</p>
                </div>
                <div class="panel bg-gradient-to-br from-primary to-primary-dark p-5 text-white">
                    <p class="text-sm opacity-80">Saldo proyectado al {{ fecha(forecast.to) }}</p>
                    <p class="mt-2 text-2xl font-bold">{{ money(forecast.totals.closing, forecast.currency) }}</p>
                    <p class="mt-1 text-xs opacity-80">Mínimo: {{ money(forecast.min_balance, forecast.currency) }} ({{ fecha(forecast.min_date) }})</p>
                </div>
            </div>

            <!-- Gráficas de la previsión -->
            <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
                <div class="panel p-5">
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Flujo proyectado</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Entradas, salidas y saldo acumulado · {{ forecast.days <= 31 ? 'por día' : 'por semana' }}
                    </p>
                    <div class="mt-4">
                        <apexchart
                            ref="chartFlujo"
                            type="line"
                            height="320"
                            :options="opcionesFlujo"
                            :series="seriesFlujo"
                        />
                    </div>
                </div>

                <div class="panel p-5">
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Salidas por tipo</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Qué compromisos explican el egreso esperado
                    </p>
                    <div v-if="composicion.salidas.length" class="mt-4">
                        <apexchart
                            ref="chartComposicion"
                            type="bar"
                            height="320"
                            :options="opcionesComposicion"
                            :series="seriesComposicion"
                        />
                    </div>
                    <p v-else class="py-10 text-center text-sm text-gray-500">
                        Sin salidas comprometidas en el horizonte.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">
                <!-- Flujo diario -->
                <div class="panel overflow-hidden p-0 xl:col-span-2">
                    <div class="border-b border-gray-200 p-5 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Flujo proyectado</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Movimientos por día y saldo acumulado</p>
                    </div>
                    <div class="table-responsive max-h-[520px] overflow-y-auto">
                        <table class="min-w-full">
                            <thead class="sticky top-0 bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th>Fecha</th>
                                    <th class="min-w-[200px]">Detalle</th>
                                    <th class="text-right">Entradas</th>
                                    <th class="text-right">Salidas</th>
                                    <th class="text-right">Saldo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in forecast.rows" :key="row.date" class="align-top">
                                    <td class="whitespace-nowrap text-sm">{{ row.label }}</td>
                                    <td>
                                        <p v-for="item in row.items.slice(0, 3)" :key="item.concept + item.amount" class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ item.concept }}
                                        </p>
                                        <p v-if="row.items.length > 3" class="text-xs text-gray-400">+{{ row.items.length - 3 }} más</p>
                                        <span v-if="row.items.length === 0" class="text-xs text-gray-300">—</span>
                                    </td>
                                    <td class="text-right text-sm text-success">
                                        <template v-if="row.inflows > 0">{{ money(row.inflows, forecast.currency) }}</template>
                                        <span v-else class="text-gray-300">—</span>
                                        <div v-if="row.inflows > 0" class="mt-1 h-1.5 w-full rounded bg-success/10">
                                            <div class="h-1.5 rounded bg-success" :style="{ width: barWidth(row.inflows) + '%' }"></div>
                                        </div>
                                    </td>
                                    <td class="text-right text-sm text-danger">
                                        <template v-if="row.outflows > 0">{{ money(row.outflows, forecast.currency) }}</template>
                                        <span v-else class="text-gray-300">—</span>
                                        <div v-if="row.outflows > 0" class="mt-1 h-1.5 w-full rounded bg-danger/10">
                                            <div class="h-1.5 rounded bg-danger" :style="{ width: barWidth(row.outflows) + '%' }"></div>
                                        </div>
                                    </td>
                                    <td class="text-right">
                                        <span class="font-semibold" :class="row.balance < 0 ? 'text-danger' : 'text-gray-800 dark:text-gray-100'">
                                            {{ money(row.balance, forecast.currency) }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Ajustes -->
                <div class="panel p-5">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Ajustes manuales</h2>
                        <button type="button" class="btn btn-sm btn-outline-primary" @click="openModal">
                            <font-awesome-icon :icon="faPlus" />
                        </button>
                    </div>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Impuestos, gratificaciones, CTS o compromisos sin orden de pago.
                    </p>

                    <div class="mt-4 space-y-3">
                        <div v-for="item in adjustments" :key="item.id" class="flex items-start justify-between rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                            <div>
                                <p class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ item.concept }}</p>
                                <p class="text-xs text-gray-500">{{ item.date }} · {{ typeLabel(item.type) }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-semibold">{{ money(item.amount, forecast.currency) }}</p>
                                <button type="button" class="text-xs text-danger" @click="remove(item)">
                                    <font-awesome-icon :icon="faTrash" /> Quitar
                                </button>
                            </div>
                        </div>
                        <p v-if="adjustments.length === 0" class="py-6 text-center text-sm text-gray-500">
                            Sin ajustes para esta moneda.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <ModalMedium :show="modal" :on-close="() => (modal = false)" :icon="'/img/cuenta-bancaria.png'">
            <template #title>Nuevo ajuste de previsión</template>
            <template #message><span>Se sumará al flujo del día indicado</span></template>
            <template #content>
                <div class="space-y-4">
                    <div>
                        <InputLabel value="Concepto *" class="mb-1.5" />
                        <input v-model="form.concept" type="text" class="form-input w-full" placeholder="Ej: Pago de IGV" />
                        <InputError :message="form.errors.concept" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Fecha *" class="mb-1.5" />
                            <input v-model="form.date" type="date" class="form-input w-full" />
                            <InputError :message="form.errors.date" />
                        </div>
                        <div>
                            <InputLabel value="Tipo" class="mb-1.5" />
                            <select v-model="form.type" class="form-select w-full">
                                <option value="expense">Egreso</option>
                                <option value="income">Ingreso</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel value="Moneda" class="mb-1.5" />
                            <select v-model="form.currency" class="form-select w-full">
                                <option value="PEN">PEN</option>
                                <option value="USD">USD</option>
                            </select>
                        </div>
                        <div>
                            <InputLabel value="Monto *" class="mb-1.5" />
                            <input v-model="form.amount" type="number" min="0.01" step="0.01" class="form-input w-full" />
                            <InputError :message="form.errors.amount" />
                        </div>
                    </div>
                </div>
            </template>
            <template #buttons>
                <PrimaryButton :disabled="form.processing" @click="save">Agregar</PrimaryButton>
            </template>
        </ModalMedium>
    </AppLayout>
</template>
