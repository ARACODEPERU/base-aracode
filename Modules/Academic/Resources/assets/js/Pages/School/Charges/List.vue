<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import Pagination from '@/Components/Pagination.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref } from 'vue';
import { faMoneyBillWave, faPlus, faSearch, faClock, faTriangleExclamation, faCircleCheck, faFileInvoiceDollar } from '@fortawesome/free-solid-svg-icons';

const page = usePage();

const permissions = computed(() => page.props.auth?.permissions || []);

const hasPermission = (permission) => {
    if (!permission) {
        return true;
    }

    return permissions.value.includes(permission);
};

const props = defineProps({
    movimientos: { type: Object, default: () => ({}) },
    totals: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
    filterOptions: { type: Array, default: () => [] },
    years: { type: Array, default: () => [] },
    concepts: { type: Array, default: () => [] },
    statusLabels: { type: Object, default: () => ({}) },
});

const form = ref({
    search: props.filters.search ?? '',
    estado: props.filters.estado ?? 'por_cobrar',
    anio_id: props.filters.anio_id ?? '',
    nivel_id: props.filters.nivel_id ?? '',
    grado_id: props.filters.grado_id ?? '',
    seccion_id: props.filters.seccion_id ?? '',
    fee_type_id: props.filters.fee_type_id ?? '',
});

/* ---------- Filtros en cascada Nivel -> Grado -> Sección ---------- */
const levelOptions = computed(() => props.filterOptions ?? []);
const gradeOptions = computed(
    () => levelOptions.value.find((l) => String(l.id) === String(form.value.nivel_id))?.grades ?? []
);
const sectionOptions = computed(
    () => gradeOptions.value.find((g) => String(g.id) === String(form.value.grado_id))?.sections ?? []
);

const estadoTabs = computed(() => Object.entries(props.statusLabels ?? {}));

