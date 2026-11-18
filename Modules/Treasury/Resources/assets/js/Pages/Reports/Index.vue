<script setup>
    import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
    import Navigation from '@/Components/vristo/layout/Navigation.vue';
    import { Head, router, useForm, usePage } from '@inertiajs/vue3';
    import ModalMedium from '@/Components/ModalMedium.vue';
    import InputLabel from '@/Components/InputLabel.vue';
    import InputError from '@/Components/InputError.vue';
    import PrimaryButton from '@/Components/PrimaryButton.vue';
    import { computed, ref } from 'vue';
    import apexchart from 'vue3-apexcharts';
    import { useAppStore } from '@/stores/index';
    import { faPlus, faPen, faBan, faFileExcel, faFilePdf } from '@fortawesome/free-solid-svg-icons';
    import {
        exportarReporteExcel,
        exportarReportePdf,
        construirMetaReporte,
        formatFechaReporte,
    } from '../../Utils/treasuryReportExport';

    const props = defineProps({
        position: { type: Object, required: true },
        flows: { type: Object, required: true },
        byCostCenter: { type: Array, default: () => [] },
        totalsByCurrency: { type: Array, default: () => [] },
        costCenters: { type: Array, default: () => [] },
        currencies: { type: Array, default: () => ['PEN'] },
        filters: { type: Object, default: () => ({}) },
        projection: { type: Object, required: true },
        budgets: { type: Object, default: () => ({ month: '', label: '', rows: [], totals: [], over: 0, warning: 0 }) },
        budgetOptions: { type: Object, default: () => ({ costCenters: [], categories: [] }) },
        canManageCenters: { type: Boolean, default: false },
        canManageBudgets: { type: Boolean, default: false },
    });

    const money = (value, currency = 'PEN') => {
        try {
            return new Intl.NumberFormat('es-PE', { style: 'currency', currency }).format(value || 0);
        } catch (e) {
            return `${currency} ${Number(value || 0).toFixed(2)}`;
        }
    };

    const store = useAppStore();
    const modoOscuro = computed(() => store.theme === 'dark' || store.isDarkMode);

    const hoy = new Date();
    const isoFecha = (fecha) =>
        `${fecha.getFullYear()}-${String(fecha.getMonth() + 1).padStart(2, '0')}-${String(fecha.getDate()).padStart(2, '0')}`;
    const hoyIso = isoFecha(hoy);

    const nombreMes = (yyyyMM) => {
        const [year, month] = String(yyyyMM).split('-').map(Number);
        const texto = new Date(year, (month || 1) - 1, 1).toLocaleDateString('es-PE', {
            month: 'long',
            year: 'numeric',
        });

        return texto.charAt(0).toUpperCase() + texto.slice(1);
    };

    // Periodos rápidos: rango libre, mes completo, mes en curso, año completo y año en curso.
    const preset = ref(props.filters.preset || 'custom');
    const mes = ref(props.filters.month || hoyIso.slice(0, 7));
    const anio = ref(props.filters.year || hoy.getFullYear());

    const query = ref({
        from: props.filters.from,
        to: props.filters.to,
        currency: props.filters.currency,
        cost_center_id: props.filters.cost_center_id || null,
        days: props.filters.days || 90,
    });

    // El rango que se envía al servidor se deriva del preset elegido.
    const rango = computed(() => {
        if (preset.value === 'month') {
            const [year, month] = mes.value.split('-').map(Number);

            return { from: `${mes.value}-01`, to: isoFecha(new Date(year, month, 0)) };
        }

        if (preset.value === 'month_current') {
            return { from: `${hoyIso.slice(0, 7)}-01`, to: hoyIso };
        }

        if (preset.value === 'year_current') {
            return { from: `${hoy.getFullYear()}-01-01`, to: hoyIso };
        }

        if (preset.value === 'year') {
            return { from: `${anio.value}-01-01`, to: `${anio.value}-12-31` };
        }

        return { from: query.value.from, to: query.value.to };
    });

    const etiquetaPeriodo = computed(() => {
        if (preset.value === 'month') return `Mes completo · ${nombreMes(mes.value)}`;
        if (preset.value === 'month_current') return 'Mes en curso';
        if (preset.value === 'year_current') return `Año en curso ${hoy.getFullYear()}`;
        if (preset.value === 'year') return `Año completo ${anio.value}`;

        return 'Rango personalizado';
    });

    const alCambiarFecha = () => {
        preset.value = 'custom';
    };

    const apply = () => {
        router.get(
            route('treasury_reports_index'),
            {
                ...query.value,
                ...rango.value,
                preset: preset.value,
                month: mes.value,
                year: anio.value,
            },
            { preserveScroll: true, preserveState: true }
        );
    };

    // ---------------- Gráficos ----------------
    const formatoCompacto = (valor) =>
        new Intl.NumberFormat('es-PE', { notation: 'compact', maximumFractionDigits: 1 }).format(valor || 0);

    const categoriasFlujo = computed(() => [...new Set(props.flows.rows.map((row) => row.category))]);

    const seriesCategorias = computed(() => {
        const suma = (categoria, tipo) =>
            props.flows.rows
                .filter((row) => row.category === categoria && row.type === tipo)
                .reduce((total, row) => total + Number(row.total || 0), 0);

        return [
            { name: 'Ingresos', data: categoriasFlujo.value.map((categoria) => suma(categoria, 'income')) },
            { name: 'Egresos', data: categoriasFlujo.value.map((categoria) => suma(categoria, 'expense')) },
        ];
    });

    const opcionesCategorias = computed(() => ({
        chart: { type: 'bar', height: 300, toolbar: { show: false }, background: 'transparent' },
        plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '55%' } },
        colors: ['#10b981', '#ef4444'],
        dataLabels: { enabled: false },
        xaxis: { categories: categoriasFlujo.value, labels: { formatter: (valor) => formatoCompacto(valor) } },
        theme: { mode: modoOscuro.value ? 'dark' : 'light' },
        legend: { position: 'top' },
        tooltip: { y: { formatter: (valor) => money(valor, props.flows.currency) } },
    }));

    // El gráfico usa el resumen mensual: con 90 días, una barra por día no se lee.
    const seriesProyeccion = computed(() => {
        const filas = resumenProyeccion.value;

        return [
            { name: 'Ingresos esperados', type: 'column', data: filas.map((fila) => fila.inflows) },
            { name: 'Egresos proyectados', type: 'column', data: filas.map((fila) => fila.outflows) },
            { name: 'Saldo al cierre del mes', type: 'line', data: filas.map((fila) => fila.balance) },
        ];
    });

    const opcionesProyeccion = computed(() => ({
        chart: { type: 'line', height: 320, toolbar: { show: false }, background: 'transparent' },
        colors: ['#10b981', '#ef4444', '#4361ee'],
        stroke: { curve: 'smooth', width: [0, 0, 3] },
        plotOptions: { bar: { columnWidth: '55%' } },
        dataLabels: { enabled: false },
        xaxis: {
            categories: resumenProyeccion.value.map((fila) => fila.label),
            labels: { rotate: 0, hideOverlappingLabels: true },
        },
        yaxis: { labels: { formatter: (valor) => formatoCompacto(valor) } },
        theme: { mode: modoOscuro.value ? 'dark' : 'light' },
        legend: { position: 'top' },
        tooltip: { shared: true, y: { formatter: (valor) => money(valor, props.projection.currency) } },
    }));

    // Resumen mensual de la proyección: se muestra en pantalla y va en los archivos.
    const resumenProyeccion = computed(() => {
        const meses = {};

        (props.projection.rows || []).forEach((fila) => {
            const clave = String(fila.date).slice(0, 7);

            meses[clave] ??= {
                month: clave,
                label: nombreMes(clave),
                inflows: 0,
                outflows: 0,
                net: 0,
                balance: fila.balance,
            };

            meses[clave].inflows += Number(fila.inflows || 0);
            meses[clave].outflows += Number(fila.outflows || 0);
            meses[clave].net += Number(fila.net || 0);
            meses[clave].balance = fila.balance;
        });

        const redondear = (valor) => Math.round((Number(valor) || 0) * 100) / 100;

        return Object.values(meses).map((fila) => ({
            ...fila,
            inflows: redondear(fila.inflows),
            outflows: redondear(fila.outflows),
            net: redondear(fila.net),
            balance: redondear(fila.balance),
        }));
    });

    const notifyFlash = (page) => {
        const flash = page?.props?.flash || {};
        if (flash.error) window.alertaSistema('No se pudo completar', flash.error, 'error');
        else if (flash.success) window.alertaSistema('Listo', flash.success, 'success');
    };

    // Centros de costo
    const centerModal = ref(false);
    const editing = ref(null);
    const centerForm = useForm({ code: '', name: '', status: true });

    const openCenter = (center = null) => {
        editing.value = center?.id || null;
        centerForm.clearErrors();
        centerForm.code = center?.code || '';
        centerForm.name = center?.name || '';
        centerForm.status = center ? !!center.status : true;
        centerModal.value = true;
    };

    const saveCenter = () => {
        const url = editing.value
            ? route('treasury_cost_centers_update', editing.value)
            : route('treasury_cost_centers_store');

        centerForm.post(url, {
            preserveScroll: true,
            onSuccess: (page) => {
                notifyFlash(page);
                centerModal.value = false;
            },
            onError: () => window.alertaSistema('Revisa el formulario', 'El código debe ser único y el nombre obligatorio.', 'warning'),
        });
    };

    const removeCenter = async (center) => {
        const result = await window.alertaSistema(
            'Eliminar centro de costo',
            `Si «${center.name}» tiene movimientos, se desactivará en lugar de eliminarse.`,
            'warning',
            { duration: 0, showCancelButton: true, confirmButtonText: 'Sí, continuar', cancelButtonText: 'Cancelar' }
        );

        if (!result?.isConfirmed) return;

        router.delete(route('treasury_cost_centers_destroy', center.id), {
            preserveScroll: true,
            onSuccess: notifyFlash,
        });
    };

    const typeLabel = (type) => (type === 'income' ? 'Ingreso' : 'Egreso');

    // ---------------- Presupuesto por centro de costo ----------------
    const budgetModal = ref(false);
    const budgetEditing = ref(null);
    const budgetForm = useForm({
        month: props.budgets.month || new Date().toISOString().slice(0, 7),
        cost_center_id: null,
        treasury_category_id: null,
        currency: 'PEN',
        amount: '',
        notes: '',
    });

    const openBudget = (row = null) => {
        budgetEditing.value = row?.id || null;
        budgetForm.clearErrors();
        budgetForm.month = props.budgets.month || new Date().toISOString().slice(0, 7);
        budgetForm.cost_center_id = row?.cost_center_id || null;
        budgetForm.treasury_category_id = row?.category_id || null;
        budgetForm.currency = row?.currency || props.filters.currency || 'PEN';
        budgetForm.amount = row?.amount ?? '';
        budgetForm.notes = row?.notes || '';
        budgetModal.value = true;
    };

    const saveBudget = () => {
        budgetForm.post(route('treasury_budgets_store'), {
            preserveScroll: true,
            onSuccess: (page) => {
                notifyFlash(page);
                budgetModal.value = false;
            },
            onError: () => window.alertaSistema('Revisa el presupuesto', 'Elige el centro de costo e indica un monto mayor que cero.', 'warning'),
        });
    };

    const removeBudget = async (row) => {
        const result = await window.alertaSistema(
            'Eliminar presupuesto',
            `Se quitará el presupuesto de «${row.center}» (${row.category}). Los movimientos reales no se tocan.`,
            'warning',
            { duration: 0, showCancelButton: true, confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar' }
        );

        if (!result?.isConfirmed) return;

        router.delete(route('treasury_budgets_destroy', row.id), { preserveScroll: true, onSuccess: notifyFlash });
    };

    const budgetStatusClass = (status) => ({
        ok: 'bg-success/10 text-success',
        warning: 'bg-warning/10 text-warning',
        over: 'bg-danger/10 text-danger',
    }[status] || 'bg-gray-100 text-gray-600');

    const budgetBarClass = (status) => ({
        ok: 'bg-success',
        warning: 'bg-warning',
        over: 'bg-danger',
    }[status] || 'bg-primary');

    // ---------------- Exportación (Excel y PDF en el navegador) ----------------
    const usuario = computed(() => usePage().props.auth?.user || {});
    const exportando = ref(null);

    const centroSeleccionado = computed(() => {
        const id = props.filters.cost_center_id;

        if (!id) return 'Todos';

        const centro = props.costCenters.find((item) => item.id === Number(id));

        return centro ? `${centro.code} · ${centro.name}` : 'Todos';
    });

    // Quién pide el reporte, cuándo y con qué filtros: va dentro del archivo.
    const metaReporte = () =>
        construirMetaReporte({
            user: usuario.value,
            filters: props.filters,
            currency: props.flows.currency,
            costCenter: centroSeleccionado.value,
            periodLabel: etiquetaPeriodo.value,
        });

    const exportarExcel = () => {
        if (exportando.value) return;

        exportando.value = 'excel';

        try {
            exportarReporteExcel({
                position: props.position,
                flows: props.flows,
                byCostCenter: props.byCostCenter,
                totalsByCurrency: props.totalsByCurrency,
                projection: props.projection,
                resumenProyeccion: resumenProyeccion.value,
                meta: metaReporte(),
            });

            window.alertaSistema('Excel generado', 'El archivo se descargó con el detalle del reporte.', 'success');
        } catch (error) {
            window.alertaSistema('No se pudo generar el Excel', 'Intenta de nuevo o revisa la consola del navegador.', 'error');
        } finally {
            exportando.value = null;
        }
    };

    const exportarPdf = () => {
        if (exportando.value) return;

        exportando.value = 'pdf';

        try {
            exportarReportePdf({
                position: props.position,
                flows: props.flows,
                byCostCenter: props.byCostCenter,
                totalsByCurrency: props.totalsByCurrency,
                projection: props.projection,
                resumenProyeccion: resumenProyeccion.value,
                meta: metaReporte(),
            });

            window.alertaSistema('PDF generado', 'El archivo se descargó con el detalle del reporte.', 'success');
        } catch (error) {
            window.alertaSistema('No se pudo generar el PDF', 'Intenta de nuevo o revisa la consola del navegador.', 'error');
        } finally {
            exportando.value = null;
        }
    };
</script>

<template>
    <AppLayout title="Reportes de tesorería">
        <Head title="Tesorería · Reportes">
            <meta name="aracodito-view" content="new" />
        </Head>
        <Navigation>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2"><span>Tesorería</span></li>
            <li class="before:content-['/'] ltr:before:mr-2 rtl:before:ml-2"><span>Reportes</span></li>
        </Navigation>

        <div class="mt-5 space-y-5">
            <!-- Filtros -->
            <div class="panel flex flex-col gap-4 p-5 xl:flex-row xl:items-end xl:justify-between">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:flex-wrap">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Periodo</label>
                        <select v-model="preset" class="form-select sm:w-48">
                            <option value="custom">Rango personalizado</option>
                            <option value="month">Mes completo</option>
                            <option value="month_current">Mes en curso</option>
                            <option value="year">Año completo</option>
                            <option value="year_current">Año en curso</option>
                        </select>
                    </div>
                    <div v-if="preset === 'month'">
                        <label class="mb-1.5 block text-sm font-medium">Mes</label>
                        <input v-model="mes" type="month" class="form-input sm:w-44" />
                    </div>
                    <div v-if="preset === 'year'">
                        <label class="mb-1.5 block text-sm font-medium">Año</label>
                        <input v-model.number="anio" type="number" min="2000" max="2100" class="form-input sm:w-28" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Desde</label>
                        <input
                            :value="rango.from"
                            type="date"
                            class="form-input disabled:opacity-60 sm:w-40"
                            :disabled="preset !== 'custom'"
                            @change="query.from = $event.target.value; alCambiarFecha()"
                        />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Hasta</label>
                        <input
                            :value="rango.to"
                            type="date"
                            class="form-input disabled:opacity-60 sm:w-40"
                            :disabled="preset !== 'custom'"
                            @change="query.to = $event.target.value; alCambiarFecha()"
                        />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Moneda</label>
                        <select v-model="query.currency" class="form-select sm:w-32">
                            <option v-for="code in currencies" :key="code" :value="code">{{ code }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Centro de costo</label>
                        <select v-model="query.cost_center_id" class="form-select sm:w-52">
                            <option :value="null">Todos</option>
                            <option v-for="center in costCenters" :key="center.id" :value="center.id">{{ center.code }} · {{ center.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Proyección</label>
                        <select v-model.number="query.days" class="form-select sm:w-32">
                            <option :value="30">30 días</option>
                            <option :value="90">90 días</option>
                            <option :value="180">180 días</option>
                        </select>
                    </div>
                    <button type="button" class="btn btn-primary" @click="apply">Aplicar</button>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        class="btn btn-outline-primary disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="exportando !== null"
                        title="Descargar el reporte en Excel"
                        @click="exportarExcel"
                    >
                        <font-awesome-icon :icon="faFileExcel" class="mr-1" />
                        {{ exportando === 'excel' ? 'Generando…' : 'Excel' }}
                    </button>
                    <button
                        type="button"
                        class="btn btn-outline-danger disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="exportando !== null"
                        title="Descargar el reporte en PDF"
                        @click="exportarPdf"
                    >
                        <font-awesome-icon :icon="faFilePdf" class="mr-1" />
                        {{ exportando === 'pdf' ? 'Generando…' : 'PDF' }}
                    </button>
                    <button v-if="canManageCenters" type="button" class="btn btn-outline-primary" @click="openCenter()">
                        <font-awesome-icon :icon="faPlus" class="mr-1" /> Centro de costo
                    </button>
                </div>
            </div>

            <!-- Posición consolidada -->
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
                <div class="panel bg-gradient-to-br from-primary to-primary-dark p-5 text-white">
                    <p class="text-sm opacity-80">Consolidado en {{ position.base_currency }}</p>
                    <p class="mt-2 text-2xl font-bold">{{ money(position.consolidated, position.base_currency) }}</p>
                    <p v-if="position.incomplete" class="mt-1 text-xs text-warning">Faltan tipos de cambio</p>
                    <p v-if="position.detraction" class="mt-1 text-xs opacity-80">
                        Aparte: {{ money(position.detraction, position.base_currency) }} en detracción (solo IGV)
                    </p>
                </div>
                <div v-for="group in position.groups" :key="group.currency" class="panel p-5">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Saldo en {{ group.currency }}</p>
                    <p class="mt-2 text-2xl font-bold text-gray-800 dark:text-gray-100">{{ money(group.total, group.currency) }}</p>
                    <p class="mt-1 text-xs text-gray-500">{{ group.accounts }} cuenta(s)</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
                <!-- Posición por cuenta -->
                <div class="panel overflow-hidden p-0">
                    <div class="border-b border-gray-200 p-5 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Posición por cuenta</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Cada saldo en su moneda; la conversión se informa aparte</p>
                    </div>
                    <div class="table-responsive">
                        <table class="min-w-full">
                            <thead class="bg-gray-50 dark:bg-gray-800/60">
                                <tr>
                                    <th>Cuenta</th>
                                    <th>Moneda</th>
                                    <th class="text-right">Saldo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="account in position.balances" :key="account.id">
                                    <td class="text-sm font-medium">{{ account.label }}</td>
                                    <td class="text-sm">{{ account.currency }}</td>
                                    <td class="text-right font-semibold" :class="account.balance < 0 ? 'text-danger' : ''">
                                        {{ money(account.balance, account.currency) }}
                                    </td>
                                </tr>
                                <tr v-if="position.balances.length === 0">
                                    <td colspan="3" class="py-6 text-center text-sm text-gray-500">Sin cuentas activas.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-if="position.rates.length" class="border-t border-gray-200 p-4 text-xs text-gray-500 dark:border-gray-700">
                        <p v-for="rate in position.rates" :key="rate.currency">
                            TC {{ rate.currency }}: {{ rate.rate }} ({{ rate.date }}{{ rate.is_stale ? ' · desactualizado' : '' }})
                        </p>
                    </div>
                </div>

                <!-- Totales por moneda -->
                <div class="panel overflow-hidden p-0">
                    <div class="border-b border-gray-200 p-5 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Totales del periodo por moneda</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Sin consolidar: cada moneda por separado</p>
                    </div>
                    <div class="table-responsive">
                        <table class="min-w-full">
                            <thead class="bg-gray-50 dark:bg-gray-800/60">
                                <tr>
                                    <th>Moneda</th>
                                    <th class="text-right">Ingresos</th>
                                    <th class="text-right">Egresos</th>
                                    <th class="text-right">Neto</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in totalsByCurrency" :key="row.currency">
                                    <td class="text-sm font-medium">{{ row.currency }}</td>
                                    <td class="text-right text-success">{{ money(row.incomes, row.currency) }}</td>
                                    <td class="text-right text-danger">{{ money(row.expenses, row.currency) }}</td>
                                    <td class="text-right font-semibold" :class="row.net < 0 ? 'text-danger' : ''">{{ money(row.net, row.currency) }}</td>
                                </tr>
                                <tr v-if="totalsByCurrency.length === 0">
                                    <td colspan="4" class="py-6 text-center text-sm text-gray-500">Sin movimientos en el periodo.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Gráficos del periodo y de la proyección -->
            <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
                <div class="panel p-5">
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Ingresos y egresos por categoría · {{ flows.currency }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Comparación visual del periodo seleccionado</p>
                    <div v-if="flows.rows.length" class="mt-4">
                        <apexchart type="bar" height="300" :options="opcionesCategorias" :series="seriesCategorias" />
                    </div>
                    <p v-else class="py-10 text-center text-sm text-gray-500">Sin movimientos para el filtro.</p>
                </div>

                <div class="panel p-5">
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Proyección de caja · {{ projection.currency }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Esperado para los próximos {{ projection.days }} días, según cobranza, órdenes aprobadas y recurrentes
                    </p>
                    <div v-if="projection.rows.length" class="mt-4">
                        <apexchart type="line" height="320" :options="opcionesProyeccion" :series="seriesProyeccion" />
                    </div>
                    <p v-else class="py-10 text-center text-sm text-gray-500">Sin datos de proyección.</p>
                </div>
            </div>

            <!-- Proyección de ingresos y gastos -->
            <div class="panel overflow-hidden p-0">
                <div class="flex flex-col gap-2 border-b border-gray-200 p-5 sm:flex-row sm:items-center sm:justify-between dark:border-gray-700">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Ingresos esperados y gastos proyectados</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Estimación al {{ formatFechaReporte(projection.to) }} · no es saldo real, es lo que se espera cobrar y pagar
                        </p>
                    </div>
                    <span class="rounded-md bg-primary/10 px-3 py-1 text-xs font-medium text-primary">
                        {{ projection.days }} días · cobranza {{ Math.round(projection.collection_ratio * 100) }} %
                    </span>
                </div>

                <div class="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Saldo inicial</p>
                        <p class="mt-1 text-xl font-bold text-gray-800 dark:text-gray-100">{{ money(projection.opening_balance, projection.currency) }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Ingresos esperados</p>
                        <p class="mt-1 text-xl font-bold text-success">{{ money(projection.totals.inflows, projection.currency) }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Egresos proyectados</p>
                        <p class="mt-1 text-xl font-bold text-danger">{{ money(projection.totals.outflows, projection.currency) }}</p>
                    </div>
                    <div class="rounded-lg border-2 border-primary/40 bg-primary/5 p-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Saldo al cierre</p>
                        <p class="mt-1 text-xl font-bold" :class="projection.totals.closing < 0 ? 'text-danger' : 'text-primary'">
                            {{ money(projection.totals.closing, projection.currency) }}
                        </p>
                        <p class="mt-1 text-xs text-gray-500">Mínimo {{ money(projection.min_balance, projection.currency) }}</p>
                    </div>
                </div>

                <div v-if="projection.warnings.length" class="mx-5 mb-5 rounded-lg border border-warning/40 bg-warning/10 p-3 text-sm text-warning">
                    <p v-for="warning in projection.warnings" :key="warning">{{ warning }}</p>
                </div>

                <div class="table-responsive">
                    <table class="min-w-full">
                        <thead class="bg-gray-50 dark:bg-gray-800/60">
                            <tr>
                                <th>Mes</th>
                                <th class="text-right">Ingresos esperados</th>
                                <th class="text-right">Egresos proyectados</th>
                                <th class="text-right">Neto</th>
                                <th class="text-right">Saldo al cierre del mes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="fila in resumenProyeccion" :key="fila.month">
                                <td class="text-sm font-medium">{{ fila.label }}</td>
                                <td class="text-right text-success">{{ money(fila.inflows, projection.currency) }}</td>
                                <td class="text-right text-danger">{{ money(fila.outflows, projection.currency) }}</td>
                                <td class="text-right font-semibold" :class="fila.net < 0 ? 'text-danger' : ''">{{ money(fila.net, projection.currency) }}</td>
                                <td class="text-right font-bold" :class="fila.balance < 0 ? 'text-danger' : 'text-gray-800 dark:text-gray-100'">{{ money(fila.balance, projection.currency) }}</td>
                            </tr>
                            <tr v-if="resumenProyeccion.length === 0">
                                <td colspan="5" class="py-6 text-center text-sm text-gray-500">Sin datos de proyección.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Flujo por categoría -->
            <div class="panel overflow-hidden p-0">
                <div class="border-b border-gray-200 p-5 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Flujo por categoría · {{ flows.currency }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Ingresos {{ money(flows.incomes, flows.currency) }} · Egresos {{ money(flows.expenses, flows.currency) }} · Neto {{ money(flows.net, flows.currency) }}
                    </p>
                </div>
                <div class="table-responsive">
                    <table class="min-w-full">
                        <thead class="bg-gray-50 dark:bg-gray-800/60">
                            <tr>
                                <th>Categoría</th>
                                <th>Tipo</th>
                                <th class="text-center">Movimientos</th>
                                <th class="text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in flows.rows" :key="row.category + row.type">
                                <td class="text-sm">
                                    <span class="mr-2 inline-block h-3 w-3 rounded-full" :style="{ backgroundColor: row.color }"></span>
                                    {{ row.category }}
                                </td>
                                <td>
                                    <span class="rounded-md px-2 py-1 text-xs" :class="row.type === 'income' ? 'bg-success/10 text-success' : 'bg-danger/10 text-danger'">
                                        {{ typeLabel(row.type) }}
                                    </span>
                                </td>
                                <td class="text-center text-sm">{{ row.movements }}</td>
                                <td class="text-right font-semibold">{{ money(row.total, flows.currency) }}</td>
                            </tr>
                            <tr v-if="flows.rows.length === 0">
                                <td colspan="4" class="py-6 text-center text-sm text-gray-500">Sin movimientos para el filtro.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Por centro de costo -->
            <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
                <div class="panel overflow-hidden p-0">
                    <div class="border-b border-gray-200 p-5 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Por centro de costo · {{ flows.currency }}</h2>
                    </div>
                    <div class="table-responsive">
                        <table class="min-w-full">
                            <thead class="bg-gray-50 dark:bg-gray-800/60">
                                <tr>
                                    <th>Centro</th>
                                    <th class="text-right">Ingresos</th>
                                    <th class="text-right">Egresos</th>
                                    <th class="text-right">Neto</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in byCostCenter" :key="row.center">
                                    <td class="text-sm">{{ row.center }}</td>
                                    <td class="text-right text-success">{{ money(row.incomes, flows.currency) }}</td>
                                    <td class="text-right text-danger">{{ money(row.expenses, flows.currency) }}</td>
                                    <td class="text-right font-semibold">{{ money(row.net, flows.currency) }}</td>
                                </tr>
                                <tr v-if="byCostCenter.length === 0">
                                    <td colspan="4" class="py-6 text-center text-sm text-gray-500">Sin movimientos etiquetados.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="panel overflow-hidden p-0">
                    <div class="border-b border-gray-200 p-5 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Centros de costo</h2>
                    </div>
                    <div class="table-responsive">
                        <table class="min-w-full">
                            <thead class="bg-gray-50 dark:bg-gray-800/60">
                                <tr>
                                    <th>Código</th>
                                    <th>Nombre</th>
                                    <th class="text-center">Movimientos</th>
                                    <th class="text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="center in costCenters" :key="center.id">
                                    <td class="font-mono text-xs">{{ center.code }}</td>
                                    <td class="text-sm">
                                        {{ center.name }}
                                        <span v-if="!center.status" class="ml-1 text-xs text-danger">Inactivo</span>
                                    </td>
                                    <td class="text-center text-sm">{{ center.movements }}</td>
                                    <td class="text-right">
                                        <div v-if="canManageCenters" class="flex justify-end gap-2">
                                            <button type="button" class="btn btn-sm btn-outline-primary" @click="openCenter(center)">
                                                <font-awesome-icon :icon="faPen" />
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger" @click="removeCenter(center)">
                                                <font-awesome-icon :icon="faBan" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="costCenters.length === 0">
                                    <td colspan="4" class="py-6 text-center text-sm text-gray-500">Sin centros de costo.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Presupuesto por centro de costo -->
        <div class="panel mt-5 overflow-hidden p-0">
            <div class="flex flex-col gap-2 border-b border-gray-200 p-5 sm:flex-row sm:items-center sm:justify-between dark:border-gray-700">
                <div>
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Presupuesto por centro de costo · {{ budgets.label }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Monto planificado del mes contra el gasto real del libro. La ejecución se calcula, no se guarda.
                        <span v-if="budgets.over" class="ml-1 font-medium text-danger">{{ budgets.over }} excedido(s)</span>
                        <span v-if="budgets.warning" class="ml-1 font-medium text-warning">{{ budgets.warning }} en alerta</span>
                    </p>
                </div>
                <button v-if="canManageBudgets" type="button" class="btn btn-outline-primary" @click="openBudget()">
                    <font-awesome-icon :icon="faPlus" class="mr-1" /> Presupuesto
                </button>
            </div>

            <div v-if="budgets.totals.length" class="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2 xl:grid-cols-4">
                <div v-for="total in budgets.totals" :key="total.currency" class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Presupuesto {{ total.currency }}</p>
                    <p class="mt-1 text-xl font-bold">{{ money(total.budget, total.currency) }}</p>
                    <p class="mt-1 text-xs text-gray-500">
                        Ejecutado {{ money(total.executed, total.currency) }} ·
                        <span :class="total.available < 0 ? 'text-danger' : 'text-success'">
                            {{ total.available < 0 ? 'Excedido' : 'Disponible' }} {{ money(Math.abs(total.available), total.currency) }}
                        </span>
                    </p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="min-w-full">
                    <thead class="bg-gray-50 dark:bg-gray-800/60">
                        <tr>
                            <th>Centro de costo</th>
                            <th>Categoría</th>
                            <th class="text-right">Presupuesto</th>
                            <th class="text-right">Ejecutado</th>
                            <th class="text-right">Disponible</th>
                            <th class="min-w-[160px]">Avance</th>
                            <th class="text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in budgets.rows" :key="row.id">
                            <td class="text-sm font-medium">{{ row.center }}</td>
                            <td class="text-sm">{{ row.category }}</td>
                            <td class="text-right">{{ money(row.amount, row.currency) }}</td>
                            <td class="text-right">{{ money(row.executed, row.currency) }}</td>
                            <td class="text-right font-semibold" :class="row.available < 0 ? 'text-danger' : 'text-success'">
                                {{ money(row.available, row.currency) }}
                            </td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <div class="h-2 w-full rounded bg-gray-100 dark:bg-gray-700">
                                        <div
                                            class="h-2 rounded"
                                            :class="budgetBarClass(row.status)"
                                            :style="{ width: Math.min(100, Number(row.usage || 0)) + '%' }"
                                        ></div>
                                    </div>
                                    <span class="rounded-md px-2 py-0.5 text-xs font-medium whitespace-nowrap" :class="budgetStatusClass(row.status)">
                                        {{ row.usage === null ? '—' : row.usage + ' %' }}
                                    </span>
                                </div>
                            </td>
                            <td class="text-right">
                                <div v-if="canManageBudgets" class="flex justify-end gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-primary" title="Editar" @click="openBudget(row)">
                                        <font-awesome-icon :icon="faPen" />
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" title="Eliminar" @click="removeBudget(row)">
                                        <font-awesome-icon :icon="faBan" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="budgets.rows.length === 0">
                            <td colspan="7" class="py-6 text-center text-sm text-gray-500">
                                Sin presupuesto para {{ budgets.label }}. Crea uno para comparar contra el gasto real.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <ModalMedium :show="budgetModal" :on-close="() => (budgetModal = false)" :icon="'/img/cuenta-bancaria.png'">
            <template #title>{{ budgetEditing ? 'Editar presupuesto' : 'Nuevo presupuesto' }}</template>
            <template #message><span>Monto mensual de egresos para el centro de costo</span></template>
            <template #content>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <InputLabel value="Mes *" class="mb-1.5" />
                        <input v-model="budgetForm.month" type="month" class="form-input w-full" />
                        <InputError :message="budgetForm.errors.month" />
                    </div>
                    <div>
                        <InputLabel value="Moneda" class="mb-1.5" />
                        <select v-model="budgetForm.currency" class="form-select w-full">
                            <option value="PEN">Soles (PEN)</option>
                            <option value="USD">Dólares (USD)</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel value="Centro de costo *" class="mb-1.5" />
                        <select v-model="budgetForm.cost_center_id" class="form-select w-full">
                            <option :value="null">Elegir centro</option>
                            <option v-for="center in budgetOptions.costCenters" :key="center.id" :value="center.id">
                                {{ center.code }} · {{ center.name }}
                            </option>
                        </select>
                        <InputError :message="budgetForm.errors.cost_center_id" />
                    </div>
                    <div>
                        <InputLabel value="Categoría" class="mb-1.5" />
                        <select v-model="budgetForm.treasury_category_id" class="form-select w-full">
                            <option :value="null">Todas las categorías</option>
                            <option v-for="category in budgetOptions.categories" :key="category.id" :value="category.id">{{ category.name }}</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel value="Monto *" class="mb-1.5" />
                        <input v-model="budgetForm.amount" type="number" min="0.01" step="0.01" class="form-input w-full" />
                        <InputError :message="budgetForm.errors.amount" />
                    </div>
                    <div class="md:col-span-2">
                        <InputLabel value="Notas" class="mb-1.5" />
                        <textarea v-model="budgetForm.notes" rows="2" class="form-textarea w-full"></textarea>
                    </div>
                    <p class="text-xs text-gray-500 md:col-span-2">
                        Si ya existe un presupuesto del mismo mes, centro, categoría y moneda, se actualiza en lugar de duplicarse.
                    </p>
                </div>
            </template>
            <template #buttons>
                <PrimaryButton :disabled="budgetForm.processing" @click="saveBudget">Guardar presupuesto</PrimaryButton>
            </template>
        </ModalMedium>

        <ModalMedium :show="centerModal" :on-close="() => (centerModal = false)" :icon="'/img/cuenta-bancaria.png'">
            <template #title>{{ editing ? 'Editar centro de costo' : 'Nuevo centro de costo' }}</template>
            <template #message><span>Dimensión para separar el flujo por área o proyecto</span></template>
            <template #content>
                <div class="space-y-4">
                    <div>
                        <InputLabel value="Código *" class="mb-1.5" />
                        <input v-model="centerForm.code" type="text" class="form-input w-full" placeholder="Ej: ADM" />
                        <InputError :message="centerForm.errors.code" />
                    </div>
                    <div>
                        <InputLabel value="Nombre *" class="mb-1.5" />
                        <input v-model="centerForm.name" type="text" class="form-input w-full" placeholder="Ej: Administración" />
                        <InputError :message="centerForm.errors.name" />
                    </div>
                    <label v-if="editing" class="flex items-center gap-2">
                        <input v-model="centerForm.status" type="checkbox" class="form-checkbox" />
                        <span>Activo</span>
                    </label>
                </div>
            </template>
            <template #buttons>
                <PrimaryButton :disabled="centerForm.processing" @click="saveCenter">Guardar</PrimaryButton>
            </template>
        </ModalMedium>
    </AppLayout>
</template>
