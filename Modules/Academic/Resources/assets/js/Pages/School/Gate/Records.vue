<script setup>
import AppLayout from '@/Layouts/Vristo/AppLayout.vue';
import Navigation from '@/Components/vristo/layout/Navigation.vue';
import Pagination from '@/Components/Pagination.vue';
import { Link, router } from '@inertiajs/vue3';
import { computed, reactive, watch } from 'vue';
import {
    faArrowRotateLeft,
    faMagnifyingGlass,
    faQrcode,
    faRightToBracket,
    faRightFromBracket,
    faTableList,
    faTriangleExclamation,
    faUserCheck,
} from '@fortawesome/free-solid-svg-icons';

/**
 * Listado histórico de portería: todas las filas de asistencia institucional
 * (una por alumno y día) con los filtros del servidor. No reemplaza el CSV del
 * día: es la vista para consultar el historial sin descargar nada.
 */
const props = defineProps({
    schoolName: { type: String, default: '' },
    records: { type: Object, default: () => ({}) },
    summary: {
        type: Object,
        default: () => ({ total: 0, entries: 0, exits: 0, inside: 0, late: 0 }),
    },
    filters: { type: Object, default: () => ({}) },
    filterOptions: { type: Array, default: () => [] },
    canScan: { type: Boolean, default: false },
});

const form = reactive({
    search: props.filters.search ?? '',
    event: props.filters.event ?? '',
    level_id: props.filters.level_id ?? '',
    grade_id: props.filters.grade_id ?? '',
    section_id: props.filters.section_id ?? '',
    date_from: props.filters.date_from ?? '',
    date_to: props.filters.date_to ?? '',
});

/* ---------- Filtros en cascada Nivel → Grado → Sección ---------- */
const levelOptions = computed(() => props.filterOptions ?? []);

const gradeOptions = computed(
    () => levelOptions.value.find((level) => String(level.id) === String(form.level_id))?.grades ?? []
);

const sectionOptions = computed(
    () => gradeOptions.value.find((grade) => String(grade.id) === String(form.grade_id))?.sections ?? []
);

const rows = computed(() => props.records?.data ?? []);

const hasFilters = computed(
    () =>
        !!form.search ||
        !!form.event ||
        !!form.level_id ||
        !!form.grade_id ||
        !!form.section_id ||
        !!form.date_from ||
        !!form.date_to
);

const queryParams = () => {
    const params = {};

    Object.entries(form).forEach(([key, value]) => {
        if (value !== '' && value !== null && value !== undefined) {
            params[key] = value;
        }
    });

    return params;
};