const applyFilters = () => {
    router.get(route('aca_school_charges_list'), form.value, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const changeEstado = (estado) => {
    form.value.estado = estado;
    applyFilters();
};

const changeYear = () => {
    form.value.nivel_id = '';
    form.value.grado_id = '';
    form.value.seccion_id = '';
    applyFilters();
};

const onLevelChange = () => {
    form.value.grado_id = '';
    form.value.seccion_id = '';
    applyFilters();
};

const onGradeChange = () => {
    form.value.seccion_id = '';
    applyFilters();
};

const clearFilters = () => {
    form.value = {
        search: '',
        estado: 'por_cobrar',
        anio_id: '',
        nivel_id: '',
        grado_id: '',
        seccion_id: '',
        fee_type_id: '',
    };
    applyFilters();
};

/* ---------- Presentación ---------- */
const money = (value) =>
    'S/ ' + Number(value ?? 0).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const formatDate = (value) => {
    if (! value) return '—';
    const date = String(value).slice(0, 10);
    const [year, month, day] = date.split('-');
    return `${day}/${month}/${year}`;
};

const estadoBadges = {
    pendiente: 'bg-warning',
    vencido: 'bg-danger',
    pagado: 'bg-success',
    anulado: 'bg-secondary',
};

const ubicacion = (row) =>
    [row.nivel, row.grado, row.seccion ? 'Sección ' + row.seccion : null].filter(Boolean).join(' · ') || 'Sin sección';

/* ---------- Nuevo cobro: busca al alumno y abre su ficha de cobranza ---------- */
const nuevoModal = ref(false);
const busqueda = ref('');
const resultados = ref([]);
const buscando = ref(false);
const busquedaError = ref('');
let searchTimer = null;

const abrirNuevo = () => {
    nuevoModal.value = true;
    busqueda.value = '';
    resultados.value = [];
    busquedaError.value = '';
    buscarAlumnos();
};

const buscarAlumnos = () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(async () => {
        buscando.value = true;
        busquedaError.value = '';

        try {
            const { data } = await axios.post(route('aca_school_enrollments_search_students'), {
                search: busqueda.value || undefined,
            });
            resultados.value = data ?? [];
        } catch {
            resultados.value = [];
            busquedaError.value = 'No se pudo buscar alumnos. Intenta nuevamente.';
        } finally {
            buscando.value = false;
        }
    }, 350);
};

const abrirCobroAlumno = (student) => {
    window.location.href = route('aca_school_charges_by_student', student.id);
};

onBeforeUnmount(() => clearTimeout(searchTimer));
</script>

<template>
    <AppLayout title="Cobros del Colegio">
        <Navigation :routeModule="route('aca_dashboard')" :titleModule="'Académico'"
            :data="[
                { route: route('aca_school_years_list'), title: 'Colegio' },
                { title: 'Cobros' },
            ]"
        />

        <div class="pt-5">
            <!-- Cabecera -->
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div>
                    <h2 class="text-xl">Cobros</h2>
                    <p class="text-sm text-white-dark mt-1">
                        Matrículas, mensualidades y otros conceptos por cobrar o ya pagados.
                    </p>
                </div>
                <div class="flex sm:flex-row flex-col sm:items-center sm:gap-3 gap-4 w-full sm:w-auto">
                    <button v-if="hasPermission('aca_school_cobro_registrar')" type="button" class="btn btn-primary" @click="abrirNuevo">
                        <font-awesome-icon :icon="faPlus" class="ltr:mr-2 rtl:ml-2" />
                        Nuevo cobro
                    </button>
                    <div class="relative">
                        <input
                            type="text"
                            placeholder="Buscar por nombre, DNI o código"
                            class="form-input py-2 w-full sm:w-80"
                            v-model="form.search"
                            @keyup.enter="applyFilters"
                        />
                    </div>
                </div>
            </div>

            <!-- Resumen -->
            <div class="mt-5 grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="panel p-4 flex items-center gap-3">
                    <span class="w-10 h-10 rounded-full bg-warning/20 text-warning flex items-center justify-center">
                        <font-awesome-icon :icon="faClock" />
                    </span>
                    <div>
                        <p class="text-xs text-white-dark uppercase tracking-wide">Por cobrar</p>
                        <p class="text-lg font-semibold">{{ money(totals.pendiente_monto) }}</p>
                        <p class="text-xs text-white-dark">{{ totals.pendiente_cantidad ?? 0 }} concepto(s)</p>
                    </div>
                </div>
                <div class="panel p-4 flex items-center gap-3">
                    <span class="w-10 h-10 rounded-full bg-danger/20 text-danger flex items-center justify-center">
                        <font-awesome-icon :icon="faTriangleExclamation" />
                    </span>
                    <div>
                        <p class="text-xs text-white-dark uppercase tracking-wide">Vencido</p>
                        <p class="text-lg font-semibold">{{ money(totals.vencido_monto) }}</p>
                        <p class="text-xs text-white-dark">{{ totals.vencido_cantidad ?? 0 }} cuota(s) vencida(s)</p>
                    </div>
                </div>
                <div class="panel p-4 flex items-center gap-3">
                    <span class="w-10 h-10 rounded-full bg-success/20 text-success flex items-center justify-center">
                        <font-awesome-icon :icon="faCircleCheck" />
                    </span>
                    <div>
                        <p class="text-xs text-white-dark uppercase tracking-wide">Cobrado</p>
                        <p class="text-lg font-semibold">{{ money(totals.pagado_monto) }}</p>
                        <p class="text-xs text-white-dark">{{ totals.pagado_cantidad ?? 0 }} pago(s) registrado(s)</p>
                    </div>
                </div>
            </div>

            <!-- Pestañas de estado -->
            <div class="mt-5 flex flex-wrap gap-2">
                <button
                    v-for="[key, label] in estadoTabs"
                    :key="key"
                    type="button"
                    class="btn btn-sm"
                    :class="form.estado === key ? 'btn-dark' : 'btn-outline-dark'"
                    @click="changeEstado(key)"
                >
                    {{ label }}
                </button>
            </div>

            <!-- Filtros -->
            <div class="mt-4 panel p-4 flex flex-wrap items-end gap-3">
                <div>
                    <label class="form-label text-xs">Año escolar</label>
                    <select v-model="form.anio_id" class="form-select w-36 py-2" @change="changeYear">
                        <option value="">Todos</option>
                        <option v-for="year in years" :key="year.id" :value="year.id">{{ year.year }}</option>
                    </select>
                </div>
                <div>
                    <label class="form-label text-xs">Nivel</label>
                    <select v-model="form.nivel_id" class="form-select w-40 py-2" @change="onLevelChange">
                        <option value="">Todos</option>
                        <option v-for="level in levelOptions" :key="level.id" :value="level.id">{{ level.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="form-label text-xs">Grado</label>
                    <select v-model="form.grado_id" class="form-select w-40 py-2" :disabled="!form.nivel_id" @change="onGradeChange">
                        <option value="">Todos</option>
                        <option v-for="grade in gradeOptions" :key="grade.id" :value="grade.id">{{ grade.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="form-label text-xs">Sección</label>
                    <select v-model="form.seccion_id" class="form-select w-36 py-2" :disabled="!form.grado_id" @change="applyFilters">
                        <option value="">Todas</option>
                        <option v-for="section in sectionOptions" :key="section.id" :value="section.id">{{ section.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="form-label text-xs">Concepto</label>
                    <select v-model="form.fee_type_id" class="form-select w-52 py-2" @change="applyFilters">
                        <option value="">Todos</option>
                        <option v-for="concept in concepts" :key="concept.id" :value="concept.id">{{ concept.name }}</option>
                    </select>
                </div>
                <button type="button" class="btn btn-outline-secondary" @click="clearFilters">Limpiar</button>
            </div>

            <!-- Tabla -->
            <div class="mt-5 panel p-0 border-0 overflow-hidden">
                <div class="table-responsive">
                    <table class="table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Alumno</th>
                                <th>Año / Ubicación</th>
                                <th>Concepto</th>
                                <th class="!text-right">Importe</th>
                                <th class="!text-center">Estado</th>
                                <th>Comprobante</th>
                                <th class="!text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="!movimientos.data || movimientos.data.length === 0">
                                <td colspan="7" class="text-center py-10">
                                    <div class="flex flex-col items-center gap-2 text-white-dark">
                                        <font-awesome-icon :icon="faMoneyBillWave" class="text-3xl opacity-40" />
                                        <p class="font-semibold text-sm">No hay cobros con estos filtros</p>
                                        <p class="text-xs">Prueba con otra pestaña o limpia los filtros para ver todo el historial.</p>
                                    </div>
                                </td>
                            </tr>
                            <tr v-for="row in movimientos.data" :key="row.origen + '-' + row.origen_id">
                                <td>
                                    <div class="font-semibold">{{ row.alumno_nombre }}</div>
                                    <div class="text-xs text-white-dark">
                                        DNI {{ row.alumno_documento || '—' }} · Código {{ row.alumno_codigo || '—' }}
                                    </div>
                                </td>
                                <td class="whitespace-nowrap">
                                    <div>{{ row.anio || '—' }}</div>
                                    <div class="text-xs text-white-dark">{{ ubicacion(row) }}</div>
                                </td>
                                <td>
                                    <div>{{ row.descripcion || row.concepto || 'Concepto' }}</div>
                                    <div class="text-xs text-white-dark">
                                        <span v-if="row.cuota">Vence {{ formatDate(row.vencimiento) }}</span>
                                        <span v-else-if="row.pagado_en">Pagado el {{ formatDate(row.pagado_en) }}</span>
                                        <span v-else>Sin vencimiento</span>
                                    </div>
                                </td>
                                <td class="text-right font-semibold whitespace-nowrap">{{ money(row.monto) }}</td>
                                <td class="text-center">
                                    <span class="badge" :class="estadoBadges[row.estado] ?? 'bg-secondary'">
                                        {{ row.estado_label }}
                                    </span>
                                </td>
                                <td>
                                    <a v-if="row.pdf_url" :href="row.pdf_url" target="_blank" class="text-primary hover:underline whitespace-nowrap">
                                        {{ row.comprobante || 'Ver PDF' }}
                                    </a>
                                    <span v-else class="text-white-dark">—</span>
                                </td>
                                <td class="text-center">
                                    <div class="flex gap-1 items-center justify-center">
                                        <Link
                                            v-if="row.estado !== 'anulado'"
                                            :href="route('aca_school_charges_show', row.enrollment_id)"
                                            class="btn btn-sm btn-outline-info"
                                        >
                                            {{ row.estado === 'pagado' ? 'Ver detalle' : 'Cobrar' }}
                                        </Link>
                                        <a
                                            v-if="row.pdf_url"
                                            :href="row.pdf_url"
                                            target="_blank"
                                            class="btn btn-sm btn-outline-secondary"
                                        >
                                            <font-awesome-icon :icon="faFileInvoiceDollar" class="m-0" />
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="movimientos.links" class="p-4">
                    <Pagination :data="movimientos" />
                </div>
            </div>
        </div>

        <!-- Modal: nuevo cobro -->
        <div v-if="nuevoModal" class="fixed inset-0 z-[999] bg-black/40 flex items-start justify-center p-4 pt-20" @click.self="nuevoModal = false">
            <div class="panel w-full max-w-lg p-6">
                <div class="flex items-start justify-between gap-3 mb-1">
                    <h3 class="text-lg font-semibold">Nuevo cobro</h3>
                    <button type="button" class="btn btn-sm btn-outline-danger" @click="nuevoModal = false">Cerrar</button>
                </div>
                <p class="text-sm text-white-dark mb-4">
                    Busca al alumno por nombre, DNI o código y se abrirá su ficha para registrar el cobro y emitir el comprobante.
                </p>

                <div class="relative">
                    <input
                        v-model="busqueda"
                        type="text"
                        class="form-input py-2 w-full peer ltr:pr-10 rtl:pl-10"
                        placeholder="Nombre, DNI o código del alumno"
                        @input="buscarAlumnos"
                    />
                    <font-awesome-icon :icon="faSearch" class="absolute ltr:right-3 rtl:left-3 top-2.5 text-white-dark" />
                </div>

                <div class="mt-4 max-h-72 overflow-y-auto divide-y divide-[#e0e6ed] dark:divide-[#191e3a]">
                    <div v-if="buscando" class="py-6 text-center text-sm text-white-dark">Buscando alumnos...</div>
                    <div v-else-if="busquedaError" class="py-6 text-center text-sm text-danger">{{ busquedaError }}</div>
                    <div v-else-if="resultados.length === 0" class="py-6 text-center text-sm text-white-dark">
                        No se encontraron alumnos con esa búsqueda.
                    </div>
                    <button
                        v-for="student in resultados"
                        :key="student.id"
                        type="button"
                        class="w-full text-left py-3 px-2 hover:bg-gray-100 dark:hover:bg-[#191e3a] rounded"
                        @click="abrirCobroAlumno(student)"
                    >
                        <div class="font-semibold">{{ student.full_name }}</div>
                        <div class="text-xs text-white-dark">DNI {{ student.number }} · Código {{ student.student_code }}</div>
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