const applyFilters = () => {
    router.get(route('aca_school_gate_records'), queryParams(), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const clearFilters = () => {
    Object.keys(form).forEach((key) => {
        form[key] = '';
    });

    applyFilters();
};

const onLevelChange = () => {
    form.grade_id = '';
    form.section_id = '';
    applyFilters();
};

const onGradeChange = () => {
    form.section_id = '';
    applyFilters();
};

// Si otra navegación (paginación o atrás/adelante) cambia los filtros del
// servidor, el formulario se sincroniza para no mostrar una consulta distinta
// a la que se está viendo.
watch(
    () => props.filters,
    (value) => {
        form.search = value.search ?? '';
        form.event = value.event ?? '';
        form.level_id = value.level_id ?? '';
        form.grade_id = value.grade_id ?? '';
        form.section_id = value.section_id ?? '';
        form.date_from = value.date_from ?? '';
        form.date_to = value.date_to ?? '';
    }
);

const statusClass = (row) => {
    if (row.entry_status === 'T') {
        return 'bg-warning';
    }

    return row.entry_time ? 'bg-success' : 'bg-secondary';
};

const situation = (row) => {
    if (row.inside) {
        return { label: 'Dentro', class: 'bg-info' };
    }

    if (row.exit_time) {
        return { label: 'Salió', class: 'bg-secondary' };
    }

    return { label: 'Sin salida', class: 'bg-warning' };
};
</script>

<template>
    <AppLayout title="Registros de Portería">
        <Navigation
            :routeModule="route('aca_dashboard')"
            :titleModule="'Académico'"
            :data="[
                { route: route('aca_school_years_list'), title: 'Colegio' },
                { route: route('aca_school_gate_scanner'), title: 'Portería' },
                { title: 'Registros' },
            ]"
        />

        <div class="pt-5">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div>
                    <h2 class="text-xl">Registros de Portería</h2>
                    <p class="text-sm text-white-dark mt-1">
                        {{ schoolName || 'Colegio' }} · Asistencia institucional (entradas y salidas)
                    </p>
                </div>

                <Link v-if="canScan" :href="route('aca_school_gate_scanner')" class="btn btn-primary">
                    <font-awesome-icon :icon="faQrcode" class="ltr:mr-2 rtl:ml-2" />
                    Ir al escáner
                </Link>
            </div>

            <!-- Resumen del conjunto filtrado -->
            <div class="mt-5 grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="panel p-4">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold uppercase tracking-wide text-white-dark">Registros</p>
                        <font-awesome-icon :icon="faTableList" class="text-primary" />
                    </div>
                    <p class="text-2xl font-semibold mt-2">{{ summary.total }}</p>
                    <p class="text-xs text-white-dark mt-1">Alumno y día registrados</p>
                </div>

                <div class="panel p-4">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold uppercase tracking-wide text-white-dark">Entradas</p>
                        <font-awesome-icon :icon="faRightToBracket" class="text-success" />
                    </div>
                    <p class="text-2xl font-semibold mt-2">{{ summary.entries }}</p>
                    <p class="text-xs text-white-dark mt-1">{{ summary.late }} con tardanza</p>
                </div>

                <div class="panel p-4">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold uppercase tracking-wide text-white-dark">Salidas</p>
                        <font-awesome-icon :icon="faRightFromBracket" class="text-info" />
                    </div>
                    <p class="text-2xl font-semibold mt-2">{{ summary.exits }}</p>
                    <p class="text-xs text-white-dark mt-1">{{ summary.inside }} sin salida</p>
                </div>

                <div class="panel p-4">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold uppercase tracking-wide text-white-dark">Tardanzas</p>
                        <font-awesome-icon :icon="faTriangleExclamation" class="text-warning" />
                    </div>
                    <p class="text-2xl font-semibold mt-2">{{ summary.late }}</p>
                    <p class="text-xs text-white-dark mt-1">Entradas fuera de la hora</p>
                </div>
            </div>

            <!-- Filtros -->
            <div class="mt-5 panel p-4">
                <div class="flex flex-wrap items-end gap-3">
                    <div class="w-full lg:w-72">
                        <label class="form-label text-xs">Buscar alumno</label>
                        <div class="relative">
                            <font-awesome-icon
                                :icon="faMagnifyingGlass"
                                class="absolute ltr:left-3 rtl:right-3 top-1/2 -translate-y-1/2 text-white-dark text-xs"
                            />
                            <input
                                v-model="form.search"
                                type="text"
                                class="form-input py-2 ltr:pl-9 rtl:pr-9"
                                placeholder="Nombre completo, DNI o código"
                                @keyup.enter="applyFilters"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="form-label text-xs">Movimiento</label>
                        <select v-model="form.event" class="form-select w-44 py-2" @change="applyFilters">
                            <option value="">Todos</option>
                            <option value="in">Solo entradas</option>
                            <option value="out">Solo salidas</option>
                        </select>
                    </div>

                    <div>
                        <label class="form-label text-xs">Desde</label>
                        <input v-model="form.date_from" type="date" class="form-input w-40 py-2" @change="applyFilters" />
                    </div>

                    <div>
                        <label class="form-label text-xs">Hasta</label>
                        <input v-model="form.date_to" type="date" class="form-input w-40 py-2" @change="applyFilters" />
                    </div>

                    <div>
                        <label class="form-label text-xs">Nivel</label>
                        <select v-model="form.level_id" class="form-select w-40 py-2" @change="onLevelChange">
                            <option value="">Todos</option>
                            <option v-for="level in levelOptions" :key="level.id" :value="level.id">
                                {{ level.name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="form-label text-xs">Grado</label>
                        <select
                            v-model="form.grade_id"
                            class="form-select w-36 py-2"
                            :disabled="!form.level_id"
                            @change="onGradeChange"
                        >
                            <option value="">Todos</option>
                            <option v-for="grade in gradeOptions" :key="grade.id" :value="grade.id">
                                {{ grade.name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="form-label text-xs">Sección</label>
                        <select
                            v-model="form.section_id"
                            class="form-select w-32 py-2"
                            :disabled="!form.grade_id"
                            @change="applyFilters"
                        >
                            <option value="">Todas</option>
                            <option v-for="section in sectionOptions" :key="section.id" :value="section.id">
                                {{ section.name }}
                            </option>
                        </select>
                    </div>

                    <div class="flex items-center gap-2 ltr:ml-auto rtl:mr-auto">
                        <button type="button" class="btn btn-primary" @click="applyFilters">
                            <font-awesome-icon :icon="faMagnifyingGlass" class="ltr:mr-1 rtl:ml-1" />
                            Buscar
                        </button>
                        <button type="button" class="btn btn-outline-secondary" :disabled="!hasFilters" @click="clearFilters">
                            <font-awesome-icon :icon="faArrowRotateLeft" class="ltr:mr-1 rtl:ml-1" />
                            Limpiar
                        </button>
                    </div>
                </div>

                <p class="text-xs text-white-dark mt-3">
                    Sin fechas se consulta todo el historial. Los filtros de nivel, grado y sección se arman con las secciones que ya tienen registros.
                </p>
            </div>

            <!-- Listado -->
            <div class="mt-5 panel p-0 border-0 overflow-hidden">
                <div class="table-responsive">
                    <table class="table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Alumno</th>
                                <th>DNI</th>
                                <th>Código</th>
                                <th>Nivel</th>
                                <th>Grado y sección</th>
                                <th class="!text-center">Entrada</th>
                                <th class="!text-center">Estado</th>
                                <th class="!text-center">Salida</th>
                                <th class="!text-center">Situación</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="rows.length === 0">
                                <td colspan="10" class="text-center py-10">
                                    <font-awesome-icon :icon="faUserCheck" class="text-3xl text-white-dark" />
                                    <p class="text-sm text-white-dark mt-3">
                                        No hay registros de portería para los filtros seleccionados.
                                    </p>
                                </td>
                            </tr>

                            <tr v-for="row in rows" :key="row.id">
                                <td class="whitespace-nowrap">{{ row.attendance_date }}</td>
                                <td class="font-semibold">{{ row.student }}</td>
                                <td>{{ row.dni || '—' }}</td>
                                <td>{{ row.code || '—' }}</td>
                                <td>{{ row.level || '—' }}</td>
                                <td class="whitespace-nowrap">
                                    {{ row.grade || '—' }}<span v-if="row.section"> · {{ row.section }}</span>
                                </td>
                                <td class="text-center whitespace-nowrap">{{ row.entry_time || '—' }}</td>
                                <td class="text-center">
                                    <span class="badge" :class="statusClass(row)">{{ row.entry_status_label }}</span>
                                </td>
                                <td class="text-center whitespace-nowrap">
                                    {{ row.exit_time || '—' }}
                                    <span v-if="row.early_exit" class="badge bg-warning ltr:ml-1 rtl:mr-1">Anticipada</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge" :class="situation(row).class">{{ situation(row).label }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="rows.length" class="p-4">
                    <Pagination :data="records" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
